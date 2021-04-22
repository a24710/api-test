<?php


namespace App\Validators;


use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UserConstraintValidator extends ConstraintValidator
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function validate($value, Constraint $constraint)
    {
        $this->checkEmail($value);
        $this->checkAtLeastOneAdmin($value);
        $this->checkRoles($value);
    }

    protected function checkRoles(User $user)
    {
        $userRoles = $user->getRoles();
        $availableRoles = User::getAvailableRoles();

        foreach ($userRoles as $userRole){
            if (!in_array($userRole, $availableRoles)){
                $this->context->buildViolation('Role ' . $userRole . ' is not valid')
                    ->atPath('role')
                    ->addViolation();
            }
        }
    }

    protected function checkEmail(User $user)
    {
        $userRepo = $this->entityManager->getRepository(User::class);
        $dbUser = $userRepo->findOneBy(['email' => $user->getEmail()]);

        if ($dbUser === null){
            return;
        }

        if ($user->isPersisted()){
            //PATCH
            if ($dbUser->getId() !== $user->getId()){
                $this->context->buildViolation('Email already in use')
                    ->atPath('email')
                    ->addViolation();
            }

        } else {
            //POST
            if ($dbUser !== null){
                $this->context->buildViolation('Email already in use')
                    ->atPath('email')
                    ->addViolation();
            }
        }
    }

    public function checkAtLeastOneAdmin(User $user)
    {
        $currentUser = $this->security->getUser();

        if ($currentUser === null){
            //done by system
            return;
        }

        if (in_array(User::_ROLE_ADMIN, $currentUser->getRoles())){
            return;
        }

        $userRepo = $this->entityManager->getRepository(User::class);
        $adminUsers = $userRepo->findAdminUsers($currentUser);

        if (count($adminUsers) === 0){
            $this->context->buildViolation('There must be, at least, one admin user')
                ->atPath('email')
                ->addViolation();
        }
    }
}