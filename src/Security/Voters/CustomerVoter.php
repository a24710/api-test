<?php


namespace App\Security\Voters;


use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CustomerVoter extends Voter
{
    public const _CUSTOMER_GET_COLLECTION = 'CUSTOMER_GET_COLLECTION';
    public const _CUSTOMER_GET_ITEM = 'CUSTOMER_GET_ITEM';
    public const _CUSTOMER_PATCH = 'CUSTOMER_PATCH';
    public const _CUSTOMER_POST = 'CUSTOMER_POST';
    public const _CUSTOMER_DELETE = 'CUSTOMER_DELETE';

    private array $attributes;

    public function __construct()
    {
        $this->attributes = [
            self::_CUSTOMER_DELETE,
            self::_CUSTOMER_POST,
            self::_CUSTOMER_PATCH,
            self::_CUSTOMER_GET_COLLECTION,
            self::_CUSTOMER_GET_ITEM
        ];
    }

    protected function supports(string $attribute, $subject)
    {
        return in_array($attribute, $this->attributes);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token)
    {
        //both normal user and admin user have full control over the customer CRUD
        //fill this method with custom logic for more elaborated authorization
        return true;
    }
}