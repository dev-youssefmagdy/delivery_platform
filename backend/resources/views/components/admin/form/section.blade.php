@props(['title' => null, 'description' => null])
<x-admin.section :title="$title" :description="$description" {{ $attributes }}>{{ $slot }}</x-admin.section>
