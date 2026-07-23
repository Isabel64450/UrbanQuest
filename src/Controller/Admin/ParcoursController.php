<?php

namespace App\Controller\Admin;

use App\Entity\Parcours;
use App\Form\ParcoursType;
use App\Repository\AdministrateurRepository;
use App\Repository\ParcoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class ParcoursController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }


    

     #[Route('/admin/parcours', name: 'app_admin_parcours_index', methods: ['GET'])]
    public function index(ParcoursRepository $parcoursRepository): Response
    {
        $lignes = [];
        foreach ($parcoursRepository->findAll() as $parcours) {
            $nombreEquipesAyantJoue = 0;
            foreach ($parcours->getEquipes() as $equipe) {
                if ($equipe->getProgressions()->count() > 0) {
                    ++$nombreEquipesAyantJoue;
                }
            }

            $lignes[] = [
                'parcours' => $parcours,
                'nombreEtapes' => $parcours->getEtapes()->count(),
                'nombreEquipesAyantJoue' => $nombreEquipesAyantJoue,
            ];
        }

        return $this->render('admin/parcours/index.html.twig', [
            'lignes' => $lignes,
        ]);
    }




    #[Route('/admin/parcours/new', name: 'app_admin_parcours_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $parcours = new Parcours();
        $form = $this->createForm(ParcoursType::class, $parcours);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($parcours);
            $entityManager->flush();
            $this->addFlash('success', 'Parcours créé. Vous pouvez maintenant y ajouter des étapes.');

            return $this->redirectToRoute('app_admin_parcours_edit', ['id' => $parcours->getId()]);
        }

        return $this->render('admin/parcours/form.html.twig', [
            'parcour' => $parcours,
            'form' => $form,
        ]);
    }

    




    #[Route('/admin/parcours/{id}/edit', name: 'app_admin_parcours_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Parcours $parcours, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ParcoursType::class, $parcours);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Parcours mis à jour.');


            return $this->redirectToRoute('app_admin_parcours_edit', ['id' => $parcours->getId()]);
        }

        return $this->render('admin/parcours/form.html.twig', [
            'parcour' => $parcours,
            'form' => $form,
        ]);
    }

      #[Route('/admin/parcours/{id}/archiver', name: 'app_admin_parcours_archiver', methods: ['POST'])]
    public function archiver(Parcours $parcours, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('archiver_parcours'.$parcours->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $parcours->setEstArchive(!$parcours->isEstArchive());
        $this->entityManager->flush();

        $this->addFlash('success', $parcours->isEstArchive() ? 'Parcours archivé.' : 'Parcours désarchivé.');

        return $this->redirectToRoute('app_admin_parcours_index');
    }




    #[Route('/admin/parcours/{id}/supprimer', name: 'app_admin_parcours_supprimer', methods: ['POST'])]
    public function supprimer(Parcours $parcours, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('supprimer_parcours'.$parcours->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        // Une équipe est liée au parcours par une clé étrangère non nullable
        // et sans cascade : sa seule existence (qu'elle ait joué ou non)
        // empêcherait la suppression en base. On bloque donc dès qu'une
        // équipe est associée, plutôt que de laisser Doctrine échouer.
        if ($parcours->getEquipes()->count() > 0) {
            $this->addFlash('error', 'Des équipes sont associées à ce parcours : archivez-le plutôt que de le supprimer.');

            return $this->redirectToRoute('app_admin_parcours_index');
        }

        $this->entityManager->remove($parcours);
        $this->entityManager->flush();

        $this->addFlash('success', 'Parcours supprimé.');

        return $this->redirectToRoute('app_admin_parcours_index');
    }






}
