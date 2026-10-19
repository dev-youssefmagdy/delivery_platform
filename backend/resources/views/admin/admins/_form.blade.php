{{-- Shared create/edit fields. $model: ?\App\Models\Admin; $roleOptions: [role name => label] --}}
<x-admin.form.section :title="__('admin.admins.sections.account')">
    <x-admin.form.input name="name" :label="__('admin.admins.fields.name')" :value="$model?->name" maxlength="191" required />
    <x-admin.form.input name="email" type="email" :label="__('admin.admins.fields.email')" :value="$model?->email" maxlength="191" required dir="ltr" />
    <x-admin.form.input name="phone" :label="__('admin.admins.fields.phone')" :value="$model?->phone" maxlength="32" dir="ltr" />
    <x-admin.form.enum-select name="locale" :label="__('admin.admins.fields.locale')" :enum="\App\Enums\Common\Locale::class" :value="$model?->locale" :placeholder="__('admin.admins.locale_auto')" />
</x-admin.form.section>

<x-admin.form.section :title="__('admin.admins.sections.access')" :description="__('admin.admins.sections.access_hint')">
    <x-admin.form.select name="roles[]" :label="__('admin.admins.fields.roles')" :options="$roleOptions" :value="$model?->getRoleNames()->all() ?? []" multiple />
</x-admin.form.section>

@unless ($model)
    <x-admin.alert type="info">{{ __('admin.admins.invitation_note') }}</x-admin.alert>
@endunless
