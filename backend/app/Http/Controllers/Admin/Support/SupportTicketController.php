<?php

namespace App\Http\Controllers\Admin\Support;

use App\DataTables\Admin\Support\SupportTicketsDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Support\AssignTicketRequest;
use App\Http\Requests\Admin\Support\ClaimTicketRequest;
use App\Http\Requests\Admin\Support\EscalateTicketRequest;
use App\Http\Requests\Admin\Support\LinkTicketFinanceRequest;
use App\Http\Requests\Admin\Support\PriorityTicketRequest;
use App\Http\Requests\Admin\Support\ReopenTicketRequest;
use App\Http\Requests\Admin\Support\ReplyTicketRequest;
use App\Http\Requests\Admin\Support\ResolveTicketRequest;
use App\Models\SupportTicket;
use App\Services\Audit\AuditTrailService;
use App\Services\Support\BookingChatService;
use App\Services\Support\SupportMacroService;
use App\Services\Support\TicketStaffService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ticket inbox and ticket screen (support.view / support.manage). Thin: each action authorizes through the policy (route
 * `can:` and the FormRequest), calls one service method and answers with the AJAX protocol. The service audits every change.
 */
final class SupportTicketController extends AdminController
{
    public function __construct(
        private readonly TicketStaffService $service,
        private readonly SupportMacroService $macros,
    ) {}

    public function index(SupportTicketsDataTable $table): mixed
    {
        return $table->render('admin.support-tickets.index');
    }

    public function show(SupportTicket $ticket): View
    {
        $ticket->load(['user', 'booking.customer', 'booking.provider.user', 'assignedAdmin', 'payment', 'payout']);

        return view('admin.support-tickets.show', [
            'ticket' => $ticket,
            'thread' => $this->service->fullThread($ticket),
            'events' => $this->service->events($ticket),
            'assignees' => $this->service->assignees($ticket),
            'macros' => $this->macros->forTicket($ticket->category),
            'payments' => $this->service->linkedPayments($ticket),
        ]);
    }

    public function activity(SupportTicket $ticket, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($ticket)]);
    }

    public function reply(ReplyTicketRequest $request, SupportTicket $ticket): AdminFormResponse
    {
        $macro = $request->macroUuid() === null ? null : $this->macros->findActive($request->macroUuid());
        $this->service->reply($ticket, $this->actor(), $request->toDto($macro?->id));

        return $this->formResponse(__('admin.support_tickets.replied'))->redirect(route('admin.support-tickets.show', $ticket));
    }

    public function claim(ClaimTicketRequest $request, SupportTicket $ticket): AdminFormResponse
    {
        $this->service->claim($ticket, $this->actor());

        return $this->formResponse(__('admin.support_tickets.claimed'))->redirect(route('admin.support-tickets.show', $ticket));
    }

    public function assign(AssignTicketRequest $request, SupportTicket $ticket): AdminFormResponse
    {
        $this->service->assign($ticket, $this->actor(), $request->adminId(), $request->reason());

        return $this->formResponse(__('admin.support_tickets.assigned'))->redirect(route('admin.support-tickets.show', $ticket));
    }

    public function escalate(EscalateTicketRequest $request, SupportTicket $ticket): AdminFormResponse
    {
        $this->service->escalate($ticket, $this->actor(), $request->group(), (string) $request->reason());

        return $this->formResponse(__('admin.support_tickets.escalated'))->redirect(route('admin.support-tickets.show', $ticket));
    }

    public function resolve(ResolveTicketRequest $request, SupportTicket $ticket): AdminFormResponse
    {
        $this->service->resolve($ticket, $this->actor(), (string) $request->reason());

        return $this->formResponse(__('admin.support_tickets.resolved'))->redirect(route('admin.support-tickets.show', $ticket));
    }

    public function reopen(ReopenTicketRequest $request, SupportTicket $ticket): AdminFormResponse
    {
        $this->service->reopen($ticket, $this->actor(), (string) $request->reason());

        return $this->formResponse(__('admin.support_tickets.reopened'))->redirect(route('admin.support-tickets.show', $ticket));
    }

    public function priority(PriorityTicketRequest $request, SupportTicket $ticket): AdminFormResponse
    {
        $this->service->changePriority($ticket, $this->actor(), $request->priority());

        return $this->formResponse(__('admin.support_tickets.priority_changed'))->redirect(route('admin.support-tickets.show', $ticket));
    }

    public function linkFinance(LinkTicketFinanceRequest $request, SupportTicket $ticket): AdminFormResponse
    {
        $this->service->linkFinance($ticket, $this->actor(), $request->filled('payment_id') ? (string) $request->input('payment_id') : null, $request->filled('payout_id') ? (string) $request->input('payout_id') : null);

        return $this->formResponse(__('admin.support_tickets.linked'))->redirect(route('admin.support-tickets.show', $ticket));
    }

    public function attachment(SupportTicket $ticket, string $attachment): StreamedResponse
    {
        return $this->service->attachmentFor($ticket, $attachment);
    }

    /**
     * The booking chat as evidence for a dispute: every read is written to the audit log with the acting admin.
     */
    public function chat(SupportTicket $ticket, BookingChatService $chat): View
    {
        $booking = $ticket->booking ?? abort(404);

        return view('admin.support-tickets.chat', [
            'booking' => $booking,
            'messages' => $chat->staffTranscript($booking, $this->actor(), 'ticket '.$ticket->number),
        ]);
    }
}
