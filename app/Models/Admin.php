<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    private const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'owner' => ['*'],
        'manager' => [
            'product.view',
            'product.create',
            'product.update',
            'product.delete',
            'order.view',
            'order.status.update',
            'coupon.view',
            'coupon.create',
            'coupon.update',
            'coupon.delete',
            'report.view',
            'report.export',
        ],
        'staff' => [
            'order.view',
            'order.status.update',
        ],
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
    ];

    /**
     * Check if the admin is an owner.
     */
    public function isOwner(): bool
    {
        return in_array($this->role, ['owner', 'super_admin'], true);
    }

    /**
     * Check if the admin is a staff member.
     */
    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function canPerform(string $permission): bool
    {
        $permissions = self::ROLE_PERMISSIONS[$this->role] ?? [];

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }
}
