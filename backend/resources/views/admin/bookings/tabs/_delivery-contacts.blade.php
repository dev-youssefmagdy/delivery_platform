<x-admin.detail-list :cols="3">
    @foreach ($contacts as $contact)
        <x-admin.detail-list.item :label="$contact['role']">
            <span class="block"><span data-reveal-field="{{ $contact['name_key'] }}">{{ $contact['name'] ?? '—' }}</span></span>
            <span class="ltr-only block text-slate-600" data-reveal-field="{{ $contact['phone_key'] }}">{{ $contact['phone'] ?? '—' }}</span>
            @if ($contact['note_key'] && $contact['note'])
                <span class="block text-xs text-slate-500" data-reveal-field="{{ $contact['note_key'] }}">{{ $contact['note'] }}</span>
            @endif
        </x-admin.detail-list.item>
    @endforeach
</x-admin.detail-list>
