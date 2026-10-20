@props(['items' => []])
{{--
    Side-by-side review viewer: a list of documents and photos and a pane showing the selected file. Files are never in
    the page: selecting one asks the server for a fresh 5-minute signed URL (audited when served). See document-viewer.js.
--}}
@php
    $labels = [
        'approve' => __('admin.documents.actions.approve'),
        'approve_title' => __('admin.documents.confirm.approve_title'),
        'approve_message' => __('admin.documents.confirm.approve_message'),
        'reject' => __('admin.documents.actions.reject'),
        'reject_title' => __('admin.documents.confirm.reject_title'),
        'reject_message' => __('admin.documents.confirm.reject_message'),
    ];
@endphp
<div {{ $attributes }} x-data="documentViewer(@js($items), @js($labels))" data-document-viewer>
    @if ($items === [])
        <x-admin.empty-state :title="__('admin.verification.viewer.empty')" />
    @else
        <div class="grid gap-4 xl:grid-cols-[14rem_1fr]">
            <nav aria-label="{{ __('admin.verification.viewer.list') }}" class="space-y-4">
                <template x-for="group in groups" :key="group.name">
                    <div>
                        <p class="mb-1 text-xs font-semibold tracking-wide text-slate-500 uppercase" x-text="group.name"></p>
                        <ul class="space-y-1">
                            <template x-for="item in group.entries" :key="item.key">
                                <li>
                                    <button type="button" class="flex w-full items-center justify-between gap-2 rounded-(--radius-control) border px-3 py-2 text-start text-sm"
                                        x-bind:class="currentKey === item.key ? 'border-brand-500 bg-brand-50' : 'border-slate-200 hover:bg-slate-50'"
                                        x-bind:aria-current="currentKey === item.key ? 'true' : null"
                                        x-on:click="select(item.key)" data-viewer-item>
                                        <span class="min-w-0 truncate" x-text="item.label"></span>
                                        <span class="badge shrink-0" x-show="item.status" x-bind:class="'badge-' + statusTone(item.status)" x-text="item.status_label"></span>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>
            </nav>

            <div class="min-w-0">
                <div x-show="!current" class="rounded-(--radius-control) border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">{{ __('admin.verification.viewer.select') }}</div>

                <div x-show="current" x-cloak class="space-y-3">
                    <dl class="grid gap-x-4 gap-y-2 text-sm sm:grid-cols-2" x-show="current?.kind === 'document'">
                        <div><dt class="text-xs text-slate-500">{{ __('admin.documents.fields.expires_at') }}</dt><dd class="ltr-only" x-text="current?.expires_at || '—'"></dd></div>
                        <div><dt class="text-xs text-slate-500">{{ __('admin.documents.fields.number') }}</dt><dd class="ltr-only" x-text="current?.number_masked || '—'"></dd></div>
                        <div class="sm:col-span-2"><dt class="text-xs text-slate-500">{{ __('admin.documents.fields.scan') }}</dt><dd><span class="badge" x-bind:class="current?.scan === 'clean' ? 'badge-success' : (current?.scan === 'infected' ? 'badge-danger' : 'badge-warning')" x-text="current?.scan_label"></span></dd></div>
                        <div class="sm:col-span-2" x-show="current?.reason"><dt class="text-xs text-slate-500">{{ __('admin.documents.fields.reason') }}</dt><dd x-text="current?.reason"></dd></div>
                    </dl>

                    <div class="flex flex-wrap items-center gap-2">
                        <x-admin.button variant="secondary" size="sm" icon="eye" x-on:click="open('view')">{{ __('admin.documents.actions.view') }}</x-admin.button>
                        <template x-if="current?.kind === 'document'">
                            <x-admin.button variant="secondary" size="sm" icon="download" x-on:click="open('download')">{{ __('admin.documents.actions.download') }}</x-admin.button>
                        </template>
                        <template x-if="current?.can_approve">
                            <x-admin.button variant="success" size="sm" icon="check-circle" x-on:click="approve()" data-viewer-approve>{{ __('admin.documents.actions.approve') }}</x-admin.button>
                        </template>
                        <template x-if="current?.can_reject">
                            <x-admin.button variant="danger" size="sm" icon="x-mark" x-on:click="reject()" data-viewer-reject>{{ __('admin.documents.actions.reject') }}</x-admin.button>
                        </template>
                    </div>

                    <div class="min-h-72 overflow-hidden rounded-(--radius-control) border border-slate-200 bg-slate-50">
                        <div x-show="loading" class="p-6"><x-admin.skeleton :lines="3" /></div>
                        <p x-show="failed" x-cloak class="p-6 text-sm text-rose-700">{{ __('admin.verification.viewer.failed') }}</p>
                        <template x-if="url && !isPdf">
                            <img x-bind:src="url" x-bind:alt="current?.label" class="mx-auto max-h-[70vh] w-auto" data-viewer-image>
                        </template>
                        <template x-if="url && isPdf">
                            <iframe x-bind:src="url" x-bind:title="current?.label" class="h-[70vh] w-full" referrerpolicy="no-referrer"></iframe>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
