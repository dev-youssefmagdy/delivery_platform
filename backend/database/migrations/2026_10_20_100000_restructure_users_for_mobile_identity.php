<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The skeleton `users` table (email + password) becomes the mobile identity table: phone OTP is the only
     * credential (SRS §6.2), so email/password columns go and `phone_e164` is the unique identity (ADR-60).
     * Nothing has written to this table before P1.3.
     */
    public function up(): void
    {
        Schema::dropIfExists('password_reset_tokens');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_email_unique');
            $table->dropColumn(['password', 'remember_token', 'email_verified_at']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('phone_e164', 20)->nullable()->unique()->after('name');
            $table->timestamp('phone_verified_at')->nullable()->after('phone_e164');
            $table->string('photo_path')->nullable()->after('email');
            $table->string('preferred_locale', 5)->nullable()->after('photo_path');
            $table->string('status', 32)->default('active')->after('preferred_locale');
            $table->timestamp('last_seen_at')->nullable()->after('status');
            $table->softDeletes();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropSoftDeletes();
            $table->dropColumn(['uuid', 'phone_e164', 'phone_verified_at', 'photo_path', 'preferred_locale', 'status', 'last_seen_at']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('password')->default('');
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
        });
    }
};
