<?php

namespace App\Controller;

use App\Entity\Joueur;
use App\Repository\ProgressionRepository;
use App\Service\ClassementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_JOUEUR')]
class ClassementController extends AbstractController
{
    public function __construct(
        private readonly ClassementService $classementService,
        private readonly ProgressionRepository $progressionRepository,
    ) {
    }

    #[Route('/classement', name: 'app_classement_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var Joueur $joueur */
        $joueur = $this->getUser();

        return $this->render('classement/index.html.twig', [
            'classement' => $this->classementService->getClassementGlobal(),
            'equipeConnectee' => $joueur->getEquipe(),
            'activites' => $this->progressionRepository->findActiviteRecente(),
        ]);
    }
}