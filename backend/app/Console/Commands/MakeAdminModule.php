<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Scaffolds a complete admin module (Plan §5.5) from stubs/admin-module/*.stub so every module has the same
 * shape: Model + migration + factory, status enum, input/filter DTOs, repository (interface + Eloquent +
 * binding), service, policy, Store/Update/Destroy requests, controller, DataTable, views, routes file, menu
 * entry, permissions, morph-map alias, ar/en translations and a Feature test (Admin Module Contract).
 *
 * Shared files are edited at `// @admin-module:*` markers. The output passes Pint and PHPStan unmodified.
 */
class MakeAdminModule extends Command
{
    protected $signature = 'make:admin-module
        {name : Model name in StudlyCase, e.g. Coupon}
        {--module= : Module namespace segment (App\\Enums\\<Module>, App\\Services\\<Module>, …)}
        {--translatable=* : Translatable (ar/en JSON) text fields; comma-separated or repeated}
        {--enum-status= : Generate a status enum with this class name, e.g. CouponStatus}
        {--group=system : Sidebar group key from config/admin_menu.php}
        {--title-en= : Plural English title}
        {--title-ar= : Plural Arabic title}
        {--singular-en= : Singular English name}
        {--singular-ar= : Singular Arabic name}
        {--force : Overwrite files that already exist}
        {--base-path= : Write into another root (used by the generator tests)}';

    protected $description = 'Generate a full admin module (Plan §5.5) from stubs/admin-module';

    /** @var array<string, string> */
    private array $replacements = [];

    /** @var array<string, bool> */
    private array $flags = [];

    /** @var list<string> */
    private array $written = [];

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = Str::studly((string) $this->argument('name'));
        $module = Str::studly((string) ($this->option('module') ?: $name));

        if (! preg_match('/^[A-Z][A-Za-z0-9]+$/', $name) || ! preg_match('/^[A-Z][A-Za-z0-9]+$/', $module)) {
            $this->components->error('Name and --module must be StudlyCase identifiers.');

            return self::FAILURE;
        }

        $translatable = $this->translatableFields();
        $statusEnum = $this->option('enum-status') ? Str::studly((string) $this->option('enum-status')) : null;

        if (! array_key_exists((string) $this->option('group'), (array) config('admin_menu.groups', []))) {
            $this->components->error('Unknown --group. Use one of: '.implode(', ', array_keys((array) config('admin_menu.groups', []))));

            return self::FAILURE;
        }

        $this->prepare($name, $module, $translatable, $statusEnum);

        try {
            $this->generateFiles($statusEnum !== null);
            $this->updateSharedFiles($statusEnum !== null);
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Admin module [{$name}] generated.");
        $this->components->bulletList($this->written);
        $this->components->warn('Next: translate the Arabic strings if --title-ar/--singular-ar were not given, run `php artisan migrate`, then `php artisan test --filter='.$name.'AdminTest`.');

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function translatableFields(): array
    {
        $fields = [];

        foreach ((array) $this->option('translatable') as $value) {
            foreach (explode(',', (string) $value) as $field) {
                $field = Str::snake(trim($field));

                if ($field !== '' && preg_match('/^[a-z][a-z0-9_]*$/', $field)) {
                    $fields[] = $field;
                }
            }
        }

        return array_values(array_unique($fields));
    }

    /**
     * @param list<string> $translatable
     */
    private function prepare(string $name, string $module, array $translatable, ?string $statusEnum): void
    {
        $plural = Str::pluralStudly($name);
        $display = $translatable[0] ?? 'name';
        $langKey = Str::snake($plural);
        $titleEn = (string) ($this->option('title-en') ?: Str::headline($plural));
        $singularEn = (string) ($this->option('singular-en') ?: Str::headline($name));

        $this->flags = [
            'status' => $statusEnum !== null,
            'translatable' => $translatable !== [],
        ];

        $this->replacements = [
            'module' => $module,
            'model' => $name,
            'modelVar' => Str::camel($name),
            'modelPlural' => $plural,
            'modelPluralVar' => Str::camel($plural),
            'table' => Str::snake($plural),
            'routeName' => Str::kebab($plural),
            'viewDir' => Str::kebab($plural),
            'langKey' => $langKey,
            'permGroup' => $langKey,
            'permView' => $plural.'View',
            'permManage' => $plural.'Manage',
            'permExport' => $plural.'Export',
            'tableId' => Str::kebab($plural).'-table',
            'morphAlias' => Str::snake($name),
            'statusEnum' => (string) $statusEnum,
            'statusEnumSnake' => Str::snake((string) $statusEnum),
            'statusDefault' => 'draft',
            'statusDefaultCase' => 'Draft',
            'display' => $display,
            'titleEn' => $titleEn,
            'titleAr' => (string) ($this->option('title-ar') ?: $titleEn),
            'singularEn' => $singularEn,
            'singularAr' => (string) ($this->option('singular-ar') ?: $singularEn),
        ] + $this->fragments($translatable, $display, Str::camel($name), $langKey);
    }

    /**
     * Code fragments that depend on the field list.
     *
     * @param list<string> $translatable
     * @return array<string, string>
     */
    private function fragments(array $translatable, string $display, string $modelVar, string $langKey): array
    {
        $fields = $translatable !== [] ? $translatable : ['name'];
        $isTranslatable = $translatable !== [];
        $out = array_fill_keys([
            'docProperties', 'migrationColumns', 'factoryDefinition', 'dtoProperties', 'dtoFromRequest', 'dtoAttributes',
            'rules', 'attributeNames', 'formFields', 'showFields', 'validPayload',
        ], []);

        foreach ($fields as $field) {
            $prop = Str::camel($field);
            $label = "__('admin.{$langKey}.fields.{$field}')";

            if ($isTranslatable) {
                $out['docProperties'][] = " * @property string \${$field} translated in the current locale; raw JSON {ar, en}";
                $out['migrationColumns'][] = "            \$table->json('{$field}');";
                $out['factoryDefinition'][] = "            '{$field}' => ['ar' => fake('ar_SA')->words(3, true), 'en' => fake()->words(3, true)],";
                $out['dtoProperties'][] = "        /** @var array{ar: string, en?: string|null} */\n        public array \${$prop},";
                $out['dtoFromRequest'][] = "            {$prop}: array_filter((array) \$data['{$field}'], static fn (mixed \$value): bool => \$value !== null && \$value !== ''),";
                $out['rules'][] = "            '{$field}' => ['required', 'array'],\n            '{$field}.ar' => ['required', 'string', 'max:191'],\n            '{$field}.en' => ['nullable', 'string', 'max:191'],";
                $out['attributeNames'][] = "            '{$field}.ar' => {$label}.' (ar)',\n            '{$field}.en' => {$label}.' (en)',";
                $out['formFields'][] = "    <x-admin.form.translatable-input name=\"{$field}\" :label=\"{$label}\" :value=\"\$model?->getTranslations('{$field}') ?? []\" maxlength=\"191\" required />";
                $out['showFields'][] = "                    <x-admin.detail-list.item :label=\"{$label}\" :value=\"\${$modelVar}->getTranslation('{$field}', app()->getLocale(), true)\" />";
                $out['validPayload'][] = "            '{$field}' => ['ar' => 'قيمة تجريبية', 'en' => 'Sample value'],";
            } else {
                $out['docProperties'][] = " * @property string \${$field}";
                $out['migrationColumns'][] = "            \$table->string('{$field}', 191);";
                $out['factoryDefinition'][] = "            '{$field}' => fake()->words(3, true),";
                $out['dtoProperties'][] = "        public string \${$prop},";
                $out['dtoFromRequest'][] = "            {$prop}: (string) \$data['{$field}'],";
                $out['rules'][] = "            '{$field}' => ['required', 'string', 'max:191'],";
                $out['attributeNames'][] = "            '{$field}' => {$label},";
                $out['formFields'][] = "    <x-admin.form.input name=\"{$field}\" :label=\"{$label}\" :value=\"\$model?->{$field}\" maxlength=\"191\" required />";
                $out['showFields'][] = "                    <x-admin.detail-list.item :label=\"{$label}\" :value=\"\${$modelVar}->{$field}\" />";
                $out['validPayload'][] = "            '{$field}' => 'Sample value',";
            }

            $out['dtoAttributes'][] = "            '{$field}' => \$this->{$prop},";
        }

        $rendered = array_map(static fn (array $lines): string => implode("\n", $lines), $out);

        $searchClause = $isTranslatable
            ? "                \$query->where(static fn (Builder \$inner) => \$inner\n                    ->where('{$display}->ar', 'like', \$like)\n                    ->orWhere('{$display}->en', 'like', \$like));"
            : "                \$query->where('{$display}', 'like', \$like);";

        $matchValue = $isTranslatable ? "['ar' => 'Needle عنصر', 'en' => 'Needle item']" : "'Needle item'";
        $otherValue = $isTranslatable ? "['ar' => 'آخر', 'en' => 'Other']" : "'Other'";

        return $rendered + [
            'translatableList' => implode(', ', array_map(static fn (string $field): string => "'{$field}'", $translatable)),
            'fillable' => implode(', ', array_map(static fn (string $field): string => "'{$field}'", [...$fields, ...($this->flags['status'] ? ['status'] : []), 'lock_version'])),
            'searchClause' => $searchClause,
            'searchMatchAttributes' => "'{$display}' => {$matchValue}",
            'searchOtherAttributes' => "'{$display}' => {$otherValue}",
            'requiredKeys' => implode(', ', array_map(static fn (string $field): string => $isTranslatable ? "'{$field}.ar'" : "'{$field}'", $fields)).($this->flags['status'] ? ", 'status'" : ''),
            'showTitle' => $isTranslatable
                ? "\${$modelVar}->getTranslation('{$display}', app()->getLocale(), true)"
                : "\${$modelVar}->{$display}",
        ];
    }

    private function generateFiles(bool $withStatus): void
    {
        $r = $this->replacements;
        $m = $r['module'];
        $model = $r['model'];

        $map = [
            'model' => "app/Models/{$model}.php",
            'migration' => 'database/migrations/'.date('Y_m_d_His')."_create_{$r['table']}_table.php",
            'factory' => "database/factories/{$model}Factory.php",
            'data' => "app/DTOs/{$m}/{$model}Data.php",
            'filter-data' => "app/DTOs/{$m}/{$model}FilterData.php",
            'repository-interface' => "app/Repositories/Contracts/{$m}/{$model}RepositoryInterface.php",
            'repository' => "app/Repositories/Eloquent/{$m}/{$model}Repository.php",
            'service' => "app/Services/{$m}/{$model}Service.php",
            'policy' => "app/Policies/{$model}Policy.php",
            'store-request' => "app/Http/Requests/Admin/{$m}/Store{$model}Request.php",
            'update-request' => "app/Http/Requests/Admin/{$m}/Update{$model}Request.php",
            'destroy-request' => "app/Http/Requests/Admin/{$m}/Destroy{$model}Request.php",
            'controller' => "app/Http/Controllers/Admin/{$m}/{$model}Controller.php",
            'datatable' => "app/DataTables/Admin/{$m}/{$r['modelPlural']}DataTable.php",
            'routes' => "routes/admin/{$r['routeName']}.php",
            'view-index' => "resources/views/admin/{$r['viewDir']}/index.blade.php",
            'view-create' => "resources/views/admin/{$r['viewDir']}/create.blade.php",
            'view-edit' => "resources/views/admin/{$r['viewDir']}/edit.blade.php",
            'view-show' => "resources/views/admin/{$r['viewDir']}/show.blade.php",
            'view-form' => "resources/views/admin/{$r['viewDir']}/_form.blade.php",
            'test' => "tests/Feature/Admin/{$m}/{$model}AdminTest.php",
        ];

        if ($withStatus) {
            $map['enum'] = "app/Enums/{$m}/{$r['statusEnum']}.php";
        }

        $existingMigration = $this->files->glob($this->path("database/migrations/*_create_{$r['table']}_table.php"));

        if ($existingMigration !== [] && ! $this->option('force')) {
            throw new RuntimeException("A migration creating [{$r['table']}] already exists. Use --force to regenerate the other files.");
        }

        foreach ($map as $stub => $target) {
            if ($stub === 'migration' && $existingMigration !== []) {
                continue;
            }

            $path = $this->path($target);

            if ($this->files->exists($path) && ! $this->option('force')) {
                throw new RuntimeException("[{$target}] already exists. Use --force to overwrite.");
            }

            $this->files->ensureDirectoryExists(dirname($path));
            $content = $this->render($this->files->get(base_path("stubs/admin-module/{$stub}.stub")));
            $this->files->put($path, str_ends_with($target, '.php') && ! str_ends_with($target, '.blade.php') ? $this->sortImports($content) : $content);
            $this->written[] = $target;
        }
    }

    private function updateSharedFiles(bool $withStatus): void
    {
        $r = $this->replacements;
        $m = $r['module'];
        $model = $r['model'];

        $this->insertUses('app/Providers/RepositoryServiceProvider.php', [
            "App\\Repositories\\Contracts\\{$m}\\{$model}RepositoryInterface",
            "App\\Repositories\\Eloquent\\{$m}\\{$model}Repository",
        ]);
        $this->insertAtMarker('app/Providers/RepositoryServiceProvider.php', 'bindings', "        {$model}RepositoryInterface::class => {$model}Repository::class,");

        $this->insertUses('app/Providers/AppServiceProvider.php', ["App\\Models\\{$model}"]);
        $this->insertAtMarker('app/Providers/AppServiceProvider.php', 'morph-map', "            '{$r['morphAlias']}' => {$model}::class,");

        $permissionSource = $this->files->get($this->path('app/Enums/Admin/AdminPermission.php'));
        $cases = [];

        foreach (['View' => 'view', 'Manage' => 'manage', 'Export' => 'export'] as $suffix => $action) {
            if (! str_contains($permissionSource, "'{$r['permGroup']}.{$action}'")) {
                $cases[] = "    case {$r['modelPlural']}{$suffix} = '{$r['permGroup']}.{$action}';";
            }
        }

        if ($cases !== []) {
            $this->insertAtMarker('app/Enums/Admin/AdminPermission.php', 'permissions', implode("\n", $cases));
        }

        $this->insertAtMarker('config/admin_menu.php', 'menu', implode("\n", [
            '        [',
            "            'key' => '{$r['langKey']}',",
            "            'group' => '{$this->option('group')}',",
            "            'label' => 'admin.{$r['langKey']}.menu',",
            "            'route' => 'admin.{$r['routeName']}.index',",
            "            'active' => 'admin.{$r['routeName']}.*',",
            "            'icon' => 'cube',",
            "            'permission' => App\\Enums\\Admin\\AdminPermission::{$r['permView']},",
            '        ],',
        ]));

        foreach (['en', 'ar'] as $locale) {
            $ar = $locale === 'ar';
            $enums = $this->files->get($this->path("lang/{$locale}/enums.php"));
            $groups = (string) strstr((string) strstr($enums, "'admin_permission_group' => ["), '// @admin-module:permission-groups', true);

            if (! str_contains($groups, "'{$r['permGroup']}' =>")) {
                $this->insertAtMarker("lang/{$locale}/enums.php", 'permission-groups', "        '{$r['permGroup']}' => '".$this->escape($ar ? $r['titleAr'] : $r['titleEn'])."',");
            }
            $this->insertAtMarker("lang/{$locale}/admin.php", 'lang', $this->langBlock($ar));

            if ($withStatus) {
                $labels = $ar ? ['draft' => 'مسودة', 'active' => 'نشط', 'inactive' => 'غير نشط'] : ['draft' => 'Draft', 'active' => 'Active', 'inactive' => 'Inactive'];
                $lines = ["    '{$r['statusEnumSnake']}' => ["];

                foreach ($labels as $value => $label) {
                    $lines[] = "        '{$value}' => '{$label}',";
                }

                $lines[] = '    ],';
                $lines[] = '';
                $this->insertAtMarker("lang/{$locale}/enums.php", 'enums', implode("\n", $lines));
            }
        }
    }

    private function langBlock(bool $arabic): string
    {
        $r = $this->replacements;
        $title = $this->escape($arabic ? $r['titleAr'] : $r['titleEn']);
        $singular = $this->escape($arabic ? $r['singularAr'] : $r['singularEn']);
        $fieldNames = array_filter(array_map('trim', explode(',', str_replace("'", '', $r['fillable']))), static fn (string $field): bool => $field !== 'lock_version');
        $common = $arabic
            ? ['name' => 'الاسم', 'title' => 'العنوان', 'description' => 'الوصف', 'status' => 'الحالة']
            : ['name' => 'Name', 'title' => 'Title', 'description' => 'Description', 'status' => 'Status'];

        $lines = [
            "    '{$r['langKey']}' => [",
            "        'menu' => '{$title}',",
            "        'title' => '{$title}',",
            "        'create_title' => '".($arabic ? "إضافة {$singular}" : "New {$singular}")."',",
            "        'edit_title' => '".($arabic ? "تعديل {$singular}" : "Edit {$singular}")."',",
            "        'show_title' => '{$singular}',",
            "        'created' => '".($arabic ? 'تمت الإضافة بنجاح.' : "{$singular} created.")."',",
            "        'updated' => '".($arabic ? 'تم الحفظ بنجاح.' : "{$singular} updated.")."',",
            "        'deleted' => '".($arabic ? 'تم الحذف بنجاح.' : "{$singular} deleted.")."',",
            "        'sections' => [",
            "            'basic' => '".($arabic ? 'البيانات الأساسية' : 'Basic information')."',",
            '        ],',
            "        'fields' => [",
        ];

        foreach ($fieldNames as $field) {
            $lines[] = "            '{$field}' => '".$this->escape($common[$field] ?? Str::headline($field))."',";
        }

        $lines[] = '        ],';
        $lines[] = '    ],';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Renders {{ key }} placeholders and {{#flag}}…{{/flag}} / {{^flag}}…{{/flag}} sections (whole lines).
     */
    private function render(string $stub): string
    {
        foreach ($this->flags as $flag => $enabled) {
            $stub = (string) preg_replace_callback(
                '/^[ \t]*\{\{([#^])'.$flag.'\}\}[ \t]*\n(.*?)^[ \t]*\{\{\/'.$flag.'\}\}[ \t]*\n/sm',
                static fn (array $match): string => ($match[1] === '#') === $enabled ? $match[2] : '',
                $stub,
            );
            $stub = (string) preg_replace_callback(
                '/\{\{([#^])'.$flag.'\}\}(.*?)\{\{\/'.$flag.'\}\}/s',
                static fn (array $match): string => ($match[1] === '#') === $enabled ? $match[2] : '',
                $stub,
            );
        }

        foreach ($this->replacements as $key => $value) {
            $stub = str_replace('{{ '.$key.' }}', $value, $stub);
        }

        // Fragments that rendered empty leave blank lines behind; collapse them.
        return (string) preg_replace("/\n{3,}/", "\n\n", (string) preg_replace('/^[ \t]*\n(?=[ \t]*[\]\)])/m', '', $stub));
    }

    private function insertAtMarker(string $relative, string $marker, string $content): void
    {
        $path = $this->path($relative);
        $source = $this->files->get($path);
        $needle = "// @admin-module:{$marker}";
        $position = strpos($source, $needle);

        if ($position === false) {
            throw new RuntimeException("Marker [{$needle}] not found in [{$relative}].");
        }

        if (str_contains($source, trim($content))) {
            return;
        }

        $lineStart = strrpos(substr($source, 0, $position), "\n");
        $lineStart = $lineStart === false ? 0 : $lineStart + 1;

        $block = rtrim($content, "\n")."\n".(str_ends_with($content, "\n") ? "\n" : '');
        $this->files->put($path, substr($source, 0, $lineStart).$block.substr($source, $lineStart));
        $this->written[] = "{$relative} (@{$marker})";
    }

    /**
     * Adds `use` imports and keeps the import block sorted like Pint's ordered_imports (alpha).
     *
     * @param list<string> $classes
     */
    private function insertUses(string $relative, array $classes): void
    {
        $path = $this->path($relative);
        $source = $this->files->get($path);

        if (! preg_match('/^(use [^;]+;\n)+/m', $source, $match, PREG_OFFSET_CAPTURE)) {
            throw new RuntimeException("No import block found in [{$relative}].");
        }

        $block = $match[0][0];
        $imports = array_merge(explode("\n", trim($block)), array_map(static fn (string $class): string => "use {$class};", $classes));

        $this->files->put($path, $this->sortImports(substr_replace($source, implode("\n", array_unique($imports))."\n", $match[0][1], strlen($block))));
    }

    /**
     * Sorts the leading class-import block like Pint's ordered_imports (alpha, `\` compared as a space).
     */
    private function sortImports(string $source): string
    {
        if (! preg_match('/^(use [^;(]+;\n)+/m', $source, $match, PREG_OFFSET_CAPTURE)) {
            return $source;
        }

        $imports = array_values(array_unique(array_filter(explode("\n", trim($match[0][0])))));
        usort($imports, static fn (string $a, string $b): int => strcasecmp(
            str_replace('\\', ' ', substr($a, 4, -1)),
            str_replace('\\', ' ', substr($b, 4, -1)),
        ));

        return substr_replace($source, implode("\n", $imports)."\n", $match[0][1], strlen($match[0][0]));
    }

    private function path(string $relative): string
    {
        $base = $this->option('base-path') ? rtrim((string) $this->option('base-path'), '/') : base_path();

        return $base.'/'.$relative;
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }
}
