@props(['for' => null, 'required' => false])
<label @if ($for) for="{{ $for }}" @endif {{ $attributes->class(['form-label']) }}>
    {{ $slot }}
    @if ($required)
        <span class="form-required" aria-hidden="true">*</span>
    @endif
</label>
