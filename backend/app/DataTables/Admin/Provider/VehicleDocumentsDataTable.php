<?php

namespace App\DataTables\Admin\Provider;

use App\DataTables\Admin\AdminDataTable;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Repositories\Contracts\Provider\VehicleDocumentRepositoryInterface;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Eloquent\Builder;
use LogicException;
use Yajra\DataTables\EloquentDataTable;

/**
 * Documents tab of a vehicle (registration, insurance, inspection ...).
 */
final class VehicleDocumentsDataTable extends AdminDataTable
{
    use DocumentsTableConcerns;

    public const TABLE_ID = 'vehicle-documents-table';

    protected bool $globalSearch = false;

    public function __construct(
        private readonly VehicleDocumentRepositoryInterface $documents,
        private readonly SettingsService $settings,
    ) {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<\App\Models\OwnedDocument>
     */
    public function query(): Builder
    {
        return $this->documents->datatableQuery($this->vehicle()->id);
    }

    protected function columns(): array
    {
        return [
            ...array_map(fn (array $spec) => $this->column($spec[0], $spec[1], orderable: $spec[2]), $this->documentColumnSpecs()),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->configureDocumentColumns($table, $this->settings);
        $this->actionsColumn($table, fn (VehicleDocument $document): array => $this->documentActions($document, self::TABLE_ID));
    }

    private function vehicle(): Vehicle
    {
        $vehicle = $this->request()->route('vehicle');

        return $vehicle instanceof Vehicle ? $vehicle : throw new LogicException('The vehicle documents table needs a {vehicle} route parameter.');
    }
}
