<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'invoice_number',
        'branch_id',
        'created_date',
        'payment_date',
        'dp_date',
        'customer_id',
        'designer_id',
        'status',
        'discount',
        'dp_amount',
        'accounts_receivable',
        'grand_total',
        'total_cost',
        'total_profit',
        'deadline',
        'pickup_status',
        'pickup_date',
        'source',
    ];

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function designer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'designer_id');
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Recalculate accounts receivable and status based on payments.
     */
    public function recalculatePayments(): void
    {
        $totalPaid = (float) $this->payments()->sum('amount');
        $downPayments = (float) $this->payments()->where('type', 'down_payment')->sum('amount');

        $this->dp_amount = round($downPayments, 2);
        $this->accounts_receivable = round(max(0, (float) $this->grand_total - $totalPaid), 2);

        if ($this->accounts_receivable <= 0) {
            $this->status = 2; // Fully Paid
            $this->payment_date = $this->payment_date ?: now()->toDateString();
        } elseif ($totalPaid > 0) {
            $this->status = 1; // Down Payment Paid
            $this->dp_date = $this->dp_date ?: now()->toDateString();
        } else {
            $this->status = 0; // Unpaid
        }

        $this->save();
    }
}
