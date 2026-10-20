{{-- Requirements tab fragment: add button (modal form with mandatory reason) and the requirements DataTable. --}}
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-slate-500">{{ __('admin.document_types.requirements_intro') }}</p>
    @can('update', $type)
        <x-admin.button icon="plus" data-modal-open="document-requirement-add">{{ __('admin.document_types.actions.add_requirement') }}</x-admin.button>
    @endcan
</div>
<x-admin.datatable :table="$dataTable" />

@can('update', $type)
    <x-admin.action-modal id="document-requirement-add" :title="__('admin.document_types.requirement_form.title')" :message="__('admin.document_types.requirement_form.message')" :action="route('admin.document-types.requirements.store', $type)" :submit="__('admin.document_types.actions.add_requirement')" variant="primary" icon="plus" :reason-hint="__('admin.document_types.requirement_form.reason_hint')">
        <x-admin.form.remote-select name="country_id" id="requirement-country" :label="__('admin.document_types.fields.country')" lookup="countries" />
        <x-admin.form.remote-select name="city_id" id="requirement-city" :label="__('admin.document_types.fields.city')" lookup="cities" />
        <x-admin.form.remote-select name="service_category_id" id="requirement-service" :label="__('admin.document_types.fields.service_category')" lookup="service-categories" />
        @if ($type->applies_to === \App\Enums\Provider\DocumentSubject::Vehicle)
            <x-admin.form.remote-select name="vehicle_category_id" id="requirement-vehicle-category" :label="__('admin.document_types.fields.vehicle_category')" lookup="vehicle-categories" />
        @endif
        <x-admin.form.enum-select name="provider_type" id="requirement-provider-type" :label="__('admin.document_types.fields.provider_type')" :enum="\App\Enums\Provider\ProviderType::class" :placeholder="__('admin.document_types.any')" />
        <x-admin.form.toggle name="mandatory" id="requirement-mandatory" :label="__('admin.document_types.fields.mandatory')" :hint="__('admin.document_types.requirement_form.mandatory_hint')" :checked="true" />
    </x-admin.action-modal>
@endcan
