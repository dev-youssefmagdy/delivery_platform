<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Provider\DocumentStatus;
use App\Models\OwnedDocument;
use App\Models\ProviderDocument;
use App\Models\User;
use App\Models\VehicleDocument;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Authorization for provider and vehicle documents (registered for the shared `OwnedDocument` base class).
 * Viewing the file needs `documents.view`; deciding needs the permission of the document's owner kind, so a role with
 * read-only access can never approve or reject.
 */
class DocumentPolicy
{
    public function view(Authorizable $admin, OwnedDocument $document): bool
    {
        return $admin->can(AdminPermission::DocumentsView->value);
    }

    public function approve(Authorizable $admin, OwnedDocument $document): bool
    {
        $permission = $document instanceof VehicleDocument ? AdminPermission::VehiclesApprove : AdminPermission::DriversVerify;

        return $admin->can($permission->value) && $document->status === DocumentStatus::Pending;
    }

    public function reject(Authorizable $admin, OwnedDocument $document): bool
    {
        $permission = $document instanceof VehicleDocument ? AdminPermission::VehiclesReject : AdminPermission::DriversReject;

        return $admin->can($permission->value) && in_array($document->status, [DocumentStatus::Pending, DocumentStatus::Approved], true);
    }

    /**
     * The driver API: a provider only ever touches its own documents and those of its own vehicles.
     */
    public function manageOwn(Authorizable $actor, OwnedDocument $document): bool
    {
        if (! $actor instanceof User) {
            return false;
        }

        $profileId = $actor->providerProfile?->id;

        if ($profileId === null) {
            return false;
        }

        return match (true) {
            $document instanceof ProviderDocument => $document->provider_profile_id === $profileId,
            $document instanceof VehicleDocument => $document->vehicle->provider_profile_id === $profileId,
            default => false,
        };
    }
}
