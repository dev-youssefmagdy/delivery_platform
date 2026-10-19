<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demo Item proof module (P0.3, local/testing only): one column per admin form component.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_items', function (Blueprint $table): void {
            $table->id();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('code', 32)->unique();
            $table->string('type', 32)->index();
            $table->string('status', 32)->index();
            $table->bigInteger('price_minor');
            $table->char('currency', 3);
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->json('tags')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_public')->default(false);
            $table->date('available_on')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('cover_path')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('area')->nullable();
            $table->json('specs')->nullable();
            $table->json('options')->nullable();
            $table->text('notes')->nullable();
            $table->text('archived_reason')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index('created_at');
            $table->foreign('currency')->references('code')->on('currencies');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_items');
    }
};
