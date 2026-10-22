{{-- Shared create/edit fields. $model: ?\App\Models\PushCampaign --}}
<x-admin.alert type="info" class="mb-6">{{ __('admin.push_campaigns.consent_notice') }}</x-admin.alert>

<x-admin.form.section :title="__('admin.push_campaigns.sections.basic')">
    <x-admin.grid>
        <x-admin.form.input name="name" :label="__('admin.push_campaigns.fields.name')" :hint="__('admin.push_campaigns.hints.name')" :value="$model?->name" maxlength="120" required />
        <x-admin.form.enum-select name="audience" :label="__('admin.push_campaigns.fields.audience')" :enum="\App\Enums\Notification\CampaignAudience::class" :value="$model?->audience" required />
    </x-admin.grid>
</x-admin.form.section>

<x-admin.form.section :title="__('admin.push_campaigns.sections.content')" :description="__('admin.push_campaigns.sections.content_hint')">
    <x-admin.form.translatable-input name="title" :label="__('admin.push_campaigns.fields.title')" :hint="__('admin.push_campaigns.hints.title')" :value="['ar' => $model?->title_ar, 'en' => $model?->title_en]" maxlength="65" required />
    <x-admin.form.translatable-textarea name="body" :label="__('admin.push_campaigns.fields.body')" :hint="__('admin.push_campaigns.hints.body')" :value="['ar' => $model?->body_ar, 'en' => $model?->body_en]" maxlength="178" required />
</x-admin.form.section>

<x-admin.form.section :title="__('admin.push_campaigns.sections.schedule')" :description="__('admin.push_campaigns.sections.schedule_hint')">
    <x-admin.grid>
        <x-admin.form.datetime name="scheduled_at" :label="__('admin.push_campaigns.fields.scheduled_at')" :hint="__('admin.push_campaigns.hints.scheduled_at')" :value="$model?->scheduled_at" />
    </x-admin.grid>
</x-admin.form.section>
