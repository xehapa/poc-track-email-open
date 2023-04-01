<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/')]
    public function __invoke(): Response
    {
        return new Response($this->getParameter('MAILER_DSN'));
        return $this->file('stockclubs.webp', 'stockclubs-logo.webp', ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
