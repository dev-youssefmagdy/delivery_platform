<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\Admin;
use App\Models\SafetyIncident;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Safety cases (incidents.view / manage / view_evidence / export_restricted). Permission separation (SRS §16.10): `view` shows the
 * case's structure (category, severity, status, timers, timeline without bodies); the narrative, location, notes, callback number
 * and files need `view_evidence`, and every use of it is audited. Support agents hold none of these permissions. Working a case
 * needs `manage` and a state that allows the action.
 */
class SafetyIncidentPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $this->can($admin, AdminPermission::IncidentsView);
    }

    public function view(Authorizable $admin, SafetyIncident $incident): bool
    {
        return $this->can($admin, AdminPermission::IncidentsView);
    }

    public function viewEvidence(Authorizable $admin, SafetyIncident $incident): bool
    {
        return $this->can($admin, AdminPermission::IncidentsViewEvidence);
    }

    public function exportRestricted(Authorizable $admin, ?SafetyIncident $incident = null): bool
    {
        return $this->can($admin, AdminPermission::IncidentsExportRestricted);
    }

    public function exportEvidence(Authorizable $admin, SafetyIncident $incident): bool
    {
        return $this->can($admin, AdminPermission::IncidentsExportRestricted) && $this->can($admin, AdminPermission::IncidentsViewEvidence);
    }

    public function acknowledge(Authorizable $admin, SafetyIncident $incident): bool
    {
        return $this->manage($admin) && $incident->status->value === 'new';
    }

    public function assign(Authorizable $admin, SafetyIncident $incident): bool
    {
        return $this->manage($admin) && $incident->status->isActive();
    }

    public function note(Authorizable $admin, SafetyIncident $incident): bool
    {
        return $this->manage($admin);
    }

    public function message(Authorizable $admin, SafetyIncident $incident): bool
    {
        return $this->manage($admin) && $incident->status->isActive();
    }

    public function triage(Authorizable $admin, SafetyIncident $incident): bool
    {
        return $this->manage($admin) && $incident->status->isActive();
    }

    public function escalate(Authorizable $admin, SafetyIncident $incident): bool
    {
        return $this->manage($admin) && $incident->status->isActive();
    }

    public function resolve(Authorizable $admin, SafetyIncident $incident): bool
    {
        return $this->manage($admin) && $incident->status->isActive();
    }

    public function reopen(Authorizable $admin, SafetyIncident $incident): bool
    {
        return $this->manage($admin) && ! $incident->status->isActive();
    }

    public function legalHold(Authorizable $admin, SafetyIncident $incident): bool
    {
        return $this->manage($admin);
    }

    private function manage(Authorizable $admin): bool
    {
        return $this->can($admin, AdminPermission::IncidentsManage);
    }

    private function can(Authorizable $admin, AdminPermission $permission): bool
    {
        return $admin instanceof Admin && $admin->can($permission->value);
    }
}
