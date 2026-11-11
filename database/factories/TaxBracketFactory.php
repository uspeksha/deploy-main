<?php

namespace Database\Factories;

use App\Models\TaxBracket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxBracket>
 */
class TaxBracketFactory extends Factory
{
    protected $model = TaxBracket::class;

    public function definition(): array
    {
        return [
            'min_income' => 0,
            'max_income' => 60000000,
            'rate' => 0.0500,
            'effective_at' => '2022-01-01',
        ];
    }
}
