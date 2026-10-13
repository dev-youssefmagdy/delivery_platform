<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Guards the P0.1 baseline: tests run on the dedicated MySQL testing database
 * and Redis logical DB 15, with Arabic as the default locale (ADR-03, SRS §3).
 */
class InfrastructureBaselineTest extends TestCase
{
    use RefreshDatabase;

    public function test_tests_run_against_the_mysql_testing_database(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertStringStartsWith('delivery_platform_testing', DB::connection()->getDatabaseName());
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertSame('utf8mb4_unicode_ci', config('database.connections.mysql.collation'));
    }

    public function test_redis_is_reachable_on_the_testing_logical_database(): void
    {
        $this->assertSame('15', (string) config('database.redis.default.database'));

        $connection = Redis::connection();
        $connection->set('baseline:ping', 'pong');

        $this->assertSame('pong', $connection->get('baseline:ping'));

        $connection->del('baseline:ping');
    }

    public function test_arabic_is_the_default_and_fallback_locale(): void
    {
        $this->assertSame('ar', app()->getLocale());
        $this->assertSame('ar', config('app.fallback_locale'));
    }
}
