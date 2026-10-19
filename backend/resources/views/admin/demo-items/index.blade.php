<x-admin.layouts.app :title="__('admin.demo_items.title')">
    <x-admin.page-header
        :title="__('admin.demo_items.title')"
        :subtitle="__('admin.demo_items.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.demo_items.title')]]"
    >
        @can('export', \App\Models\DemoItem::class)
            <x-admin.button variant="secondary" icon="download" :data-export-url="route('admin.demo-items.export')" data-export-table="{{ \App\DataTables\Admin\Demo\DemoItemsDataTable::TABLE_ID }}">{{ __('admin.common.export') }}</x-admin.button>
        @endcan
        @can('create', \App\Models\DemoItem::class)
            <x-admin.button variant="secondary" icon="bolt" data-modal-open="demo-items-quick-create">{{ __('admin.demo_items.quick_create') }}</x-admin.button>
            <x-admin.button :href="route('admin.demo-items.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.grid cols="4" class="mb-6">
        <x-admin.stat-tile :label="__('admin.demo_items.stats.total')" :value="$stats['total']" icon="cube" />
        <x-admin.stat-tile :label="__('admin.demo_items.stats.active')" :value="$stats['active']" icon="check-circle" tone="success" />
        <x-admin.stat-tile :label="__('admin.demo_items.stats.draft')" :value="$stats['draft']" icon="pencil" tone="neutral" />
        <x-admin.stat-tile :label="__('admin.demo_items.stats.archived')" :value="$stats['archived']" icon="archive" tone="danger" />
    </x-admin.grid>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Demo\DemoItemsDataTable::TABLE_ID }}">
        <x-admin.filters.text name="search" :label="__('admin.common.search')" :placeholder="__('admin.demo_items.search_placeholder')" />
        <x-admin.filters.enum name="statuses" :label="__('admin.demo_items.fields.status')" :enum="\App\Enums\Demo\DemoItemStatus::class" multiple />
        <x-admin.filters.enum name="type" :label="__('admin.demo_items.fields.type')" :enum="\App\Enums\Demo\DemoItemType::class" />
        <x-admin.filters.boolean name="featured" :label="__('admin.demo_items.fields.is_featured')" />
        <x-admin.filters.remote-select name="country" :label="__('admin.demo_items.fields.country')" lookup="countries" multiple />
        <x-admin.filters.number-range name="price" :label="__('admin.demo_items.fields.price')" step="0.01" min="0" :suffix="config('admin.default_currency')" />
        <x-admin.filters.date-range name="created_at" :label="__('admin.common.created_at')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>

    @can('create', \App\Models\DemoItem::class)
        <x-admin.modal id="demo-items-quick-create" :title="__('admin.demo_items.quick_create')" size="lg">
            <x-admin.form :action="route('admin.demo-items.store')" method="POST">
                <input type="hidden" name="quick" value="1">
                <div class="space-y-5">
                    <x-admin.form.translatable-input name="name" :label="__('admin.demo_items.fields.name')" maxlength="191" required />
                    <x-admin.grid>
                        <x-admin.form.input name="code" :label="__('admin.demo_items.fields.code')" maxlength="32" dir="ltr" required id="quick-code" />
                        <x-admin.form.money name="price" :label="__('admin.demo_items.fields.price')" required id="quick-price" />
                        <x-admin.form.enum-select name="type" :label="__('admin.demo_items.fields.type')" :enum="\App\Enums\Demo\DemoItemType::class" required id="quick-type" />
                        <x-admin.form.enum-select name="status" :label="__('admin.demo_items.fields.status')" :enum="\App\Enums\Demo\DemoItemStatus::class" :except="[\App\Enums\Demo\DemoItemStatus::Archived]" required id="quick-status" />
                    </x-admin.grid>
                </div>
                <x-admin.form.actions>
                    <x-admin.button variant="secondary" x-on:click="$dispatch('admin-modal:close', { id: 'demo-items-quick-create' })">{{ __('admin.common.cancel') }}</x-admin.button>
                </x-admin.form.actions>
            </x-admin.form>
        </x-admin.modal>
    @endcan
</x-admin.layouts.app>
