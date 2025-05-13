<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\AvatarService;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;

final class Team extends JetstreamTeam implements HasAvatar
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'personal_team',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
        ];
    }

    public function isPersonalTeam(): bool
    {
        return $this->personal_team;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return app(AvatarService::class)->generate(name: $this->name, bgColor: '#000000', textColor: '#ffffff');
    }

    public function people(): HasMany
    {
        return $this->hasMany(People::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }
}
