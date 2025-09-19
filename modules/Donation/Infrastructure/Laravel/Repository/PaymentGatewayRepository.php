<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Repository;

use DB;
use Modules\Donation\Domain\Model\PaymentGateway;
use Modules\Donation\Domain\Repository\PaymentGatewayRepositoryInterface;

/**
 * Payment Gateway Eloquent Repository.
 *
 * Implements the PaymentGatewayRepositoryInterface using Eloquent ORM
 * for database operations with comprehensive query optimization.
 */
class PaymentGatewayRepository implements PaymentGatewayRepositoryInterface
{
    public function __construct(
        private readonly PaymentGateway $model,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PaymentGateway
    {
        return $this->model->create($data);
    }

    public function findById(int $id): ?PaymentGateway
    {
        return $this->model->find($id);
    }

    public function findByProvider(string $provider, bool $testMode = true): ?PaymentGateway
    {
        return $this->model
            ->where('provider', $provider)
            ->where('test_mode', $testMode)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateById(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->model->where('id', $id)->delete() > 0;
    }

    /**
     * @return array<int, PaymentGateway>
     */
    public function findActive(): array
    {
        return $this->model
            ->active()
            ->byPriority()
            ->get()
            ->all();
    }

    /**
     * @return array<int, PaymentGateway>
     */
    public function findByPriority(): array
    {
        return $this->model
            ->byPriority()
            ->get()
            ->all();
    }

    /**
     * @return array<int, PaymentGateway>
     */
    public function findConfigured(): array
    {
        return $this->model
            ->configured()
            ->byPriority()
            ->get()
            ->all();
    }

    /**
     * @return array<int, PaymentGateway>
     */
    public function findActiveAndConfigured(): array
    {
        return $this->model
            ->active()
            ->configured()
            ->byPriority()
            ->get()
            ->all();
    }

    /**
     * @return array<int, PaymentGateway>
     */
    public function findByCurrency(string $currency): array
    {
        return $this->model
            ->active()
            ->configured()
            ->whereHas('currencies', function ($query) use ($currency): void {
                $query->where('code', $currency);
            })
            ->byPriority()
            ->get()
            ->all();
    }

    /**
     * @return array<int, PaymentGateway>
     */
    public function findForPayment(float $amount, string $currency): array
    {
        return $this->model
            ->active()
            ->configured()
            ->whereHas('currencies', function ($query) use ($currency): void {
                $query->where('code', $currency);
            })
            ->where('min_amount', '<=', $amount)
            ->where('max_amount', '>=', $amount)
            ->byPriority()
            ->get()
            ->filter(fn (PaymentGateway $gateway): bool => $gateway->canProcessPayment($amount, $currency))
            ->values()
            ->all();
    }

    public function existsByProvider(string $provider, bool $testMode = true): bool
    {
        return DB::table('payment_gateways')
            ->where('provider', $provider)
            ->where('test_mode', $testMode)
            ->exists();
    }

    public function getHighestPriorityByProvider(string $provider): ?PaymentGateway
    {
        return $this->model
            ->byProvider($provider)
            ->active()
            ->configured()
            ->byPriority()
            ->first();
    }

    /**
     * @return array<int, PaymentGateway>
     */
    public function findTestMode(): array
    {
        return $this->model
            ->where('test_mode', true)
            ->byPriority()
            ->get()
            ->all();
    }

    /**
     * @return array<int, PaymentGateway>
     */
    public function findProductionMode(): array
    {
        return $this->model
            ->where('test_mode', false)
            ->byPriority()
            ->get()
            ->all();
    }
}
