{{-- Lazy tab: published versions of a category. $category, $versions (newest first) --}}
@php
    $v = static fn (string $key): string => __('admin.service_categories.versions.'.$key);
@endphp
@if ($versions->isEmpty())
    <x-admin.empty-state :title="$v('empty')" />
@else
    <div class="space-y-4">
        @foreach ($versions as $version)
            <div class="rounded-(--radius-card) border border-slate-200 p-4" x-data="{ open: false }">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="text-base font-semibold text-slate-900">{{ $v('version') }} {{ $version->version }}</span>
                        @if ($category->published_version_id === $version->id)
                            <x-admin.badge :label="$v('current')" color="success" />
                        @endif
                    </div>
                    <span class="text-sm text-slate-500">{{ $version->created_at->copy()->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i') }}</span>
                </div>
                <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-3">
                    <div><dt class="text-xs text-slate-500">{{ $v('published_by') }}</dt><dd>{{ $version->publisher?->name ?? '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-slate-500">{{ $v('reason') }}</dt><dd>{{ $version->reason }}</dd></div>
                    <div><dt class="text-xs text-slate-500">{{ $v('fields_count') }}</dt><dd>{{ count($version->snapshot['form_schema']['fields'] ?? []) }}</dd></div>
                </dl>
                <button type="button" class="mt-3 text-sm font-medium text-brand-700 hover:underline" x-on:click="open = !open" x-bind:aria-expanded="open">{{ $v('snapshot') }}</button>
                <div x-show="open" x-cloak class="mt-2"><x-admin.json-viewer :data="$version->snapshot" :collapsed="false" /></div>
            </div>
        @endforeach
    </div>
@endif
