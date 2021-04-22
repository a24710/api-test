<?php


namespace App\Tests\Controller;


use App\Entity\Customer;
use App\Entity\Media;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MediaControllerTest extends AuthenticatedWebTestCase
{
    private const _DUMMY_AVATAR_IMAGE = '/tests/Resources/dummy.png';

    public function testUploadAvatarWithAdmin()
    {
        $client = $this->getAuthenticatedClient(self::_ADMIN_FIXTURE_EMAIL);
        $customer = $this->testUploadAvatar($client);

        $client = $this->getAuthenticatedClient(self::_ADMIN_FIXTURE_EMAIL, $client);
        $this->testDeleteCustomerAvatar($client, $customer);
    }

    public function testUploadAvatarWithNonAdmin()
    {
        $client = $this->getAuthenticatedClient(self::_NON_ADMIN_FIXTURE_EMAIL);
        $customer = $this->testUploadAvatar($client);

        $client = $this->getAuthenticatedClient(self::_NON_ADMIN_FIXTURE_EMAIL, $client);
        $this->testDeleteCustomerAvatar($client, $customer);
    }

    private function testUploadAvatar(KernelBrowser $client): Customer
    {
        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $customerRepo = $entityManager->getRepository(Customer::class);
        $dbCustomer = $customerRepo->findOneBy([]); //get a user from db

        self::assertNotNull($dbCustomer);

        $filename = $client->getKernel()->getProjectDir() . self::_DUMMY_AVATAR_IMAGE;
        //modify it
        $customerId = $dbCustomer->getId();
        $iri = 'api/customers/' . $dbCustomer->getUuid() . '/avatar';

        //check the file exists
        $uploadFile = null;

        try{
            $uploadFile = new UploadedFile(
                $filename,
                'dummy.png'
            );
        } catch (\Exception $exception){
            $uploadFile = null;
        }

        self::assertNotNull($uploadFile);

        //update avatar
        $client->request(Request::METHOD_POST, $iri, [], ['file' => $uploadFile]);
        $response = $client->getResponse();
        self::assertEquals(302, $response->getStatusCode());
        $client->followRedirect();
        $response = $client->getResponse();
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());

        //check in the db it now has an avatar
        $dbModifiedCustomer = $customerRepo->find($customerId);
        self::assertInstanceOf(Media::class, $dbModifiedCustomer->getAvatar());

        return $dbModifiedCustomer;
    }

    private function testDeleteCustomerAvatar(KernelBrowser $client, Customer $customer): void
    {
        $customerId = $customer->getId();
        $iri = 'api/customers/' . $customer->getUuid() . '/avatar';

        //now delete the avatar
        $client->request(Request::METHOD_DELETE, $iri, []);
        $response = $client->getResponse();
        self::assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        //check in the db it now has an avatar
        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $customerRepo = $entityManager->getRepository(Customer::class);
        $dbModifiedCustomer = $customerRepo->find($customerId);
        self::assertNull($dbModifiedCustomer->getAvatar());
    }
}