@props(['title', 'milestone', 'module'])
{{-- Honest placeholder for a related tab whose module does not exist yet: no rows, no fake data. --}}
<x-admin.empty-state icon="clock" :title="$title" :description="__('admin.common.available_after', ['milestone' => $milestone, 'module' => $module])" {{ $attributes }} />
