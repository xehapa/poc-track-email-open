<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
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

    #[Route('/{edp}', name: 'app_home')]
    public function __invoke(Request $request, string | null $edp = null): Response
    {
        if ($edp === 'http-api') {
            return $this->forward(__CLASS__ . '::httpApi', [
                'request' => $request,
            ]);
        }

        if ($edp === 'pixel-api') {
            return $this->forward(__CLASS__ . '::pixelApi', [
                'request' => $request,
            ]);
        }
        
        return new Response('Hello POC Track Email');
    }

    public function pixelApi(Request $request): Response
    {
        $imgSrc = 'https://api.segment.io/v1/pixel/track?' . http_build_query([
                'data' => base64_encode(
                    json_encode([
                        'writeKey' => $this->getParameter('SEGMENT_PIXEL_API_WRITE_KEY'),
                        'userId' => uniqid('pixel_api_'),
                        'event' => 'Email Opened',
                        'properties' => [
                            'subject' => 'PIXEL API',
                            'recipient' => $request->query->get('r'),
                            'ref' => $request->getSchemeAndHttpHost(),
                        ],
                    ]),
                ),
            ]);

        $this->sendMail($imgSrc, $request->query->get('r'));

        return new Response('Tracking email open using Segment Pixel API');
    }

    public function httpApi(Request $request): Response
    {
        $imgSrc = $this->generateUrl('app_logo_img', [
            'customQueryParam' => uniqid(prefix: 'custom_param_value'),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->sendMail(imgSrc: $imgSrc, recipient: $request->query->get('r'));

        return new Response('Tracking email open using Segment Pixel API');
    }

    #[Route('/image/logo.png', name: 'app_logo_img')]
    public function serveImage(Request $request): BinaryFileResponse
    {
        try {
            $this->httpClient->request(method: 'POST', url:  'track', options: [
                'json' => [
                    'writeKey' => $this->getParameter('SEGMENT_HTTP_API_WRITE_KEY'),
                    'userId' => uniqid('http_api_'),
                    'event' => 'Email Opened',
                    'properties' => [
                        'subject' => 'HTTP API',
                        'recipient' => $request->query->get('r'),
                        'ref' => $request->getSchemeAndHttpHost(),
                    ],
                ],
                'base_uri' => 'https://api.segment.io/v1'
            ]);
        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            throw new TransportException($e->getMessage());
        }

        return $this->file('dummy.png', 'dummy.png', ResponseHeaderBag::DISPOSITION_INLINE);
    }

    private function sendMail(string $imgSrc, ?string $recipient = null): void
    {
        try {
            $email = (new Email())
                ->from(new Address(address: 'suppor@alex-poc-track-email-is-opened.herokuapp.com', name: 'Joko Bodo'))
                ->to($recipient ?? 'aintdra@gmail.com')
                ->subject('Testing HTTP API')
                ->html('<img src="' . $imgSrc . '" alt="logo" />');

            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            throw new TransportException($e->getMessage());
        }
    }
}
