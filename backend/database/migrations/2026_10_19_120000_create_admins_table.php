<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 191);
            $table->string('email', 191)->unique();
            $table->string('phone', 32)->nullable();
            $table->string('password');
            // null = no stored preference (follow the browser/session, then Arabic).
            $table->string('locale', 5)->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->text('mfa_secret')->nullable();
            $table->text('mfa_recovery_codes')->nullable();
            $table->unsignedBigInteger('mfa_last_used_step')->nullable();
            $table->timestamp('mfa_enabled_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('admin_password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('admin_login_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('email', 191)->nullable()->index();
            $table->string('outcome', 30)->index();
            $table->string('ip', 45)->nullable()->index();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        Schema::create('admin_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('session_id', 100)->unique();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->json('label')->nullable()->after('guard_name');
            $table->boolean('is_system')->default(false)->after('label');
            $table->unsignedInteger('lock_version')->default(0)->after('is_system');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn(['label', 'is_system', 'lock_version']);
        });
        Schema::dropIfExists('admin_sessions');
        Schema::dropIfExists('admin_login_events');
        Schema::dropIfExists('admin_password_reset_tokens');
        Schema::dropIfExists('admins');
    }
};
