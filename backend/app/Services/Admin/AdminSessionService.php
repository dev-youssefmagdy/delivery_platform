<?php

namespace App\Services\Admin;

use App\Enums\Common\AuditAction;
use App\Models\Admin;
use App\Models\AdminSession;
use App\Repositories\Contracts\Admin\AdminSessionRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Tracks authenticated admin browser sessions so they can be listed and revoked (ADR-06). The row exists next
 * to the framework session (any driver); revoking deletes both. A session that was marked as started but lost
 * its row (revoked, admin suspended/deleted, password reset) is treated as ended.
 */
final class AdminSessionService
{
    public const MARKER = 'admin.session_started';

    public function __construct(
        private readonly AdminSessionRepositoryInterface $sessions,
        private readonly SessionManager $manager,
        private readonly AuditLogger $audit,
    ) {}

    public function start(Admin $admin, string $sessionId): AdminSession
    {
        return $this->sessions->create([
            'admin_id' => $admin->getKey(),
            'session_id' => $sessionId,
            'ip' => $this->context('ip'),
            'user_agent' => $this->context('user_agent'),
            'last_active_at' => now(),
        ]);
    }

    /**
     * Called on every authenticated request. Returns false when the session was revoked and must end.
     * A session without a marker (created outside the login flow, e.g. tests) gets its row lazily.
     */
    public function validate(Admin $admin, string $sessionId, bool $marked): bool
    {
        $row = $this->sessions->findBySessionId($sessionId);

        if ($row === null) {
            if ($marked) {
                return false;
            }

            $this->start($admin, $sessionId);

            return true;
        }

        if ($row->admin_id !== $admin->getKey()) {
            return false;
        }

        $touchAfter = (int) config('admin.auth.sessions.touch_after_seconds', 60);

        if ($row->last_active_at === null || $row->last_active_at->diffInSeconds(now(), true) >= $touchAfter) {
            $this->sessions->touch($row);
        }

        return true;
    }

    /**
     * @return Collection<int, AdminSession>
     */
    public function listFor(Admin $admin): Collection
    {
        return $this->sessions->forAdmin((int) $admin->getKey());
    }

    /**
     * Revokes one of the admin's own sessions (never the current one: that is "log out").
     */
    public function revoke(Admin $admin, int $sessionRowId, string $currentSessionId): void
    {
        $row = $this->sessions->find($sessionRowId);

        if ($row === null || $row->admin_id !== $admin->getKey()) {
            throw new NotFoundHttpException;
        }

        if ($row->session_id === $currentSessionId) {
            throw new NotFoundHttpException;
        }

        $this->destroyPayload($row->session_id);
        $this->sessions->delete($row);

        $this->audit->record(AuditAction::SessionRevoked, $admin, $admin, meta: ['session' => Str::limit($row->session_id, 8, '')]);
    }

    /**
     * Ends every session of an admin (optionally keeping the current one).
     */
    public function revokeAll(Admin|int $admin, ?string $exceptSessionId = null): int
    {
        $ids = $this->sessions->deleteForAdmin($admin instanceof Admin ? (int) $admin->getKey() : $admin, $exceptSessionId);

        foreach ($ids as $id) {
            $this->destroyPayload($id);
        }

        return count($ids);
    }

    /**
     * Removes the tracking row of a session that is logging out.
     */
    public function end(string $sessionId): void
    {
        $row = $this->sessions->findBySessionId($sessionId);

        if ($row !== null) {
            $this->sessions->delete($row);
        }
    }

    private function destroyPayload(string $sessionId): void
    {
        $this->manager->driver()->getHandler()->destroy($sessionId);
    }

    private function context(string $key): ?string
    {
        $value = Context::getHidden($key);

        return is_string($value) ? Str::limit($value, 512, '') : null;
    }
}
