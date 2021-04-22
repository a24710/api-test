<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validators as Validators;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(indexes={@ORM\Index(name="user_uuid_idx", columns={"uuid"})})
 * @ORM\HasLifecycleCallbacks()
 * @Validators\UserConstraint()
 * @ApiResource(
 *     collectionOperations={
 *          "get"={
 *              "security"="is_granted('USER_GET_COLLECTION')",
 *              "normalization_context"={ "groups"={"user:read", "base:read"} }
 *          },
 *          "post"={
 *              "security"="is_granted('USER_POST')",
 *              "normalization_context"={ "groups"={"user:read", "base:read"} },
 *              "denormalization_context"={ "groups"={"user:write"} }
 *          }
 *     },
 *     itemOperations={
 *          "get"={
 *              "security"="is_granted('USER_GET_ITEM', object)",
 *              "normalization_context"={ "groups"={"user:read", "base:read"} },
 *          },
 *          "patch"={
 *              "security"="is_granted('USER_PATCH', object)",
 *              "normalization_context"={ "groups"={"user:read", "base:read"} },
 *              "denormalization_context"={ "groups"={"user:write"} }
 *          },
 *          "delete"={
 *              "security"="is_granted('USER_DELETE', object)",
 *          }
 *     }
 * )
 */
class User extends BaseEntity implements UserInterface
{
    public const _ROLE_ADMIN = 'ROLE_ADMIN';
    public const _ROLE_USER = 'ROLE_USER';

    /**
     * @Assert\Email()
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255, unique=true)
     * @Groups({"user:read", "user:write"})
     */
    private $email;

    /**
     * @ORM\Column(type="simple_array")
     * @Groups({"user:read", "user:write"})
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     * @Groups({"user:write"})
     */
    private $password;

    public function __construct()
    {
        parent::__construct();
        $this->lastActivity = null;
    }

    public static function getAvailableRoles(): array
    {
        return [
            self::_ROLE_ADMIN,
            self::_ROLE_USER
        ];
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}
