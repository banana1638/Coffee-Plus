<?php

namespace App\Models;

use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * 禁用批量赋值保护，改为手动控制（专业级项目常用做法）
     */
    protected $guarded = ['*'];

    /**
     * 序列化时隐藏敏感字段
     */
    protected $hidden = [
        'id', // 隐藏自增 ID，外部统一使用 UUID
        'password',
        'remember_token',
        'phone_index',
        'address_index',
    ];

    /**
     * 字段格式转换
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'phone' => 'encrypted',   // 数据库加密存储
            'address' => 'encrypted',
            'tangki_balance' => 'decimal:2', // 确保余额是 2 位小数
        ];
    }

    /**
     * 使用 UUID 作为模型绑定键
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // -------------------------------------------------------------------------
    // 盲索引 (Blind Index) 处理逻辑
    // -------------------------------------------------------------------------

    protected array $_pendingPlaintext = [];

    /**
     * 手机号 Mutator (新版写法)
     */
    protected function phone(): Attribute
    {
        return Attribute::make(
            set: function (?string $value) {
                $this->_pendingPlaintext['phone'] = $value;
                return $value; // Casts 会在此之后自动执行加密
            }
        );
    }

    /**
     * 地址 Mutator (新版写法)
     */
    protected function address(): Attribute
    {
        return Attribute::make(
            set: function (?string $value) {
                $this->_pendingPlaintext['address'] = $value;
                return $value;
            }
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

    /**
     * 通过盲索引查找用户 (修复 IDE 警告)
     */
    public static function findByPhone(string $phone): ?self
    {
        $index = UserObserver::makeIndex($phone);
        return static::where('phone_index', '=', $index)->first();
    }

    // -------------------------------------------------------------------------
    // 模型引导与生命周期
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
    // 关联关系 (Relationships)
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

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    // -------------------------------------------------------------------------
    // 访问器 (Accessors)
    // -------------------------------------------------------------------------

    /**
     * 邀请链接使用 UUID，更专业且安全
     */
    public function getInviterUrlAttribute(): string
    {
        return url('/register?ref=' . $this->uuid);
    }
}