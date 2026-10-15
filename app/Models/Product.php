<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'sku', 'price_buy', 'price_sell', 'price_tube',
        'stock_filled', 'stock_empty', 'stock_borrowed', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function agentDeliveries()
    {
        return $this->hasMany(AgentDelivery::class);
    }

    public function tubeLoans()
    {
        return $this->hasMany(TubeLoan::class);
    }

    public function getTotalStockAttribute(): int
    {
        return $this->stock_filled + $this->stock_empty + $this->stock_borrowed;
    }
}
