<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property int|null $company_id
 * @property string $role
 * @property bool $is_active
 * @property bool $force_password_change
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['company_id', 'role', 'is_active', 'force_password_change', 'name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'force_password_change' => 'boolean',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasMany<MilkBatch, $this>
     */
    public function milkBatches(): HasMany
    {
        return $this->hasMany(MilkBatch::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isCompanyAdmin(): bool
    {
        return $this->role === 'company_admin';
    }

    public function isTester(): bool
    {
        return $this->role === 'tester';
    }

    public function canManageCompanyUsers(?int $companyId = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->isCompanyAdmin()
            && $this->company_id !== null
            && ($companyId === null || $this->company_id === $companyId);
    }

    public function ensureCompany(): Company
    {
        if ($this->company) {
            return $this->company;
        }

        $companyName = "{$this->name} Dairy Company";

        $company = Company::create([
            'name' => $companyName,
            'slug' => Str::slug($companyName).'-'.Str::lower(Str::random(6)),
            'contact_email' => $this->email,
        ]);

        $this->forceFill(['company_id' => $company->id])->save();
        $this->setRelation('company', $company);

        return $company;
    }
}
