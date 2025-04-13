<?php

declare(strict_types=1);

namespace Database\Seeders\SampleData;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class SampleDataSeeder extends Seeder
{
    /**
     * @var SampleDataManager
     */
    private SampleDataManager $manager;

    /**
     * Constructor with dependency injection
     */
    public function __construct(SampleDataManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Run the database seeds.
     */
    public function run(Authenticatable $user): void
    {
        Auth::setUser($user);
        $this->manager->generateFor($user);
    }
}
