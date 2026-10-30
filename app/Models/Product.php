<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /** Batas stok isi yang dianggap menipis. */
    public const LOW_STOCK = 5;

    /** Tarif default harga beli dari agen per tabung (bila pemilik tidak mengisi). */
    public const DEFAULT_BUY_PRICE = 14500;

    protected $fillable = [
        'name', 'sku', 'price_buy', 'price_sell', 'price_tube',
        'stock_filled', 'stock_empty', 'stock_borrowed', 'is_active',
        'category', 'unit', 'min_stock',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_stock' => 'integer',
        'stock_filled' => 'integer',
        'stock_empty' => 'integer',
        'stock_borrowed' => 'integer',
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
        return (int) ($this->stock_filled + $this->stock_empty + $this->stock_borrowed);
    }

    /**
     * Cek apakah produk adalah kategori gas.
     */
    public function isGas(): bool
    {
        return true;
    }

    /**
     * Dapatkan label unit.
     */
    public function getUnitLabel(): string
    {
        return 'tabung';
    }
}
