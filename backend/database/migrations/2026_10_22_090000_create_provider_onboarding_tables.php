<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * P2.1 Provider onboarding: the full provider profile (the P1.3 stub is extended, not recreated), requested/approved
 * services, vehicles with photos, configurable document types and requirements, provider and vehicle documents
 * (private files, encrypted numbers) and the provider status history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('applies_to', 16);
            $table->boolean('requires_expiry')->default(false);
            $table->boolean('requires_number')->default(false);
            $table->json('allowed_mimes');
            $table->unsignedInteger('max_size_kb')->default(5120);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index(['applies_to', 'active']);
        });

        Schema::create('document_requirements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_type_id')->constrained('document_types')->cascadeOnDelete();
            $table->foreignId('country_id')->nullable()->constrained('countries')->cascadeOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->cascadeOnDelete();
            $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->cascadeOnDelete();
            $table->foreignId('vehicle_category_id')->nullable()->constrained('vehicle_categories')->cascadeOnDelete();
            $table->string('provider_type', 32)->nullable();
            $table->boolean('mandatory')->default(true);
            $table->timestamps();

            $table->index('document_type_id');
        });

        Schema::table('provider_profiles', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->string('provider_type', 32)->nullable()->after('user_id');
            $table->string('status', 32)->default('draft')->after('provider_type');
            $table->text('status_reason')->nullable()->after('status');
            $table->timestamp('status_changed_at')->nullable()->after('status_reason');
            $table->timestamp('status_expires_at')->nullable()->after('status_changed_at');
            $table->foreignId('city_id')->nullable()->after('status_expires_at')->constrained('cities')->nullOnDelete();
            $table->date('date_of_birth')->nullable()->after('city_id');
            $table->decimal('rating_avg', 3, 2)->default(0)->after('date_of_birth');
            $table->unsignedInteger('rating_count')->default(0)->after('rating_avg');
            $table->unsignedInteger('completed_jobs')->default(0)->after('rating_count');
            $table->unsignedInteger('requests_seen')->default(0)->after('completed_jobs');
            $table->unsignedInteger('requests_accepted')->default(0)->after('requests_seen');
            $table->boolean('is_online')->default(false)->after('requests_accepted');
            $table->timestamp('online_since')->nullable()->after('is_online');
            $table->unsignedBigInteger('current_vehicle_id')->nullable()->after('online_since');
            $table->timestamp('submitted_at')->nullable()->after('current_vehicle_id');
            $table->timestamp('approved_at')->nullable()->after('submitted_at');
            $table->foreignId('assigned_admin_id')->nullable()->after('approved_at')->constrained('admins')->nullOnDelete();
            $table->unsignedInteger('lock_version')->default(0)->after('assigned_admin_id');

            $table->index(['status', 'submitted_at']);
            $table->index(['status', 'status_expires_at']);
        });

        foreach (DB::table('provider_profiles')->whereNull('uuid')->pluck('id') as $id) {
            DB::table('provider_profiles')->where('id', $id)->update(['uuid' => (string) Str::uuid()]);
        }

        Schema::table('provider_profiles', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable(false)->change();
        });

        Schema::create('provider_service_category', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('provider_profile_id')->constrained('provider_profiles')->cascadeOnDelete();
            $table->foreignId('service_category_id')->constrained('service_categories')->cascadeOnDelete();
            $table->string('status', 16)->default('requested');
            $table->foreignId('decided_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_reason')->nullable();
            $table->timestamps();

            $table->unique(['provider_profile_id', 'service_category_id'], 'provider_service_unique');
            $table->index(['service_category_id', 'status']);
        });

        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('provider_profile_id')->constrained('provider_profiles')->cascadeOnDelete();
            $table->foreignId('vehicle_category_id')->constrained('vehicle_categories')->restrictOnDelete();
            $table->string('make', 60);
            $table->string('model', 60);
            $table->unsignedSmallInteger('year');
            $table->string('color', 40);
            $table->string('plate_display', 20);
            $table->string('plate_normalized', 20)->unique();
            $table->unsignedTinyInteger('passenger_capacity')->nullable();
            $table->decimal('payload_kg', 10, 2)->nullable();
            $table->decimal('volume_m3', 8, 2)->nullable();
            $table->json('capabilities');
            $table->string('status', 16)->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index(['provider_profile_id', 'status']);
            $table->index(['status', 'submitted_at']);
            $table->index('vehicle_category_id');
        });

        Schema::table('provider_profiles', function (Blueprint $table): void {
            $table->foreign('current_vehicle_id')->references('id')->on('vehicles')->nullOnDelete();
        });

        Schema::create('vehicle_photos', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->string('kind', 16);
            $table->string('path');
            $table->string('mime', 64);
            $table->unsignedInteger('size_bytes');
            $table->char('file_hash', 64);
            $table->timestamps();

            $table->index('vehicle_id');
        });

        foreach (['provider_documents' => ['provider_profile_id', 'provider_profiles'], 'vehicle_documents' => ['vehicle_id', 'vehicles']] as $tableName => [$ownerColumn, $ownerTable]) {
            Schema::create($tableName, function (Blueprint $table) use ($ownerColumn, $ownerTable, $tableName): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId($ownerColumn)->constrained($ownerTable)->cascadeOnDelete();
                $table->foreignId('document_type_id')->constrained('document_types')->restrictOnDelete();
                $table->string('path')->nullable();
                $table->string('original_name', 120)->nullable();
                $table->string('mime', 64);
                $table->unsignedInteger('size_bytes');
                $table->char('file_hash', 64);
                $table->string('scan_status', 16);
                $table->text('number')->nullable();
                $table->date('issued_at')->nullable();
                $table->date('expires_at')->nullable();
                $table->string('status', 16)->default('pending');
                $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->unsignedSmallInteger('expiry_reminder_days')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index([$ownerColumn, 'document_type_id', 'status'], $tableName.'_owner_type_status');
                $table->index(['status', 'expires_at']);
            });
        }

        Schema::create('provider_status_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('provider_profile_id')->constrained('provider_profiles')->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->string('actor_type', 32);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['provider_profile_id', 'id']);
        });

        Schema::table('account_deletion_requests', function (Blueprint $table): void {
            $table->string('previous_provider_status', 32)->nullable()->after('previous_user_status');
        });
    }

    public function down(): void
    {
        Schema::table('account_deletion_requests', function (Blueprint $table): void {
            $table->dropColumn('previous_provider_status');
        });

        Schema::dropIfExists('provider_status_events');
        Schema::dropIfExists('vehicle_documents');
        Schema::dropIfExists('provider_documents');
        Schema::dropIfExists('vehicle_photos');

        Schema::table('provider_profiles', function (Blueprint $table): void {
            $table->dropForeign(['current_vehicle_id']);
        });

        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('provider_service_category');

        Schema::table('provider_profiles', function (Blueprint $table): void {
            $table->dropForeign(['city_id']);
            $table->dropForeign(['assigned_admin_id']);
            $table->dropIndex(['status', 'submitted_at']);
            $table->dropIndex(['status', 'status_expires_at']);
            $table->dropColumn([
                'uuid', 'provider_type', 'status', 'status_reason', 'status_changed_at', 'status_expires_at', 'city_id', 'date_of_birth',
                'rating_avg', 'rating_count', 'completed_jobs', 'requests_seen', 'requests_accepted', 'is_online', 'online_since',
                'current_vehicle_id', 'submitted_at', 'approved_at', 'assigned_admin_id', 'lock_version',
            ]);
        });

        Schema::dropIfExists('document_requirements');
        Schema::dropIfExists('document_types');
    }
};
