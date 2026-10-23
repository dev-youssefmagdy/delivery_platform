<?php

namespace App\DTOs\Content;

use App\Enums\Content\AppKind;
use App\Enums\Content\AppPlatform;
use App\Http\Requests\Admin\Content\SaveAppLinkRequest;

final readonly class AppLinkData
{
    public function __construct(
        public AppKind $app,
        public AppPlatform $platform,
        public string $url,
        public bool $active,
        public int $sortOrder,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(SaveAppLinkRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();

        return new self(
            app: AppKind::from((string) $data['app']),
            platform: AppPlatform::from((string) $data['platform']),
            url: trim((string) $data['url']),
            active: (bool) ($data['active'] ?? true),
            sortOrder: (int) ($data['sort_order'] ?? 0),
            lockVersion: isset($data['lock_version']) ? (int) $data['lock_version'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return ['app' => $this->app, 'platform' => $this->platform, 'url' => $this->url, 'active' => $this->active, 'sort_order' => $this->sortOrder];
    }
}
