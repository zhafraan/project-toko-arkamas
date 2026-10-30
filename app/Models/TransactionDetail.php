<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id', 'product_id', 'transaction_type',
        'qty', 'price', 'cost_price', 'subtotal',
    ];

    protected $casts = [
        'qty'        => 'integer',
        'price'      => 'decimal:2',
        'cost_price' => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the type label for the transaction.
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->transaction_type) {
            'refill'        => 'Refill (Tukar Tabung)',
            'pinjam_tabung' => 'Beli Isi + Pinjam Tabung',
            'plus_tabung'   => 'Beli Baru + Tabung',
            default         => '-',
        };
    }

    /**
     * Get the unit label from the related product.
     */
    public function getUnitLabelAttribute(): ?string
    {
        return $this->product?->unit ?? null;
    }
}
