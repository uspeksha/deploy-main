<?php

namespace Database\Factories;

use App\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EmployeeDocument>
 */
class EmployeeDocumentFactory extends Factory
{
    protected $model = EmployeeDocument::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'doc_type' => fake()->randomElement(['contract', 'identity', 'certificate']),
            'file_path' => 'employee-documents/'.Str::uuid().'.pdf',
            'expired_at' => null,
        ];
    }
}
