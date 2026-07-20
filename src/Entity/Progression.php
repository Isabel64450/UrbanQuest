<?php

namespace App\Entity;

use App\Repository\ProgressionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProgressionRepository::class)]
class Progression
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateValidation = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
    private ?string $latitudeReleve = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
    private ?string $longitudeReleve = null;

    #[ORM\Column]
    private ?int $pointsObtenus = null;

    #[ORM\ManyToOne(inversedBy: 'progressions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Equipe $equipe = null;

    #[ORM\ManyToOne(inversedBy: 'progressions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Etape $etape = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateValidation(): ?\DateTimeImmutable
    {
        return $this->dateValidation;
    }

    public function setDateValidation(\DateTimeImmutable $dateValidation): static
    {
        $this->dateValidation = $dateValidation;

        return $this;
    }

    public function getLatitudeReleve(): ?string
    {
        return $this->latitudeReleve;
    }

    public function setLatitudeReleve(string $latitudeReleve): static
    {
        $this->latitudeReleve = $latitudeReleve;

        return $this;
    }

    public function getLongitudeReleve(): ?string
    {
        return $this->longitudeReleve;
    }

    public function setLongitudeReleve(string $longitudeReleve): static
    {
        $this->longitudeReleve = $longitudeReleve;

        return $this;
    }

    public function getPointsObtenus(): ?int
    {
        return $this->pointsObtenus;
    }

    public function setPointsObtenus(int $pointsObtenus): static
    {
        $this->pointsObtenus = $pointsObtenus;

        return $this;
    }

    public function getEquipe(): ?Equipe
    {
        return $this->equipe;
    }

    public function setEquipe(?Equipe $equipe): static
    {
        $this->equipe = $equipe;

        return $this;
    }

    public function getEtape(): ?Etape
    {
        return $this->etape;
    }

    public function setEtape(?Etape $etape): static
    {
        $this->etape = $etape;

        return $this;
    }
}
