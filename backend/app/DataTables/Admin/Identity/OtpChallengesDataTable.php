<?php

namespace App\DataTables\Admin\Identity;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Identity\OtpChallengeFilterData;
use App\Models\OtpChallenge;
use App\Repositories\Contracts\Identity\OtpChallengeRepositoryInterface;
use App\View\Components\Admin\Badge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Yajra\DataTables\EloquentDataTable;

/**
 * Read-only OTP challenge log for system admins (filters: phone, state, delivery, app, IP, date range). Shows the
 * masked phone only; codes and full numbers are never stored in this table.
 */
final class OtpChallengesDataTable extends AdminDataTable
{
    public const TABLE_ID = 'otp-challenges-table';

    protected bool $globalSearch = false;

    public function __construct(private readonly OtpChallengeRepositoryInterface $challenges)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<OtpChallenge>
     */
    public function query(): Builder
    {
        return $this->challenges->datatableQuery(OtpChallengeFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('created_at', __('admin.otp_challenges.fields.time'), orderable: true),
            $this->column('phone_masked', __('admin.otp_challenges.fields.phone')),
            $this->column('app', __('admin.otp_challenges.fields.app'), orderable: true),
            $this->column('state', __('admin.otp_challenges.fields.state')),
            $this->column('attempts', __('admin.otp_challenges.fields.attempts'), orderable: true),
            $this->column('delivery_status', __('admin.otp_challenges.fields.delivery'), orderable: true),
            $this->column('delivery_driver', __('admin.otp_challenges.fields.driver')),
            $this->column('ip', __('admin.otp_challenges.fields.ip'), orderable: true),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->dateColumn($table, 'created_at');
        $this->enumBadgeColumn($table, 'delivery_status');

        $table->editColumn('app', static fn (Model $row): string => $row instanceof OtpChallenge ? e($row->app->label()) : '—');
        $table->addColumn('state', static fn (Model $row): string => $row instanceof OtpChallenge ? Blade::renderComponent(new Badge($row->state())) : '—');
        $table->editColumn('attempts', static fn (Model $row): string => $row instanceof OtpChallenge ? $row->attempts.' / '.$row->max_attempts : '—');
        $table->editColumn('delivery_driver', static fn (Model $row): string => (string) ($row->getAttribute('delivery_driver') ?? '—'));
        $table->editColumn('ip', static fn (Model $row): string => (string) ($row->getAttribute('ip') ?? '—'));

        $this->rawColumn('state');
    }
}
