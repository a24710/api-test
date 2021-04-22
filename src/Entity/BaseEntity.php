<?php

namespace App\Entity;

use App\Utils\IDGenerator;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiProperty;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * @ORM\MappedSuperclass
 **/
abstract class BaseEntity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @ApiProperty(identifier=false)
     */
    protected int $id; 

    /**
     * @ORM\Column(type="string", length=36, nullable=false)
     * @ApiProperty(identifier=true)
     * @SerializedName("id")
     */
    protected ?string $uuid;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"base:read"})
     */
    protected ?\DateTime $created;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"base:read"})
     */
    protected ?\DateTime $updated;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id", nullable=true)
     * @Groups({"base:read"})
     * @MaxDepth(1)
     */
    protected ?User $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @Groups({"base:read"})
     * @ORM\JoinColumn(name="updated_by", referencedColumnName="id", nullable=true)
     * @MaxDepth(1)
     */
    protected ?User $updatedBy;

    public function __construct()
    {
        $this->id = 0;
        $this->uuid = null;
    }

    public function getCreated(): ?\DateTime
    {
        return $this->created;
    }

    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    /** @ORM\PrePersist() */
    public function prePersist()
    {
        $this->uuid = IDGenerator::generateUUID();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function isPersisted(): bool
    {
        return ($this->uuid !== null);
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $user): BaseEntity
    {
        $this->createdBy = $user;
        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $user): BaseEntity
    {
        $this->updatedBy = $user;
        return $this;
    }
}










