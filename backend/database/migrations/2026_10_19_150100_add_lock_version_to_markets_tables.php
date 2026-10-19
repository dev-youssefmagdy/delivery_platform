<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Countries and currencies become admin-editable in P1.2 (ADR-33), so they get optimistic locking.
     */
    public function up(): void
    {
        foreach (['currencies', 'countries'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->unsignedInteger('lock_version')->default(0)->after('active');
            });
        }
    }

    public function down(): void
    {
        foreach (['currencies', 'countries'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropColumn('lock_version');
            });
        }
    }
};
