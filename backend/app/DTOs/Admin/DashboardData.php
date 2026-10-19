<?php

namespace App\DTOs\Admin;

/**
 * What the dashboard placeholder shows: the admin's own access summary and honest "available after" notes.
 */
final readonly class DashboardData
{
    /**
     * @param list<string> $roleLabels
     * @param list<array{key: string, label: string, icon: string, active: bool, items: list<array{key: string, label: string, url: string, icon: string, active: bool}>}> $menu
     * @param list<array{title: string, availableAfter: string}> $pendingSections
     */
    public function __construct(
        public string $name,
        public array $roleLabels,
        public bool $isSuperAdmin,
        public int $permissionCount,
        public bool $mfaEnabled,
        public array $menu,
        public array $pendingSections,
    ) {}
}
