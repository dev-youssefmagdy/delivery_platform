<?php

namespace App\DataTables\Admin\Customers;

use App\Models\CustomerMessage;
use App\Repositories\Contracts\Customers\CustomerMessageRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * Help messages support sent to the customer (in-app).
 */
final class CustomerMessagesDataTable extends CustomerTabDataTable
{
    public const TABLE_ID = 'customer-messages-table';

    protected array $defaultOrder = [[0, 'desc']];

    public function __construct(private readonly CustomerMessageRepositoryInterface $messages)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<CustomerMessage>
     */
    public function query(): Builder
    {
        return $this->messages->datatableQuery($this->customer());
    }

    protected function columns(): array
    {
        return [
            $this->column('created_at', __('admin.customers.messages.sent_at'), orderable: true),
            $this->column('subject', __('admin.customers.fields.message_subject')),
            $this->column('body', __('admin.customers.fields.message_body')),
            $this->column('sender', __('admin.customers.messages.sender')),
            $this->column('read_at', __('admin.customers.messages.read_at'), orderable: true),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['created_at', 'subject', 'body', 'sender', 'read_at']);
        $this->dateColumn($table, 'created_at');
        $this->dateColumn($table, 'read_at');
        $table->editColumn('body', static fn (Model $row): string => $row instanceof CustomerMessage ? Str::limit($row->body, 160) : '');
        $table->addColumn('sender', static fn (Model $row): string => $row instanceof CustomerMessage ? ($row->sender->name ?? '—') : '—');
    }
}
