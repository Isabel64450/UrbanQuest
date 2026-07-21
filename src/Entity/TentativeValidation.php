<?php

namespace App\Entity;

use App\Repository\TentativeValidationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TentativeValidationRepository::class)]
#[ORM\Index(name: 'idx_equipe_etape', columns: ['equipe_id', 'etape_id'])]
class TentativeValidation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Equipe::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Equipe $equipe;

    #[ORM\ManyToOne(targetEntity: Etape::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Etape $etape;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reponseSaisie = null;

    #[ORM\Column]
    private bool $reussie = false;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
    private string $latitude;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
    private string $longitude;

    #[ORM\Column]
    private int $distanceCalculee;

    #[ORM\Column]
    private \DateTimeImmutable $dateTentative;

    public function __construct()
    {
        $this->dateTentative = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEquipe(): Equipe
    {
        return $this->equipe;
    }

    public function setEquipe(Equipe $equipe): static
    {
        $this->equipe = $equipe;

        return $this;
    }

    public function getEtape(): Etape
    {
        return $this->etape;
    }

    public function setEtape(Etape $etape): static
    {
        $this->etape = $etape;

        return $this;
    }

    public function getReponseSaisie(): ?string
    {
        return $this->reponseSaisie;
    }

    public function setReponseSaisie(?string $reponseSaisie): static
    {
        $this->reponseSaisie = $reponseSaisie;

        return $this;
    }

    public function isReussie(): bool
    {
        return $this->reussie;
    }

    public function setReussie(bool $reussie): static
    {
        $this->reussie = $reussie;

        return $this;
    }

    public function getLatitude(): string
    {
        return $this->latitude;
    }

    public function setLatitude(string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): string
    {
        return $this->longitude;
    }

    public function setLongitude(string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getDistanceCalculee(): int
    {
        return $this->distanceCalculee;
    }

    public function setDistanceCalculee(int $distanceCalculee): static
    {
        $this->distanceCalculee = $distanceCalculee;

        return $this;
    }

    public function getDateTentative(): \DateTimeImmutable
    {
        return $this->dateTentative;
    }
}