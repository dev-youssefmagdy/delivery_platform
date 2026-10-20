<?php

namespace App\Services\Policy;

use App\Enums\Policy\PolicyType;
use App\Models\PolicyVersion;
use App\Repositories\Contracts\Policy\PolicyVersionRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Read side of policies for the public API.
 */
final class PolicyReadService
{
    public function __construct(private readonly PolicyVersionRepositoryInterface $policies) {}

    /**
     * @throws ModelNotFoundException when no version of the type has been published yet
     */
    public function current(PolicyType $type): PolicyVersion
    {
        return $this->policies->findCurrentByType($type) ?? throw (new ModelNotFoundException)->setModel(PolicyVersion::class);
    }
}
