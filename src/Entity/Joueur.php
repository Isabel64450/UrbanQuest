<?php

namespace App\Entity;

use App\Repository\JoueurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: JoueurRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_equipe_pseudo', columns: ['equipe_id', 'pseudo'])]
class Joueur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $pseudo;

    #[ORM\Column(length: 255)]
    private ?string $codePin;

    #[ORM\ManyToOne(inversedBy: 'joueurs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Equipe $equipe;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getCodePin(): ?string
    {
        return $this->codePin;
    }

    public function setCodePin(string $codePin): static
    {
        $this->codePin = $codePin;

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
 public function getPassword(): string
    {
        return $this->codePin;
    }
public function getUserIdentifier(): string
{
    return sprintf('%s:%s', $this->equipe->getCodeAcces(), $this->pseudo);
}

public function getRoles(): array
{
    return ['ROLE_JOUEUR'];
}

public function eraseCredentials(): void
{
}

}
