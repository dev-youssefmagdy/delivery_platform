<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table): void {
            $table->id();
            $table->char('iso2', 2)->unique();
            $table->json('name');
            $table->string('phone_code', 8);
            $table->char('currency_code', 3);
            $table->string('timezone', 64);
            $table->string('default_locale', 5)->default('ar');
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->foreign('currency_code')->references('code')->on('currencies')->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
