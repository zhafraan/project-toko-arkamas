<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Jejak audit penyesuaian stok: siapa mengubah angka stok apa, dari berapa ke berapa, dan mengapa. */
class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'user_id',
        'filled_before', 'filled_after',
        'empty_before', 'empty_after',
        'borrowed_before', 'borrowed_after',
        'reason',
    ];

    protected $casts = [
        'filled_before'   => 'integer',
        'filled_after'    => 'integer',
        'empty_before'    => 'integer',
        'empty_after'     => 'integer',
        'borrowed_before' => 'integer',
        'borrowed_after'  => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Apakah ada angka stok yang benar-benar berubah? */
    public function hasChange(): bool
    {
        return $this->filled_before !== $this->filled_after
            || $this->empty_before !== $this->empty_after
            || $this->borrowed_before !== $this->borrowed_after;
    }

    /** Ringkasan selisih per jenis stok, contoh: "isi +5, kosong -5". */
    public function getDiffLabelAttribute(): string
    {
        $parts = [];
        foreach ([['filled', 'isi'], ['empty', 'kosong'], ['borrowed', 'dipinjam']] as [$key, $label]) {
            $diff = $this->{$key . '_after'} - $this->{$key . '_before'};
            if ($diff !== 0) {
                $parts[] = $label . ' ' . ($diff > 0 ? '+' : '') . $diff;
            }
        }

        return $parts ? implode(', ', $parts) : 'tidak ada perubahan';
    }
}
