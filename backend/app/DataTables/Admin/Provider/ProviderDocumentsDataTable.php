<?php

namespace App\DataTables\Admin\Provider;

use App\Models\ProviderDocument;
use App\Repositories\Contracts\Provider\ProviderDocumentRepositoryInterface;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * Documents tab of a provider (the provider's own documents; vehicle documents are on each vehicle). Viewing a file,
 * downloading it and deciding are permission-gated and the file access is audited.
 */
final class ProviderDocumentsDataTable extends ProviderTabDataTable
{
    use DocumentsTableConcerns;

    public const TABLE_ID = 'provider-documents-table';

    public function __construct(
        private readonly ProviderDocumentRepositoryInterface $documents,
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
        return $this->documents->datatableQuery($this->provider()->id);
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
        $this->actionsColumn($table, fn (ProviderDocument $document): array => $this->documentActions($document, self::TABLE_ID));
    }
}
