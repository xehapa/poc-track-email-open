<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\HttpApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
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
                        'userId' => uniqid('pixel_api'),
                        'event' => 'Email Opened',
                        'properties' => [
                            'subject' => 'Hello Xehapa',
                            'email' => 'hendra@xehapa.com',
                            'ref' => $request->getUri(),
                        ],
                    ]),
                ),
            ]);

        $this->sendMail($imgSrc);

        return new Response('Tracking email open using Segment Pixel API');
    }

    #[Route('/http-api')]
    public function httpApi(Request $request): Response
    {
        $payload = [
            'userId' => uniqid('http_api'),
            'event' => 'Email Opened',
            'properties' => [
                'subject' => 'Hello Xehapa',
                'email' => 'hendra@xehapa.com',
                'ref' => $request->getUri(),
                'name' => 'Someone Local',
            ],
        ];
        
        $imgSrc = $this->generateUrl('app_logo_img', [
            'data' => base64_encode(json_encode($payload)),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->sendMail(imgSrc: $imgSrc);

        return new Response('Tracking email open using Segment Pixel API');
    }

    #[Route('/image/logo.png', name: 'app_logo_img')]
    public function serveImage(Request $request, EntityManagerInterface $em): BinaryFileResponse
    {
//        try {
//            $this->httpClient->request('POST', 'https://api.segment.io/v1/track', [
//                'json' => [
//                    'writeKey' => $this->getParameter('SEGMENT_HTTP_API_WRITE_KEY'),
//                    'userId' => uniqid('http_api'),
//                    'event' => 'Email Opened',
//                    'properties' => [
//                        'subject' => 'Hello Xehapa',
//                        'email' => 'hendra@xehapa.com',
//                        'ref' => $request->getUri(),
//                        'name' => $request->query->get('name'),
//                    ],
//                ],
//            ]);
//        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
//            throw new TransportException($e->getMessage());
//        }

        $data = json_decode(base64_decode($request->query->get('data')), true);
        $properties = $data['properties'];
        
        $entity = (new HttpApi())
            ->setName($properties['name'] ?? null)
            ->setEmail($properties['email'] ?? null)
            ->setReference($properties['ref'] ?? null)
            ->setSubject($properties['subject'] ?? null)
        ;

        $em->persist($entity);
        $em->flush();
        
        return $this->file('stockclubs.webp', 'stockclubs-logo.webp', ResponseHeaderBag::DISPOSITION_INLINE);
    }

    private function sendMail(string $imgSrc): void
    {
        try {
            $email = (new Email())
                ->from('test@local.test')
                ->to('aintdra@gmail.com')
                ->html('<img src="' . $imgSrc . '" alt="testing image"/>');

            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            throw new TransportException($e->getMessage());
        }
    }
}
