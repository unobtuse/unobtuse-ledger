<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Account Model
 * 
 * Represents a linked bank account via Plaid integration.
 * Supports checking, savings, credit cards, investments, and loans.
 */
class Account extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'plaid_account_id',
        'plaid_access_token',
        'plaid_item_id',
        'account_name',
        'official_name',
        'account_type',
        'account_subtype',
        'institution_id',
        'institution_name',
        'balance',
        'available_balance',
        'credit_limit',
        'currency',
        'mask',
        'account_number',
        'routing_number',
        'sync_status',
        'last_synced_at',
        'sync_error',
        'is_active',
        'metadata',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'plaid_access_token',
        'account_number',
        'routing_number',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'available_balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'last_synced_at' => 'datetime',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user that owns the account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transactions for the account.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the bills associated with this account.
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Check if account is a credit card.
     *
     * @return bool
     */
    public function isCreditCard(): bool
    {
        return $this->account_type === 'credit_card';
    }

    /**
     * Check if account needs synchronization.
     *
     * @return bool
     */
    public function needsSync(): bool
    {
        if (!$this->last_synced_at) {
            return true;
        }

        $syncInterval = config('plaid.sync.sync_interval', 6);
        return $this->last_synced_at->addHours($syncInterval)->isPast();
    }

    /**
     * Get formatted account display name.
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->account_name . ($this->mask ? ' (••••' . $this->mask . ')' : '');
    }

    /**
     * Get formatted balance.
     *
     * @return string
     */
    public function getFormattedBalanceAttribute(): string
    {
        return $this->currency . ' ' . number_format((float) $this->balance, 2);
    }

    /**
     * Scope to only active accounts.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only credit card accounts.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreditCards($query)
    {
        return $query->where('account_type', 'credit_card');
    }
}
