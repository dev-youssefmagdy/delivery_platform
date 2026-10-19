@props(['name'])
{{-- Date + time (submits Y-m-d H:i in the admin display timezone). --}}
<x-admin.form.date :name="$name" mode="datetime" {{ $attributes }} />
