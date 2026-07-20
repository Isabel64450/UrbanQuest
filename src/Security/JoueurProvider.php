<?php

namespace App\Security;

use App\Entity\Joueur;
use App\Repository\JoueurRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Résout un Joueur à partir de l'identifiant "{codeAcces}:{pseudo}" produit
 * par Joueur::getUserIdentifier(). Le pseudo n'étant unique que par équipe,
 * la résolution passe toujours par le couple (codeAcces, pseudo).
 *
 * @implements UserProviderInterface<Joueur>
 */
class JoueurProvider implements UserProviderInterface
{
    public function __construct(
        private readonly JoueurRepository $joueurRepository,
    ) {
    }
 
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        [$codeAcces, $pseudo] = array_pad(explode(':', $identifier, 2), 2, '');

        $joueur = $this->joueurRepository->findOneByCodeAccesAndPseudo($codeAcces, $pseudo);

        if (null === $joueur) {
            throw new UserNotFoundException(sprintf('Aucun joueur trouvé pour l\'identifiant "%s".', $identifier));
        }

        return $joueur;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof Joueur) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_debug_type($user)));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return Joueur::class === $class || is_subclass_of($class, Joueur::class);
    }
}