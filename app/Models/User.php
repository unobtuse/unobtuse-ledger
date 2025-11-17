<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * User Model
 * 
 * Represents a user in the Unobtuse Ledger system with support for:
 * - Email/password authentication
 * - Google OAuth authentication
 * - Two-factor authentication (TOTP)
 * - Email verification
 * - Soft deletes for GDPR compliance
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use HasUuids;
    use Notifiable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar_url',
        'provider',
        'preferences',
        'email_verified_at',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
            'preferences' => 'array',
            'two_factor_confirmed_at' => 'datetime',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Check if the user registered via OAuth (Google).
     *
     * @return bool
     */
    public function isOAuthUser(): bool
    {
        return !is_null($this->google_id) && is_null($this->password);
    }

    /**
     * Check if the user has two-factor authentication enabled.
     *
     * @return bool
     */
    public function hasTwoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_secret);
    }

    /**
     * Get user preference by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPreference(string $key, mixed $default = null): mixed
    {
        return $this->preferences[$key] ?? $default;
    }

    /**
     * Set user preference.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setPreference(string $key, mixed $value): void
    {
        $preferences = $this->preferences ?? [];
        $preferences[$key] = $value;
        $this->preferences = $preferences;
        $this->save();
    }

    /**
     * Get the user's linked accounts.
     */
    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Get the user's active accounts.
     */
    public function activeAccounts()
    {
        return $this->hasMany(Account::class)->where('is_active', true);
    }

    /**
     * Get the user's transactions.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the user's bills.
     */
    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Get the user's pay schedules.
     */
    public function paySchedules()
    {
        return $this->hasMany(PaySchedule::class);
    }

    /**
     * Get the user's active pay schedules (multiple).
     */
    public function activePaySchedules()
    {
        return $this->hasMany(PaySchedule::class)->where('is_active', true);
    }

    /**
     * Get the user's active pay schedule (single, for backward compatibility).
     * Returns the first active schedule.
     * 
     * @deprecated Use activePaySchedules() for multiple schedules support
     */
    public function activePaySchedule()
    {
        return $this->hasOne(PaySchedule::class)->where('is_active', true);
    }

    /**
     * Get the user's budgets.
     */
    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * Get the current month's budget.
     */
    public function currentBudget()
    {
        return $this->hasOne(Budget::class)
                    ->where('month', now()->format('Y-m'));
    }

    /**
     * Check if user is an admin.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return (bool) ($this->is_admin ?? false);
    }
}
