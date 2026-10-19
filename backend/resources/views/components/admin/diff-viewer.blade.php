@props(['rows' => []])
{{-- Before/after comparison of an audit entry. $rows: list<App\DTOs\Admin\AuditDiffRow> (secrets already masked). --}}
@if ($rows === [])
    <x-admin.empty-state icon="inbox" :title="__('admin.audit_logs.no_changes')" compact />
@else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-start text-xs tracking-wide text-slate-500 uppercase">
                    <th scope="col" class="px-3 py-2 text-start font-medium">{{ __('admin.audit_logs.diff.field') }}</th>
                    <th scope="col" class="px-3 py-2 text-start font-medium">{{ __('admin.audit_logs.diff.before') }}</th>
                    <th scope="col" class="px-3 py-2 text-start font-medium">{{ __('admin.audit_logs.diff.after') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($rows as $row)
                    <tr @class(['bg-warning-50/50' => $row->changed])>
                        <th scope="row" class="px-3 py-2 text-start align-top font-mono text-xs font-medium text-slate-700" dir="ltr">{{ $row->key }}</th>
                        <td class="px-3 py-2 align-top">
                            @if ($row->before === null)
                                <span class="text-slate-400">—</span>
                            @else
                                <pre dir="ltr" @class(['m-0 font-mono text-xs break-words whitespace-pre-wrap', 'text-danger-700 line-through decoration-rose-300' => $row->changed, 'text-slate-600' => ! $row->changed])>{{ $row->before }}</pre>
                            @endif
                        </td>
                        <td class="px-3 py-2 align-top">
                            @if ($row->after === null)
                                <span class="text-slate-400">—</span>
                            @else
                                <pre dir="ltr" @class(['m-0 font-mono text-xs break-words whitespace-pre-wrap', 'font-semibold text-success-700' => $row->changed, 'text-slate-600' => ! $row->changed])>{{ $row->after }}</pre>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
