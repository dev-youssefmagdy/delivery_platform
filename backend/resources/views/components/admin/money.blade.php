@if ($formatted !== null)
    <span {{ $attributes->class(['tabular-nums whitespace-nowrap']) }}>{{ $formatted }}</span>
@else
    <span {{ $attributes->class(['text-slate-400']) }}>—</span>
@endif
