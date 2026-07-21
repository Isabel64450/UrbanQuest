<?php

namespace App\Service;

use App\Entity\Equipe;
use App\Repository\ProgressionRepository;

class ClassementService
{
    public function __construct(
        private readonly ProgressionRepository $progressionRepository,
    ) {
    }

    /**
     * @return list<array{equipe: Equipe, score: int, derniereValidation: \DateTimeImmutable, rang: int}>
     */
    public function getClassementGlobal(): array
    {
        $classement = [];

        foreach ($this->progressionRepository->getClassementGlobal() as $index => $ligne) {
            $classement[] = [
                'equipe' => $ligne['equipe'],
                'score' => (int) $ligne['score'],
                'derniereValidation' => $this->toDateTimeImmutable($ligne['derniereValidation']),
                'rang' => $index + 1,
            ];
        }

        return $classement;
    }

    /**
     * Retrouve la ligne de classement d'une équipe. Une équipe sans aucune
     * Progression n'apparaît pas dans getClassementGlobal() (qui ne porte
     * que sur les équipes ayant au moins une validation) : on retourne donc
     * null plutôt qu'un score de 0 et un rang arbitraire.
     *
     * @return array{equipe: Equipe, score: int, derniereValidation: \DateTimeImmutable, rang: int}|null
     */
    public function getRangEquipe(Equipe $equipe): ?array
    {
        foreach ($this->getClassementGlobal() as $ligne) {
            if ($ligne['equipe']->getId() === $equipe->getId()) {
                return $ligne;
            }
        }

        return null;
    }

    private function toDateTimeImmutable(\DateTimeImmutable|string $valeur): \DateTimeImmutable
    {
        return $valeur instanceof \DateTimeImmutable ? $valeur : new \DateTimeImmutable($valeur);
    }
}