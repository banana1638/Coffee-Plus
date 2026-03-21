<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @property string|null $phone
 * @property string|null $address
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'id',
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
            'password' => 'hashed',
            'phone' => 'encrypted',
            'address' => 'encrypted',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
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
    protected function phone(): Attribute
    {
        return Attribute::make(
            set: function (?string $value) {
                $this->_pendingPlaintext['phone'] = $value;
                return $value;
            },
        );
    }

    /**
     * Intercept address assignment: same pattern as phone.
     */
    protected function address(): Attribute
    {
        return Attribute::make(
            set: function (?string $value) {
                $this->_pendingPlaintext['address'] = $value;
                return $value;
            },
        );
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
        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });

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

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }
}

