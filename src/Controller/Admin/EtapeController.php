<?php

namespace App\Controller\Admin;

use App\Entity\Etape;
use App\Entity\Parcours;
use App\Form\EtapeType;
use App\Repository\EtapeRepository;
use App\Repository\ProgressionRepository;
use App\Repository\TentativeValidationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class EtapeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProgressionRepository $progressionRepository,
        private readonly TentativeValidationRepository $tentativeValidationRepository,
    ) {
    }




    

    #[Route('/admin/parcours/{parcours}/etape/new', name: 'app_admin_etape_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Parcours $parcours): Response
    {
        $etape = new Etape();
        $etape->setParcours($parcours);
        $ordreMax = 0;
        foreach ($parcours->getEtapes() as $existante) {
            $ordreMax = max($ordreMax, $existante->getOrdre());
        }
        $etape->setOrdre($ordreMax + 1);

        $form = $this->createForm(EtapeType::class, $etape);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($etape);
            $this->entityManager->flush();

            $this->addFlash('success', 'Étape créée.');

            return $this->redirectToRoute('app_admin_parcours_editer', ['id' => $parcours->getId()]);
        }

        return $this->render('admin/etape/form.html.twig', [
            'form' => $form,
            'parcours' => $parcours,
            'etape' => null,
        ]);
    }

    

    #[Route('/admin/parcours/{parcours}/etape/{etape}/edit', name: 'app_admin_etape_edit', methods: ['GET', 'POST'])]
    public function editer(Parcours $parcours, Etape $etape, Request $request): Response
    {
        $this->verifierAppartenance($parcours, $etape);

        $form = $this->createForm(EtapeType::class, $etape);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Étape mise à jour.');

            return $this->redirectToRoute('app_admin_parcours_editer', ['id' => $parcours->getId()]);
        }

        return $this->render('admin/etape/form.html.twig', [
            'form' => $form,
            'parcours' => $parcours,
            'etape' => $etape,
        ]);
    }

    #[Route('/admin/parcours/{parcours}/etape/{etape}/delete', name: 'app_admin_etape_delete', methods: ['POST'])]
    public function supprimer(Parcours $parcours, Etape $etape, Request $request): Response
    {
        $this->verifierAppartenance($parcours, $etape);

        if (!$this->isCsrfTokenValid('supprimer_etape'.$etape->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($this->progressionRepository->existePourEtape($etape) || $this->tentativeValidationRepository->existePourEtape($etape)) {
            $this->addFlash('error', 'Cette étape a déjà des tentatives ou validations enregistrées : impossible de la supprimer.');

            return $this->redirectToRoute('app_admin_parcours_edit', ['id' => $parcours->getId()]);
        }

        $this->entityManager->remove($etape);
        $this->entityManager->flush();

        $this->addFlash('success', 'Étape supprimée.');

        return $this->redirectToRoute('app_admin_parcours_edit', ['id' => $parcours->getId()]);
    }

    #[Route('/admin/parcours/{parcours}/etape/{etape}/monter', name: 'app_admin_etape_monter', methods: ['POST'])]
    public function monter(Parcours $parcours, Etape $etape, Request $request): Response
    {
        $this->verifierAppartenance($parcours, $etape);
        $this->verifierCsrfReordonnancement($etape, $request);

        $precedente = null;
        foreach ($parcours->getEtapes() as $candidate) {
            if ($candidate->getId() === $etape->getId()) {
                break;
            }
            $precedente = $candidate;
        }

        if (null !== $precedente) {
            $this->echangerOrdre($etape, $precedente);
        }

        return $this->redirectToRoute('app_admin_parcours_edit', ['id' => $parcours->getId()]);
    }

    #[Route('/admin/parcours/{parcours}/etape/{etape}/descendre', name: 'app_admin_etape_descendre', methods: ['POST'])]
    public function descendre(Parcours $parcours, Etape $etape, Request $request): Response
    {
        $this->verifierAppartenance($parcours, $etape);
        $this->verifierCsrfReordonnancement($etape, $request);

        $trouvee = false;
        $suivante = null;
        foreach ($parcours->getEtapes() as $candidate) {
            if ($trouvee) {
                $suivante = $candidate;
                break;
            }
            if ($candidate->getId() === $etape->getId()) {
                $trouvee = true;
            }
        }

        if (null !== $suivante) {
            $this->echangerOrdre($etape, $suivante);
        }

        return $this->redirectToRoute('app_admin_parcours_edit', ['id' => $parcours->getId()]);
    }

    private function verifierAppartenance(Parcours $parcours, Etape $etape): void
    {
        if ($etape->getParcours()->getId() !== $parcours->getId()) {
            throw $this->createNotFoundException('Cette étape n\'appartient pas à ce parcours.');
        }
    }

    private function verifierCsrfReordonnancement(Etape $etape, Request $request): void
    {
        if (!$this->isCsrfTokenValid('reordonner_etape'.$etape->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    }

    /**
     * Échange l'ordre de deux étapes via une valeur temporaire, en trois
     * flushes distincts. La contrainte d'unicité (parcours_id, ordre) est
     * vérifiée par MySQL immédiatement à chaque UPDATE (pas en fin de
     * transaction) : regrouper les deux affectations finales dans un même
     * flush() échoue dès que l'ordre d'exécution des UPDATE place l'une des
     * deux valeurs cibles encore détenue par l'autre ligne à cet instant.
     */
    private function echangerOrdre(Etape $a, Etape $b): void
    {
        $ordreA = $a->getOrdre();
        $ordreB = $b->getOrdre();

        $a->setOrdre(-1);
        $this->entityManager->flush();

        $b->setOrdre($ordreA);
        $this->entityManager->flush();

        $a->setOrdre($ordreB);
        $this->entityManager->flush();
    }
}
