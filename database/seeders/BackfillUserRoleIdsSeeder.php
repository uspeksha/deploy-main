<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BackfillUserRoleIdsSeeder extends Seeder
{
    public function run(): void
    {
        $rolesByName = Role::query()->pluck('id', 'name');

        foreach ($rolesByName as $name => $id) {
            DB::table('users')
                ->where('role', $name)
                ->where(function ($query) use ($id) {
                    $query->whereNull('role_id')->orWhere('role_id', '!=', $id);
                })
                ->update(['role_id' => $id]);
        }

        $unmatchedQuery = DB::table('users')
            ->whereNotIn('role', $rolesByName->keys())
            ->orWhereNull('role');
        $unmatched = (clone $unmatchedQuery)->count();

        $unmatchedQuery->update(['role_id' => null]);

        $this->command?->info("User dengan role yang tidak cocok (dibiarkan role_id null): {$unmatched}");
    }
}
