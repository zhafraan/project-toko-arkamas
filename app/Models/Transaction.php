<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number', 'user_id', 'customer_id', 'transaction_date',
        'total_amount', 'paid_amount', 'remaining_debt', 'change_amount',
        'payment_status', 'payment_method', 'payment_note', 'paid_at',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function details()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function tubeLoans()
    {
        return $this->hasMany(TubeLoan::class);
    }

    public function debtPayments()
    {
        return $this->hasMany(DebtPayment::class);
    }

    public function isLunas(): bool
    {
        return $this->payment_status === 'lunas';
    }

    // Generate invoice number format: INV-YYYYMMDD-XXXX
    public static function generateInvoiceNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = "INV-{$date}-";
        $last = static::where('invoice_number', 'like', "{$prefix}%")
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $seq = $last ? (int) substr($last, -4) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
