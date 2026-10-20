<?php

namespace App\Support\Admin\Tables;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * A permission-aware action rendered by <x-admin.table.actions> (row buttons and page headers).
 * The action is shown only when `ability` passes the Gate (policy) for `subject`.
 */
final readonly class TableAction
{
    /**
     * @param array<string, string> $confirm title/message/confirm_label overrides for Confirm actions
     */
    public function __construct(
        public string $label,
        public string $url,
        public TableActionKind $kind = TableActionKind::Link,
        public string $icon = 'arrow-right',
        public string $variant = 'neutral',
        public ?string $ability = null,
        public Model|string|null $subject = null,
        public string $method = 'POST',
        public bool $reasonRequired = false,
        public array $confirm = [],
        public ?string $reloadTable = null,
    ) {}

    public static function view(string $url, ?Model $subject = null): self
    {
        return new self(__('admin.common.view'), $url, icon: 'eye', ability: 'view', subject: $subject);
    }

    public static function quickView(string $url, ?Model $subject = null): self
    {
        return new self(__('admin.common.quick_view'), $url, TableActionKind::Drawer, icon: 'window', ability: 'view', subject: $subject);
    }

    public static function edit(string $url, ?Model $subject = null): self
    {
        return new self(__('admin.common.edit'), $url, icon: 'pencil', ability: 'update', subject: $subject);
    }

    public static function delete(string $url, ?Model $subject = null, ?string $reloadTable = null, bool $reasonRequired = false): self
    {
        return new self(
            __('admin.common.delete'),
            $url,
            TableActionKind::Confirm,
            icon: 'trash',
            variant: 'danger',
            ability: 'delete',
            subject: $subject,
            method: 'DELETE',
            reasonRequired: $reasonRequired,
            confirm: ['title' => __('admin.confirm.delete_title'), 'message' => __('admin.confirm.delete_message')],
            reloadTable: $reloadTable,
        );
    }

    /**
     * View or download a private document: `$url` is the endpoint that issues a short-lived signed URL.
     */
    public static function document(string $label, string $url, Model $subject, bool $download = false): self
    {
        return new self($label, $url, TableActionKind::Document, $download ? 'download' : 'eye', ability: 'view', subject: $subject, method: $download ? 'download' : 'view');
    }

    /**
     * @param array<string, string> $confirm
     */
    public static function confirm(
        string $label,
        string $url,
        string $ability,
        ?Model $subject = null,
        string $icon = 'exclamation',
        string $variant = 'danger',
        string $method = 'POST',
        bool $reasonRequired = false,
        array $confirm = [],
        ?string $reloadTable = null,
    ): self {
        return new self($label, $url, TableActionKind::Confirm, $icon, $variant, $ability, $subject, $method, $reasonRequired, $confirm, $reloadTable);
    }

    public function isAllowed(): bool
    {
        if ($this->ability === null) {
            return true;
        }

        return Gate::allows($this->ability, $this->subject === null ? [] : [$this->subject]);
    }
}
