<?php

namespace Database\Factories;

use App\Models\Chat;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ChatFactory extends Factory
{
  protected $model = Chat::class;

  public function definition(): array
  {
    return [
      'session_id' => (string) Str::uuid(),
      'user_id' => null,
      'title' => $this->faker->sentence(3),
      'status' => 'active',
      'metadata' => null,
      'last_activity_at' => now(),
    ];
  }
}
