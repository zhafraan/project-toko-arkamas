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

    /** Tarif default harga beli telur per unit (bila pemilik tidak mengisi). */
    public const DEFAULT_BUY_PRICE_TELUR = 25000;

    protected $fillable = [
        'name', 'sku', 'price_buy', 'price_sell', 'price_tube',
        'stock_filled', 'stock_empty', 'stock_borrowed', 'is_active',
        'category', 'unit', 'min_stock',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_stock' => 'decimal:2',
        'stock_filled' => 'decimal:2',
        'stock_empty' => 'decimal:2',
        'stock_borrowed' => 'decimal:2',
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

    public function goodsReceipts()
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function getTotalStockAttribute(): int
    {
        return $this->stock_filled + $this->stock_empty + $this->stock_borrowed;
    }

    /**
     * Cek apakah produk adalah kategori gas.
     */
    public function isGas(): bool
    {
        return $this->category === 'gas';
    }

    /**
     * Cek apakah produk adalah kategori telur.
     */
    public function isTelur(): bool
    {
        return $this->category === 'telur';
    }

    /**
     * Dapatkan label unit.
     */
    public function getUnitLabel(): string
    {
        return match ($this->unit) {
            'tabung' => 'tabung',
            'butir' => 'butir',
            'tray' => 'tray',
            'kg' => 'kg',
            default => '',
        };
    }
}
