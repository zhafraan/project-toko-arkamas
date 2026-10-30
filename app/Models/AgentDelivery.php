<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'product_id', 'delivery_date',
        'qty_received', 'buy_price_per_unit', 'total_cost', 'admin_fee', 'notes',
    ];

    protected $casts = [
        'qty_received'       => 'integer',
        'buy_price_per_unit' => 'decimal:2',
        'total_cost'         => 'decimal:2',
        'admin_fee'          => 'decimal:2',
        'delivery_date'      => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
