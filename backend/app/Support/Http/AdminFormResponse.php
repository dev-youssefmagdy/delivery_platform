<?php

namespace App\Support\Http;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

/**
 * Success response for <x-admin.form> / confirm actions (Plan §5.3). Instructions are applied by
 * resources/js/admin/core/ajax-form.js in this order: toast → replace_html → reset → close_modal →
 * reload_table → reload → redirect. Always rendered through ApiResponse (ADR-14).
 */
final class AdminFormResponse implements Responsable
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    private function __construct(private readonly ?string $message) {}

    public static function make(?string $message = null): self
    {
        return new self($message);
    }

    public function redirect(string $url): self
    {
        $this->data['redirect'] = $url;

        return $this;
    }

    /**
     * Reload one or more AdminTables by table id.
     */
    public function reloadTable(string ...$tableIds): self
    {
        $this->data['reload_table'] = array_values(array_unique(array_merge((array) ($this->data['reload_table'] ?? []), $tableIds)));

        return $this;
    }

    /**
     * Close the modal containing the form (true) or a modal by id.
     */
    public function closeModal(string|bool $modal = true): self
    {
        $this->data['close_modal'] = $modal;

        return $this;
    }

    public function reset(): self
    {
        $this->data['reset'] = true;

        return $this;
    }

    public function reload(): self
    {
        $this->data['reload'] = true;

        return $this;
    }

    /**
     * Replace the inner HTML of elements matched by CSS selector.
     */
    public function replaceHtml(string $selector, string $html): self
    {
        $this->data['replace_html'][$selector] = $html;

        return $this;
    }

    /**
     * Arbitrary extra payload (ids, etc.).
     */
    public function with(string $key, mixed $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function toResponse($request): JsonResponse
    {
        return ApiResponse::success($this->message, $this->data === [] ? null : $this->data);
    }
}
