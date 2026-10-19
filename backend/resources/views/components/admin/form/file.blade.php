@props(['name', 'label' => null, 'hint' => null, 'id' => null, 'accept' => null, 'maxKb' => null, 'currentUrl' => null, 'currentLabel' => null, 'image' => false])
{{-- File input with image preview and size/type hints; the file is sent in both the Precognition and the real request. --}}
@php
    $id ??= \App\Support\Admin\FieldName::id($name);
    $required = (bool) $attributes->get('required', false);
    $types = $accept ? collect(explode(',', $accept))->map(fn ($type) => strtoupper(trim(\Illuminate\Support\Str::afterLast($type, '/'), '. ')))->implode(', ') : null;
    $autoHint = $types && $maxKb ? __('admin.form.file_hint', ['types' => $types, 'size' => $maxKb]) : null;
@endphp
<x-admin.form.field :name="$name" :label="$label" :hint="$hint ?? $autoHint" :id="$id" :required="$required" x-data="adminFile()">
    <div class="flex flex-wrap items-center gap-4">
        <template x-if="preview">
            <img x-bind:src="preview" alt="" class="size-16 rounded-(--radius-control) border border-slate-200 object-cover">
        </template>
        @if ($currentUrl)
            <a href="{{ $currentUrl }}" target="_blank" rel="noopener" class="flex items-center gap-2 text-sm text-brand-700 hover:underline" x-show="!preview">
                @if ($image)
                    <img src="{{ $currentUrl }}" alt="{{ $currentLabel ?? __('admin.form.file_current') }}" class="size-16 rounded-(--radius-control) border border-slate-200 object-cover">
                @else
                    <x-admin.icon name="document" class="size-5" />
                    <span>{{ $currentLabel ?? __('admin.form.file_current') }}</span>
                @endif
            </a>
        @endif
        <div class="min-w-0 flex-1">
            <input
                type="file"
                name="{{ $name }}"
                id="{{ $id }}"
                x-ref="input"
                x-on:change="change($event)"
                @if ($accept) accept="{{ $accept }}" @endif
                @if ($maxKb) data-max-kb="{{ $maxKb }}" @endif
                @if ($hint || $autoHint) aria-describedby="{{ $id }}-hint" @endif
                {{ $attributes->class(['block w-full text-sm text-slate-600 file:me-3 file:rounded-(--radius-control) file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100']) }}
            >
            <p class="mt-1 text-xs text-slate-500" x-show="name" x-cloak>
                <span x-text="name"></span> · <span x-text="size"></span>
                <button type="button" class="btn-link ms-2 text-xs" x-on:click="clear()">{{ __('admin.form.file_clear') }}</button>
            </p>
        </div>
    </div>
</x-admin.form.field>
