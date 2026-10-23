<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Uploaded images (public disk). Files are re-encoded on upload; `variants` lists the resized copies.
        Schema::create('content_media', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('disk', 24)->default('public');
            $table->string('path', 255);
            $table->string('original_name', 191);
            $table->string('mime', 64);
            $table->unsignedInteger('size_bytes');
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->json('alt')->nullable();
            $table->json('variants')->nullable();
            $table->foreignId('uploaded_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('content_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('kind', 16);
            $table->string('slug', 96);
            $table->json('name');
            $table->json('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique(['kind', 'slug']);
        });

        Schema::create('content_pages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('kind', 16);
            $table->string('slug', 96)->unique();
            $table->json('title');
            $table->json('summary')->nullable();
            $table->string('body_format', 16)->default('markdown');
            $table->json('body')->nullable();
            $table->json('body_html')->nullable();
            $table->json('blocks')->nullable();
            $table->foreignId('hero_media_id')->nullable()->constrained('content_media')->nullOnDelete();
            $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->boolean('fallback_to_ar')->default(true);
            $this->seo($table);
            $this->workflow($table);

            $table->index(['kind', 'status']);
        });

        Schema::create('content_articles', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type', 16);
            $table->string('slug', 96)->unique();
            $table->foreignId('category_id')->nullable()->constrained('content_categories')->nullOnDelete();
            $table->json('title');
            $table->json('summary')->nullable();
            $table->string('body_format', 16)->default('markdown');
            $table->json('body')->nullable();
            $table->json('body_html')->nullable();
            $table->foreignId('cover_media_id')->nullable()->constrained('content_media')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('fallback_to_ar')->default(true);
            $this->seo($table);
            $this->workflow($table);

            $table->index(['type', 'status']);
        });

        Schema::create('content_faqs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('category_id')->nullable()->constrained('content_categories')->nullOnDelete();
            $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->nullOnDelete();
            $table->json('question');
            $table->json('answer');
            $table->json('answer_html')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('fallback_to_ar')->default(true);
            $this->workflow($table);

            $table->index(['category_id', 'status']);
        });

        Schema::create('content_banners', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('kind', 16);
            $table->string('placement', 24);
            $table->string('audience', 16)->default('all');
            $table->json('title');
            $table->json('body')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('content_media')->nullOnDelete();
            $table->json('cta_label')->nullable();
            $table->string('cta_url', 500)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('fallback_to_ar')->default(true);
            $this->workflow($table);

            $table->index(['placement', 'status']);
        });

        Schema::create('content_menus', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('location', 24);
            $table->json('title');
            $table->json('items');
            $table->boolean('fallback_to_ar')->default(true);
            $this->workflow($table);

            $table->index(['location', 'status']);
        });

        Schema::create('content_app_links', function (Blueprint $table): void {
            $table->id();
            $table->string('app', 16);
            $table->string('platform', 16);
            $table->string('url', 500);
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique(['app', 'platform']);
        });

        // Snapshot of an entity each time it is saved or its status changes (history and restore of drafts).
        Schema::create('content_revisions', function (Blueprint $table): void {
            $table->id();
            $table->string('revisionable_type', 32);
            $table->unsignedBigInteger('revisionable_id');
            $table->unsignedInteger('number');
            $table->string('action', 24);
            $table->string('status', 16);
            $table->json('snapshot');
            $table->string('reason', 1000)->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('created_at');

            $table->unique(['revisionable_type', 'revisionable_id', 'number'], 'content_revisions_number_unique');
        });
    }

    public function down(): void
    {
        foreach (['content_revisions', 'content_app_links', 'content_menus', 'content_banners', 'content_faqs', 'content_articles', 'content_pages', 'content_categories', 'content_media'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function workflow(Blueprint $table): void
    {
        $table->string('status', 16)->default('draft');
        $table->timestamp('scheduled_at')->nullable();
        $table->timestamp('published_at')->nullable();
        $table->foreignId('published_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
        $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
        $table->foreignId('updated_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
        $table->unsignedInteger('lock_version')->default(0);
        $table->timestamps();

        $table->index(['status', 'scheduled_at']);
    }

    private function seo(Blueprint $table): void
    {
        $table->json('seo_title')->nullable();
        $table->json('seo_description')->nullable();
        $table->json('og_title')->nullable();
        $table->json('og_description')->nullable();
        $table->foreignId('og_image_id')->nullable()->constrained('content_media')->nullOnDelete();
        $table->string('canonical_url', 500)->nullable();
        $table->boolean('noindex')->default(false);
    }
};
