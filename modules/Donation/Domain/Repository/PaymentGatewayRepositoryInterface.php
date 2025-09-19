<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\Repository;

use Modules\Donation\Domain\Model\PaymentGateway;

/**
 * Payment Gateway Repository Interface.
 *
 * Defines the contract for payment gateway data persistence operations
 * following the hexagonal architecture pattern.
 */
interface PaymentGatewayRepositoryInterface
{
    /**
     * Create a new payment gateway.
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PaymentGateway;

    /**
     * Find a payment gateway by ID.
     */
    public function findById(int $id): ?PaymentGateway;

    /**
     * Find a payment gateway by provider.
     */
    public function findByProvider(string $provider, bool $testMode = true): ?PaymentGateway;

    /**
     * Update a payment gateway by ID.
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function updateById(int $id, array $data): bool;

    /**
     * Delete a payment gateway by ID.
     */
    public function delete(int $id): bool;

    /**
     * Get all active payment gateways ordered by priority.
     *
     * @return array<int, PaymentGateway>
     */
    public function findActive(): array;

    /**
     * Get all payment gateways ordered by priority (highest first).
     *
     * @return array<int, PaymentGateway>
     */
    public function findByPriority(): array;

    /**
     * Get all configured payment gateways (have required settings).
     *
     * @return array<int, PaymentGateway>
     */
    public function findConfigured(): array;

    /**
     * Get all active and configured payment gateways ordered by priority.
     *
     * @return array<int, PaymentGateway>
     */
    public function findActiveAndConfigured(): array;

    /**
     * Find gateways that support a specific currency.
     *
     * @return array<int, PaymentGateway>
     */
    public function findByCurrency(string $currency): array;

    /**
     * Find gateways that can process a payment of given amount and currency.
     *
     * @return array<int, PaymentGateway>
     */
    public function findForPayment(float $amount, string $currency): array;

    /**
     * Check if a provider already exists for the given mode.
     */
    public function existsByProvider(string $provider, bool $testMode = true): bool;

    /**
     * Get the highest priority gateway for a specific provider.
     */
    public function getHighestPriorityByProvider(string $provider): ?PaymentGateway;

    /**
     * Get all gateways in test mode.
     *
     * @return array<int, PaymentGateway>
     */
    public function findTestMode(): array;

    /**
     * Get all gateways in production mode.
     *
     * @return array<int, PaymentGateway>
     */
    public function findProductionMode(): array;
}
