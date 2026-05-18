<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'password', 'api_token'];

    // Ocultar campos sensibles en respuestas JSON
    protected $hidden = ['password', 'api_token', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
