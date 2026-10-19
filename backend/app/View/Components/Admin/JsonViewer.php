<?php

namespace App\View\Components\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Read-only pretty JSON (snapshots, payloads). Keys that look secret are masked before rendering.
 */
class JsonViewer extends Component
{
    /**
     * Key fragments that are always masked.
     *
     * @var list<string>
     */
    public const SENSITIVE = ['password', 'secret', 'token', 'otp', 'pin', 'api_key', 'apikey', 'authorization', 'card', 'cvv', 'iban'];

    public string $json;

    public function __construct(mixed $data = null, public bool $collapsed = false)
    {
        $this->json = (string) json_encode(
            $this->sanitize($data),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR,
        );
    }

    private function sanitize(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        $clean = [];

        foreach ($data as $key => $value) {
            $clean[$key] = is_string($key) && $this->isSensitive($key) ? '••••••' : $this->sanitize($value);
        }

        return $clean;
    }

    private function isSensitive(string $key): bool
    {
        $key = strtolower($key);

        foreach (self::SENSITIVE as $fragment) {
            if (str_contains($key, $fragment)) {
                return true;
            }
        }

        return false;
    }

    public function render(): View
    {
        return view('components.admin.json-viewer');
    }
}
