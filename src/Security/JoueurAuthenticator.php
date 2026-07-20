<?php

namespace App\Security;

use App\Entity\Joueur;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\Exception\RuntimeException as MercureRuntimeException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Authentifie un Joueur à partir de codeAcces + pseudo + codePin soumis par
 * le formulaire de connexion de /rejoindre. La résolution du Joueur
 * (couple codeAcces/pseudo) est déléguée à JoueurProvider via l'identifiant
 * "{codeAcces}:{pseudo}".
 */
class JoueurAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public const ROUTE_CONNEXION = 'app_rejoindre_connexion';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Authorization $mercureAuthorization,
        private readonly RateLimiterFactoryInterface $connexionEquipeLimiter,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return self::ROUTE_CONNEXION === $request->attributes->get('_route') && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $codeAcces = $request->request->get('codeAcces', '');
        $pseudo = $request->request->get('pseudo', '');
        $codePin = $request->request->get('codePin', '');

        if ('' === $codeAcces || '' === $pseudo || '' === $codePin) {
            throw new AuthenticationException('Merci de renseigner votre code PIN.');
        }

        // Clé = codeAcces soumis (l'équipe visée), jamais l'IP : plusieurs
        // équipes derrière la même box/4G lors d'un événement ne doivent pas
        // se bloquer mutuellement.
        $limite = $this->connexionEquipeLimiter->create($codeAcces)->consume();
        if (!$limite->isAccepted()) {
            $minutes = (int) ceil(($limite->getRetryAfter()->getTimestamp() - time()) / 60);
            throw new TooManyLoginAttemptsAuthenticationException(max(1, $minutes));
        }

        $identifier = sprintf('%s:%s', $codeAcces, $pseudo);

        return new Passport(
            new UserBadge($identifier),
            new PasswordCredentials($codePin),
            [new CsrfTokenBadge('authenticate', $request->request->get('_token'))]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var Joueur $joueur */
        $joueur = $token->getUser();

        // Scope le cookie JWT au seul topic Mercure privé de cette équipe :
        // une équipe ne peut jamais s'abonner au topic "equipe/{id}" d'une
        // autre équipe (vérifié manuellement, cf. journal de session 6).
        //
        // Non fatal : si la requête arrive sur un host différent de celui
        // configuré dans MERCURE_PUBLIC_URL (ex. tunnel ngrok en test mobile),
        // Authorization::setCookie() lève une RuntimeException. Le temps réel
        // est alors indisponible pour cette session, l'authentification ne
        // doit pas échouer pour autant.
        try {
            $this->mercureAuthorization->setCookie(
                $request,
                [sprintf('equipe/%d', $joueur->getEquipe()->getId())]
            );
        } catch (MercureRuntimeException) {
        }

        return new RedirectResponse($this->urlGenerator->generate('app_equipe_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = $exception instanceof TooManyLoginAttemptsAuthenticationException
            ? 'Trop de tentatives pour ce code d\'accès. Réessayez dans quelques minutes.'
            : 'Pseudo ou code PIN incorrect.';

        $session = $request->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('error', $message);
        }

        $codeAcces = $request->request->get('codeAcces', '');

        return new RedirectResponse($this->urlGenerator->generate('app_rejoindre_equipe', ['codeAcces' => $codeAcces]));
    }

    /**
     * Accès anonyme à une route protégée (ex. /equipe/tableau-de-bord) : on
     * redirige vers le formulaire de saisie du code d'accès plutôt que de
     * renvoyer une erreur 401.
     */
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate('app_rejoindre'));
    }
}