<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `perf:seed`: fills a SEPARATE performance database with volume for EXPLAIN and load tests (P6.2). Refuses to run unless the
 * connected database name ends in `_perf`. Prerequisite: `migrate` and a normal seed (cities, categories, a few users, providers
 * and at least one booking and one provider profile; they are used as templates). The number of customers and providers is whatever the normal seeders created (bookings and samples are spread over them; scale users and providers by repeating the demo seeder). Rows are cloned in bulk with set-based SQL, so
 * 200k bookings take seconds, not minutes. Documented in docs/PERFORMANCE.md; never part of a deploy.
 */
class SeedPerfData extends Command
{
    protected $signature = 'perf:seed {--bookings=200000} {--locations=2000000} {--print-sql : Print the statements and exit}';

    protected $description = 'Seed volume into the *_perf database (refuses any other database)';

    public function handle(): int
    {
        $database = (string) DB::connection()->getDatabaseName();

        if (! $this->option('print-sql') && ! str_ends_with($database, '_perf')) {
            $this->error("Refusing to seed '{$database}': the database name must end with _perf (create delivery_platform_perf, point DB_DATABASE at it, migrate).");

            return self::FAILURE;
        }

        $statements = $this->statements();

        if ($this->option('print-sql')) {
            foreach ($statements as $name => $sql) {
                $this->line("-- {$name}\n{$sql}\n");
            }

            return self::SUCCESS;
        }

        if (DB::table('bookings')->doesntExist() || DB::table('provider_profiles')->doesntExist() || DB::table('users')->doesntExist()) {
            $this->error('Seed one booking, one provider profile and one user first (php artisan db:seed and create a booking through the API or demo data).');

            return self::FAILURE;
        }

        foreach ($statements as $name => $sql) {
            $this->info("Seeding {$name}...");
            DB::unprepared($sql);
        }

        DB::statement('ANALYZE TABLE bookings, provider_locations, provider_last_locations, users, provider_profiles');
        $this->info('Done. Run the EXPLAINs of docs/PERFORMANCE.md.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function statements(): array
    {
        $n = fn (string $option): int => max(0, (int) $this->option($option));
        $numbers = fn (int $count): string => "WITH RECURSIVE seq (n) AS (SELECT 1 UNION ALL SELECT n + 1 FROM seq WHERE n < {$count}) ";
        $statements = ['cte_depth' => 'SET SESSION cte_max_recursion_depth = '.max(1000, $n('bookings'), $n('locations')).';'];

        // Bookings: clone the oldest booking, spreading status, city, dates and customers. Codes and uuids are unique per row.
        $overridden = ['id', 'uuid', 'code', 'customer_id', 'status', 'created_at', 'updated_at', 'idempotency_key', 'selected_offer_id', 'coupon_reservation_id', 'provider_id', 'search_expires_at'];
        $columns = array_values(array_diff(Schema::getColumnListing('bookings'), $overridden));
        $list = implode(', ', array_map(static fn (string $c): string => "`{$c}`", $columns));
        $template = implode(', ', array_map(static fn (string $c): string => "t.`{$c}`", $columns));

        if ($n('bookings') > 0) {
            $statements['bookings'] = <<<SQL
INSERT INTO bookings (uuid, code, customer_id, status, created_at, updated_at, {$list})
{$numbers($n('bookings'))}
SELECT UUID(), CONCAT('P', LPAD(CONV(1000000 + seq.n, 10, 36), 9, '0')),
       u.id,
       ELT(1 + MOD(seq.n, 6), 'completed', 'completed', 'completed', 'cancelled_by_customer', 'completed', 'expired_no_provider'),
       NOW() - INTERVAL MOD(seq.n * 7919, 525600) MINUTE, NOW() - INTERVAL MOD(seq.n * 7919, 525600) MINUTE, {$template}
FROM seq CROSS JOIN (SELECT * FROM bookings ORDER BY id LIMIT 1) t
JOIN (SELECT id, ROW_NUMBER() OVER (ORDER BY id) - 1 AS rn FROM users) u ON u.rn = MOD(seq.n, (SELECT COUNT(*) FROM users));
SQL;
        }

        // Provider history: samples around the template booking's pickup, spread over the last 120 days.
        if ($n('locations') > 0) {
            $statements['provider_locations'] = <<<SQL
INSERT INTO provider_locations (provider_profile_id, point, accuracy_m, heading, speed_mps, source, booking_id, recorded_at)
{$numbers($n('locations'))}
SELECT p.id,
       ST_GeomFromText(CONCAT('POINT(', 31.2 + (MOD(seq.n * 7, 1000) / 5000), ' ', 30.0 + (MOD(seq.n * 13, 1000) / 5000), ')'), 4326, 'axis-order=long-lat'),
       8, MOD(seq.n, 360), 5, 'gps', NULL, NOW() - INTERVAL MOD(seq.n * 31, 172800) MINUTE
FROM seq JOIN (SELECT id, ROW_NUMBER() OVER (ORDER BY id) - 1 AS rn FROM provider_profiles) p ON p.rn = MOD(seq.n, (SELECT COUNT(*) FROM provider_profiles));
SQL;
        }

        return $statements;
    }
}
