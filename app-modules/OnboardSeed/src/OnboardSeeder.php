<?php

declare(strict_types=1);

namespace Relaticle\OnboardSeed;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

final class OnboardSeeder extends Seeder
{
    /**
     * Constructor with dependency injection
     */
    public function __construct(private readonly OnboardSeedManager $manager) {}

    /**
     * Run the database seeds.
     */
    public function run(Authenticatable $user): void
    {
        Auth::setUser($user);
        $this->manager->generateFor($user);
    }
}
