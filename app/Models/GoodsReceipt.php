<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'user_id', 'receipt_date', 'crates', 'qty', 'buy_price_per_unit', 'total_cost', 'supplier', 'notes',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'crates' => 'decimal:2',
        'qty' => 'decimal:2',
        'buy_price_per_unit' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
