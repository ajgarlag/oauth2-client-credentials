<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TimeController extends AbstractController
{
    #[Route(path: '/', name: 'time', methods: ['GET'])]
    public function getAction(): JsonResponse
    {
        return new JsonResponse(['time' => time()], 200);
    }
}
