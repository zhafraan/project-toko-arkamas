<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isKasir(): bool
    {
        return $this->role === 'kasir';
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function agentDeliveries()
    {
        return $this->hasMany(AgentDelivery::class);
    }

    public function debtPayments()
    {
        return $this->hasMany(DebtPayment::class);
    }

    /** Catatan riwayat penghapusan yang dibuat oleh pengguna ini (jejak audit). */
    public function deletionLogs()
    {
        return $this->hasMany(DeletionLog::class);
    }

    /** Catatan penyesuaian stok yang dibuat oleh pengguna ini (jejak audit). */
    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }
}
