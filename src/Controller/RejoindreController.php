<?php

namespace App\Controller;

use App\Entity\Joueur;
use App\Form\CodeAccesType;
use App\Form\NouveauJoueurType;
use App\Repository\EquipeRepository;
use App\Repository\JoueurRepository;
use App\Security\JoueurAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RejoindreController extends AbstractController
{
    #[Route('/rejoindre', name: 'app_rejoindre', methods: ['GET', 'POST'])]
    public function saisirCodeAcces(Request $request, EquipeRepository $equipeRepository): Response
    {
        $form = $this->createForm(CodeAccesType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $codeAcces = $form->get('codeAcces')->getData();
            $equipe = $equipeRepository->findOneAccessibleByCodeAcces($codeAcces);

            if (null === $equipe) {
                $this->addFlash('error', 'Code d\'accès invalide ou parcours non disponible.');
            } else {
                return $this->redirectToRoute('app_rejoindre_equipe', ['codeAcces' => $codeAcces]);
            }
        }

        // Turbo 8 exige soit une redirection, soit un statut non-2xx pour
        // accepter de réafficher la page sur une soumission de formulaire :
        // un 200 est traité comme une anomalie ("Form responses must
        // redirect to another location") et la réponse est ignorée côté
        // client, laissant l'utilisateur bloqué sur la page sans feedback.
        return $this->render('rejoindre/code_acces.html.twig', [
            'form' => $form,
        ], new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/rejoindre/{codeAcces}', name: 'app_rejoindre_equipe', methods: ['GET'])]
    public function afficherEquipe(string $codeAcces, Request $request, EquipeRepository $equipeRepository): Response
    {
        $equipe = $equipeRepository->findOneAccessibleByCodeAcces($codeAcces);

        if (null === $equipe) {
            $this->addFlash('error', 'Code d\'accès invalide ou parcours non disponible.');

            return $this->redirectToRoute('app_rejoindre');
        }

        $nouveauJoueurForm = $this->createForm(NouveauJoueurType::class);
        $nouveauJoueurForm->handleRequest($request);

        return $this->render('rejoindre/equipe.html.twig', [
            'equipe' => $equipe,
            'codeAcces' => $codeAcces,
            'nouveauJoueurForm' => $nouveauJoueurForm,
        ]);
    }

    #[Route('/rejoindre/{codeAcces}/nouveau', name: 'app_rejoindre_nouveau', methods: ['POST'])]
    public function creerJoueur(
        string $codeAcces,
        Request $request,
        EquipeRepository $equipeRepository,
        JoueurRepository $joueurRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        Security $security,
    ): Response {
        $equipe = $equipeRepository->findOneAccessibleByCodeAcces($codeAcces);

        if (null === $equipe) {
            $this->addFlash('error', 'Code d\'accès invalide ou parcours non disponible.');

            return $this->redirectToRoute('app_rejoindre');
        }

        $form = $this->createForm(NouveauJoueurType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pseudo = $form->get('pseudo')->getData();

            $existant = $joueurRepository->findOneBy(['equipe' => $equipe, 'pseudo' => $pseudo]);
            if (null !== $existant) {
                $form->get('pseudo')->addError(new FormError('Ce pseudo est déjà pris dans cette équipe.'));
            } else {
                $joueur = new Joueur();
                $joueur
                    ->setEquipe($equipe)
                    ->setPseudo($pseudo);
                $joueur->setCodePin($passwordHasher->hashPassword($joueur, $form->get('codePin')->getData()));

                $entityManager->persist($joueur);
                $entityManager->flush();

                $security->login($joueur, JoueurAuthenticator::class, 'jeu');

                return $this->redirectToRoute('app_equipe_dashboard');
            }
        }

        // Cette méthode ne gère que POST : on n'atteint ce render() qu'après
        // un échec de validation (cf. commentaire ci-dessus sur Turbo 8).
        return $this->render('rejoindre/equipe.html.twig', [
            'equipe' => $equipe,
            'codeAcces' => $codeAcces,
            'nouveauJoueurForm' => $form,
        ], new Response(status: Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    #[Route('/rejoindre/{codeAcces}/{pseudo}', name: 'app_rejoindre_pseudo', methods: ['GET'])]
    public function afficherConnexionJoueur(string $codeAcces, string $pseudo, EquipeRepository $equipeRepository, JoueurRepository $joueurRepository): Response
    {
        $equipe = $equipeRepository->findOneAccessibleByCodeAcces($codeAcces);

        if (null === $equipe) {
            $this->addFlash('error', 'Code d\'accès invalide ou parcours non disponible.');

            return $this->redirectToRoute('app_rejoindre');
        }

        $joueur = $joueurRepository->findOneBy(['equipe' => $equipe, 'pseudo' => $pseudo]);

        if (null === $joueur) {
            $this->addFlash('error', 'Ce joueur n\'existe pas dans cette équipe.');

            return $this->redirectToRoute('app_rejoindre_equipe', ['codeAcces' => $codeAcces]);
        }

        return $this->render('rejoindre/connexion.html.twig', [
            'codeAcces' => $codeAcces,
            'pseudo' => $pseudo,
        ]);
    }

    /**
     * Cette route n'est jamais réellement exécutée : JoueurAuthenticator
     * intercepte la requête POST avant que le contrôleur ne s'exécute.
     */
    #[Route('/rejoindre/{codeAcces}/connexion', name: 'app_rejoindre_connexion', methods: ['POST'])]
    public function connexion(): Response
    {
        throw new \LogicException('Cette route est interceptée par JoueurAuthenticator.');
    }

    /**
     * Cette route n'est jamais réellement exécutée : le firewall intercepte
     * la requête grâce à la configuration "logout" de security.yaml.
     */
    #[Route('/deconnexion', name: 'app_logout', methods: ['GET'])]
    public function deconnexion(): Response
    {
        throw new \LogicException('Cette route est interceptée par le firewall.');
    }
}