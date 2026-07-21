<?php

namespace App\Service;

use App\Entity\Equipe;
use App\Repository\ProgressionRepository;
use App\Service\ClassementService;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Twig\Environment;

/**
 * Diffuse en temps réel, via Mercure, le HTML déjà calculé côté serveur :
 * aucune donnée brute n'est jamais envoyée au client, seulement des
 * fragments Turbo Stream prêts à être injectés dans le DOM.
 *
 * Publication synchrone dans la requête HTTP (pas de worker/file d'attente)
 * : suffisant au volume attendu (un atelier, quelques dizaines d'équipes au
 * plus), à revoir si le nombre d'équipes simultanées devenait important.
 */
class MercureNotificationService
{
    private const TOPIC_CLASSEMENT = 'classement';
    private const TOPIC_ACTIVITE = 'activite';

    public function __construct(
        private readonly HubInterface $hub,
        private readonly Environment $twig,
        private readonly ClassementService $classementService,
        private readonly ProgressionRepository $progressionRepository,
    ) {
    }

    public function publierClassementMisAJour(): void
    {
        $html = $this->twig->render('classement/_classement.stream.html.twig', [
            'classement' => $this->classementService->getClassementGlobal(),
        ]);

        $this->hub->publish(new Update(self::TOPIC_CLASSEMENT, $html));
    }

    public function publierActivite(Equipe $equipe): void
    {
        $html = $this->twig->render('activite/_activite.stream.html.twig', [
            'activites' => $this->progressionRepository->findActiviteRecente(),
        ]);

        $this->hub->publish(new Update(self::TOPIC_ACTIVITE, $html));
    }

    public function publierDashboardEquipe(Equipe $equipe): void
    {
        $html = $this->twig->render('equipe/_dashboard_score.stream.html.twig', [
            'classementEquipe' => $this->classementService->getRangEquipe($equipe),
        ]);

        $this->hub->publish(new Update(
            sprintf('equipe/%d', $equipe->getId()),
            $html,
            true
        ));
    }

    /**
     * Republie systématiquement les trois topics plutôt que de calculer un
     * diff de rang avant/après validation : une mise à jour Turbo Stream
     * "update" étant idempotente, ce choix de simplicité ne risque jamais
     * d'afficher un état incohérent, même en cas de publications concurrentes.
     */
    public function notifierValidationReussie(Equipe $equipeQuiVientDeValider): void
    {
        $this->publierClassementMisAJour();
        $this->publierActivite($equipeQuiVientDeValider);

        foreach ($this->classementService->getClassementGlobal() as $ligne) {
            $this->publierDashboardEquipe($ligne['equipe']);
        }
    }
}