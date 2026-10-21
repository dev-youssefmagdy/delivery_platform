<?php

namespace App\Services\Location\Index;

use App\DTOs\Location\NearbyProvider;
use App\DTOs\Location\ProviderPosition;
use App\Support\Geo\GeoPoint;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Redis\Factory;
use Illuminate\Redis\Connections\PhpRedisConnection;
use JsonException;
use LogicException;
use Redis;

/**
 * GEOADD / GEOSEARCH per city plus a hash per provider with the live details and a TTL (freshness). A provider whose
 * hash expired is dropped from the GEO set when a search meets it, so a crashed app cannot stay "online" on the map.
 *
 * Commands go through `rawCommand` with the key prefix applied by hand, because phpredis does not prefix raw commands.
 */
final class RedisGeoIndex implements ProviderGeoIndex
{
    public function __construct(
        private readonly Factory $redis,
        private readonly ?string $connection = null,
    ) {}

    public function put(ProviderPosition $position, int $ttlSeconds): void
    {
        $client = $this->client();
        $previous = $this->readHash($position->providerId);

        if ($previous !== null && (int) $previous['city'] !== $position->cityId) {
            $client->rawCommand('ZREM', $this->cityKey((int) $previous['city']), (string) $position->providerId);
        }

        $client->rawCommand('GEOADD', $this->cityKey($position->cityId), (string) $position->point->lng, (string) $position->point->lat, (string) $position->providerId);

        $key = $this->hashKey($position->providerId);
        $client->rawCommand(
            'HSET', $key,
            'lat', (string) $position->point->lat,
            'lng', (string) $position->point->lng,
            'city', (string) $position->cityId,
            'accuracy', $position->accuracyM === null ? '' : (string) $position->accuracyM,
            'heading', $position->heading === null ? '' : (string) $position->heading,
            'speed', $position->speedMps === null ? '' : (string) $position->speedMps,
            'recorded_at', (string) $position->recordedAt->getTimestampMs(),
            'vehicle_category_ids', json_encode($position->vehicleCategoryIds, JSON_THROW_ON_ERROR),
            'service_category_ids', json_encode($position->serviceCategoryIds, JSON_THROW_ON_ERROR),
        );
        $client->rawCommand('EXPIRE', $key, (string) max(1, $ttlSeconds));
    }

    public function remove(int $providerId): void
    {
        $client = $this->client();
        $hash = $this->readHash($providerId);

        if ($hash !== null) {
            $client->rawCommand('ZREM', $this->cityKey((int) $hash['city']), (string) $providerId);
        }

        $client->rawCommand('DEL', $this->hashKey($providerId));
    }

    public function get(int $providerId): ?ProviderPosition
    {
        $hash = $this->readHash($providerId);

        return $hash === null ? null : $this->position($providerId, $hash);
    }

    public function nearby(int $cityId, GeoPoint $centre, float $radiusMeters, CarbonInterface $freshSince, int $limit): array
    {
        $client = $this->client();
        $rows = $client->rawCommand(
            'GEOSEARCH', $this->cityKey($cityId),
            'FROMLONLAT', (string) $centre->lng, (string) $centre->lat,
            'BYRADIUS', (string) $radiusMeters, 'm',
            'ASC', 'COUNT', (string) ($limit * 2 + 10), 'WITHDIST',
        );

        $hits = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            $providerId = (int) $row[0];
            $hash = $this->readHash($providerId);

            if ($hash === null) {
                $client->rawCommand('ZREM', $this->cityKey($cityId), (string) $providerId);

                continue;
            }

            $position = $this->position($providerId, $hash);

            if ($position->recordedAt->lessThan($freshSince) || $position->cityId !== $cityId) {
                continue;
            }

            $hits[] = new NearbyProvider($position, (float) $row[1]);

            if (count($hits) >= $limit) {
                break;
            }
        }

        return $hits;
    }

    /**
     * Removes every key this index owns (tests).
     */
    public function flush(): void
    {
        $client = $this->client();
        $keys = $client->rawCommand('KEYS', $this->prefix().'providers:*');

        foreach (is_array($keys) ? $keys : [] as $key) {
            $client->rawCommand('DEL', (string) $key);
        }
    }

    /**
     * @return array<string, string>|null
     */
    private function readHash(int $providerId): ?array
    {
        $flat = $this->client()->rawCommand('HGETALL', $this->hashKey($providerId));

        if (! is_array($flat) || $flat === []) {
            return null;
        }

        $hash = [];

        for ($i = 0, $count = count($flat); $i + 1 < $count; $i += 2) {
            $hash[(string) $flat[$i]] = (string) $flat[$i + 1];
        }

        return $hash;
    }

    /**
     * @param array<string, string> $hash
     */
    private function position(int $providerId, array $hash): ProviderPosition
    {
        return new ProviderPosition(
            providerId: $providerId,
            cityId: (int) $hash['city'],
            point: GeoPoint::make($hash['lat'], $hash['lng']),
            recordedAt: CarbonImmutable::createFromTimestampMs((int) $hash['recorded_at']),
            accuracyM: ($hash['accuracy'] ?? '') === '' ? null : (float) $hash['accuracy'],
            heading: ($hash['heading'] ?? '') === '' ? null : (int) $hash['heading'],
            speedMps: ($hash['speed'] ?? '') === '' ? null : (float) $hash['speed'],
            vehicleCategoryIds: $this->ids($hash['vehicle_category_ids'] ?? '[]'),
            serviceCategoryIds: $this->ids($hash['service_category_ids'] ?? '[]'),
        );
    }

    /**
     * @return list<int>
     */
    private function ids(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 4, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($decoded) ? array_values(array_map('intval', $decoded)) : [];
    }

    private function cityKey(int $cityId): string
    {
        return $this->prefix()."providers:online:{$cityId}";
    }

    private function hashKey(int $providerId): string
    {
        return $this->prefix()."providers:position:{$providerId}";
    }

    private function prefix(): string
    {
        return (string) config('database.redis.options.prefix', '');
    }

    private function client(): Redis
    {
        $connection = $this->redis->connection($this->connection);

        if (! $connection instanceof PhpRedisConnection) {
            throw new LogicException('RedisGeoIndex needs the phpredis client (REDIS_CLIENT=phpredis).');
        }

        /** @var Redis $client */
        $client = $connection->client();

        return $client;
    }
}
