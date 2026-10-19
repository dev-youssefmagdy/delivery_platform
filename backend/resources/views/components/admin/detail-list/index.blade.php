@props(['cols' => 2])
{{-- Label/value grid; children are <x-admin.detail-list.item>. --}}
<dl {{ $attributes->class(['grid grid-cols-1 gap-x-6 gap-y-5', 'sm:grid-cols-2' => (int) $cols >= 2, 'lg:grid-cols-3' => (int) $cols >= 3]) }}>
    {{ $slot }}
</dl>
