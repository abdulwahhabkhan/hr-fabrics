<?php

namespace App\Models;

use App\Traits\HasPermission;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

/**
 * @method static inRandomOrder()
 */
class User extends Authenticatable implements PasskeyUser
{
    use HasFactory, HasPermission, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role_id',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'active' => 'boolean',
        'password_changed_at' => 'datetime',
    ];

    public static function scriptUser(): ?self
    {
        return self::query()->where('name', 'script')->first();
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class)
            ->withDefault();
    }

    /**
     * @return HasMany<LoginActivity, $this>
     */
    public function loginActivity(): HasMany
    {
        return $this->hasMany(LoginActivity::class, 'user_id', 'id');
    }

    /**
     * @return HasMany<PasswordHistory, $this>
     */
    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class);
    }

    public function isPasswordExpired(): bool
    {
        $days = (int) config('password-policy.expiry_days');

        if ($days <= 0 || ! $this->password_changed_at) {
            return false;
        }

        return $this->password_changed_at->addDays($days)->isPast();
    }

    /**
     * Whether the plain password matches the current one or a recent previous one.
     */
    public function hasUsedPassword(string $plain): bool
    {
        if (Hash::check($plain, $this->password)) {
            return true;
        }

        $limit = (int) config('password-policy.history_count');

        if ($limit <= 0) {
            return false;
        }

        return $this->passwordHistories()
            ->latest('id')
            ->limit($limit)
            ->pluck('password')
            ->contains(fn (string $hash) => Hash::check($plain, $hash));
    }

    /**
     * Store a new password, archiving the previous hash and resetting the expiry clock.
     */
    public function changePassword(string $plain): void
    {
        if ($this->password) {
            $this->passwordHistories()->create(['password' => $this->password]);
        }

        $this->forceFill([
            'password' => Hash::make($plain),
            'password_changed_at' => now(),
        ])->save();

        $keep = $this->passwordHistories()
            ->latest('id')
            ->limit(max((int) config('password-policy.history_count'), 1))
            ->pluck('id');

        $this->passwordHistories()->whereNotIn('id', $keep)->delete();
    }

    public function isAdmin(): bool
    {
        return $this->role_id === \App\Enums\Role::SuperAdmin->value;
    }

    /**
     * Filter based on request params
     *
     * @return Builder mixed
     */
    #[Scope]
    protected function filter(Builder $query, Request $params): Builder
    {
        if ($params->has('search')) {
            $query->where('name', 'like', '%'.$params->get('search').'%');
        }

        return $query;
    }
}
