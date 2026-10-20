<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P1.6 Customers admin module: account restriction state (reason + optional expiry for auto-reactivation), the
 * anonymisation marker, in-app help messages sent by support, and JSON personal-data exports.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('status_reason')->nullable()->after('status');
            $table->timestamp('status_changed_at')->nullable()->after('status_reason');
            $table->timestamp('status_expires_at')->nullable()->after('status_changed_at');
            $table->timestamp('anonymised_at')->nullable()->after('last_seen_at');
            $table->index(['status', 'status_expires_at']);
        });

        Schema::create('customer_messages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('sent_by_admin_id')->nullable();
            $table->string('subject', 191);
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'id']);
            $table->foreign('sent_by_admin_id')->references('id')->on('admins')->nullOnDelete();
        });

        Schema::table('admin_exports', function (Blueprint $table): void {
            $table->string('format', 8)->default('csv')->after('table');
            $table->unsignedBigInteger('subject_id')->nullable()->after('format');
        });
    }

    public function down(): void
    {
        Schema::table('admin_exports', function (Blueprint $table): void {
            $table->dropColumn(['format', 'subject_id']);
        });

        Schema::dropIfExists('customer_messages');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['status', 'status_expires_at']);
            $table->dropColumn(['status_reason', 'status_changed_at', 'status_expires_at', 'anonymised_at']);
        });
    }
};
