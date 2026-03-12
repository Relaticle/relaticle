<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->personalTeam();
});

describe('expired token', function (): void {
    it('returns 401 for an expired token', function (): void {
        $newToken = $this->user->createToken('expired', ['*'], now()->subHour());
        $newToken->accessToken->fill(['team_id' => $this->team->id])->save();

        $this->withToken($newToken->plainTextToken)
            ->getJson('/api/v1/companies')
            ->assertUnauthorized();
    });
});

describe('token-based team scoping', function (): void {
    it('resolves team context from token team_id', function (): void {
        $otherTeam = Team::factory()->create();
        $this->user->teams()->attach($otherTeam);

        $otherCompany = Company::withoutEvents(fn () => Company::factory()->create(['team_id' => $otherTeam->id]));
        Company::factory()->for($this->team)->create();

        $raw = Str::random(40);
        $token = $this->user->tokens()->create([
            'name' => 'team-scoped',
            'token' => hash('sha256', $raw),
            'abilities' => ['*'],
            'team_id' => $otherTeam->id,
        ]);

        $plainToken = "{$token->id}|{$raw}";

        $response = $this->withToken($plainToken)
            ->getJson('/api/v1/companies');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        expect($ids)->toContain($otherCompany->id);
    });

    it('ignores X-Team-Id header when token has team_id', function (): void {
        $otherTeam = Team::factory()->create();
        $this->user->teams()->attach($otherTeam);

        $otherCompany = Company::withoutEvents(fn () => Company::factory()->create(['team_id' => $otherTeam->id]));
        Company::factory()->for($this->team)->create();

        $raw = Str::random(40);
        $token = $this->user->tokens()->create([
            'name' => 'team-scoped',
            'token' => hash('sha256', $raw),
            'abilities' => ['*'],
            'team_id' => $otherTeam->id,
        ]);

        $plainToken = "{$token->id}|{$raw}";

        $response = $this->withToken($plainToken)
            ->getJson('/api/v1/companies', ['X-Team-Id' => $this->team->id]);

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        expect($ids)->toContain($otherCompany->id);
    });
});

describe('soft-deleted records invisible via API', function (): void {
    beforeEach(function (): void {
        $this->token = $this->user->createToken('test', ['*'])->plainTextToken;
    });

    it('excludes soft-deleted people from list', function (): void {
        $person = People::factory()->for($this->team)->create();
        $deleted = People::factory()->for($this->team)->create();
        $deleted->delete();

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/people');

        $ids = collect($response->json('data'))->pluck('id');
        expect($ids)->toContain($person->id);
        expect($ids)->not->toContain($deleted->id);
    });

    it('cannot show a soft-deleted person', function (): void {
        $person = People::factory()->for($this->team)->create();
        $person->delete();

        $this->withToken($this->token)
            ->getJson("/api/v1/people/{$person->id}")
            ->assertNotFound();
    });

    it('excludes soft-deleted notes from list', function (): void {
        $note = Note::factory()->for($this->team)->create();
        $deleted = Note::factory()->for($this->team)->create();
        $deleted->delete();

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/notes');

        $ids = collect($response->json('data'))->pluck('id');
        expect($ids)->toContain($note->id);
        expect($ids)->not->toContain($deleted->id);
    });

    it('cannot show a soft-deleted note', function (): void {
        $note = Note::factory()->for($this->team)->create();
        $note->delete();

        $this->withToken($this->token)
            ->getJson("/api/v1/notes/{$note->id}")
            ->assertNotFound();
    });

    it('excludes soft-deleted tasks from list', function (): void {
        $task = Task::factory()->for($this->team)->create();
        $deleted = Task::factory()->for($this->team)->create();
        $deleted->delete();

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/tasks');

        $ids = collect($response->json('data'))->pluck('id');
        expect($ids)->toContain($task->id);
        expect($ids)->not->toContain($deleted->id);
    });

    it('cannot show a soft-deleted task', function (): void {
        $task = Task::factory()->for($this->team)->create();
        $task->delete();

        $this->withToken($this->token)
            ->getJson("/api/v1/tasks/{$task->id}")
            ->assertNotFound();
    });

    it('excludes soft-deleted opportunities from list', function (): void {
        $opportunity = Opportunity::factory()->for($this->team)->create();
        $deleted = Opportunity::factory()->for($this->team)->create();
        $deleted->delete();

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/opportunities');

        $ids = collect($response->json('data'))->pluck('id');
        expect($ids)->toContain($opportunity->id);
        expect($ids)->not->toContain($deleted->id);
    });

    it('cannot show a soft-deleted opportunity', function (): void {
        $opportunity = Opportunity::factory()->for($this->team)->create();
        $opportunity->delete();

        $this->withToken($this->token)
            ->getJson("/api/v1/opportunities/{$opportunity->id}")
            ->assertNotFound();
    });
});

describe('ForceJsonResponse middleware', function (): void {
    it('returns JSON even without Accept header', function (): void {
        $token = $this->user->createToken('test', ['*'])->plainTextToken;

        $response = $this->withToken($token)
            ->get('/api/v1/companies');

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('json');
    });

    it('returns JSON validation error without Accept header', function (): void {
        $token = $this->user->createToken('test', ['*'])->plainTextToken;

        $response = $this->withToken($token)
            ->post('/api/v1/companies', []);

        $response->assertUnprocessable();
        expect($response->headers->get('Content-Type'))->toContain('json');
    });
});

describe('rate limiting', function (): void {
    it('returns 429 after exceeding threshold', function (): void {
        RateLimiter::for('api', fn () => Limit::perMinute(3)->by($this->user->id));

        $token = $this->user->createToken('test', ['*'])->plainTextToken;

        for ($i = 0; $i < 3; $i++) {
            $this->withToken($token)
                ->getJson('/api/v1/companies')
                ->assertOk();
        }

        $this->withToken($token)
            ->getJson('/api/v1/companies')
            ->assertTooManyRequests();
    });
});

describe('non-existent UUID', function (): void {
    beforeEach(function (): void {
        $this->token = $this->user->createToken('test', ['*'])->plainTextToken;
    });

    it('returns 404 for non-existent company', function (): void {
        $this->withToken($this->token)
            ->getJson('/api/v1/companies/'.Str::ulid())
            ->assertNotFound();
    });

    it('returns 404 for non-existent person', function (): void {
        $this->withToken($this->token)
            ->getJson('/api/v1/people/'.Str::ulid())
            ->assertNotFound();
    });

    it('returns 404 for non-existent opportunity', function (): void {
        $this->withToken($this->token)
            ->getJson('/api/v1/opportunities/'.Str::ulid())
            ->assertNotFound();
    });

    it('returns 404 for non-existent task', function (): void {
        $this->withToken($this->token)
            ->getJson('/api/v1/tasks/'.Str::ulid())
            ->assertNotFound();
    });

    it('returns 404 for non-existent note', function (): void {
        $this->withToken($this->token)
            ->getJson('/api/v1/notes/'.Str::ulid())
            ->assertNotFound();
    });
});
