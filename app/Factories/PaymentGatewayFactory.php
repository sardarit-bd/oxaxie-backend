<?php

namespace App\Factories;

use App\Contracts\PaymentGatewayInterface;
use App\Services\PaymentGateways\StripePaymentGateway;
use InvalidArgumentException;

class PaymentGatewayFactory
{
    /**
     * Gateway mappings
     */
    protected static array $gateways = [
        'stripe' => StripePaymentGateway::class,
    ];

    /**
     * Create a payment gateway instance based on the gateway type
     */
    public static function create(string $gateway): PaymentGatewayInterface
    {
        $gateway = strtolower($gateway);

        if (!isset(self::$gateways[$gateway])) {
            throw new InvalidArgumentException("Payment gateway [{$gateway}] is not supported.");
        }

        $gatewayClass = self::$gateways[$gateway];
        $instance = new $gatewayClass();

        // Check if gateway is available
        if (!$instance->isAvailable()) {
            throw new InvalidArgumentException("Payment gateway [{$gateway}] is not available or properly configured.");
        }

        return $instance;
    }

    /**
     * Register a new gateway dynamically
     */
    public static function register(string $name, string $gatewayClass): void
    {
        if (!is_subclass_of($gatewayClass, PaymentGatewayInterface::class)) {
            throw new InvalidArgumentException("Gateway class must implement PaymentGatewayInterface");
        }

        self::$gateways[strtolower($name)] = $gatewayClass;
    }

    /**
     * Get list of supported payment gateways
     */
    public static function getSupportedGateways(): array
    {
        return array_keys(self::$gateways);
    }

    /**
     * Get available (configured and ready) gateways
     */
    public static function getAvailableGateways(): array
    {
        $available = [];

        foreach (self::$gateways as $name => $class) {
            try {
                $instance = new $class();
                if ($instance->isAvailable()) {
                    $available[] = $name;
                }
            } catch (\Exception $e) {
                // Skip unavailable gateways
                continue;
            }
        }

        return $available;
    }

    /**
     * Check if a gateway is supported
     */
    public static function isSupported(string $gateway): bool
    {
        return isset(self::$gateways[strtolower($gateway)]);
    }

    /**
     * Check if a gateway supports a specific capability
     */
    public static function supportsCapability(string $gateway, string $interface): bool
    {
        if (!self::isSupported($gateway)) {
            return false;
        }

        $gatewayClass = self::$gateways[strtolower($gateway)];
        return is_subclass_of($gatewayClass, $interface);
    }

    /**
     * Get gateway capabilities
     */
    public static function getGatewayCapabilities(string $gateway): array
    {
        if (!self::isSupported($gateway)) {
            return [];
        }

        $gatewayClass = self::$gateways[strtolower($gateway)];
        $interfaces = class_implements($gatewayClass);

        $capabilities = [
            'online_payment' => in_array('App\Contracts\OnlinePaymentInterface', $interfaces),
            'manual_payment' => in_array('App\Contracts\ManualPaymentInterface', $interfaces),
            'refundable' => in_array('App\Contracts\RefundablePaymentInterface', $interfaces),
            'recurring' => in_array('App\Contracts\RecurringPaymentInterface', $interfaces),
            'webhook_support' => in_array('App\Contracts\WebhookSupportInterface', $interfaces),
        ];

        return array_filter($capabilities);
    }
}