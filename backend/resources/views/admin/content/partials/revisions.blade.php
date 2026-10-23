{{-- Revision history (lazy "Revisions" tab). $content, $type, $revisions: Collection<ContentRevision> --}}
@if ($revisions->isEmpty())
    <x-admin.empty-state icon="clock" :title="__('content.revisions.empty')" compact />
@else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-xs tracking-wide text-slate-500 uppercase">
                    <th scope="col" class="px-3 py-2 text-start font-medium">#</th>
                    <th scope="col" class="px-3 py-2 text-start font-medium">{{ __('content.revisions.action') }}</th>
                    <th scope="col" class="px-3 py-2 text-start font-medium">{{ __('content.fields.status') }}</th>
                    <th scope="col" class="px-3 py-2 text-start font-medium">{{ __('content.revisions.by') }}</th>
                    <th scope="col" class="px-3 py-2 text-start font-medium">{{ __('admin.common.reason') }}</th>
                    <th scope="col" class="px-3 py-2 text-start font-medium">{{ __('admin.common.created_at') }}</th>
                    <th scope="col" class="px-3 py-2"><span class="sr-only">{{ __('admin.common.actions') }}</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($revisions as $revision)
                    <tr>
                        <td class="px-3 py-2 tabular-nums">{{ $revision->number }}</td>
                        <td class="px-3 py-2">{{ __('content.revisions.actions.'.$revision->action) }}</td>
                        <td class="px-3 py-2"><x-admin.badge :value="$revision->status" /></td>
                        <td class="px-3 py-2">{{ $revision->admin?->name ?? __('content.revisions.system') }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $revision->reason }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ $revision->created_at->copy()->setTimezone((string) config('admin.display_timezone'))->format('Y-m-d H:i') }}</td>
                        <td class="px-3 py-2 text-end">
                            @can('update', $content)
                                <x-admin.confirm-action
                                    :url="route('admin.content.revisions.restore', ['contentType' => $type->value, 'content' => $content->getKey(), 'number' => $revision->number])"
                                    :title="__('content.revisions.restore_title', ['number' => $revision->number])"
                                    :message="__('content.revisions.restore_message')"
                                    :confirm-label="__('content.revisions.restore')"
                                    variant="secondary"
                                    icon="refresh"
                                >{{ __('content.revisions.restore') }}</x-admin.confirm-action>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
