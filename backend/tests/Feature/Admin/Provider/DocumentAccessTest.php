<?php

namespace Tests\Feature\Admin\Provider;

use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Models\AdminAuditLog;
use App\Models\ProviderDocument;
use App\Models\VehicleDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsProviders;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * ADR-11: private files are reached only through short-lived signed URLs after a policy check, and every admin view or
 * download of a document is audited.
 */
class DocumentAccessTest extends TestCase
{
    use BuildsProviders;
    use InteractsWithAdmin;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakePrivateDisk();
    }

    private function storedDocument(string $mime = 'image/jpeg'): ProviderDocument
    {
        $provider = $this->makeProvider();
        $document = $this->providerDocument($provider, $this->documentType(['code' => 'licence']));
        $path = 'providers/'.$provider->uuid.'/documents/'.fake()->uuid().($mime === 'application/pdf' ? '.pdf' : '.jpg');
        $image = $this->photo();
        Storage::disk('private')->put($path, $mime === 'application/pdf' ? "%PDF-1.4\n%%EOF" : (string) file_get_contents((string) $image->getRealPath()));
        $document->forceFill(['path' => $path, 'mime' => $mime])->save();

        return $document;
    }

    public function test_issuing_a_url_needs_documents_view_and_does_not_audit_anything_yet(): void
    {
        $document = $this->storedDocument();

        $this->actingAsAdmin([AdminPermission::DriversView, AdminPermission::DriversVerify]);
        $this->postJson(route('admin.provider-documents.url', $document->uuid))->assertForbidden();

        $this->actingAsAdmin([AdminPermission::DocumentsView]);
        $response = $this->postJson(route('admin.provider-documents.url', $document->uuid))->assertOk();
        $this->assertStringContainsString('signature=', $response->json('data.url'));
        $this->assertSame(300, $response->json('data.expires_in'));
        $this->assertSame(0, AdminAuditLog::query()->count());
    }

    public function test_serving_the_file_is_audited_as_a_view_and_a_download_with_the_right_headers(): void
    {
        $document = $this->storedDocument();
        $admin = $this->actingAsAdmin([AdminPermission::DocumentsView]);
        $url = $this->postJson(route('admin.provider-documents.url', $document->uuid))->json('data.url');

        $view = $this->get($url)->assertOk();
        $view->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('inline', (string) $view->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', (string) $view->headers->get('Cache-Control'));

        $downloadUrl = $this->postJson(route('admin.provider-documents.url', $document->uuid), ['mode' => 'download'])->json('data.url');
        $download = $this->get($downloadUrl)->assertOk();
        $this->assertStringContainsString('attachment', (string) $download->headers->get('Content-Disposition'));

        $logs = AdminAuditLog::query()->orderBy('id')->get();
        $this->assertSame([AuditAction::Viewed->value, AuditAction::Downloaded->value], $logs->map(fn ($log) => $log->action->value)->all());
        $this->assertSame($admin->id, $logs[0]->actor_id);
        $this->assertSame('provider_profile', $logs[0]->auditable_type);
        $this->assertSame($document->uuid, $logs[0]->meta['document']);
        $this->assertSame('licence', $logs[0]->meta['document_type']);
        $this->assertSame('skipped', $logs[0]->meta['scan_status']);

        $this->get($url)->assertOk();
        $this->assertSame(3, AdminAuditLog::query()->count(), 'every load of the file is an audited access');
    }

    public function test_pdfs_are_served_inline_as_pdf(): void
    {
        $document = $this->storedDocument('application/pdf');
        $this->actingAsAdmin([AdminPermission::DocumentsView]);
        $url = $this->postJson(route('admin.provider-documents.url', $document->uuid))->json('data.url');

        $response = $this->get($url)->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
    }

    public function test_the_signed_url_is_not_enough_on_its_own(): void
    {
        $document = $this->storedDocument();
        $issuer = $this->actingAsAdmin([AdminPermission::DocumentsView]);
        $url = $this->postJson(route('admin.provider-documents.url', $document->uuid))->json('data.url');

        // No session: redirected to the login page. Tampered signature: 403.
        auth('admin')->logout();
        $this->assertGuestBlocked($this->get($url));
        $this->actingAsAdmin([AdminPermission::DocumentsView]);
        $this->get($url.'x')->assertForbidden();
        $this->get(route('admin.provider-files.provider-document', ['document' => $document->uuid, 'admin' => $issuer->id]))->assertForbidden();

        // Another admin who got hold of the link cannot use it.
        $this->get($url)->assertForbidden();

        // An admin who lost the permission cannot use their own link either.
        $this->actingAs($issuer, 'admin');
        $issuer->revokePermissionTo(AdminPermission::DocumentsView->value);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->get($url)->assertForbidden();
        $this->assertSame(0, AdminAuditLog::query()->count(), 'refused accesses are not accesses');
    }

    public function test_the_link_expires_after_five_minutes(): void
    {
        $document = $this->storedDocument();
        $this->actingAsAdmin([AdminPermission::DocumentsView]);
        $url = $this->postJson(route('admin.provider-documents.url', $document->uuid))->json('data.url');

        $this->travel(4)->minutes();
        $this->get($url)->assertOk();
        $this->travel(2)->minutes();
        $this->get($url)->assertForbidden();
    }

    public function test_a_missing_file_is_a_404_and_not_audited_as_an_access(): void
    {
        $document = $this->storedDocument();
        Storage::disk('private')->delete((string) $document->path);
        $this->actingAsAdmin([AdminPermission::DocumentsView]);
        $url = $this->postJson(route('admin.provider-documents.url', $document->uuid))->json('data.url');

        $this->get($url)->assertNotFound();
        $this->assertSame(0, AdminAuditLog::query()->count());
    }

    public function test_vehicle_documents_are_audited_against_the_vehicle(): void
    {
        $vehicle = $this->vehicleFor($this->makeProvider());
        $document = $this->documentOfVehicle($vehicle, $this->vehicleDocumentType(['code' => 'registration']));
        Storage::disk('private')->put((string) $document->path, 'jpegbytes');
        $this->actingAsAdmin([AdminPermission::DocumentsView]);

        $url = $this->postJson(route('admin.vehicle-documents.url', $document->uuid))->assertOk()->json('data.url');
        $this->get($url)->assertOk();

        $log = AdminAuditLog::query()->sole();
        $this->assertSame(['vehicle', (string) $vehicle->id, AuditAction::Viewed->value], [$log->auditable_type, $log->auditable_id, $log->action->value]);
        $this->assertInstanceOf(VehicleDocument::class, VehicleDocument::query()->where('uuid', $document->uuid)->first());
    }

    public function test_vehicle_photos_use_signed_urls_for_vehicle_viewers(): void
    {
        $vehicle = $this->vehicleFor($this->makeProvider());
        $photo = $vehicle->photos()->create(['kind' => 'front', 'path' => 'p/front.jpg', 'mime' => 'image/jpeg', 'size_bytes' => 4, 'file_hash' => str_repeat('b', 64)]);
        Storage::disk('private')->put('p/front.jpg', 'jpeg');

        $this->actingAsAdmin([AdminPermission::DocumentsView]);
        $this->postJson(route('admin.vehicle-photos.url', $photo->uuid))->assertForbidden();

        $this->actingAsAdmin([AdminPermission::VehiclesView]);
        $url = $this->postJson(route('admin.vehicle-photos.url', $photo->uuid))->assertOk()->json('data.url');
        $this->get($url)->assertOk();
        $this->get($url.'x')->assertForbidden();
    }
}
