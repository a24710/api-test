<?php


namespace App\ApiPlatform\DataPersister;


use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserDataPersister extends BaseDataPersister
{
    private UserPasswordEncoderInterface $passwordEncoder;

    public function __construct(EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder)
    {
        parent::__construct($entityManager);
        $this->passwordEncoder = $passwordEncoder;
    }

    public function supports($data, array $context = []): bool
    {
        return ($data instanceof User);
    }

    public function handlePost($data, array $context)
    {
        //Normal procedure : generate random password and send an email to the user with it
        //for the test: set 'mypassword'
        $encodedPassword = $this->passwordEncoder->encodePassword($data, 'mypassword');
        $data->setPassword($encodedPassword);

        parent::handlePost($data, $context);
    }
}