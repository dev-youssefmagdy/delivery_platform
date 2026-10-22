<?php

use App\Enums\Booking\ReportCategory;
use App\Enums\Support\TicketEventType;
use App\Enums\Support\TicketSource;
use App\Enums\Support\TicketStatus;
use App\Enums\Support\TicketVisibility;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
| P2.5 `booking_reports` become support tickets (P3.7): one ticket per report, with the description as the first public
| message. Idempotent through `support_tickets.source_report_id`; the old table stays as history (rows get `ticket_id`).
*/
return new class extends Migration
{
    public function up(): void
    {
        DB::table('booking_reports')->whereNull('ticket_id')->orderBy('id')->chunkById(200, function ($reports): void {
            foreach ($reports as $report) {
                $byProvider = $report->reporter_type === 'provider';
                $userId = $byProvider
                    ? DB::table('provider_profiles')->where('id', $report->reporter_id)->value('user_id')
                    : $report->reporter_id;

                if ($userId === null) {
                    continue;
                }

                $category = (ReportCategory::tryFrom($report->category) ?? ReportCategory::Other)->ticketCategory($byProvider);
                $closed = $report->status === 'closed';
                $created = $report->created_at ?? now();

                DB::transaction(function () use ($report, $userId, $byProvider, $category, $closed, $created): void {
                    $ticketId = DB::table('support_tickets')->insertGetId([
                        'uuid' => (string) Str::uuid(),
                        'user_id' => $userId,
                        'app' => $byProvider ? 'provider' : 'customer',
                        'category' => $category->value,
                        'priority' => $category->defaultPriority()->value,
                        'status' => ($closed ? TicketStatus::Closed : TicketStatus::Open)->value,
                        'source' => TicketSource::Report->value,
                        'subject' => Str::limit($category->label(), 150, ''),
                        'booking_id' => $report->booking_id,
                        'group' => $category->defaultGroup()->value,
                        'sla_due_at' => $closed ? null : now()->addMinutes($category->defaultPriority()->slaMinutes()),
                        'last_message_at' => $created,
                        'closed_at' => $closed ? now() : null,
                        'source_report_id' => $report->id,
                        'created_at' => $created,
                        'updated_at' => $created,
                    ]);

                    DB::table('support_tickets')->where('id', $ticketId)->update(['number' => 'TK-'.str_pad((string) $ticketId, 6, '0', STR_PAD_LEFT)]);
                    DB::table('ticket_messages')->insert([
                        'uuid' => (string) Str::uuid(),
                        'ticket_id' => $ticketId,
                        'visibility' => TicketVisibility::Public->value,
                        'author_type' => $byProvider ? 'provider' : 'customer',
                        'author_user_id' => $userId,
                        'body' => $report->description,
                        'created_at' => $created,
                        'updated_at' => $created,
                    ]);
                    DB::table('ticket_events')->insert([
                        'ticket_id' => $ticketId,
                        'type' => TicketEventType::Created->value,
                        'actor_type' => $byProvider ? 'provider' : 'customer',
                        'actor_id' => $userId,
                        'meta' => json_encode(['migrated_from_report' => $report->id]),
                        'created_at' => $created,
                    ]);
                    DB::table('booking_reports')->where('id', $report->id)->update(['ticket_id' => $ticketId]);
                });
            }
        });
    }

    public function down(): void
    {
        // The tickets stay: they are real support history. Only the link is cleared by the table rollback.
    }
};
