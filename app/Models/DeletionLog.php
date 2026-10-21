<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeletionLog extends Model
{
    protected $fillable = [
        'user_id', 'deletable_type', 'deletable_id', 'summary', 'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record(Model $model, string $summary, array $data = []): self
    {
        return self::create([
            'user_id'        => auth()->id(),
            'deletable_type' => class_basename($model),
            'deletable_id'   => $model->getKey(),
            'summary'        => $summary,
            'data'           => $data,
        ]);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->deletable_type) {
            'Transaction'   => 'Nota',
            'AgentDelivery' => 'Penerimaan Agen',
            'Customer'      => 'Pelanggan',
            'Product'       => 'Produk',
            'User'          => 'Pengguna',
            default         => $this->deletable_type,
        };
    }
}
