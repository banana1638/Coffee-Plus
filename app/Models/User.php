<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'phone_index',
        'address_index',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'phone'             => 'encrypted',
            'address'           => 'encrypted',
        ];
    }

    // -------------------------------------------------------------------------
    // Blind Index — Pending Plaintext Pattern
    // -------------------------------------------------------------------------

    /**
     * Transient store for plaintext values before the `encrypted` cast runs.
     * The Observer reads this on `saving` to compute the blind index.
     */
    protected array $_pendingPlaintext = [];

    /**
     * Intercept phone assignment: stash the plaintext, then let the normal
     * attribute setter (and encrypted cast) process it as usual.
     */
    public function setPhoneAttribute(?string $value): void
    {
        $this->_pendingPlaintext['phone'] = $value;
        $this->attributes['phone'] = $value; // cast will encrypt on next sync
    }

    /**
     * Intercept address assignment: same pattern as phone.
     */
    public function setAddressAttribute(?string $value): void
    {
        $this->_pendingPlaintext['address'] = $value;
        $this->attributes['address'] = $value;
    }

    public function getPendingPlaintext(): array
    {
        return $this->_pendingPlaintext;
    }

    public function clearPendingPlaintext(): void
    {
        $this->_pendingPlaintext = [];
    }

    // -------------------------------------------------------------------------
    // Searchable via Blind Index
    // -------------------------------------------------------------------------

    /**
     * Find a user by their phone number using the blind index.
     * Usage: User::findByPhone('012-3456789')
     */
    public static function findByPhone(string $phone): ?self
    {
        $index = UserObserver::makeIndex($phone);
        return static::where('phone_index', $index)->first();
    }

    // -------------------------------------------------------------------------
    // Register Observer
    // -------------------------------------------------------------------------

    protected static function booted(): void
    {
        static::observe(UserObserver::class);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function friends()
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->wherePivot('status', 'accepted');
    }

    public function getInviterUrlAttribute()
    {
        return url('/register?ref=' . base64_encode($this->id));
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}

