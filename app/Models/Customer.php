<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'nik', 'phone', 'customer_type', 'address'];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function tubeLoans()
    {
        return $this->hasMany(TubeLoan::class);
    }

    public function activeLoans()
    {
        return $this->hasMany(TubeLoan::class)->whereIn('status', ['dipinjam', 'sebagian_kembali']);
    }

    public function getCustomerTypeLabelAttribute(): string
    {
        return match($this->customer_type) {
            'rumah_tangga' => 'Rumah Tangga',
            'umkm'         => 'UMKM',
            'pengecer'     => 'Pengecer',
            default        => '-',
        };
    }
}
