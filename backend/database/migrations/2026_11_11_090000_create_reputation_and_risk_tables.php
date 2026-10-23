<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_ratings', function (Blueprint $table): void {
            // Open reports of the rating (moderation queue sort and the "reported only" filter).
            $table->unsignedSmallInteger('reports_count')->default(0)->after('moderation_status');
            $table->timestamp('moderated_at')->nullable()->after('reports_count');
            $table->index(['moderation_status', 'created_at'], 'booking_ratings_moderation_index');
        });

        // A user flags a rating they received. One report per person and rating. `details` is the person's own text (scrubbed on deletion).
        Schema::create('rating_reports', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('rating_id')->constrained('booking_ratings')->cascadeOnDelete();
            $table->string('reporter_type', 16);
            $table->unsignedBigInteger('reporter_id');
            $table->unsignedBigInteger('reporter_user_id');
            $table->string('reason', 24);
            $table->text('details')->nullable();
            $table->string('status', 16)->default('open');
            $table->foreignId('handled_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->string('resolution_note', 1000)->nullable();
            $table->timestamps();

            $table->unique(['rating_id', 'reporter_type', 'reporter_id'], 'rating_reports_one_per_person');
            $table->index(['status', 'created_at']);
            $table->index('reporter_user_id');
        });

        // Append-only history of hide / restore decisions. The review text is never touched: only its visibility changes.
        Schema::create('moderation_actions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('rating_id')->constrained('booking_ratings')->cascadeOnDelete();
            $table->string('action', 16);
            $table->string('from_status', 24);
            $table->string('to_status', 24);
            $table->string('reason', 1000);
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('report_id')->nullable()->constrained('rating_reports')->nullOnDelete();
            $table->unsignedBigInteger('appeal_id')->nullable();
            $table->timestamp('created_at');

            $table->index(['rating_id', 'id']);
        });

        // A person contests a moderation outcome (their review was hidden) or a low rating they received. One appeal per person and rating.
        Schema::create('rating_appeals', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('rating_id')->constrained('booking_ratings')->cascadeOnDelete();
            $table->string('appellant_type', 16);
            $table->unsignedBigInteger('appellant_id');
            $table->unsignedBigInteger('appellant_user_id');
            $table->string('grounds', 24);
            $table->text('message');
            $table->string('status', 16)->default('open');
            $table->foreignId('decided_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_reason', 1000)->nullable();
            $table->timestamps();

            $table->unique(['rating_id', 'appellant_type', 'appellant_id'], 'rating_appeals_one_per_person');
            $table->index(['status', 'created_at']);
            $table->index('appellant_user_id');
        });

        Schema::table('moderation_actions', function (Blueprint $table): void {
            $table->foreign('appeal_id')->references('id')->on('rating_appeals')->nullOnDelete();
        });

        // Internal, explainable abuse indicators (SRS §15). Never exposed to the apps. At most one ACTIVE signal per type and subject
        // (`active_marker` = 1 while open or escalated), so the scheduled job refreshes it instead of duplicating it.
        Schema::create('risk_signals', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type', 24);
            // customer (users.id) | provider (provider_profiles.id)
            $table->string('subject_type', 16);
            $table->unsignedBigInteger('subject_id');
            $table->unsignedTinyInteger('score');
            $table->json('evidence');
            $table->string('status', 16)->default('open');
            $table->unsignedTinyInteger('active_marker')->nullable();
            $table->timestamp('first_detected_at');
            $table->timestamp('last_evaluated_at');
            $table->timestamp('escalated_at')->nullable();
            $table->foreignId('resolved_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution_action', 16)->nullable();
            $table->string('resolution_reason', 1000)->nullable();
            $table->timestamps();

            $table->unique(['type', 'subject_type', 'subject_id', 'active_marker'], 'risk_signals_one_active_per_subject');
            $table->index(['status', 'score']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_signals');
        Schema::table('moderation_actions', function (Blueprint $table): void {
            $table->dropForeign(['appeal_id']);
        });
        Schema::dropIfExists('rating_appeals');
        Schema::dropIfExists('moderation_actions');
        Schema::dropIfExists('rating_reports');
        Schema::table('booking_ratings', function (Blueprint $table): void {
            $table->dropIndex('booking_ratings_moderation_index');
            $table->dropColumn(['reports_count', 'moderated_at']);
        });
    }
};
