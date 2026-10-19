{{-- Create/edit fields: one of every <x-admin.form.*> component. $item: ?\App\Models\DemoItem, $coverUrl: ?string --}}
@php
    use App\Enums\Demo\DemoItemStatus;
    use App\Enums\Demo\DemoItemTag;
    use App\Enums\Demo\DemoItemType;
@endphp

<x-admin.form.section :title="__('admin.demo_items.sections.basic')" :description="__('admin.demo_items.sections.basic_hint')">
    <x-admin.form.translatable-input name="name" :label="__('admin.demo_items.fields.name')" :value="$item?->getTranslations('name') ?? []" maxlength="191" required />
    <x-admin.form.translatable-textarea name="description" :label="__('admin.demo_items.fields.description')" :value="$item?->getTranslations('description') ?? []" maxlength="2000" />
    <x-admin.grid>
        <x-admin.form.input name="code" :label="__('admin.demo_items.fields.code')" :value="$item?->code" :hint="__('admin.demo_items.hints.code')" maxlength="32" pattern="[A-Za-z0-9_\-]+" dir="ltr" required />
        <x-admin.form.enum-select name="status" :label="__('admin.demo_items.fields.status')" :enum="DemoItemStatus::class" :except="[DemoItemStatus::Archived]" :value="$item?->status" required />
    </x-admin.grid>
    <x-admin.form.radio-group name="type" :label="__('admin.demo_items.fields.type')" :enum="DemoItemType::class" :value="$item?->type ?? DemoItemType::Standard" required />
</x-admin.form.section>

<x-admin.form.section :title="__('admin.demo_items.sections.commercial')">
    <x-admin.grid>
        <x-admin.form.money name="price" :label="__('admin.demo_items.fields.price')" :value="$item?->price" required />
        <x-admin.form.remote-select name="country_id" :label="__('admin.demo_items.fields.country')" lookup="countries" :selected="$item?->country ? [$item->country->id => $item->country->name.' ('.$item->country->iso2.')'] : []" />
    </x-admin.grid>
    <x-admin.form.select name="tags[]" :label="__('admin.demo_items.fields.tags')" :options="DemoItemTag::options()" :value="$item?->tags?->map(fn ($tag) => $tag->value)->all() ?? []" multiple />
    <x-admin.form.toggle name="is_featured" :label="__('admin.demo_items.fields.is_featured')" :hint="__('admin.demo_items.hints.is_featured')" :checked="$item?->is_featured ?? false" />
    <x-admin.form.checkbox name="is_public" :label="__('admin.demo_items.fields.is_public')" :checked="$item?->is_public ?? false" />
</x-admin.form.section>

<x-admin.form.section :title="__('admin.demo_items.sections.schedule')">
    <x-admin.grid>
        <x-admin.form.date name="available_on" :label="__('admin.demo_items.fields.available_on')" :value="$item?->available_on?->toDateString()" />
        <x-admin.form.datetime name="published_at" :label="__('admin.demo_items.fields.published_at')" :value="$item?->published_at" />
    </x-admin.grid>
    <x-admin.form.date-range :label="__('admin.demo_items.fields.period')" from="starts_on" to="ends_on" :from-value="$item?->starts_on?->toDateString()" :to-value="$item?->ends_on?->toDateString()" />
</x-admin.form.section>

<x-admin.form.section :title="__('admin.demo_items.sections.media')">
    <x-admin.form.file name="cover" :label="__('admin.demo_items.fields.cover')" accept="image/jpeg,image/png,image/webp" :max-kb="config('admin.uploads.image_max_kb')" :current-url="$coverUrl ?? null" image />
</x-admin.form.section>

<x-admin.form.section :title="__('admin.demo_items.sections.location')">
    <x-admin.form.map-point :label="__('admin.demo_items.fields.location')" lat-name="latitude" lng-name="longitude" :lat="$item?->latitude" :lng="$item?->longitude" />
    <x-admin.form.polygon name="area" :label="__('admin.demo_items.fields.area')" :value="$item?->area" />
</x-admin.form.section>

<x-admin.form.section :title="__('admin.demo_items.sections.details')">
    <x-admin.form.key-value name="specs" :label="__('admin.demo_items.fields.specs')" :value="$item?->specs ?? []" />
    <x-admin.form.repeater
        name="options"
        :label="__('admin.demo_items.fields.options')"
        :items="$item?->options ?? []"
        :max="20"
        :fields="[
            ['name' => 'label', 'label' => __('admin.demo_items.fields.option_label'), 'required' => true, 'attributes' => ['maxlength' => 100]],
            ['name' => 'price', 'label' => __('admin.demo_items.fields.option_price'), 'type' => 'number', 'attributes' => ['min' => 0, 'step' => 1, 'dir' => 'ltr']],
            ['name' => 'is_default', 'label' => __('admin.demo_items.fields.option_default'), 'type' => 'checkbox'],
        ]"
    />
    <x-admin.form.textarea name="notes" :label="__('admin.demo_items.fields.notes')" :value="$item?->notes" maxlength="5000" rows="3" />
</x-admin.form.section>
