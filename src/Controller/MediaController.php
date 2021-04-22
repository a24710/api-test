<?php


namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Media;
use App\Entity\User;
use App\Security\Voters\CustomerVoter;
use App\Services\MediaManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\SerializerInterface;

class MediaController extends AbstractController
{
    /**
     * @Route("api/customers/{uuid}/avatar", name="upload_user_avatar", methods={"POST"})
     */
    public function uploadUserAvatar(string $uuid,
                                    Request $request,
                                    EntityManagerInterface $entityManager,
                                    MediaManager $mediaManager, SerializerInterface $serializer)
    {
        $file = $request->files->get('file');

        if (!($file instanceof File)){
            return new JsonResponse(['error' => 'file field not found'], Response::HTTP_BAD_REQUEST);
        }

        $customerToPatch = $entityManager
            ->getRepository(Customer::class)
            ->findOneBy(['uuid' => $uuid]);

        if ($customerToPatch === null){
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(CustomerVoter::_CUSTOMER_PATCH, $customerToPatch);

        $error = '';
        $oldAvatar = $customerToPatch->getAvatar();
        $media = $mediaManager->create($file,
                                       Media::_TYPE_USER_AVATAR,
                                       $customerToPatch,
                                       $error);

        if ($media === null){
            return new JsonResponse(['error' => $error], Response::HTTP_BAD_REQUEST);
        }

        if ($oldAvatar !== null){
            $mediaManager->delete($oldAvatar);
        }

        $response = $this->redirectToRoute('api_customers_get_item', ['uuid' => $customerToPatch->getUuid()]);

        return $response;
    }

    /**
     * @Route("api/customers/{uuid}/avatar", name="delete_user_avatar", methods={"DELETE"})
     */
    public function deleteUserAvatar(string $uuid,
        Request $request,
        EntityManagerInterface $entityManager,
        MediaManager $mediaManager)
    {
        $customerToPatch = $entityManager
            ->getRepository(Customer::class)
            ->findOneBy(['uuid' => $uuid]);

        if ($customerToPatch === null){
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(CustomerVoter::_CUSTOMER_PATCH, $customerToPatch);

        $searchParams = ['referencedClass' => get_class($customerToPatch),
            'referencedId' => $customerToPatch->getUuid(),
            'type' => Media::_TYPE_USER_AVATAR];

        $dbMedia = $entityManager
            ->getRepository(Media::class)
            ->findOneBy($searchParams);

        if ($dbMedia === null){
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $customerToPatch->setAvatar(null);
        $entityManager->flush();
        $mediaManager->delete($dbMedia);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}



