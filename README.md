# Laravel Service Scaffold

[![Latest Version on Packagist](https://img.shields.io/packagist/v/erdikoroglu/laravel-service-scaffold.svg?style=flat-square)](https://packagist.org/packages/erdikoroglu/laravel-service-scaffold)
[![Total Downloads](https://img.shields.io/packagist/dt/erdikoroglu/laravel-service-scaffold.svg?style=flat-square)](https://packagist.org/packages/erdikoroglu/laravel-service-scaffold)

Laravel projelerinizde hızlı bir şekilde tam bir servis katmanı (Service Layer) oluşturmanızı sağlayan interaktif bir Artisan komutu. Bu paket, Repository Pattern'ını temel alarak Model, Migration, Factory, Interface, Repository, Service sınıfı ve çeşitli Form Request'ler (Store, Update, Delete, Search, Show, Restore, Toggle) otomatik olarak üretir. Ayrıca, AppServiceProvider'a binding ekleyerek dependency injection'ı kolaylaştırır.

Bu araç, veritabanı tablosu tasarımı sırasında interaktif prompt'lar ile sütun eklemenize izin verir. SoftDeletes desteği opsiyonel olarak eklenir ve model'in `fillable` alanları otomatik güncellenir.

## Özellikler

- **Interaktif Sütun Oluşturma**: Sütun adı, tipi (string, integer, boolean vb.), uzunluk, nullable/unique varsayılan değer ayarları için adım adım rehber.
- **Tam Scaffolding**: Aşağıdaki bileşenleri otomatik üretir:
    - Model (örneğin: `app/Models/Test/Test.php`)
    - Factory (`database/factories/Test/TestFactory.php`)
    - Migration (`database/migrations/YYYY_MM_DD_HHMMSS_create_tests_table.php`)
    - Interface (`app/Repositories/Contracts/TestInterface.php`)
    - Repository (`app/Repositories/TestRepository.php`)
    - Service (`app/Services/TestService.php`)
    - Form Request'ler:
        - `TestStoreRequest`
        - `TestUpdateRequest`
        - `TestDeleteRequest`
        - `TestSearchRequest`
        - `TestShowRequest`
        - `TestRestoreRequest`
        - `TestToggleRequest`
- **SoftDeletes Desteği**: Opsiyonel olarak `deleted_at` sütunu ve trait eklenir.
- **Dependency Injection**: AppServiceProvider'a otomatik binding (`'Test' => TestService::class`).
- **Esneklik**: Çıkış için boş bırakma seçenekleri, varsayılan değerler ve birden fazla sütun ekleme.
- **Dil Desteği**: Komut mesajları Türkçe (paket geliştiricisi tercihiyle).

## Kurulum

1. Composer ile paketi yükleyin:

   ```bash
   composer require erdikoroglu/laravel-service-scaffold
   ```

2. Laravel 10+ sürümlerinde paket otomatik olarak service provider'ı keşfedilir (auto-discovery). Eğer manuel kayıt gerekiyorsa (eski sürümlerde), `config/app.php`'ye ekleyin:

   ```php
   'providers' => [
       // ...
       Erdikoroglu\LaravelServiceScaffold\ServiceScaffoldServiceProvider::class,
   ],
   ```

3. Komut artık Artisan menüsünde görünür: `php artisan make:service`.

## Kullanım

Komutu şu şekilde çalıştırın:

```bash
php artisan make:service Test
```

### Adım Adım Süreç

Komut çalıştırıldığında interaktif bir wizard başlar:

1. **Model Oluşturma**: Model, Factory ve Migration otomatik oluşturulur.
    - Örnek çıktı:
      ```
      INFO  Model [app/Models/Test/Test.php] created successfully.
      INFO  Factory [database/factories/Test/TestFactory.php] created successfully.
      INFO  Migration [database/migrations/2025_11_03_185741_create_tests_table.php] created successfully.
      ✅ Model ve migration oluşturuldu: Test
      ```

2. **Sütun Tanımlama**:
    - **Sütun adı**: Sütun adını girin (boş bırakırsanız çıkış).
      ```
      Sütun adı (boş bırak çıkış): > title
      ```
    - **Sütun tipi**: Listeden seçin (0-16 arası numaralar):
      | Seçenek | Tip          | Açıklama                  |
      |---------|--------------|---------------------------|
      | 0      | id          | Otomatik artan primary key |
      | 1      | uuid        | UUID primary key          |
      | 2      | string      | VARCHAR (varsayılan)      |
      | 3      | text        | TEXT                      |
      | 4      | longText    | LONGTEXT                  |
      | 5      | integer     | INTEGER                   |
      | 6      | bigInteger  | BIGINT                    |
      | 7      | tinyInteger | TINYINT                   |
      | 8      | boolean     | BOOLEAN                   |
      | 9      | decimal     | DECIMAL                   |
      | 10     | float       | FLOAT                     |
      | 11     | double      | DOUBLE                    |
      | 12     | date        | DATE                      |
      | 13     | datetime    | DATETIME                  |
      | 14     | timestamp   | TIMESTAMP                 |
      | 15     | json        | JSON                      |
      | 16     | foreignId   | Foreign ID (unsignedBigInteger) |

      Örnek:
      ```
      Sütun tipi [string]: > 2  (string için)
      ```

    - **Uzunluk**: Varsayılan 255 (string için), Enter ile atlayın.
      ```
      Uzunluk (varsayılan: 255) [255]: >
      ```

    - **Nullable?**: `yes/no` (varsayılan: no).
    - **Unique?**: `yes/no` (varsayılan: no).
    - **Varsayılan Değer?**: `yes/no` (varsayılan: no). Eğer yes ise değer girin.
    - **Başka Sütun?**: `yes/no` (varsayılan: yes). No ile sütun eklemeyi bitirin.

3. **SoftDeletes**: `deleted_at` sütunu ve trait eklemek için `yes/no` (varsayılan: yes).
   ```
   🕊️ SoftDeletes sütunu (deleted_at) eklensin mi? (yes/no) [yes]: > yes
   ```

4. **Sonuç**: Migration güncellenir, model'e fillable ve SoftDeletes trait eklenir, Interface/Repository/Service oluşturulur, Request'ler üretilir ve AppServiceProvider'a binding eklenir.
    - Örnek çıktı:
      ```
      🧩 Migration güncellendi: 2025_11_03_185741_create_tests_table.php
      🕊️ SoftDeletes trait eklendi: Models/Test/Test.php
      🛡️ Model fillable alanları eklendi: Models/Test/Test.php
      📘 Interface oluşturuldu: TestInterface
      🧩 Repository oluşturuldu: TestRepository
      ⚙️ Service oluşturuldu: TestService
      📝 Request oluşturuldu: TestStoreRequest
      ... (diğer Request'ler)
      🔗 AppServiceProvider'a binding eklendi
      🎉 Tüm Test sınıfları başarıyla oluşturuldu!
      ```

### Örnek Migration Üretimi

Bir `title` (string, 255) sütunu eklediğinizde migration şöyle olur:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->softDeletes(); // Eğer seçildiyse
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
```

### Örnek Service Kullanımı

Oluşturulan `TestService` şöyle kullanılabilir (Controller'da):

```php
use App\Services\TestService;

class TestController extends Controller
{
    public function store(TestStoreRequest $request, TestService $service)
    {
        return $service->create($request->validated());
    }

    public function update(TestUpdateRequest $request, Test $test, TestService $service)
    {
        return $service->update($test, $request->validated());
    }

    // Search, delete, restore vb. için benzer
}
```

Service içinde Repository çağrıları yapılır:

```php
public function create(array $data): Test
{
    return $this->repository->create($data);
}
```

## Konfigürasyon

Şu an için ekstra konfigürasyon gerekmez. Gelecek sürümlerde migration şablonları veya dil dosyaları için publish edilebilir dosyalar eklenebilir.

## Katkı Sağlama

1. Fork'layın.
2. Değişiklikleri geliştirin (`develop` branch'inde).
3. Test edin (`php artisan test`).
4. Pull Request gönderin.

Pull Request'ler için `develop` branch'ini hedefleyin. Kod kalitesi için PHP CS Fixer ve PHPUnit kullanın.

## Lisans

MIT Lisansı. Detaylar için [LICENSE](LICENSE) dosyasını inceleyin.

## İletişim

- **Yazar**: Erdi Koroglu
- **GitHub**: [@erdikoroglu](https://github.com/erdikoroglu)
- **Packagist**: [erdikoroglu/laravel-service-scaffold](https://packagist.org/packages/erdikoroglu/laravel-service-scaffold)

Sorunlarınızı [GitHub Issues](https://github.com/erdikoroglu/laravel-service-scaffold/issues) üzerinden bildirin. Teşekkürler! 🚀