@props(['selected' => [], 'readonly' => false, 'name' => 'permissions'])
{{--
    Permission matrix grouped by module (AdminPermission::grouped()). Each group has a select-all checkbox.
    Editable: submits `{name}[]` values. `readonly` shows only the granted permissions (no inputs).
--}}
@php
    $groups = \App\Enums\Admin\AdminPermission::grouped();
    $selected = array_map('strval', $selected);
@endphp
<div {{ $attributes->class(['grid gap-4 md:grid-cols-2 xl:grid-cols-3']) }} data-field="{{ $name }}">
    @foreach ($groups as $group => $permissions)
        @php
            $visible = $readonly ? array_filter($permissions, fn ($permission) => in_array($permission->value, $selected, true)) : $permissions;
            $groupLabel = __("enums.admin_permission_group.{$group}");
        @endphp
        @continue($visible === [])
        <fieldset
            class="rounded-lg border border-slate-200 p-4"
            x-data="{
                all: false,
                boxes() { return Array.from($refs.list.querySelectorAll('input[type=checkbox]')); },
                sync() { const boxes = this.boxes(); this.all = boxes.length > 0 && boxes.every((box) => box.checked); },
                toggleAll() { this.boxes().forEach((box) => { box.checked = this.all; }); },
            }"
            x-init="sync()"
        >
            <legend class="sr-only">{{ $groupLabel }}</legend>
            <div class="mb-3 flex items-center justify-between gap-3 border-b border-slate-100 pb-2">
                <h4 class="text-sm font-semibold text-slate-900">{{ $groupLabel }}</h4>
                @unless ($readonly)
                    <label class="flex cursor-pointer items-center gap-2 text-xs text-slate-500">
                        <input type="checkbox" class="form-check" x-model="all" x-on:change="toggleAll()">
                        {{ __('admin.roles.select_all') }}
                    </label>
                @endunless
            </div>
            <ul class="space-y-2" x-ref="list" x-on:change="sync()">
                @foreach ($visible as $permission)
                    <li>
                        @if ($readonly)
                            <span class="flex items-center gap-2 text-sm text-slate-700">
                                <x-admin.icon name="check" class="size-4 text-success-600" />
                                {{ __("enums.admin_permission_action.{$permission->action()}") }}
                            </span>
                        @else
                            <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="{{ $name }}[]" value="{{ $permission->value }}" class="form-check" @checked(in_array($permission->value, $selected, true))>
                                {{ __("enums.admin_permission_action.{$permission->action()}") }}
                            </label>
                        @endif
                    </li>
                @endforeach
            </ul>
        </fieldset>
    @endforeach
</div>
@unless ($readonly)
    <x-admin.form.field-error for="{{ $name }}" />
@endunless
