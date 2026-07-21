<?php

namespace App\Service;

use App\Entity\Equipe;
use App\Entity\Etape;
use App\Entity\Progression;

class ProgressionService
{
    /**
     * Première étape du parcours de l'équipe qui n'a pas encore de
     * Progression associée, triée par ordre. Retourne null si toutes les
     * étapes sont validées (parcours terminé).
     */
    public function getEtapeCourante(Equipe $equipe): ?Etape
    {
        $etapesValideesIds = array_map(
            static fn (Progression $progression): int => $progression->getEtape()->getId(),
            $equipe->getProgressions()->toArray()
        );

        foreach ($equipe->getParcours()->getEtapes() as $etape) {
            if (!in_array($etape->getId(), $etapesValideesIds, true)) {
                return $etape;
            }
        }

        return null;
    }

    public function estParcoursTermine(Equipe $equipe): bool
    {
        return null === $this->getEtapeCourante($equipe);
    }
}