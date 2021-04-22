<?php


namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Class Media
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(indexes={@ORM\Index(name="media_uuid_idx", columns={"uuid"})})
 */
class Media extends BaseEntity
{
    public const _TYPE_USER_AVATAR = 'user_avatar';
    public const _AVATAR_FOLDER = 'avatars';

    /**
     * @ORM\Column(type="string", length=255, nullable=false, options={"default"=""})
     * @Groups({"media:read"})
     */
    private string $originalFileName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $type;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private ?string $hashedName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"media:read"})
     */
    private string $mimeType;

    /**
    * @ORM\Column(type="boolean")
    **/
    private bool $public;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private ?string $referencedClass;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private ?string $referencedId;

    public function __construct()
    {
        parent::__construct();
        $this->public = false;
        $this->referencedClass = null;
        $this->referencedId = null;
        $this->hashedName = null;
    }

    /**
     * @Groups({"media:read"})
     */
    public function getPublicUrl(): ?string
    {
        $outValue = null;

        if ($this->type === self::_TYPE_USER_AVATAR){
            $outValue = self::_AVATAR_FOLDER . '/' . $this->hashedName;
        }

        return $outValue;
    }

    public function getOriginalFileName(): string
    {
        return $this->originalFileName;
    }


    public function setOriginalFileName(string $originalFileName): Media
    {
        $this->originalFileName = $originalFileName;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): Media
    {
        $this->type = $type;
        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): Media
    {
        $this->path = $path;
        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): Media
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): Media
    {
        $this->public = $public;
        return $this;
    }

    public function getReferencedClass(): ?string
    {
        return $this->referencedClass;
    }

    public function setReferencedClass(?string $referencedClass): Media
    {
        $this->referencedClass = $referencedClass;
        return $this;
    }

    public function getReferencedId(): ?string
    {
        return $this->referencedId;
    }

    public function setReferencedId(?string $referencedId): Media
    {
        $this->referencedId = $referencedId;
        return $this;
    }

    public function getHashedName(): ?string
    {
        return $this->hashedName;
    }

    public function setHashedName(?string $hashedName): Media
    {
        $this->hashedName = $hashedName;
        return $this;
    }
}

