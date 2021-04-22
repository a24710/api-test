<?php


namespace App\Services;


use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use PhpParser\Node\Stmt\Break_;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class SecurityManager
{
    protected EntityManagerInterface $entityManager;
    protected ContainerInterface $container;


    public function __construct(EntityManagerInterface $entityManager,
                                ContainerInterface $container)
    {
        $this->entityManager = $entityManager;
        $this->container = $container;
    }

    public function createJWTTokens(string $userMail, bool $rememberMe): array
    {
        $expires = new \DateTime();

        if ($rememberMe){
            $expires->modify('+1 year');

        } else {
            $expires->modify('+1 day');
        }

        $payload = [
            'user' => $userMail,
            'expires' => $expires->format(\DateTime::ATOM),
            'rememberMe' => $rememberMe
        ];

        $jwtPassword = $this->container->getParameter('jwt_password');
        $privatePemPath = $this->container->get('kernel')->getProjectDir();
        $privatePemPath = $privatePemPath . '/' . $this->container->getParameter('jwt_private_key');
        $privatePemPath = 'file://' . $privatePemPath;

        $privateKey = openssl_pkey_get_private($privatePemPath, $jwtPassword);
        $newTokens = [];

        if ($privateKey !== false){
            $newTokens['token'] = JWT::encode($payload, $privateKey, 'RS256');

            //generate refresh
            $expires = new \DateTime();
            $expires->modify('+1 week');

            $payload['expires'] = $expires->format(\DateTime::ATOM);
            $payload['refresh'] = true;
            $newTokens['refreshToken'] = JWT::encode($payload, $privateKey, 'RS256');
        }

        return $newTokens;
    }

    public function decodeJWTToken(string $token): ?array
    {
        $decodedToken = null;
        $publicPemPath = $this->container->get('kernel')->getProjectDir();
        $publicPemPath = $publicPemPath . '/' . $this->container->getParameter('jwt_public_key');
        $publicPemPath = 'file://' . $publicPemPath;

        $publicKey = openssl_get_publickey($publicPemPath);
        $newToken = null;

        if ($publicKey !== false){

            try{
                $decoded = JWT::decode($token, $publicKey, array('RS256'));
                $decodedToken = $this->objectToArray($decoded);

            } catch (\Exception $exception){
            }
        }

        return $decodedToken;
    }

    public function checkValidPassword(string $password, User $user, string &$error): bool
    {
        $userEmail = $user->getemail();

        if (strlen($password) < 8) {
            $error = "Your password must contain at least 8 characters";
            return false;
        }

        if (!preg_match("#[0-9]+#",$password)){
            $error = "Your password must contain at least 1 number";
            return false;
        }

        if (!preg_match("#[A-Z]+#",$password)) {
            $error = "Your password must contain at least 1 capital letter";
            return false;
        }

        if (!preg_match("#[a-z]+#",$password)) {
            $error = "Your password must contain at least 1 lower case letter";
            return false;
        }

        if (strpos($userEmail,$password) !== false){
            $error = "Your password must not match username";
            return false;
        }

        return true;
    }

    private function objectToArray($data)
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        if (is_array($data)) {
            return array_map(array($this, 'objectToArray'), $data);
        }

        return $data;
    }
}



