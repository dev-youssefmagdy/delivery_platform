<?php

namespace App\DTOs\Identity;

use App\Enums\Identity\ClientApp;
use App\Models\AccountDeletionRequest;
use App\Models\User;

final readonly class MeData
{
    /**
     * @param list<\App\Models\PolicyVersion> $pendingConsents
     */
    public function __construct(
        public User $user,
        public ClientApp $app,
        public array $pendingConsents,
        public ?AccountDeletionRequest $deletionRequest,
    ) {}
}
