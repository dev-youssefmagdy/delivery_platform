<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Queued admin CSV exports (Plan §5.4). The file lives on the private exports disk; the row tracks status so
 * the requesting admin can poll it and download through a signed link.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_exports', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('actor_type', 64)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('table', 191);
            $table->string('permission', 100)->nullable();
            $table->json('filters')->nullable();
            $table->string('status', 20)->index();
            $table->string('disk', 50);
            $table->string('path')->nullable();
            $table->string('filename');
            $table->unsignedInteger('rows')->default(0);
            $table->string('error_code', 100)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['actor_type', 'actor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_exports');
    }
};
