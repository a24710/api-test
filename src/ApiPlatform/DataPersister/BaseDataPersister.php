<?php


namespace App\ApiPlatform\DataPersister;


use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use ApiPlatform\Core\JsonLd\Action\ContextAction;
use Doctrine\ORM\EntityManagerInterface;

abstract class BaseDataPersister implements ContextAwareDataPersisterInterface
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    //mandatory redefine in child class
    abstract public function supports($data, array $context = []): bool;

    //child class can not redefine this method
    final public function persist($data, array $context = [])
    {
        $operationName = '';

        if (array_key_exists('collection_operation_name', $context)){
            $operationName = $context['collection_operation_name'];

        } else if (array_key_exists('item_operation_name', $context)) {
            $operationName = $context['item_operation_name'];
        }

        if ($operationName === 'post'){
            $this->handlePost($data, $context);

        } else if ($operationName === 'put'){
            $this->handlePut($data, $context);

        } else if ($operationName === 'patch'){
            $this->handlePatch($data, $context);
        }
    }

    //redefine in child class if custom REMOVE desired
    public function remove($data, array $context = [])
    {
        $this->entityManager->remove($data);
        $this->entityManager->flush();
    }

    //redefine in child class if custom POST desired
    public function handlePost($data, array $context)
    {
        $this->entityManager->persist($data);
        $this->entityManager->flush();
    }

    //redefine in child class if custom PUT desired
    public function handlePut($data, array $context)
    {
        $this->entityManager->persist($data);
        $this->entityManager->flush();
    }

    //redefine in child class if custom PATCH desired
    public function handlePatch($data, array $context)
    {
        $this->entityManager->persist($data);
        $this->entityManager->flush();
    }
}


