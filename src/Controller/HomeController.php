<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/')]
    public function __invoke(): BinaryFileResponse
    {
        return $this->file('stockclubs.webp', 'stockclubs-logo.webp', ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
