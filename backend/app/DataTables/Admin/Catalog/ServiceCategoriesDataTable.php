<?php

namespace App\DataTables\Admin\Catalog;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Catalog\ServiceCategoryFilterData;
use App\Enums\Catalog\BookingType;
use App\Models\ServiceCategory;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * Service categories listing (filters: search, family, active, booking type, negotiation enabled).
 */
final class ServiceCategoriesDataTable extends AdminDataTable
{
    public const TABLE_ID = 'service-categories-table';

    protected array $defaultOrder = [[3, 'asc']];

    public function __construct(private readonly ServiceCategoryRepositoryInterface $categories)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<ServiceCategory>
     */
    public function query(): Builder
    {
        return $this->categories->datatableQuery(ServiceCategoryFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('code', __('admin.catalog.fields.code'), orderable: true),
            $this->column('name', __('admin.service_categories.fields.name')),
            $this->column('sort_order', __('admin.catalog.fields.sort_order'), orderable: true),
            $this->column('family', __('admin.service_categories.fields.family')),
            $this->column('allowed_booking_types', __('admin.service_categories.fields.allowed_booking_types')),
            $this->column('negotiation_enabled', __('admin.service_categories.fields.negotiation_enabled'), orderable: true),
            $this->column('published_version', __('admin.service_categories.fields.published_version')),
            $this->column('active', __('admin.catalog.fields.active'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->translatedColumn($table, 'name');
        $this->boolColumn($table, 'active');
        $this->boolColumn($table, 'negotiation_enabled');
        $table->addColumn('family', static fn (ServiceCategory $category): string => (string) $category->family->getTranslation('name', app()->getLocale(), true));
        $table->addColumn('published_version', static fn (ServiceCategory $category): string => $category->publishedVersion === null ? '—' : 'v'.$category->publishedVersion->version);
        $table->editColumn('allowed_booking_types', static fn (ServiceCategory $category): string => implode('، ', array_map(
            static fn (string $type): string => BookingType::tryFrom($type)?->label() ?? $type,
            $category->allowed_booking_types,
        )));
        $this->actionsColumn($table, fn (ServiceCategory $category): array => [
            TableAction::view(route('admin.service-categories.show', $category), $category),
            TableAction::edit(route('admin.service-categories.edit', $category), $category),
            TableAction::confirm(
                __('admin.catalog.actions.activate'),
                route('admin.service-categories.activate', $category),
                'activate',
                $category,
                icon: 'play',
                variant: 'success',
                confirm: ['title' => __('admin.catalog.confirm.activate_title'), 'message' => __('admin.service_categories.confirm.activate_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::delete(route('admin.service-categories.destroy', $category), $category, self::TABLE_ID, reasonRequired: true),
        ]);
    }
}
