{{-- Rendered ar/en preview. $rows: output of NotificationTemplatePreviewService::preview() --}}
@if (($rows ?? []) === [])
    <x-admin.empty-state icon="document" :title="__('admin.notification_templates.preview.empty')" compact />
@else
    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($rows as $row)
            <div class="rounded-(--radius-control) border border-slate-200 p-4" lang="{{ $row['locale'] }}" dir="{{ $row['direction'] }}">
                <div class="mb-3 flex items-center justify-between gap-2 text-xs text-slate-500">
                    <span class="font-semibold text-slate-700">{{ $row['label'] }}</span>
                    <span class="ltr-only tabular-nums">
                        {{ $row['length'] }} {{ __('admin.notification_templates.preview.chars') }}@if ($row['segments'] !== null) · {{ $row['segments'] }} {{ __('admin.notification_templates.preview.segments') }}@endif
                    </span>
                </div>
                <div class="rounded-xl bg-slate-900 p-3 text-slate-50 shadow-sm">
                    @if ($row['title'] !== null)
                        <p class="text-sm font-semibold">{{ $row['title'] }}</p>
                    @endif
                    <p class="whitespace-pre-line text-sm text-slate-200">{{ $row['body'] }}</p>
                </div>
                @if ($row['unknown'] !== [])
                    <p class="mt-3 text-xs text-danger-700">{{ __('admin.notification_templates.preview.unknown', ['variables' => implode(', ', $row['unknown'])]) }}</p>
                @endif
            </div>
        @endforeach
    </div>
@endif
