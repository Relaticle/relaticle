<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserSocialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserSocialAccount>
 */
final class UserSocialAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<UserSocialAccount>
     */
    protected $model = UserSocialAccount::class;

    #[\Override]
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider_name' => 'facebook',
            'provider_id' => $this->faker->unique()->randomNumber(),
        ];
    }
}
