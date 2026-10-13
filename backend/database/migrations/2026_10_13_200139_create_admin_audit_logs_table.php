<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only audit trail for privileged and sensitive actions (SRS §23.4, Plan §4.4). No updated_at:
     * rows are never edited or deleted by application code.
     */
    public function up(): void
    {
        Schema::create('admin_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('actor_type', 64);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action', 64);
            $table->string('auditable_type', 128);
            $table->string('auditable_id', 64)->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('request_id', 128)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id', 'created_at'], 'admin_audit_logs_auditable_index');
            $table->index(['actor_type', 'actor_id', 'created_at'], 'admin_audit_logs_actor_index');
            $table->index(['action', 'created_at']);
            $table->index('created_at');
            $table->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
    }
};
