<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
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
