<?php

namespace App\Entity;

use App\Repository\EtapeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtapeRepository::class)]
  #[ORM\UniqueConstraint(
    name: 'uniq_parcours_ordre',
    columns: ['parcours_id', 'ordre']
)]
class Etape
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $consigne = null;

    #[ORM\Column]
    private ?int $ordre = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
    private ?string $longitude = null;

    #[ORM\Column(options:['default'=> 20])]
    private ?int $rayonValidationMetres = 20;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reponseAttendue = null;

    #[ORM\Column]
    private ?int $points = null;

    #[ORM\Column]
    private ?int $nombreEchecsAvantIndice = null;

    #[ORM\Column(type: Types::TEXT, nullable:true)]
    private ?string $indice = null;

    #[ORM\ManyToOne(inversedBy: 'etapes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Parcours $parcours = null;

    /**
     * @var Collection<int, Progression>
     */
    #[ORM\OneToMany(targetEntity: Progression::class, mappedBy: 'etape', orphanRemoval: true)]
    private Collection $progressions;

    public function __construct()
    {
        $this->progressions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getConsigne(): ?string
    {
        return $this->consigne;
    }

    public function setConsigne(string $consigne): static
    {
        $this->consigne = $consigne;

        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getLatitud(): ?string
    {
        return $this->latitude;
    }

    public function setLatitud(string $latitud): static
    {
        $this->latitude = $latitud;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getRayonValidationMetres(): ?int
    {
        return $this->rayonValidationMetres;
    }

    public function setRayonValidationMetres(int $rayonValidationMetres): static
    {
        $this->rayonValidationMetres = $rayonValidationMetres;

        return $this;
    }

    public function getResponseAttendue(): ?string
    {
        return $this->reponseAttendue;
    }

    public function setResponseAttendue(?string $responseAttendue): static
    {
        $this->reponseAttendue = $responseAttendue;

        return $this;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = $points;

        return $this;
    }

    public function getNombreEchecsAvantIndice(): ?int
    {
        return $this->nombreEchecsAvantIndice;
    }

    public function setNombreEchecsAvantIndice(int $nombreEchecsAvantIndice): static
    {
        $this->nombreEchecsAvantIndice = $nombreEchecsAvantIndice;

        return $this;
    }

    public function getIndice(): ?string
    {
        return $this->indice;
    }

    public function setIndice(?string $indice): static
    {
        $this->indice = $indice;

        return $this;
    }

    public function getParcours(): ?Parcours
    {
        return $this->parcours;
    }

    public function setParcours(?Parcours $parcours): static
    {
        $this->parcours = $parcours;

        return $this;
    }

    /**
     * @return Collection<int, Progression>
     */
    public function getProgressions(): Collection
    {
        return $this->progressions;
    }

    public function addProgression(Progression $progression): static
    {
        if (!$this->progressions->contains($progression)) {
            $this->progressions->add($progression);
            $progression->setEtape($this);
        }

        return $this;
    }

    public function removeProgression(Progression $progression): static
    {
        if ($this->progressions->removeElement($progression)) {
            // set the owning side to null (unless already changed)
            if ($progression->getEtape() === $this) {
                $progression->setEtape(null);
            }
        }

        return $this;
    }
}
