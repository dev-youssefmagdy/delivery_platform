<x-admin.layouts.app :title="__('content.articles.title')">
    <x-admin.page-header
        :title="__('content.articles.title')"
        :subtitle="__('content.articles.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('content.articles.title')]]"
    >
        @can('create', \App\Models\ContentArticle::class)
            <x-admin.button :href="route('admin.content-articles.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Content\ContentArticlesDataTable::TABLE_ID }}">
        <x-admin.filters.text name="search" :label="__('admin.common.search')" :placeholder="__('content.articles.search_placeholder')" />
        <x-admin.filters.enum name="types" :label="__('content.fields.article_type')" :enum="\App\Enums\Content\ArticleType::class" multiple />
        <x-admin.filters.remote-select name="category_ids" lookup="article-categories" :label="__('content.fields.category')" multiple />
        <x-admin.filters.enum name="statuses" :label="__('content.fields.status')" :enum="\App\Enums\Content\ContentWorkflowStatus::class" multiple />
        <x-admin.filters.enum name="completeness" :label="__('content.fields.locales')" :enum="\App\Enums\Content\LocaleCompleteness::class" />
        <x-admin.filters.date-range name="updated_at" :label="__('admin.common.updated_at')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
