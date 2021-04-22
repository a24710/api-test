<?php


namespace App\Security\Voters;


use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class UserVoter extends Voter
{
    //list all operations
    public const _USER_GET_COLLECTION = 'USER_GET_COLLECTION';
    public const _USER_GET_ITEM = 'USER_GET_ITEM';
    public const _USER_DELETE = 'USER_DELETE';
    public const _USER_PATCH = 'USER_PATCH';
    public const _USER_POST = 'USER_POST';

    private array $attributes;
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;

        $this->attributes = [
            self::_USER_GET_COLLECTION,
            self::_USER_GET_ITEM,
            self::_USER_DELETE,
            self::_USER_PATCH,
            self::_USER_POST
        ];
    }

    protected function supports(string $attribute, $subject)
    {
        return in_array($attribute, $this->attributes);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $currentUser = $this->security->getUser();

        if ($attribute === self::_USER_DELETE &&
            $subject instanceof User &&
            $currentUser->getId() === $subject->getId())
        {
            //do not allow to delete itself
            return false;
        }

        if (in_array(User::_ROLE_ADMIN, $currentUser->getRoles())){
            //admin can do anything
            return true;
        }

        if (in_array(User::_ROLE_USER, $currentUser->getRoles()) &&
            $attribute === self::_USER_GET_ITEM &&
            $subject instanceof User &&
            $subject->getId() === $currentUser->getId())
        {
            //regular user can only read itself
            return true;
        }

        return false;
    }
}