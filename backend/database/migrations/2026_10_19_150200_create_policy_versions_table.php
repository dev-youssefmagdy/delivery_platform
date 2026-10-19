<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Versioned legal/policy documents. `version` is assigned when a draft is published (null for drafts), so
     * published versions of a type are numbered 1, 2, 3 … without gaps; published rows are immutable.
     * user_consents (P1.3) will reference these rows.
     */
    public function up(): void
    {
        Schema::create('policy_versions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type', 32);
            $table->unsignedInteger('version')->nullable();
            $table->json('title');
            $table->json('content');
            $table->string('status', 16)->default('draft');
            $table->text('change_note')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('published_by_admin_id')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique(['type', 'version']);
            $table->index(['type', 'status']);
            $table->index('published_at');
            $table->foreign('published_by_admin_id')->references('id')->on('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_versions');
    }
};
