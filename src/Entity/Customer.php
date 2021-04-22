<?php


namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\CustomerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validators as Validators;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=CustomerRepository::class)
 * @ORM\Table(indexes={@ORM\Index(name="customer_uuid_idx", columns={"uuid"})})
 * @ORM\HasLifecycleCallbacks()
 * @Validators\CustomerConstraint()
 * @ApiResource(
 *     collectionOperations={
 *          "get"={
 *              "security"="is_granted('CUSTOMER_GET_COLLECTION')",
 *              "normalization_context"={ "groups"={"customer:read", "base:read", "media:read"} }
 *          },
 *          "post"={
 *              "security"="is_granted('CUSTOMER_POST')",
 *              "normalization_context"={ "groups"={"customer:read", "base:read"} },
 *              "denormalization_context"={ "groups"={"customer:write"} }
 *          }
 *     },
 *     itemOperations={
 *          "get"={
 *              "security"="is_granted('CUSTOMER_GET_ITEM', object)",
 *              "normalization_context"={ "groups"={"customer:read", "base:read", "media:read"} },
 *          },
 *          "patch"={
 *              "security"="is_granted('CUSTOMER_PATCH', object)",
 *              "normalization_context"={ "groups"={"customer:read", "base:read", "media:read"} },
 *              "denormalization_context"={ "groups"={"customer:write"} }
 *          },
 *          "delete"={
 *              "security"="is_granted('CUSTOMER_DELETE', object)",
 *          }
 *     }
 * )
 */
class Customer extends BaseEntity
{
    /**
     * @Assert\Length(max=128)
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=128, nullable=false)
     * @Groups({"customer:read", "customer:write"})
     */
    private ?string $name;

    /**
     * @Assert\Length(max=128)
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=128, nullable=false)
     * @Groups({"customer:read", "customer:write"})
     */
    private ?string $surname;

    /**
     * @ORM\OneToOne(targetEntity="Media")
     * @ORM\JoinColumn(name="avatar_id", referencedColumnName="id")
     * @Groups({"customer:read"})
     */
    private ?Media $avatar;

    /**
     * @Assert\Email()
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255, unique=true)
     * @Groups({"customer:read", "customer:write"})
     */
    private $email;

    public function __construct()
    {
        parent::__construct();

        $this->name = null;
        $this->surname = null;
        $this->avatar = null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): Customer
    {
        $this->name = $name;
        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(?string $surname): Customer
    {
        $this->surname = $surname;
        return $this;
    }

    public function getAvatar(): ?Media
    {
        return $this->avatar;
    }

    public function setAvatar(?Media $avatar): Customer
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }
}

