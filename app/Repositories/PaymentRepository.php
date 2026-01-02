<?php

namespace App\Repositories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;

class PaymentRepository
{
    public function __construct(protected Payment $model)
    {
    }

    /**
     * Create a new payment record
     */
    public function create(array $data): Payment
    {
        return $this->model->create($data);
    }

    /**
     * Find payment by ID
     */
    public function findById(int $id): ?Payment
    {
        return $this->model->find($id);
    }

    /**
     * Find payment by transaction ID
     */
    public function findByTransactionId(string $transactionId): ?Payment
    {
        return $this->model->where('transaction_id', $transactionId)->first();
    }

    /**
     * Find payment by payment intent ID
     */
    public function findByPaymentIntentId(string $paymentIntentId): ?Payment
    {
        return $this->model->where('payment_intent_id', $paymentIntentId)->first();
    }

    /**
     * Update payment
     */
    public function update(Payment $payment, array $data): bool
    {
        return $payment->update($data);
    }

    /**
     * Get payments by user ID
     */
    public function getByUserId(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get payments by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get user payments with pagination
     */
    public function getUserPaymentsPaginated(int $userId, int $perPage = 15)
    {
        return $this->model->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Delete payment
     */
    public function delete(Payment $payment): bool
    {
        return $payment->delete();
    }
}