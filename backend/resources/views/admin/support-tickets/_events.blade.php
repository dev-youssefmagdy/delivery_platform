@php
    $items = $events->map(fn ($event): array => [
        'title' => $event->type->label(),
        'time' => $when($event->created_at),
        'description' => collect((array) $event->meta)->only(['from', 'to', 'reason'])->filter(fn ($value) => is_scalar($value) && $value !== '')->map(fn ($value, $key) => $key.': '.$value)->implode(' · ') ?: null,
        'badge' => null,
        'actor' => $event->actor_type->label(),
        'reason' => null,
    ])->values()->all();
@endphp
<x-admin.timeline :items="$items" />
