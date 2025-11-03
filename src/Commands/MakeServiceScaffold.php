<?php

namespace erdikoroglu\LaravelServiceScaffold\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MakeServiceScaffold extends Command
{
    protected $signature = 'make:service {name}';
    protected $description = 'Model, Repository, Interface, Service, Request ve Livewire bileşenlerini (sınıf + Blade) otomatik oluşturur. Volt kullanılmaz.';

    private Filesystem $files;
    private string $baseName;
    private string $namespace;
    /** @var array<int, array{name:string,type:string,unsigned:bool,length:?int,precision:?int,scale:?int,nullable:bool,hasDefault:bool,default:?string,constrained:bool,onDeleteCascade:bool,references:?string,on:?string}> */
    private array $columns = [];

    public function __construct()
    {
        parent::__construct();
        $this->files = new Filesystem();
    }

    public function handle(): int
    {
        $name = trim($this->argument('name'));
        $this->baseName = class_basename($name);
        $this->namespace = Str::replaceLast("\\{$this->baseName}", '', $name);

        $this->createModel();
        $this->buildMigrationInteractively();
        $this->ensureModelGuarded();
        $this->createInterface();
        $this->createRepository();
        $this->createService();
        $this->createRequests();
        $this->bindInAppServiceProvider();

        $this->info("\n🎉 Tüm {$this->baseName} sınıfları başarıyla oluşturuldu!");
        return Command::SUCCESS;
    }

    // ---------------------------------------------------------
    // 1️⃣ MODEL
    // ---------------------------------------------------------
    private function createModel(): void
    {
        $this->call('make:model', [
            'name' => "{$this->namespace}/{$this->baseName}",
            '--migration' => true,
            '--factory' => true,
        ]);

        $this->info("✅ Model ve migration oluşturuldu: {$this->baseName}");
    }

    // ---------------------------------------------------------
    // 2️⃣ INTERFACE
    // ---------------------------------------------------------
    private function createInterface(): void
    {
        $dir = app_path('Contracts');
        $this->files->ensureDirectoryExists($dir);

        $path = "{$dir}/{$this->baseName}Interface.php";

        $stub = <<<PHP
<?php

namespace App\Contracts;

use Illuminate\\Database\\Eloquent\\{Model, Collection};
use Illuminate\\Pagination\\LengthAwarePaginator;

interface {$this->baseName}Interface
{
    public function all(): Collection;
    public function paginate(int \$perPage = 15, array \$filters = []): LengthAwarePaginator;
    public function find(int|string \$id): ?Model;
    public function findOrFail(int|string \$id): Model;
    public function store(array \$data): Model;
    public function update(int|string \$id, array \$data): bool;
    public function destroy(int|string \$id): bool;
    public function restore(int|string \$id): bool;
    public function exists(array \$conditions): bool;
    public function pluck(string \$column, ?string \$key = null): Collection;
    public function updateToggle(int|string \$id, array \$attributes): bool;
}
PHP;

        $this->files->put($path, $stub);
        $this->info("📘 Interface oluşturuldu: {$this->baseName}Interface");
    }

    // ---------------------------------------------------------
    // 3️⃣ REPOSITORY
    // ---------------------------------------------------------
    private function createRepository(): void
    {
        $dir = app_path('Repositories');
        $this->files->ensureDirectoryExists($dir);

        $path = "{$dir}/{$this->baseName}Repository.php";

        $stub = <<<PHP
<?php

namespace App\Repositories;

use App\\Models\\{$this->namespace}\\{$this->baseName};
use App\\Contracts\\{$this->baseName}Interface;
use Illuminate\\Database\\Eloquent\\{Model, Collection};
use Illuminate\\Pagination\\LengthAwarePaginator;

class {$this->baseName}Repository implements {$this->baseName}Interface
{
    public function all(): Collection
    {
        return {$this->baseName}::all();
    }

    public function paginate(int \$perPage = 15, array \$filters = []): LengthAwarePaginator
    {
        \$query = {$this->baseName}::query();

        foreach (\$filters as \$field => \$value) {
            if (!empty(\$value)) {
                \$query->where(\$field, 'like', "%{\$value}%");
            }
        }

        return \$query->paginate(\$perPage);
    }

    public function find(int|string \$id): ?Model
    {
        return {$this->baseName}::find(\$id);
    }

    public function findOrFail(int|string \$id): Model
    {
        return {$this->baseName}::findOrFail(\$id);
    }

    public function store(array \$data): Model
    {
        return {$this->baseName}::create(\$data);
    }

    public function update(int|string \$id, array \$data): bool
    {
        return {$this->baseName}::findOrFail(\$id)->update(\$data);
    }

    public function destroy(int|string \$id): bool
    {
        return (bool) {$this->baseName}::destroy(\$id);
    }

    public function restore(int|string \$id): bool
    {
        return (bool) {$this->baseName}::withTrashed()->findOrFail(\$id)->restore();
    }

    public function exists(array \$conditions): bool
    {
        return {$this->baseName}::where(\$conditions)->exists();
    }

    public function pluck(string \$column, ?string \$key = null): Collection
    {
        return {$this->baseName}::pluck(\$column, \$key);
    }

    public function updateToggle(int|string \$id, array \$attributes): bool
    {
        \$model = {$this->baseName}::findOrFail(\$id);

        foreach (\$attributes as \$key => \$value) {
            if (\$key === 'id') continue;
            \$model->{\$key} = is_null(\$value) ? ! (bool) \$model->{\$key} : \$value;
        }

        return \$model->save();
    }
}
PHP;

        $this->files->put($path, $stub);
        $this->info("🧩 Repository oluşturuldu: {$this->baseName}Repository");
    }

    // ---------------------------------------------------------
    // 4️⃣ SERVICE
    // ---------------------------------------------------------
    private function createService(): void
    {
        $dir = app_path('Services');
        $this->files->ensureDirectoryExists($dir);

        $path = "{$dir}/{$this->baseName}Service.php";

        $stub = <<<PHP
<?php

namespace App\Services;

use App\\Contracts\\{$this->baseName}Interface;
use Illuminate\\Database\\Eloquent\\{Collection, Model};
use Illuminate\\Pagination\\LengthAwarePaginator;
use InvalidArgumentException;

class {$this->baseName}Service
{
    public function __construct(public {$this->baseName}Interface \$repository)
    {
    }

    public function all(): Collection
    {
        return \$this->repository->all();
    }

    public function paginate(array \$filters = []): LengthAwarePaginator
    {
        return \$this->repository->paginate(15, \$filters);
    }

    public function find(int|string \$id): ?Model
    {
        return \$this->repository->find(\$id);
    }

    public function store(array \$data): Model
    {
        return \$this->repository->store(\$data);
    }

    public function update(int|string \$id, array \$data): bool
    {
        return \$this->repository->update(\$id, \$data);
    }

    public function destroy(int|string \$id): bool
    {
        return \$this->repository->destroy(\$id);
    }

    public function restore(int|string \$id): bool
    {
        return \$this->repository->restore(\$id);
    }

    public function exists(array \$conditions): bool
    {
        return \$this->repository->exists(\$conditions);
    }

    public function updateToggle(array \$data): bool
    {
        \$id = \$data['id'] ?? null;
        if (!\$id) {
            throw new InvalidArgumentException('ID alanı zorunludur.');
        }

        return \$this->repository->updateToggle(\$id, \$data);
    }
}
PHP;

        $this->files->put($path, $stub);
        $this->info("⚙️ Service oluşturuldu: {$this->baseName}Service");
    }

    // ---------------------------------------------------------
    // 5️⃣ REQUESTS
    // ---------------------------------------------------------
    private function createRequests(): void
    {
        $requestDir = app_path("Http/Requests/{$this->baseName}");
        $this->files->ensureDirectoryExists($requestDir);

        $types = ['Store', 'Update', 'Delete', 'Search', 'Show', 'Restore', 'Toggle'];

        foreach ($types as $type) {
            $path = "{$requestDir}/{$this->baseName}{$type}Request.php";
            $stub = <<<PHP
<?php

namespace App\\Http\\Requests\\{$this->baseName};

use Illuminate\\Foundation\\Http\\FormRequest;

class {$this->baseName}{$type}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
PHP;
            $this->files->put($path, $stub);
            $this->info("📝 Request oluşturuldu: {$this->baseName}{$type}Request");
        }
    }

    // ---------------------------------------------------------
    // 6️⃣ SERVICE PROVIDER BINDING
    // ---------------------------------------------------------
    private function bindInAppServiceProvider(): void
    {
        $providerPath = app_path('Providers/AppServiceProvider.php');
        if (! $this->files->exists($providerPath)) return;

        $bindCode = "\$this->app->bind(\\App\\Contracts\\{$this->baseName}Interface::class, \\App\\Repositories\\{$this->baseName}Repository::class);";
        $content = $this->files->get($providerPath);

        if (!Str::contains($content, $bindCode)) {
            // register() metodunun içine binding'i ekle
            $pattern = '/(public function register\(\s*\)\s*:\s*void\s*\{)/';
            $replacement = "$1\n        {$bindCode}\n";

            $content = preg_replace($pattern, $replacement, $content, 1);

            if ($content) {
                $this->files->put($providerPath, $content);
                $this->info("🔗 AppServiceProvider'a binding eklendi");
            }
        }
    }

    /**
     * Kullanıcıdan sütun bilgilerini tek tek alarak oluşturulan migration dosyasına ekler.
     */
    private function buildMigrationInteractively(): void
    {
        $table = $this->detectTableName();
        $migrationPath = $this->findMigrationPath($table);
        $tableExists = Schema::hasTable($table);

        if (!$migrationPath && !$tableExists) {
            $this->warn("⚠️ Migration dosyası bulunamadı ve tablo da mevcut değil. Lütfen 'php artisan make:model {$this->baseName} -m' ile migration oluşturun.");
            return;
        }

        $this->info("\n🧱 {$table} tablosu için sütunları oluşturalım. Çıkmak için isim kısmını boş bırakabilirsiniz.");

        $columns = [];
        $types = [
            'id', 'uuid', 'string', 'text', 'longText', 'integer', 'bigInteger', 'tinyInteger', 'boolean',
            'decimal', 'float', 'double', 'date', 'datetime', 'timestamp', 'json', 'foreignId',
        ];

        while (true) {
            $name = trim((string)$this->ask('Sütun adı (boş bırak çıkış)'));
            if ($name === '') break;

            $type = $this->choice('Sütun tipi', $types, 'string');
            $unsigned = in_array($type, ['integer', 'bigInteger', 'tinyInteger', 'decimal', 'float', 'double'], true)
                ? $this->confirm('Unsigned olsun mu?', false)
                : false;

            $length = null;
            if ($type === 'string') {
                $lengthInput = trim((string)$this->ask('Uzunluk (varsayılan: 255)', '255'));
                $length = ctype_digit($lengthInput) ? (int)$lengthInput : 255;
            }

            $precision = null;
            $scale = null;
            if ($type === 'decimal') {
                $precision = (int)$this->ask('Decimal precision (varsayılan: 8)', '8');
                $scale = (int)$this->ask('Decimal scale (varsayılan: 2)', '2');
            }

            $nullable = $this->confirm('Boş bırakılabilir (nullable) olsun mu?', false);
            $unique = $this->confirm('Benzersiz (unique) olsun mu?', false);
            $hasDefault = $this->confirm('Varsayılan değer verilsin mi?', false);
            $default = $hasDefault ? (string)$this->ask('Varsayılan değer') : null;

            $constrained = false;
            $onDeleteCascade = false;
            $references = null;
            $on = null;
            if ($type === 'foreignId') {
                $references = (string)$this->ask('Hangi kolonu referans alacak? (varsayılan: id)', 'id');
                $on = (string)$this->ask('Hangi tabloya bağlanacak? (ör. users)');
                $constrained = $this->confirm('constrained() eklensin mi?', true);
                $onDeleteCascade = $this->confirm('onDelete("cascade") eklensin mi?', true);
            }

            $columns[] = compact(
                'name', 'type', 'unsigned', 'length', 'precision', 'scale',
                'nullable', 'unique', 'hasDefault', 'default',
                'constrained', 'onDeleteCascade', 'references', 'on'
            );

            if (!$this->confirm('Başka sütun eklemek istiyor musunuz?', true)) break;
        }

        $useSoftDeletes = $this->confirm("\n🕊️ SoftDeletes sütunu (deleted_at) eklensin mi?", true);

        $this->columns = $columns;
        $this->columns[] = ['name' => 'deleted_at', 'type' => 'softDeletes', 'unsigned' => false, 'nullable' => true] ?? [];

        if (empty($columns) && !$useSoftDeletes) {
            $this->info('⏭️ Herhangi bir sütun eklenmedi.');
            return;
        }

        // ✅ Eğer tablo zaten varsa → yeni migration oluştur
        if ($tableExists) {
            $timestamp = now()->format('Y_m_d_His');
            $migrationName = "{$timestamp}_update_{$table}_table.php";
            $path = database_path("migrations/{$migrationName}");

            $existingCols = Schema::getColumnListing($table);
            $lines = [];

            foreach ($columns as $col) {
                $line = $this->generateColumnLine($col);
                if (in_array($col['name'], $existingCols, true)) {
                    $line = preg_replace('/;$/', '->change();', $line);
                }
                $lines[] = $line;
            }

            if ($useSoftDeletes && !in_array('deleted_at', $existingCols, true)) {
                $lines[] = "\$table->softDeletes();";
            }

            $body = implode("\n            ", $lines);
            $stub = <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            {$body}
        });
    }

    public function down(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            // Geri alma işlemleri
        });
    }
};
PHP;
            file_put_contents($path, $stub);
            $this->info("🔁 Yeni migration oluşturuldu: " . basename($path));

            // 🆕 tablo zaten varsa model fillable'ı da güncelle
            $this->ensureModelGuarded(true);
            if ($useSoftDeletes) {
                $this->addSoftDeletesToModel();
            }
            return;
        }

        // ✅ tablo yoksa create migration’ı güncelle
        $lines = array_map(fn($c) => $this->generateColumnLine($c), $columns);
        if ($useSoftDeletes) $lines[] = "\$table->softDeletes();";

        $this->insertColumnsIntoMigration($migrationPath, $lines);
        $this->info("🧩 Migration güncellendi: " . basename($migrationPath));

        // 🆕 Model'e SoftDeletes eklendi
        if ($useSoftDeletes) {
            $this->addSoftDeletesToModel();
        }
    }
    private function addSoftDeletesToModel(): void
    {
        $relative = 'Models/' . ($this->namespace ? str_replace('\\', '/', $this->namespace) . '/' : '') . $this->baseName . '.php';
        $modelPath = app_path($relative);
        if (!$this->files->exists($modelPath)) return;

        $content = $this->files->get($modelPath);
        if (!Str::contains($content, 'use SoftDeletes')) {
            $content = preg_replace(
                '/namespace\s+App[^\n]+;/',
                "$0\n\nuse Illuminate\\Database\\Eloquent\\SoftDeletes;",
                $content
            );

            $content = preg_replace(
                '/(class\s+' . preg_quote($this->baseName, '/') . '\s+extends\s+Model\s*\{)/',
                "$1\n    use SoftDeletes;\n",
                $content,
                1
            );

            $this->files->put($modelPath, $content);
            $this->info('🕊️ SoftDeletes trait eklendi: ' . $relative);
        }
    }
    private function ensureModelGuarded(bool $appendOnly = false): void
    {
        $relative = 'Models/' . ($this->namespace ? str_replace('\\', '/', $this->namespace) . '/' : '') . $this->baseName . '.php';
        $modelPath = app_path($relative);
        if (!$this->files->exists($modelPath)) return;

        $content = $this->files->get($modelPath);
        $fields = $this->extractRenderableFields();
        $fillables = array_map(fn($f) => $f['name'], $fields);

        if (Str::contains($content, 'protected $fillable')) {
            if ($appendOnly) {
                // 🆕 mevcut fillable varsa yenileri ekle
                preg_match('/protected \$fillable\s*=\s*\[([^\]]*)\];/', $content, $m);
                $existing = isset($m[1]) ? array_map('trim', explode(',', str_replace(["'", '"'], '', $m[1]))) : [];
                $merged = array_unique(array_merge($existing, $fillables));
                $replacement = "protected \$fillable = ['" . implode("', '", array_filter($merged)) . "'];";
                $content = preg_replace('/protected \$fillable\s*=\s*\[[^\]]*\];/', $replacement, $content);
                $this->files->put($modelPath, $content);
                $this->info("🆕 Model fillable alanları güncellendi: {$this->baseName}");
            }
            return;
        }

        $fillableLine = '    protected $fillable = [' . (count($fillables) ? "'" . implode("', '", $fillables) . "'" : '') . '];';
        $content = preg_replace('/(class\s+' . preg_quote($this->baseName, '/') . '\s+extends\s+Model\s*\{)/', "$1\n\n{$fillableLine}\n", $content, 1);
        if ($content !== null) {
            $this->files->put($modelPath, $content);
            $this->info('🛡️ Model fillable alanları eklendi: ' . $relative);
        }
    }

    /**
     * Model isminden tablo adını tahmin eder.
     */
    private function detectTableName(): string
    {
        return Str::snake(Str::pluralStudly($this->baseName));
    }

    /**
     * Oluşturulan migration dosyasının yolunu bulur.
     */
    private function findMigrationPath(string $table): ?string
    {
        $pattern = database_path('migrations/*create_' . $table . '_table.php');
        $matches = glob($pattern);
        if ($matches && count($matches) > 0) {
            // En eski-ilk bulunan genelde model komutu ile oluşturulandır. Sonuncusunu da alabiliriz.
            return (string) end($matches);
        }

        // Bulunamazsa içinde tablo adı geçen en yeni migration dosyasını ara
        $all = glob(database_path('migrations/*.php')) ?: [];
        rsort($all);
        foreach ($all as $file) {
            $content = file_get_contents($file);
            if ($content !== false && str_contains($content, "Schema::create('{$table}'") ) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Verilen sütun bilgisine göre Blueprint satırı oluşturur.
     *
     * @param array{name:string,type:string,unsigned:bool,length:?int,precision:?int,scale:?int,nullable:bool,hasDefault:bool,default:?string,constrained:bool,onDeleteCascade:bool,references:?string,on:?string} $col
     */
    private function generateColumnLine(array $col): string
    {
        $name = $col['name'];
        $type = $col['type'];

        if ($type === 'id') {
            $line = "            $" . "table->id();";
            return $line;
        }

        if ($type === 'uuid') {
            $line = "            $" . "table->uuid('{$name}')";
        } elseif ($type === 'string') {
            $len = $col['length'] ?? 255;
            $line = "            $" . "table->string('{$name}', {$len})";
        } elseif ($type === 'decimal') {
            $precision = $col['precision'] ?? 8;
            $scale = $col['scale'] ?? 2;
            $line = "            $" . "table->decimal('{$name}', {$precision}, {$scale})";
        } elseif (in_array($type, ['integer', 'bigInteger', 'tinyInteger'], true)) {
            $method = $type;
            $line = "            $" . "table->{$method}('{$name}')";
            if ($col['unsigned']) {
                $line .= "->unsigned()";
            }
        } elseif (in_array($type, ['float', 'double'], true)) {
            $line = "            $" . "table->{$type}('{$name}')";
        } elseif (in_array($type, ['text', 'longText', 'json'], true)) {
            $line = "            $" . "table->{$type}('{$name}')";
        } elseif (in_array($type, ['date', 'datetime', 'timestamp'], true)) {
            $line = "            $" . "table->{$type}('{$name}')";
        } elseif ($type === 'foreignId') {
            // foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $line = "            $" . "table->foreignId('{$name}')";
            if ($col['constrained'] && ! empty($col['on'])) {
                $line .= "->constrained('{$col['on']}')";
            } elseif ($col['constrained']) {
                $line .= "->constrained()";
            }
            if (! empty($col['references']) && ! empty($col['on'])) {
                // explicit references/on chain
                $line = "            $" . "table->foreignId('{$name}')->references('{$col['references']}')->on('{$col['on']}')";
            }
            if ($col['onDeleteCascade']) {
                $line .= "->cascadeOnDelete()";
            }
        } else {
            // Fallback generic column
            $line = "            $" . "table->{$type}('{$name}')";
        }

        if ($col['nullable']) {
            $line .= "->nullable()";
        }
        if ($col['unique']) {
            $line .= "->unique()";
        }

        if ($col['hasDefault']) {
            $defaultExpr = $this->normalizeDefault($col['default'] ?? '', $type);
            $line .= "->default({$defaultExpr})";
        }

        $line .= ";";

        return $line;
    }

    /**
     * Varsayılan değeri uygun PHP koduna dönüştürür.
     */
    private function normalizeDefault(string $input, string $type): string
    {
        $lower = strtolower(trim($input));
        if ($lower === 'null') {
            return 'null';
        }
        if (in_array($lower, ['true', 'false'], true)) {
            return $lower;
        }
        if (is_numeric($input) && ! in_array($type, ['string', 'text', 'longText', 'json', 'uuid'], true)) {
            return $input;
        }
        // now(), CURRENT_TIMESTAMP gibi fonksiyonlar için raw ifadesi
        if (preg_match('/^now\(\)$|^CURRENT_TIMESTAMP$/i', $input) === 1) {
            return '\\DB::raw(\'CURRENT_TIMESTAMP\')';
        }

        // string
        return "'" . str_replace("'", "\\'", $input) . "'";
    }

    /**
     * Üretilen sütun satırlarını migration dosyasına en uygun yere ekler.
     */
    private function insertColumnsIntoMigration(string $path, array $lines): void
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return;
        }

        $insertion = implode("\n", $lines) . "\n";

        // 1) timestamps varsa onun ÖNCESİNE koy
        if (str_contains($content, '$table->timestamps();')) {
            $content = str_replace('$table->timestamps();', $insertion . '            $table->timestamps();', $content);
        } else {
            // 2) Schema::create kapanışından önce ekle
            $content = preg_replace(
                '/(Schema::create\(.*?function \(.*?\$table\) \{)(\s*)/s',
                "$1$2" . $insertion,
                $content,
                1
            );
        }

        file_put_contents($path, $content);
    }

    // ---------------------------------------------------------
    // Yardımcılar: Model ve Volt içerik üretimi
    // ---------------------------------------------------------


    /**
     * Form ve listede kullanılacak alanları çıkarır.
     *
     * @return array<int, array{name:string,label:string,input:string}>
     */
    private function extractRenderableFields(): array
    {
        $skip = ['id', 'created_at', 'updated_at', 'deleted_at'];
        $map = [];
        foreach ($this->columns as $c) {
            $name = $c['name'] ?? '';
            if ($name === '' || in_array($name, $skip, true)) {
                continue;
            }

            $type = $c['type'] ?? 'string';
            $input = 'text';
            if (in_array($type, ['text', 'longText'], true)) {
                $input = 'textarea';
            } elseif (in_array($type, ['integer', 'bigInteger', 'tinyInteger', 'float', 'double', 'decimal'], true)) {
                $input = 'number';
            } elseif ($type === 'boolean') {
                $input = 'checkbox';
            } elseif ($type === 'date') {
                $input = 'date';
            } elseif (in_array($type, ['datetime', 'timestamp'], true)) {
                $input = 'datetime-local';
            }

            $map[] = [
                'name' => $name,
                'label' => Str::title(str_replace('_', ' ', $name)),
                'input' => $input,
            ];
        }

        return $map;
    }

}

