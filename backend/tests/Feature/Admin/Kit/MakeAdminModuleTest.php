<?php

namespace Tests\Feature\Admin\Kit;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * make:admin-module writes every file of Plan §5.5 and edits the shared files at their markers.
 * (Pint/PHPStan/tests of generated modules were verified on a full copy; see docs/IMPLEMENTATION_STATUS.md.)
 */
class MakeAdminModuleTest extends TestCase
{
    private string $root;

    private Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->root = storage_path('framework/testing/generator-'.getmypid());
        $this->files->deleteDirectory($this->root);

        foreach ([
            'app/Providers/RepositoryServiceProvider.php', 'app/Providers/AppServiceProvider.php', 'app/Enums/Admin/AdminPermission.php',
            'config/admin_menu.php', 'lang/en/enums.php', 'lang/ar/enums.php', 'lang/en/admin.php', 'lang/ar/admin.php',
        ] as $file) {
            $this->files->ensureDirectoryExists(dirname($this->root.'/'.$file));
            $this->files->copy(base_path($file), $this->root.'/'.$file);
        }
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->root);

        parent::tearDown();
    }

    public function test_it_generates_a_complete_module_and_registers_it(): void
    {
        $this->artisan('make:admin-module', [
            'name' => 'Gadget',
            '--module' => 'Lab',
            '--translatable' => ['title'],
            '--enum-status' => 'GadgetStatus',
            '--group' => 'catalog',
            '--title-ar' => 'أدوات',
            '--base-path' => $this->root,
        ])->assertSuccessful();

        $expected = [
            'app/Models/Gadget.php', 'database/factories/GadgetFactory.php', 'app/Enums/Lab/GadgetStatus.php',
            'app/DTOs/Lab/GadgetData.php', 'app/DTOs/Lab/GadgetFilterData.php',
            'app/Repositories/Contracts/Lab/GadgetRepositoryInterface.php', 'app/Repositories/Eloquent/Lab/GadgetRepository.php',
            'app/Services/Lab/GadgetService.php', 'app/Policies/GadgetPolicy.php',
            'app/Http/Requests/Admin/Lab/StoreGadgetRequest.php', 'app/Http/Requests/Admin/Lab/UpdateGadgetRequest.php',
            'app/Http/Requests/Admin/Lab/DestroyGadgetRequest.php', 'app/Http/Controllers/Admin/Lab/GadgetController.php',
            'app/DataTables/Admin/Lab/GadgetsDataTable.php', 'routes/admin/gadgets.php',
            'resources/views/admin/gadgets/index.blade.php', 'resources/views/admin/gadgets/create.blade.php',
            'resources/views/admin/gadgets/edit.blade.php', 'resources/views/admin/gadgets/show.blade.php',
            'resources/views/admin/gadgets/_form.blade.php', 'tests/Feature/Admin/Lab/GadgetAdminTest.php',
        ];

        foreach ($expected as $file) {
            $this->assertFileExists($this->root.'/'.$file);

            if (str_ends_with($file, '.php') && ! str_ends_with($file, '.blade.php')) {
                $lint = new Process([PHP_BINARY, '-l', $this->root.'/'.$file]);
                $lint->run();
                $this->assertTrue($lint->isSuccessful(), $file.': '.$lint->getOutput());
                $this->assertStringNotContainsString('{{', (string) file_get_contents($this->root.'/'.$file), $file);
            }
        }

        $this->assertCount(1, $this->files->glob($this->root.'/database/migrations/*_create_gadgets_table.php'));
        $this->assertStringContainsString('GadgetRepositoryInterface::class => GadgetRepository::class,', $this->read('app/Providers/RepositoryServiceProvider.php'));
        $this->assertStringContainsString("'gadget' => Gadget::class,", $this->read('app/Providers/AppServiceProvider.php'));
        $this->assertStringContainsString("case GadgetsExport = 'gadgets.export';", $this->read('app/Enums/Admin/AdminPermission.php'));
        $this->assertStringContainsString("'route' => 'admin.gadgets.index',", $this->read('config/admin_menu.php'));
        $this->assertStringContainsString("'gadgets' => 'أدوات',", $this->read('lang/ar/enums.php'));
        $this->assertStringContainsString("'gadget_status' => [", $this->read('lang/en/enums.php'));
        $this->assertStringContainsString("'gadgets' => [", $this->read('lang/ar/admin.php'));
        $this->assertStringContainsString("->where('title->ar', 'like', \$like)", $this->read('app/Repositories/Eloquent/Lab/GadgetRepository.php'));

        foreach (['app/Providers/RepositoryServiceProvider.php', 'config/admin_menu.php', 'lang/ar/admin.php', 'app/Enums/Admin/AdminPermission.php'] as $file) {
            $lint = new Process([PHP_BINARY, '-l', $this->root.'/'.$file]);
            $lint->run();
            $this->assertTrue($lint->isSuccessful(), $file);
        }

        // Running again refuses to overwrite.
        $this->artisan('make:admin-module', ['name' => 'Gadget', '--module' => 'Lab', '--base-path' => $this->root])->assertFailed();
    }

    public function test_it_rejects_invalid_input(): void
    {
        $this->artisan('make:admin-module', ['name' => 'bad-name!', '--base-path' => $this->root])->assertFailed();
        $this->artisan('make:admin-module', ['name' => 'Thing', '--group' => 'nope', '--base-path' => $this->root])->assertFailed();
    }

    private function read(string $file): string
    {
        return (string) file_get_contents($this->root.'/'.$file);
    }
}
