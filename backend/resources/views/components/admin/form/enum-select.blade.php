@props(['enum', 'except' => [], 'value' => null])
{{-- Select whose options are the cases of a backed enum (labels via HasLabel). Other attributes go to <x-admin.form.select>. --}}
@php
    $excluded = array_map(static fn ($case) => $case instanceof \BackedEnum ? $case->value : $case, (array) $except);
    $options = [];
    foreach ($enum::cases() as $case) {
        if (! in_array($case->value, $excluded, true)) {
            $options[$case->value] = $case instanceof \App\Enums\Contracts\HasLabel ? $case->label() : (string) $case->value;
        }
    }
    $value = $value instanceof \BackedEnum ? $value->value : $value;
@endphp
<x-admin.form.select :options="$options" :value="$value" {{ $attributes }} />
