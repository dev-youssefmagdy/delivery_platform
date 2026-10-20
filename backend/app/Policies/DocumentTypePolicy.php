<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\DocumentType;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Document types and their requirements: one permission (documents.manage_types) covers viewing and managing.
 */
class DocumentTypePolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function view(Authorizable $admin, DocumentType $type): bool
    {
        return $this->allowed($admin);
    }

    public function create(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function update(Authorizable $admin, DocumentType $type): bool
    {
        return $this->allowed($admin);
    }

    public function delete(Authorizable $admin, DocumentType $type): bool
    {
        return $this->allowed($admin);
    }

    public function activate(Authorizable $admin, DocumentType $type): bool
    {
        return $this->allowed($admin) && ! $type->active;
    }

    public function deactivate(Authorizable $admin, DocumentType $type): bool
    {
        return $this->allowed($admin) && $type->active;
    }

    private function allowed(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::DocumentsManageTypes->value);
    }
}
