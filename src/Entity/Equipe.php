<?php

namespace App\Entity;

use App\Repository\EquipeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EquipeRepository::class)]
class Equipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, unique:true)]
    private ?string $codeAcces = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateDemarrage = null;

    #[ORM\ManyToOne(inversedBy: 'equipes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Parcours $parcours = null;

    /**
     * @var Collection<int, Joueur>
     */
    #[ORM\OneToMany(targetEntity: Joueur::class, mappedBy: 'equipe', orphanRemoval: true)]
    private Collection $joueurs;

    /**
     * @var Collection<int, Progression>
     */
    #[ORM\OneToMany(targetEntity: Progression::class, mappedBy: 'equipe', orphanRemoval: true)]
    private Collection $progressions;

    /**
     * @var Collection<int, TentativeValidation>
     */
    #[ORM\OneToMany(targetEntity: TentativeValidation::class, mappedBy: 'equipe', orphanRemoval: true)]
    private Collection $tentativeValidations;

    public function __construct()
    {
        $this->joueurs = new ArrayCollection();
        $this->progressions = new ArrayCollection();
        $this->tentativeValidations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getCodeAcces(): ?string
    {
        return $this->codeAcces;
    }

    public function setCodeAcces(string $codeAcces): static
    {
        $this->codeAcces = $codeAcces;

        return $this;
    }

    public function getDateDemarrage(): ?\DateTimeImmutable
    {
        return $this->dateDemarrage;
    }

    public function setDateDemarrage(\DateTimeImmutable $dateDemarrage): static
    {
        $this->dateDemarrage = $dateDemarrage;

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
     * @return Collection<int, Joueur>
     */
    public function getJoueurs(): Collection
    {
        return $this->joueurs;
    }

    public function addJoueur(Joueur $joueur): static
    {
        if (!$this->joueurs->contains($joueur)) {
            $this->joueurs->add($joueur);
            $joueur->setEquipe($this);
        }

        return $this;
    }

    public function removeJoueur(Joueur $joueur): static
    {
        if ($this->joueurs->removeElement($joueur)) {
            // set the owning side to null (unless already changed)
            if ($joueur->getEquipe() === $this) {
                $joueur->setEquipe(null);
            }
        }

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
            $progression->setEquipe($this);
        }

        return $this;
    }

    public function removeProgression(Progression $progression): static
    {
        if ($this->progressions->removeElement($progression)) {
            // set the owning side to null (unless already changed)
            if ($progression->getEquipe() === $this) {
                $progression->setEquipe(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TentativeValidation>
     */
    public function getTentativeValidations(): Collection
    {
        return $this->tentativeValidations;
    }

    public function addTentativeValidation(TentativeValidation $tentativeValidation): static
    {
        if (!$this->tentativeValidations->contains($tentativeValidation)) {
            $this->tentativeValidations->add($tentativeValidation);
            $tentativeValidation->setEquipe($this);
        }

        return $this;
    }

    public function removeTentativeValidation(TentativeValidation $tentativeValidation): static
    {
        if ($this->tentativeValidations->removeElement($tentativeValidation)) {
            // set the owning side to null (unless already changed)
            if ($tentativeValidation->getEquipe() === $this) {
                $tentativeValidation->setEquipe(null);
            }
        }

        return $this;
    }
}
