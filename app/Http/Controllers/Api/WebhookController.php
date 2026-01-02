<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class WebhookController extends Controller
{
    public function __construct(protected PaymentService $paymentService)
    {
    }

    /**
     * Handle Stripe webhook
     */
    public function handleStripeWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $this->paymentService->updatePaymentStatus(
                    $paymentIntent->id,
                    'succeeded'
                );
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $this->paymentService->updatePaymentStatus(
                    $paymentIntent->id,
                    'failed'
                );
                break;

            case 'payment_intent.canceled':
                $paymentIntent = $event->data->object;
                $this->paymentService->updatePaymentStatus(
                    $paymentIntent->id,
                    'canceled'
                );
                break;

            case 'charge.refunded':
                $charge = $event->data->object;
                if (isset($charge->payment_intent)) {
                    $this->paymentService->updatePaymentStatus(
                        $charge->payment_intent,
                        'refunded'
                    );
                }
                break;

            default:
                // Unexpected event type
                return response()->json(['error' => 'Unexpected event type'], 400);
        }

        return response()->json(['success' => true]);
    }
}