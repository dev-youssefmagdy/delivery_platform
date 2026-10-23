<?php

namespace App\Support\Admin;

use App\Enums\Admin\AdminPermission;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;

/**
 * Builds the visible sidebar from config/admin_menu.php: an item is visible when its route exists, the
 * current environment is allowed and the admin holds one of its permissions. Empty groups are dropped.
 */
final class AdminMenu
{
    /**
     * @return list<array{key: string, label: string, icon: string, active: bool, items: list<array{key: string, label: string, url: string, icon: string, active: bool}>}>
     */
    public function visibleGroups(): array
    {
        /** @var array<string, array{label: string, icon?: string}> $groups */
        $groups = (array) config('admin_menu.groups', []);
        /** @var list<array<string, mixed>> $items */
        $items = (array) config('admin_menu.items', []);
        $tree = [];

        foreach ($items as $item) {
            if (! $this->isVisible($item)) {
                continue;
            }

            $groupKey = (string) ($item['group'] ?? 'system');
            $active = request()->routeIs(...explode('|', (string) ($item['active'] ?? $item['route']))) && $this->queryActive($item, $items);

            $tree[$groupKey][] = [
                'key' => (string) $item['key'],
                'label' => (string) __((string) $item['label']),
                'url' => route((string) $item['route'], (array) ($item['query'] ?? [])),
                'icon' => (string) ($item['icon'] ?? 'cube'),
                'active' => $active,
            ];
        }

        $visible = [];

        foreach ($groups as $key => $group) {
            if (! isset($tree[$key])) {
                continue;
            }

            $visible[] = [
                'key' => $key,
                'label' => (string) __($group['label']),
                'icon' => $group['icon'] ?? 'squares',
                'active' => in_array(true, array_column($tree[$key], 'active'), true),
                'items' => $tree[$key],
            ];
        }

        return $visible;
    }

    /**
     * Items that share a route and differ by `query` (e.g. pages / service pages / city pages): the one whose query
     * matches the request is active; the item flagged `default` is active when none matches (create / show screens).
     *
     * @param array<string, mixed> $item
     * @param list<array<string, mixed>> $items
     */
    private function queryActive(array $item, array $items): bool
    {
        if (! isset($item['query'])) {
            return true;
        }

        $matches = static fn (array $candidate): bool => ($candidate['query'] ?? []) !== []
            && collect(Arr::dot((array) $candidate['query']))->every(static fn (mixed $value, string $key): bool => in_array((string) $value, array_map('strval', Arr::wrap(request()->query(preg_replace('/\.\d+$/', '', $key)))), true));

        if ($matches($item)) {
            return true;
        }

        if (! ($item['default'] ?? false)) {
            return false;
        }

        foreach ($items as $other) {
            if (($other['route'] ?? null) === $item['route'] && $other !== $item && $matches($other)) {
                return false;
            }
        }

        return true;
    }

    /**
     * URL of the first visible item (admin home redirect), or null.
     */
    public function firstUrl(): ?string
    {
        foreach ($this->visibleGroups() as $group) {
            foreach ($group['items'] as $item) {
                return $item['url'];
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function isVisible(array $item): bool
    {
        if (! isset($item['route'], $item['key'], $item['label']) || ! Route::has((string) $item['route'])) {
            return false;
        }

        if (isset($item['environments']) && ! app()->environment((array) $item['environments'])) {
            return false;
        }

        $permissions = $item['permission'] ?? null;

        if ($permissions === null) {
            return true;
        }

        $abilities = array_map(
            static fn (AdminPermission|string $permission): string => $permission instanceof AdminPermission ? $permission->value : $permission,
            is_array($permissions) ? $permissions : [$permissions],
        );

        return AdminAccess::allows(...$abilities);
    }
}
