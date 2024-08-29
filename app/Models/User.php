<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use ManukMinasyan\FilamentAttribute\Models\Attribute;
use ManukMinasyan\FilamentAttribute\Models\Concerns\UsesCustomAttributes;
use ManukMinasyan\FilamentAttribute\Models\Contracts\HasCustomAttributes;

/**
 * @property string $name
 * @property string $email
 * @property string $password
 * @property-read Collection<int, Attribute> $customAttributes
 */
final class User extends Authenticatable implements FilamentUser, HasCustomAttributes
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;
    use UsesCustomAttributes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'custom_attributes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function boot(): void
    {
        parent::boot();
        self::bootUsesCustomAttributes();
    }

    /**
     * Determine if the user can access the given panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
        //        return str_ends_with($this->email, '@yourdomain.com') && $this->hasVerifiedEmail();
    }
}
