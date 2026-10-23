<?php

namespace App\Services\Risk\Detectors;

use App\DTOs\Risk\RiskCandidate;
use App\Enums\Identity\ClientApp;
use App\Enums\Risk\RiskSignalType;
use App\Enums\Risk\RiskSubjectType;
use App\Repositories\Contracts\Risk\RiskDataRepositoryInterface;
use App\Services\Risk\RiskScore;
use Carbon\CarbonInterface;

/**
 * One physical device signed in by several accounts of the same app. Every account of the cluster gets a signal; the raw device id is
 * never stored, only a short keyed hash that lets staff see that two accounts share a device.
 */
final class AccountFarmDetector implements RiskDetector
{
    private const MAX_ACCOUNTS_PER_CLUSTER = 50;

    public function __construct(private readonly RiskDataRepositoryInterface $data) {}

    public function type(): RiskSignalType
    {
        return RiskSignalType::AccountFarm;
    }

    public function subjectTypes(): array
    {
        return [RiskSubjectType::Customer, RiskSubjectType::Provider];
    }

    public function enabled(): bool
    {
        return true;
    }

    public function detect(CarbonInterface $since): array
    {
        $min = max(2, (int) config('risk.account_farm.min_accounts_per_device', 3));
        /** @var array<string, array{subject: RiskSubjectType, id: int, accounts: int, devices: list<string>}> $merged */
        $merged = [];

        foreach ($this->data->sharedDevices($since, $min) as $cluster) {
            $subject = $cluster['app'] === ClientApp::Provider->value ? RiskSubjectType::Provider : RiskSubjectType::Customer;
            $accounts = count($cluster['user_ids']);

            foreach (array_slice($cluster['user_ids'], 0, self::MAX_ACCOUNTS_PER_CLUSTER) as $userId) {
                $subjectId = $subject === RiskSubjectType::Provider ? $this->data->providerProfileIdOfUser($userId) : $userId;

                if ($subjectId === null) {
                    continue;
                }

                $key = $subject->value.':'.$subjectId;
                $merged[$key] ??= ['subject' => $subject, 'id' => $subjectId, 'accounts' => 0, 'devices' => []];
                $merged[$key]['accounts'] = max($merged[$key]['accounts'], $accounts);
                $merged[$key]['devices'][] = $cluster['device_ref'];
            }
        }

        $candidates = [];

        foreach ($merged as $row) {
            $devices = array_values(array_unique($row['devices']));
            sort($devices);
            $candidates[] = new RiskCandidate($this->type(), $row['subject'], $row['id'], RiskScore::scale($row['accounts'], $min, $min * 3), [
                'window_days' => (int) config('risk.window_days', 30), 'accounts_on_device' => $row['accounts'], 'shared_devices' => count($devices),
                'device_refs' => array_slice($devices, 0, 10), 'min_accounts' => $min,
            ]);
        }

        return $candidates;
    }
}
