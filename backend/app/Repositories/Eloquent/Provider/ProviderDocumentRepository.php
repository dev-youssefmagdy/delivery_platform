<?php

namespace App\Repositories\Eloquent\Provider;

use App\Enums\Provider\DocumentSubject;
use App\Models\ProviderDocument;
use App\Repositories\Contracts\Provider\ProviderDocumentRepositoryInterface;

final class ProviderDocumentRepository extends AbstractDocumentRepository implements ProviderDocumentRepositoryInterface
{
    public function subject(): DocumentSubject
    {
        return DocumentSubject::Provider;
    }

    protected function modelClass(): string
    {
        return ProviderDocument::class;
    }

    protected function ownerColumn(): string
    {
        return 'provider_profile_id';
    }

    protected function ownerRelations(): array
    {
        return ['providerProfile'];
    }
}
