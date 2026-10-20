{{-- Permission-aware actions (each App\Support\Admin\Tables\TableAction already passed its policy check). --}}
<div {{ $attributes->class(['inline-flex items-center gap-1']) }}>
    @foreach ($visible as $action)
        @switch($action->kind)
            @case(\App\Support\Admin\Tables\TableActionKind::Confirm)
                <x-admin.confirm-action
                    :url="$action->url"
                    :method="$action->method"
                    :title="$action->confirm['title'] ?? null"
                    :message="$action->confirm['message'] ?? null"
                    :confirm-label="$action->confirm['confirm_label'] ?? $action->label"
                    :reason-required="$action->reasonRequired"
                    :table="$action->reloadTable"
                    :variant="$action->variant"
                    :icon="$action->icon"
                    :icon-only="! $labels"
                    :class="$labels ? 'btn-sm' : null"
                >{{ $action->label }}</x-admin.confirm-action>
                @break
            @case(\App\Support\Admin\Tables\TableActionKind::Document)
                @if ($labels)
                    <x-admin.button variant="secondary" size="sm" :icon="$action->icon" :data-document-open="$action->url" :data-document-mode="$action->method">{{ $action->label }}</x-admin.button>
                @else
                    <x-admin.icon-button :icon="$action->icon" :label="$action->label" :data-document-open="$action->url" :data-document-mode="$action->method" />
                @endif
                @break
            @case(\App\Support\Admin\Tables\TableActionKind::Modal)
                @if ($labels)
                    <x-admin.button variant="secondary" size="sm" :icon="$action->icon" data-modal-open="admin-global-modal" :data-modal-url="$action->url">{{ $action->label }}</x-admin.button>
                @else
                    <x-admin.icon-button :icon="$action->icon" :label="$action->label" data-modal-open="admin-global-modal" :data-modal-url="$action->url" />
                @endif
                @break
            @case(\App\Support\Admin\Tables\TableActionKind::Drawer)
                @if ($labels)
                    <x-admin.button variant="secondary" size="sm" :icon="$action->icon" data-modal-open="admin-global-drawer" :data-modal-url="$action->url">{{ $action->label }}</x-admin.button>
                @else
                    <x-admin.icon-button :icon="$action->icon" :label="$action->label" data-modal-open="admin-global-drawer" :data-modal-url="$action->url" />
                @endif
                @break
            @default
                @if ($labels)
                    <x-admin.button variant="secondary" size="sm" :icon="$action->icon" :href="$action->url">{{ $action->label }}</x-admin.button>
                @else
                    <x-admin.icon-button :icon="$action->icon" :label="$action->label" :href="$action->url" />
                @endif
        @endswitch
    @endforeach
</div>
