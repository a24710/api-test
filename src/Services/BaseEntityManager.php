<?php


namespace App\Services;


use App\Entity\BaseEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BaseEntityManager
{
    protected EntityManagerInterface $entityManager;
    protected ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    public function store(BaseEntity $entity,
        bool $validate = true,
        bool $flush = true,
        ?array $validationGroups = null,
        ?array &$validationErrors = null): ?BaseEntity
    {
        $outValue = $entity;

        if ($validate){
            $outValue = $this->validate($entity, $validationGroups, $validationErrors) ?
                $entity :
                null;
        }

        if ($outValue !== null){
            $this->entityManager->persist($outValue);

            if ($flush){
                //flush?
                $this->entityManager->flush();
            }
        }

        return $outValue;
    }

    public function validate(BaseEntity $entity, ?array &$errors, ?array $validationGroups = null): bool
    {
        $constraints = $this->validator->validate($entity, null, $validationGroups);

        //populate errors
        if ($errors !== null){
            $errors = [];

            foreach ($constraints as $constraint){
                if ($constraint instanceof ConstraintViolationInterface){
                    $errors[] = [
                        'attribute' => $constraint->getPropertyPath(),
                        'error' => $constraint->getMessage()
                    ];
                }
            }
        }

        $result = ($constraints->count() === 0);

        return $result;
    }
}

