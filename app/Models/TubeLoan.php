<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TubeLoan extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id', 'customer_id', 'product_id',
        'qty_borrowed', 'qty_returned', 'deposit_amount',
        'status', 'borrow_date', 'return_date', 'notes',
    ];

    protected $casts = [
        'qty_borrowed' => 'integer',
        'qty_returned' => 'integer',
        'deposit_amount' => 'decimal:2',
        'borrow_date' => 'date',
        'return_date' => 'date',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getRemainingQtyAttribute(): int
    {
        return $this->qty_borrowed - $this->qty_returned;
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'dipinjam'        => 'Dipinjam',
            'sebagian_kembali'=> 'Sebagian Kembali',
            'dikembalikan'    => 'Dikembalikan',
            default           => '-',
        };
    }
}
