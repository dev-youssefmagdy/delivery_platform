<?php

namespace App\Repositories\Eloquent\Provider;

use App\Enums\Provider\DocumentSubject;
use App\Models\VehicleDocument;
use App\Repositories\Contracts\Provider\VehicleDocumentRepositoryInterface;

final class VehicleDocumentRepository extends AbstractDocumentRepository implements VehicleDocumentRepositoryInterface
{
    public function subject(): DocumentSubject
    {
        return DocumentSubject::Vehicle;
    }

    protected function modelClass(): string
    {
        return VehicleDocument::class;
    }

    protected function ownerColumn(): string
    {
        return 'vehicle_id';
    }

    protected function ownerRelations(): array
    {
        return ['vehicle.providerProfile'];
    }
}
