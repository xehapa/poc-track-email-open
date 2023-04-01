<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly HttpClientInterface $httpClient
    ) {}

    #[Route('/', name: 'app_home')]
    public function __invoke(): Response
    {
        $imgSrc = $this->generateUrl(
            'app_logo_img',
            ['name' => 'Joko'],
            UrlGeneratorInterface::NETWORK_PATH
        );

        try {
            $email = (new Email())
                ->from('test@local.test')
                ->to('aintdra@gmail.com')
                ->html('<img src="' . $imgSrc . '" alt="testing image"/>');

            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            throw new TransportException($e->getMessage());
        }

        return new Response('Hello POC Track Email');
    }


    #[Route('/image/logo.png', name: 'app_logo_img')]
    public function serveImage(): BinaryFileResponse
    {
        try {
            $this->httpClient->request('POST', 'https://api.segment.io/v1/track', [
                'json' => [
                    'writeKey' => $this->getParameter('SEGMENT_WRITE_KEY'),
                    'userId' => '48d213bb-95c3-4f8d-af97-86b2b404dcfe',
                    'event' => 'Email Opened',
                    'properties' => [
                        'subject' => 'Hello Xehapa',
                        'email' => 'hendra@xehapa.com',
                    ],
                ],
            ]);
        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            throw new TransportException($e->getMessage());
        }

        return $this->file('stockclubs.webp', 'stockclubs-logo.webp', ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
