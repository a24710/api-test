<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    /**
     * @Route("test", name="test", methods={"get"})
     */
    public function test()
    {
        return new JsonResponse(['message' => 'hello']);
    }
}