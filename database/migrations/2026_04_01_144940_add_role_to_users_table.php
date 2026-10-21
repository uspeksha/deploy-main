<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The users table's initial migration already creates the role column.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The role column belongs to the users table's initial migration.
    }
};
