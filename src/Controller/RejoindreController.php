<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RejoindreController extends AbstractController
{
    #[Route('/rejoindre', name: 'app_rejoindre')]
    public function index(): Response
    {
        return $this->render('rejoindre/index.html.twig', [
            'controller_name' => 'RejoindreController',
        ]);
    }
}
