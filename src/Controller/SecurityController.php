<?php


namespace App\Controller;

use App\Entity\User;
use App\Entity\ConfirmationToken;
use App\Services\EMailManager;
use App\Services\SecurityManager;
use App\Services\ConfirmationTokenManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;


class SecurityController extends AbstractController
{
    /**
     * @Route("security/request_token", name="security_request_token")
     */
    public function requestToken(Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $userPasswordEncoder,
        SecurityManager $securityManager)
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $rememberMe = false;

        if ($email === null ||
            $password === null)
        {
            return new Response(null, Response::HTTP_BAD_REQUEST);
        }

        if ($request->request->has('rememberMe') &&
            ($request->request->get('rememberMe') === 'true'))
        {
            $rememberMe = true;
        }

        $response = new JsonResponse(['error' => 'user or password invalid'], Response::HTTP_BAD_REQUEST);
        $user = $entityManager->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($user instanceof UserInterface &&
            $userPasswordEncoder->isPasswordValid($user, $password))
        {
            $newTokens = $securityManager->createJWTTokens($email, $rememberMe);

            if (count($newTokens) > 0){
                $response =  new JsonResponse($newTokens, Response::HTTP_OK);
            }
        }

        return $response;
    }

    /**
     * @Route("security/refresh_token", name="security_refresh_token")
     */
    public function refreshToken(Request $request,
                                 SecurityManager $securityManager)
    {
        $refreshToken = $request->request->get('refreshToken');

        if ($refreshToken === null){
            return new JsonResponse(['error' => 'Refresh token no provided'], Response::HTTP_BAD_REQUEST);
        }

        $decodedToken = $securityManager->decodeJWTToken($refreshToken);

        if ($decodedToken === null ||
            count($decodedToken) == 0 ||
            !array_key_exists('user', $decodedToken) ||
            !array_key_exists('refresh', $decodedToken) ||
            !$decodedToken['refresh'])
        {
            return new JsonResponse(['error' => 'Refresh token not valid'], Response::HTTP_BAD_REQUEST);
        }

        $email = $decodedToken['user'];
        $tokenExpires = new \DateTime($decodedToken['expires']);
        $now = new \DateTime();

        if ($tokenExpires < $now) {
            return new JsonResponse(['error' => 'Refresh token not valid'], Response::HTTP_BAD_REQUEST);
        }

        $rememberMe = $decodedToken['rememberMe'] ?? false;
        $newTokens = $securityManager->createJWTTokens($email, $rememberMe);

        $response = (count($newTokens) > 0) ?
            new JsonResponse($newTokens, Response::HTTP_OK) :
            new JsonResponse(['error' => ''], Response::HTTP_BAD_REQUEST);

        return $response;
    }

    /**
     * @Route("api/authenticated_echo", name="api_authenticated_echo", methods={"GET"})
     */
    public function echo()
    {
        return new JsonResponse(['message' => 'echo ok']);
    }
}


