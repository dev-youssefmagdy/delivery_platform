{{-- Availability matrix form. Needs $zone, $categories (all), $links (keyed by category id). --}}
<x-admin.form :action="route('admin.service-zones.availability', $zone)" method="PUT">
    <input type="hidden" name="lock_version" value="{{ $zone->lock_version }}">
    <p class="mb-4 text-sm text-slate-600">{{ __('admin.service_zones.availability.help') }}</p>

    @forelse ($categories as $category)
        @php
            $link = $links->get($category->id);
            $o = (array) ($link?->overrides ?? []);
            $base = 'categories['.$category->id.']';
        @endphp
        <fieldset class="mb-4 rounded-lg border border-slate-200 p-4">
            <legend class="px-2 text-sm font-semibold text-slate-800">{{ $category->name }} <span class="ltr-only text-xs font-normal text-slate-500">({{ $category->code }})</span></legend>
            <x-admin.form.checkbox :name="$base.'[active]'" :label="__('admin.service_zones.availability.enabled')" :checked="$link?->active ?? false" />
            <x-admin.grid class="mt-3">
                <x-admin.form.money :name="$base.'[min_fare_minor]'" :label="__('admin.service_zones.availability.min_fare')" :value="$o['min_fare_minor'] ?? null" />
                <x-admin.form.money :name="$base.'[max_fare_minor]'" :label="__('admin.service_zones.availability.max_fare')" :value="$o['max_fare_minor'] ?? null" />
                <x-admin.form.input :name="$base.'[dispatch_radius_steps_km]'" :label="__('admin.service_zones.availability.radius')" :value="isset($o['dispatch_radius_steps_km']) ? implode(', ', $o['dispatch_radius_steps_km']) : null" :hint="__('admin.service_zones.availability.radius_hint')" dir="ltr" />
                <x-admin.form.input :name="$base.'[hours_from]'" type="time" :label="__('admin.service_zones.availability.hours_from')" :value="$o['hours']['from'] ?? null" dir="ltr" />
                <x-admin.form.input :name="$base.'[hours_to]'" type="time" :label="__('admin.service_zones.availability.hours_to')" :value="$o['hours']['to'] ?? null" dir="ltr" />
            </x-admin.grid>
        </fieldset>
    @empty
        <x-admin.empty-state :title="__('admin.service_zones.availability.none')" />
    @endforelse

    <x-admin.form.actions :submit="__('admin.service_zones.availability.save')" />
</x-admin.form>
