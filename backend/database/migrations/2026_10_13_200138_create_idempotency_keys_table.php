<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stored responses for replayed critical requests (Plan §4.4, invariant I9). Rows expire after 24h and are
     * pruned by `model:prune`.
     */
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table): void {
            $table->id();
            $table->string('actor_type', 64);
            $table->unsignedBigInteger('actor_id')->default(0);
            $table->string('key', 64);
            $table->string('route', 191);
            $table->char('request_hash', 64);
            $table->unsignedSmallInteger('response_status');
            $table->longText('response_body');
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->unique(['actor_type', 'actor_id', 'route', 'key'], 'idempotency_keys_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
