<?php

namespace Database\Factories;

use App\Models\Forum;
use App\Models\ForumTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ForumTopicFactory extends Factory
{
    protected $model = ForumTopic::class;

    public function definition(): array
    {
        return [
            'forum_id' => Forum::factory(),
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraph(),
        ];
    }
}
