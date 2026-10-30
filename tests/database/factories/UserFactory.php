<?php

namespace TCG\Voyager\Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
 
class UserFactory extends Factory
{
    protected $model = \TCG\Voyager\Models\User::class;

    public function definition()
    {
        static $password;

        return [
            'name'           => $this->faker->name(),
            'email'          => $this->faker->unique()->safeEmail(),
            'password'       => $password ?: $password = bcrypt('secret'),
            'remember_token' => Str::random(10),
            'role_id'        => fn () => \TCG\Voyager\Models\Role::query()->where('name', 'user')->value('id')
                ?? \TCG\Voyager\Models\Role::query()->value('id'),
        ];
    }
}
