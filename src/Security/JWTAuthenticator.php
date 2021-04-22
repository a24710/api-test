<?php


namespace App\Security;


use App\Entity\User;
use App\Services\SecurityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\HttpFoundation\JsonResponse;

class JWTAuthenticator extends AbstractGuardAuthenticator
{
    private EntityManagerInterface $entityManager;
    private SecurityManager $securityManager;
    private const JWT_TOKEN_KEY = 'JWTToken';

    public function __construct(EntityManagerInterface $entityManager, SecurityManager $securityManager)
    {
        $this->entityManager = $entityManager;
        $this->securityManager = $securityManager;
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request)
    {
        $supports = $request->headers->has(self::JWT_TOKEN_KEY);

        return $supports;
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     */
    public function getCredentials(Request $request)
    {
        $token = $request->headers->get(self::JWT_TOKEN_KEY);
        $decodedToken = $this->securityManager->decodeJWTToken($token) ?? [];

        return $decodedToken;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (null === $credentials ||
            !array_key_exists('expires', $credentials) ||
            !array_key_exists('user', $credentials))
        {
            // The token header was empty, authentication fails with HTTP Status
            // Code 401 "Unauthorized"
            return null;
        }

        $tokenExpires = new \DateTime($credentials['expires']);
        $now = new \DateTime();
        $user = null;

        if ($tokenExpires > $now){
            $user = $userProvider->loadUserByUsername($credentials['user']);
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        // Check credentials - e.g. make sure the password is valid.
        // In case of an API token, no credential check is needed.

        // Return `true` to cause authentication success
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = [
            // you may want to customize or obfuscate the message first
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())

            // or to translate this message
            // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = [
            // you might translate this message
            'message' => 'Authentication Required'
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe()
    {
        return false;
    }
}

