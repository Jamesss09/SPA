<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Phase 1: SPA auth fields. Email stays unique but nullable (students log in via username).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('name');
            $table->string('role')->default('student')->after('password'); // admin|faculty|student
            $table->string('status')->default('active')->after('role'); // active|deactivated
            $table->boolean('must_change_password')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'role', 'status', 'must_change_password']);
        });
    }
};
