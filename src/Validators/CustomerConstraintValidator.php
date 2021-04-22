<?php


namespace App\Validators;


use App\Entity\Customer;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CustomerConstraintValidator extends ConstraintValidator
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function validate($value, Constraint $constraint)
    {
        $this->checkEmail($value);
        $this->checkDuplicate($value);
    }

    public function checkDuplicate(Customer $customer)
    {
        $dbCustomer = $this->entityManager
            ->getRepository(Customer::class)
            ->findOneBy(['name' => $customer->getName(),
                         'surname' => $customer->getSurname()]);

        if ($dbCustomer === null){
            return;
        }

        if ($customer->isPersisted()){
            //patch
            if ($dbCustomer->getId() !== $customer->getId()){
                $this->context->buildViolation('Name and surname combination already in use')
                    ->atPath('name')
                    ->addViolation();
            }

        } else {
            //post
            if ($dbCustomer !== null){
                $this->context->buildViolation('Name and surname combination already in use')
                    ->atPath('name')
                    ->addViolation();
            }
        }
    }

    protected function checkEmail(Customer $customer)
    {
        $customerRepo = $this->entityManager->getRepository(Customer::class);
        $dbCustomer = $customerRepo->findOneBy(['email' => $customer->getEmail()]);

        if ($dbCustomer === null){
            return;
        }

        if ($customer->isPersisted()){
            //PATCH
            if ($dbCustomer->getId() !== $customer->getId()){
                $this->context->buildViolation('Email already in use')
                    ->atPath('email')
                    ->addViolation();
            }

        } else {
            //POST
            if ($customer !== null){
                $this->context->buildViolation('Email already in use')
                    ->atPath('email')
                    ->addViolation();
            }
        }
    }
}