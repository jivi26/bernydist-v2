<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La tabla 'users' que existe en la BD es del proyecto social-login abandonado (2021).
        // La renombramos para preservar los datos históricos sin perderlos.
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'cliente_id')) {
            DB::statement('RENAME TABLE users TO legacy_social_users');
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->integer('cliente_id')->unique();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');

        if (Schema::hasTable('legacy_social_users')) {
            DB::statement('RENAME TABLE legacy_social_users TO users');
        }
    }
};
