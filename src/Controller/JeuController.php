<?php

namespace App\Controller;

use App\Entity\Equipe;
use App\Entity\Etape;
use App\Entity\Joueur;
use App\Entity\Progression;
use App\Entity\TentativeValidation;
use App\Repository\TentativeValidationRepository;
use App\Service\DetectionAnomalieService;
use App\Service\GeolocalisationService;
use App\Service\MercureNotificationService;
use App\Service\ProgressionService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\Turbo\TurboBundle;

#[IsGranted('ROLE_JOUEUR')]
class JeuController extends AbstractController
{
    public function __construct(
        private readonly ProgressionService $progressionService,
        private readonly GeolocalisationService $geolocalisationService,
        private readonly TentativeValidationRepository $tentativeValidationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MercureNotificationService $mercureNotificationService,
        /* private readonly RateLimiterFactoryInterface $validationEtapeLimiter, */
        private readonly DetectionAnomalieService $detectionAnomalieService,
        private readonly LoggerInterface $antiTricheLogger,
    ) {
    }

    #[Route('/jeu', name: 'app_jeu_index', methods: ['GET'])]
    public function index(): Response
    {
        $equipe = $this->joueurConnecte()->getEquipe();
        $etapeCourante = $this->progressionService->getEtapeCourante($equipe);

        return $this->render('jeu/index.html.twig', [
            'etapeCourante' => $etapeCourante,
        ]);
    }

    /**
     * Ping de position envoyé périodiquement par le client (watchPosition).
     * Ne persiste rien : simple lecture de l'état courant, pas de protection
     * CSRF nécessaire pour une route qui ne modifie aucune donnée.
     */
    #[Route('/jeu/position', name: 'app_jeu_position', methods: ['POST'])]
    public function position(Request $request): Response
    {
        $donnees = json_decode($request->getContent(), true) ?? [];

        $equipe = $this->joueurConnecte()->getEquipe();
        $etapeCourante = $this->progressionService->getEtapeCourante($equipe);

        if (null === $etapeCourante) {
            return $this->reponseStream('jeu/stream/_termine.html.twig');
        }

        $distance = $this->calculerDistance($donnees, $etapeCourante);
        $dansLeRayon = $distance <= $etapeCourante->getRayonValidationMetres();

        if (!$dansLeRayon) {
            return $this->reponseStream('jeu/stream/_distance.html.twig', [
                'distance' => (int) round($distance),
            ]);
        }

        $echecs = $this->tentativeValidationRepository->compterEchecs($equipe, $etapeCourante);

        return $this->reponseStream('jeu/stream/_reveler.html.twig', [
            'etape' => $etapeCourante,
            'indiceDebloque' => $echecs >= $etapeCourante->getNombreEchecsAvantIndice(),
        ]);
    }

    #[Route('/jeu/etape/{id}/valider', name: 'app_jeu_valider', methods: ['POST'])]
    public function valider(Etape $etape, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('jeu_valider', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $equipe = $this->joueurConnecte()->getEquipe();

        // Clé = équipe authentifiée : ralentit un script qui spammerait les
        // tentatives, sans jamais gêner un joueur qui enchaîne des essais
        // légitimes au rythme humain.
       /*  if (!$this->validationEtapeLimiter->create((string) $equipe->getId())->consume()->isAccepted()) {
            return $this->reponseStream('jeu/stream/_trop_de_tentatives.html.twig');
        }
 */
        $etapeCourante = $this->progressionService->getEtapeCourante($equipe);

        // Le serveur seul décide de l'étape courante : on refuse toute
        // tentative sur une étape déjà validée ou pas encore atteinte,
        // même si son id est manipulé dans l'URL.
        if (null === $etapeCourante || $etapeCourante->getId() !== $etape->getId()) {
            throw $this->createAccessDeniedException('Cette étape n\'est pas votre étape courante.');
        }

        $latitude = (float) $request->request->get('latitude', 0);
        $longitude = (float) $request->request->get('longitude', 0);
        $reponseSaisie = trim((string) $request->request->get('reponse', ''));

        $distance = $this->geolocalisationService->calculerDistanceMetres(
            $latitude,
            $longitude,
            (float) $etape->getLatitude(),
            (float) $etape->getLongitude()
        );

        if ($distance > $etape->getRayonValidationMetres()) {
            $this->creerTentative($equipe, $etape, $reponseSaisie, false, $latitude, $longitude, $distance);
            $this->entityManager->flush();

            return $this->reponseStream('jeu/stream/_hors_rayon.html.twig', [
                'distance' => (int) round($distance),
            ]);
        }

        $reponseCorrecte = $this->reponseEstCorrecte($etape, $reponseSaisie);
        $this->creerTentative($equipe, $etape, $reponseSaisie, $reponseCorrecte, $latitude, $longitude, $distance);

        if (!$reponseCorrecte) {
            $this->entityManager->flush();
            $echecs = $this->tentativeValidationRepository->compterEchecs($equipe, $etape);

            return $this->reponseStream('jeu/stream/_echec.html.twig', [
                'etape' => $etape,
                'indiceDebloque' => $echecs >= $etape->getNombreEchecsAvantIndice(),
            ]);
        }

        $derniereProgression = $this->derniereProgression($equipe);

        $progression = new Progression();
        $progression
            ->setEtape($etape)
            ->setLatitudeReleve((string) $latitude)
            ->setLongitudeReleve((string) $longitude)
            ->setPointsObtenus($etape->getPoints());
        // addProgression() (et non setEquipe() seul) pour que la collection
        // en mémoire de l'équipe reflète immédiatement la nouvelle
        // progression : getEtapeCourante() s'appuie dessus juste après.
        $equipe->addProgression($progression);
        $this->entityManager->persist($progression);
        $this->entityManager->flush();

        // Ne bloque jamais la validation, même en cas d'anomalie : simple
        // journalisation exploitable pour un audit a posteriori (aucun
        // tableau de bord de consultation n'est demandé pour cette session).
        if (null !== $derniereProgression) {
            $distanceDepuisDerniereEtape = $this->geolocalisationService->calculerDistanceMetres(
                (float) $derniereProgression->getLatitudeReleve(),
                (float) $derniereProgression->getLongitudeReleve(),
                $latitude,
                $longitude
            );
            $vitesseKmh = $this->detectionAnomalieService->calculerVitesseKmh(
                $distanceDepuisDerniereEtape,
                $derniereProgression->getDateValidation(),
                $progression->getDateValidation()
            );

            if ($this->detectionAnomalieService->estVitesseAnormale($vitesseKmh)) {
                $this->antiTricheLogger->warning('Vitesse anormale détectée entre deux validations.', [
                    'equipe' => $equipe->getId(),
                    'etapePrecedente' => $derniereProgression->getEtape()->getId(),
                    'etape' => $etape->getId(),
                    'distanceMetres' => (int) round($distanceDepuisDerniereEtape),
                    'vitesseKmh' => round($vitesseKmh, 1),
                    'dateValidation' => $progression->getDateValidation()->format(\DateTimeInterface::ATOM),
                ]);
            }
        }

        // Diffusion temps réel (classement, fil d'activité, dashboards) :
        // n'affecte jamais la réponse ci-dessous, destinée au seul joueur
        // qui vient de valider.
        $this->mercureNotificationService->notifierValidationReussie($equipe);

        $prochaineEtape = $this->progressionService->getEtapeCourante($equipe);

        return $this->reponseStream('jeu/stream/_succes.html.twig', [
            'etape' => $etape,
            'prochaineEtape' => $prochaineEtape,
        ]);
    }

    private function joueurConnecte(): Joueur
    {
        /** @var Joueur $joueur */
        $joueur = $this->getUser();

        return $joueur;
    }

    private function calculerDistance(array $donnees, Etape $etape): float
    {
        return $this->geolocalisationService->calculerDistanceMetres(
            (float) ($donnees['latitude'] ?? 0),
            (float) ($donnees['longitude'] ?? 0),
            (float) $etape->getLatitude(),
            (float) $etape->getLongitude()
        );
    }

    /**
     * Dernière étape validée par l'équipe avant la tentative en cours (pour
     * la détection de vitesse anormale). La collection est déjà chargée en
     * mémoire (utilisée juste avant par ProgressionService), donc un simple
     * parcours PHP évite une requête dédiée.
     */
    private function derniereProgression(Equipe $equipe): ?Progression
    {
        $derniere = null;
        foreach ($equipe->getProgressions() as $progression) {
            if (null === $derniere || $progression->getDateValidation() > $derniere->getDateValidation()) {
                $derniere = $progression;
            }
        }

        return $derniere;
    }

    /**
     * Comparaison insensible à la casse et aux espaces superflus. Une étape
     * en géolocalisation pure (pas de reponseAttendue) se valide par simple
     * bouton, sans texte à comparer.
     */
    private function reponseEstCorrecte(Etape $etape, string $reponseSaisie): bool
    {
        if (null === $etape->getResponseAttendue()) {
            return true;
        }

        return '' !== $reponseSaisie
            && mb_strtolower($reponseSaisie) === mb_strtolower(trim($etape->getResponseAttendue()));
    }

    private function creerTentative(
        Equipe $equipe,
        Etape $etape,
        string $reponseSaisie,
        bool $reussie,
        float $latitude,
        float $longitude,
        float $distance,
    ): void {
        $tentative = new TentativeValidation();
        $tentative
            ->setEquipe($equipe)
            ->setEtape($etape)
            ->setReponseSaisie('' === $reponseSaisie ? null : $reponseSaisie)
            ->setReussie($reussie)
            ->setLatitude((string) $latitude)
            ->setLongitude((string) $longitude)
            ->setDistanceCalculee((int) round($distance));
        $this->entityManager->persist($tentative);
    }

    private function reponseStream(string $template, array $context = []): Response
    {
        return new Response($this->renderView($template, $context), 200, [
            'Content-Type' => TurboBundle::STREAM_MEDIA_TYPE,
        ]);
    }
}