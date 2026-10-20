<?php

namespace Tests\Feature\Provider;

use App\Enums\Provider\DocumentStatus;
use App\Enums\Provider\FileScanStatus;
use App\Enums\Provider\ProviderStatus;
use App\Integrations\Files\Contracts\FileScanner;
use App\Integrations\Files\Exceptions\FileScannerUnavailableException;
use App\Integrations\Files\FileScanResult;
use App\Models\ProviderDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsProviders;
use Tests\TestCase;

/**
 * POST/GET /provider/documents: private storage, validation, scanning, replacement and ownership.
 */
class ProviderDocumentApiTest extends TestCase
{
    use BuildsProviders;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakePrivateDisk();
    }

    public function test_an_upload_is_stored_privately_with_hash_encrypted_number_and_pending_status(): void
    {
        $provider = $this->makeProvider();
        $type = $this->documentType(['code' => 'licence', 'requires_expiry' => true, 'requires_number' => true]);
        $this->actingAsProvider($provider);
        $expires = now()->addYear()->toDateString();

        $response = $this->postJson('/api/v1/provider/documents', [
            'document_type' => 'licence', 'file' => $this->photo('licence.jpg'), 'number' => 'AB-123456', 'issued_at' => now()->subYear()->toDateString(), 'expires_at' => $expires,
        ], $this->idempotencyHeaders())->assertCreated();

        $response->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.document_type', 'licence')
            ->assertJsonPath('data.expires_at', $expires)
            ->assertJsonPath('data.number_masked', '••••456');
        $this->assertStringNotContainsString('AB-123456', $response->getContent());
        $this->assertStringNotContainsString($response->json('data.id').'.jpg', (string) json_encode($response->json('data')));

        $document = ProviderDocument::query()->firstOrFail();
        $this->assertSame(DocumentStatus::Pending, $document->status);
        $this->assertSame(FileScanStatus::Skipped, $document->scan_status);
        $this->assertSame('image/jpeg', $document->mime);
        $this->assertSame(64, strlen($document->file_hash));
        $this->assertSame('AB-123456', $document->number);
        $this->assertNotSame('AB-123456', $document->getRawOriginal('number'), 'the document number is stored encrypted');
        $this->assertStringStartsWith('providers/'.$provider->uuid.'/documents/', (string) $document->path);
        $this->assertStringEndsWith('.jpg', (string) $document->path);
        Storage::disk('private')->assertExists((string) $document->path);
    }

    public function test_the_type_rules_are_enforced(): void
    {
        $provider = $this->makeProvider();
        $this->documentType(['code' => 'licence', 'requires_expiry' => true, 'requires_number' => true, 'allowed_mimes' => ['image/jpeg'], 'max_size_kb' => 100]);
        $this->actingAsProvider($provider);
        $post = fn (array $data) => $this->postJson('/api/v1/provider/documents', $data + ['document_type' => 'licence'], $this->idempotencyHeaders());

        $post(['file' => $this->photo(), 'number' => 'AB-1'])->assertStatus(422)->assertJsonPath('errors._code', 'document_expiry_required');
        $post(['file' => $this->photo(), 'expires_at' => now()->addYear()->toDateString()])->assertStatus(422)->assertJsonPath('errors._code', 'document_number_required');
        $post(['file' => $this->photo(), 'number' => 'AB-1', 'expires_at' => now()->subDay()->toDateString()])->assertStatus(422)->assertJsonPath('errors._code', 'document_already_expired');
        $post(['file' => $this->photo(), 'number' => 'AB-1', 'expires_at' => now()->addYear()->toDateString(), 'issued_at' => now()->addDay()->toDateString()])->assertStatus(422)->assertJsonPath('errors._code', 'document_issue_date_future');
        $post(['file' => $this->pdf(), 'number' => 'AB-1', 'expires_at' => now()->addYear()->toDateString()])->assertStatus(422)->assertJsonPath('errors._code', 'document_type_not_allowed');
        $post(['file' => UploadedFile::fake()->create('big.jpg', 300, 'image/jpeg'), 'number' => 'AB-1', 'expires_at' => now()->addYear()->toDateString()])->assertStatus(422)->assertJsonPath('errors._code', 'document_too_large');
        $post(['document_type' => 'missing', 'file' => $this->photo()])->assertStatus(422)->assertJsonPath('errors._code', 'document_type_unknown');
        $this->assertDatabaseCount('provider_documents', 0);
    }

    public function test_content_is_checked_not_the_name_or_the_client_mime_type(): void
    {
        $provider = $this->makeProvider();
        $this->documentType(['code' => 'id_card', 'allowed_mimes' => ['image/jpeg', 'image/png', 'application/pdf']]);
        $this->actingAsProvider($provider);
        $post = fn (UploadedFile $file) => $this->postJson('/api/v1/provider/documents', ['document_type' => 'id_card', 'file' => $file], $this->idempotencyHeaders());

        // A script renamed to .jpg, text claiming to be an image, a PNG renamed to .jpg and an empty file are all refused.
        $post(UploadedFile::fake()->createWithContent('shell.jpg', '<?php echo 1;'))->assertStatus(422)->assertJsonPath('errors._code', 'document_type_not_allowed');
        $post(UploadedFile::fake()->createWithContent('notes.txt', 'hello'))->assertStatus(422)->assertJsonPath('errors._code', 'document_type_not_allowed');
        $post(UploadedFile::fake()->image('photo.png', 300, 300))->assertCreated();
        $png = $this->photo('x.png', 300, 300);
        $post(UploadedFile::fake()->createWithContent('renamed.jpg', (string) file_get_contents((string) $png->getRealPath())))->assertStatus(422)->assertJsonPath('errors._code', 'document_type_not_allowed');
        $post(UploadedFile::fake()->createWithContent('empty.jpg', ''))->assertStatus(422);
        $this->assertDatabaseCount('provider_documents', 1);
    }

    public function test_images_are_reencoded_and_small_images_and_active_pdfs_are_refused(): void
    {
        $provider = $this->makeProvider();
        $this->documentType(['code' => 'id_card']);
        $this->documentType(['code' => 'permit']);
        $this->actingAsProvider($provider);
        $post = fn (UploadedFile $file, string $type = 'id_card') => $this->postJson('/api/v1/provider/documents', ['document_type' => $type, 'file' => $file], $this->idempotencyHeaders());

        $post($this->photo('tiny.jpg', 50, 50))->assertStatus(422)->assertJsonPath('errors._code', 'document_image_too_small');
        $post($this->pdf('script.pdf', '<< /S /JavaScript /JS (app.alert(1)) >>'))->assertStatus(422)->assertJsonPath('errors._code', 'document_pdf_active_content');
        $post($this->photo('big.jpg', 3000, 2000))->assertCreated();
        $post($this->pdf('clean.pdf'), 'permit')->assertCreated();

        [$image, $pdf] = ProviderDocument::query()->orderBy('id')->get()->all();
        [$width, $height] = getimagesizefromstring((string) Storage::disk('private')->get((string) $image->path));
        $this->assertSame(2400, max($width, $height), 'large images are scaled down');
        $this->assertSame('application/pdf', $pdf->mime);
        $this->assertStringEndsWith('.pdf', (string) $pdf->path);
    }

    public function test_an_infected_file_is_refused_and_an_unavailable_scanner_fails_closed(): void
    {
        $provider = $this->makeProvider();
        $this->documentType(['code' => 'id_card']);
        $this->actingAsProvider($provider);
        $post = fn () => $this->postJson('/api/v1/provider/documents', ['document_type' => 'id_card', 'file' => $this->photo()], $this->idempotencyHeaders());

        $this->app->instance(FileScanner::class, new class implements FileScanner
        {
            public function scan(string $path): FileScanResult
            {
                return new FileScanResult(FileScanStatus::Infected, 'Eicar-Test');
            }

            public function name(): string
            {
                return 'test';
            }
        });
        $post()->assertStatus(422)->assertJsonPath('errors._code', 'document_infected');

        $this->app->instance(FileScanner::class, new class implements FileScanner
        {
            public function scan(string $path): FileScanResult
            {
                throw new FileScannerUnavailableException('down');
            }

            public function name(): string
            {
                return 'test';
            }
        });
        $post()->assertStatus(503)->assertJsonPath('errors._code', 'file_scanner_unavailable');
        $this->assertDatabaseCount('provider_documents', 0);
        $this->assertSame([], Storage::disk('private')->allFiles());
    }

    public function test_without_a_configured_scanner_the_file_is_stored_as_not_scanned(): void
    {
        config(['integrations.file_scanner.driver' => null]);
        $provider = $this->makeProvider();
        $this->documentType(['code' => 'id_card']);
        $this->actingAsProvider($provider);

        $this->postJson('/api/v1/provider/documents', ['document_type' => 'id_card', 'file' => $this->photo()], $this->idempotencyHeaders())->assertCreated();

        $this->assertSame(FileScanStatus::NotScanned, ProviderDocument::query()->firstOrFail()->scan_status);
    }

    public function test_a_new_upload_replaces_a_pending_one_and_keeps_reviewed_history(): void
    {
        $provider = $this->makeProvider();
        $type = $this->documentType(['code' => 'id_card']);
        $this->actingAsProvider($provider);
        $upload = fn () => $this->postJson('/api/v1/provider/documents', ['document_type' => 'id_card', 'file' => $this->photo()], $this->idempotencyHeaders())->assertCreated();

        $upload();
        $first = ProviderDocument::query()->firstOrFail();
        $firstPath = (string) $first->path;
        $upload();

        $this->assertSame(1, ProviderDocument::query()->count(), 'the pending upload was replaced');
        $this->assertSoftDeleted($first);
        Storage::disk('private')->assertMissing($firstPath);

        ProviderDocument::query()->firstOrFail()->forceFill(['status' => DocumentStatus::Rejected, 'rejection_reason' => 'Blurry photo'])->save();
        $upload();
        $this->assertSame(2, ProviderDocument::query()->count(), 'reviewed documents stay as history');
        $this->getJson('/api/v1/provider/documents')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.1.rejection_reason', 'Blurry photo');
        $this->assertSame($type->id, ProviderDocument::query()->latest('id')->firstOrFail()->document_type_id);
    }

    public function test_a_vehicle_document_needs_one_of_my_vehicles(): void
    {
        $provider = $this->makeProvider();
        $other = $this->makeProvider();
        $this->vehicleDocumentType(['code' => 'registration']);
        $mine = $this->vehicleFor($provider);
        $theirs = $this->vehicleFor($other);
        $this->actingAsProvider($provider);
        $post = fn (?string $vehicle) => $this->postJson('/api/v1/provider/documents', ['document_type' => 'registration', 'file' => $this->photo()] + ($vehicle === null ? [] : ['vehicle_id' => $vehicle]), $this->idempotencyHeaders());

        $post(null)->assertStatus(422)->assertJsonPath('errors._code', 'document_vehicle_required');
        $post($theirs->uuid)->assertStatus(422)->assertJsonPath('errors._code', 'document_vehicle_required');
        $post($mine->uuid)->assertCreated()->assertJsonPath('data.vehicle_id', $mine->uuid);
    }

    public function test_the_file_is_only_reachable_through_a_signed_url_of_its_owner(): void
    {
        $provider = $this->makeProvider();
        $intruder = $this->makeProvider();
        $this->documentType(['code' => 'id_card']);
        $this->actingAsProvider($provider);
        $created = $this->postJson('/api/v1/provider/documents', ['document_type' => 'id_card', 'file' => $this->photo()], $this->idempotencyHeaders())->assertCreated();
        $uuid = $created->json('data.id');
        $signed = $created->json('data.file_url');

        $this->assertStringContainsString('signature=', $signed);
        $this->assertStringContainsString('expires=', $signed);
        $this->getJson($signed)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->getJson("/api/v1/provider/documents/{$uuid}/file")->assertForbidden();
        $this->getJson($signed.'x')->assertForbidden();

        $this->actingAsProvider($intruder);
        $this->getJson("/api/v1/provider/documents/{$uuid}")->assertForbidden();
        $this->getJson($signed)->assertForbidden();
        $this->getJson('/api/v1/provider/documents')->assertOk()->assertJsonCount(0, 'data');

        $this->travel(6)->minutes();
        $this->actingAsProvider($provider);
        $this->getJson($signed)->assertForbidden();
    }

    public function test_uploads_are_locked_while_the_application_is_suspended_or_being_deleted(): void
    {
        $provider = $this->makeProvider(ProviderStatus::PermanentlySuspended);
        $this->documentType(['code' => 'id_card']);
        $this->actingAsProvider($provider);

        $this->postJson('/api/v1/provider/documents', ['document_type' => 'id_card', 'file' => $this->photo()], $this->idempotencyHeaders())
            ->assertStatus(422)->assertJsonPath('errors._code', 'application_locked');
        $this->assertSame([], Storage::disk('private')->allFiles());
    }

    public function test_a_suspended_account_cannot_upload_but_can_still_read(): void
    {
        $provider = $this->makeProvider(ProviderStatus::Approved, user: ['status' => \App\Enums\Identity\UserStatus::Suspended]);
        $this->documentType(['code' => 'id_card']);
        $this->actingAsProvider($provider);

        $this->postJson('/api/v1/provider/documents', ['document_type' => 'id_card', 'file' => $this->photo()], $this->idempotencyHeaders())->assertForbidden()->assertJsonPath('errors._code', 'account_suspended');
        $this->getJson('/api/v1/provider/application')->assertOk();
    }

    public function test_the_rate_limit_applies_to_uploads(): void
    {
        $provider = $this->makeProvider();
        $this->documentType(['code' => 'id_card']);
        $this->actingAsProvider($provider);
        app(\App\Services\Settings\SettingsService::class)->set(\App\Enums\Settings\SettingKey::RateLimitProviderUploadsPerMinute, 2);

        foreach (range(1, 2) as $_) {
            $this->postJson('/api/v1/provider/documents', ['document_type' => 'id_card', 'file' => $this->photo()], $this->idempotencyHeaders())->assertCreated();
        }

        $this->postJson('/api/v1/provider/documents', ['document_type' => 'id_card', 'file' => $this->photo()], $this->idempotencyHeaders())->assertStatus(429);
    }

    public function test_a_user_without_an_application_cannot_upload(): void
    {
        $this->documentType(['code' => 'id_card']);
        $this->actingAsProvider(User::factory()->create());

        $this->postJson('/api/v1/provider/documents', ['document_type' => 'id_card', 'file' => $this->photo()], $this->idempotencyHeaders())->assertForbidden();
    }
}
