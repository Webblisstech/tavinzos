<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * An administrator. Authenticated on the 'admin' guard, wholly separate from
 * the customer 'web' guard — the two sessions never overlap.
 */
class Admin extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'      => 'hashed',
            'is_active'     => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }
}