<?php

namespace App\Controller;

use App\Service\StripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PaymentController extends AbstractController
{
    public function __construct(private StripeService $stripeService, private MailerInterface $mailer) {}

    #[Route('/pay', name: 'pay_page', methods: ['GET'])]
    public function payPage(): Response
    {
        // Ensure STRIPE_PUBLISHABLE_KEY is set in your .env
        return $this->render('payment/pay.html.twig', []);
    }

    #[Route('/payment/intent', name: 'payment_intent', methods: ['POST'])]
    public function createIntent(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        // Fixed amount: 20 EUR -> 2000 cents
        $amount = 2000;

        $metadata = [];
        if ($email) {
            $metadata['email'] = $email;
        }

        $paymentIntent = $this->stripeService->createPaymentIntent($amount, 'eur', $metadata);

        return new JsonResponse([
            'client_secret'     => $paymentIntent->client_secret,
            'payment_intent_id' => $paymentIntent->id,
            'amount'            => $amount / 100,
            'currency'          => 'eur',
        ], 200);
    }

    #[Route('/payment/webhook', name: 'payment_webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        $payload   = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        if (!$sigHeader) {
            return new Response('Missing Stripe-Signature header', 400);
        }

        try {
            $event = $this->stripeService->constructWebhookEvent($payload, $sigHeader);
        } catch (\Exception $e) {
            return new Response('Invalid signature', 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;
            $email = $paymentIntent->metadata->email ?? null;

            if ($email) {
                $message = (new Email())
                    ->from('no-reply@example.com')
                    ->to($email)
                    ->subject('Gràcies per la teva compra')
                    ->text(sprintf("Gràcies per la teva compra. Import: %s %s", $paymentIntent->amount / 100, $paymentIntent->currency));

                try {
                    $this->mailer->send($message);
                } catch (\Throwable $e) {
                    // don't fail webhook because mail failed; log it
                    error_log('Failed to send confirmation email: ' . $e->getMessage());
                }
            }

            // Aquí es pot emmagatzemar la transacció a BD si es vol
            error_log('Pagament completat: ' . $paymentIntent->id);
        }

        return new Response('', 200);
    }
}
