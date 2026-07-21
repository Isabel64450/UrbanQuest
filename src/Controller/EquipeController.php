<?php

namespace App\Controller;

use App\Entity\Joueur;
use App\Service\ClassementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\Exception\RuntimeException as MercureRuntimeException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EquipeController extends AbstractController
{
    public function __construct(
        private readonly ClassementService $classementService,
        private readonly Authorization $mercureAuthorization,
    ) {
    }

    #[Route('/equipe/tableau-de-bord', name: 'app_equipe_dashboard', methods: ['GET'])]
    #[IsGranted('ROLE_JOUEUR')]
    public function tableauDeBord(Request $request): Response
    {
        /** @var Joueur $joueur */
        $joueur = $this->getUser();

        return $this->render('equipe/tableau_de_bord.html.twig', [
            'joueur' => $joueur,
            'classementEquipe' => $this->classementService->getRangEquipe($joueur->getEquipe()),
            'tempsReelDisponible' => $this->tempsReelDisponible($request),
        ]);
    }

    // Le temps réel (Turbo Stream + cookie d'autorisation Mercure) exige que
    // la requête arrive sur le même domaine que MERCURE_PUBLIC_URL (cf.
    
    // que de dupliquer la logique de comparaison de domaines du vendor.
    private function tempsReelDisponible(Request $request): bool
    {
        try {
            $this->mercureAuthorization->createCookie($request, []);

            return true;
        } catch (MercureRuntimeException) {
            return false;
        }
    }
}