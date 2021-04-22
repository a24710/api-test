<?php


namespace App\Services;


use App\Entity\BaseEntity;
use App\Entity\Customer;
use App\Entity\Media;
use App\Utils\IDGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\Security;

class MediaManager
{
    private EntityManagerInterface $entityManager;
    private string $privateImageDir;
    private string $publicImageDir;
    private string $projectDir;
    private Filesystem $filesystem;

    public function __construct(
        EntityManagerInterface $entityManager,
        Filesystem $filesystem,
        Security $security,
        string $projectDir,
        string $privateImageDir,
        string $publicImageDir)
    {
        $this->entityManager = $entityManager;
        $this->privateImageDir = $privateImageDir;
        $this->filesystem = $filesystem;
        $this->privateImageDir = $privateImageDir;
        $this->publicImageDir = $publicImageDir;
        $this->projectDir = $projectDir;
    }

    private function checkCorrectMiMeType(File $file, string $mediaType, string &$error): bool
    {
        $result = true;
        $mimeType = $file->getMimeType();

        if ($mediaType === Media::_TYPE_USER_AVATAR){
            if (strpos($mimeType, 'image') !== 0){
                $result = false;
                $error = 'File is not an image';
            }

        } else {
            $result = false;
        }

        return $result;
    }

    public function delete(Media $media): bool
    {
        //remove from folder
        $fullPath = ($media->getType() === Media::_TYPE_USER_AVATAR) ?
            $this->publicImageDir . '/' . $media->getHashedName() :
            $this->privateImageDir . '/' . $media->getHashedName();

        $this->entityManager->remove($media);
        $this->entityManager->flush();
        $result = true;

        $fullPath = $this->projectDir . '/' . $fullPath;

        try{
            $this->filesystem->remove($fullPath);

        } catch (\Exception $exception){
            $result = false;
        }

        return $result;
    }

    public function create(File $file,
        string $type,
        ?BaseEntity $referencedEntity,
        string &$error): ?Media
    {
        $media = new Media();
        $media->setType($type);
        $media->setMimeType($file->getMimeType());

        if (!$this->checkCorrectMiMeType($file, $type, $error)){
            return null;
        }

        if ($referencedEntity !== null){
            $media->setReferencedClass(get_class($referencedEntity));
            $media->setReferencedId($referencedEntity->getUuid());
        }

        $fileExtension = $file->getClientOriginalExtension();
        $hashedName = IDGenerator::generateUUID() . '.' . $fileExtension;
        $media->setOriginalFileName($file->getClientOriginalName());
        $media->setHashedName($hashedName);

        $originalPath = $file->getPathname();
        $copyPath = $this->projectDir . '/' . $this->publicImageDir .  '/' . $hashedName;
        $copied = true;

        try{
            $this->filesystem->copy($originalPath, $copyPath);

        } catch (\Exception $exception){
            $copied = false;
            $error = $exception->getMessage();
        }

        if (!$copied){
            return null;
        }

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        //attach to user
        if ($referencedEntity instanceof Customer &&
            $media->getType() === Media::_TYPE_USER_AVATAR)
        {
            $referencedEntity->setAvatar($media);
            $this->entityManager->persist($referencedEntity);
            $this->entityManager->flush();
        }

        return $media;
    }
}

