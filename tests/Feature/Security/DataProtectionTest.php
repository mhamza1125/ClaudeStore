<?php

namespace Tests\Feature\Security;

use App\Models\Category;
use App\Models\CustomerGroup;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Records must never be deleted or overwritten by migrations, seeders or scheduled jobs.
 */
class DataProtectionTest extends TestCase
{
    use RefreshDatabase;

    /** Statements that remove data. Allowed only inside a migration's down() (rollback is blocked anyway). */
    private const DESTRUCTIVE = '/->delete\(|->forceDelete\(|->truncate\(|::truncate\(|Schema::drop|->drop(Column|ConstrainedForeignId|Foreign|Index|Unique|Primary)?\(|->dropIfExists\(|DB::statement\(\s*[\'"](DELETE|DROP|TRUNCATE)|->detach\(|->sync\(|syncPermissions\(|syncRoles\(|revokePermissionTo\(|removeRole\(|updateOrCreate\(/i';

    public function test_migrations_only_add_in_up(): void
    {
        foreach (File::files(database_path('migrations')) as $file) {
            $code = $file->getContents();
            $up = preg_match('/function up\(\)[^{]*\{(.*?)\n    \}/s', $code, $m) ? $m[1] : $code;
            $this->assertDoesNotMatchRegularExpression(self::DESTRUCTIVE, $up, "{$file->getFilename()}: up() must not delete or drop anything.");
        }
    }

    public function test_seeders_never_delete_or_overwrite(): void
    {
        foreach (File::allFiles(database_path('seeders')) as $file) {
            $this->assertDoesNotMatchRegularExpression(self::DESTRUCTIVE, $file->getContents(), "{$file->getFilename()} must be create-only.");
        }
    }

    public function test_no_scheduled_record_pruning(): void
    {
        $console = File::get(base_path('routes/console.php'));
        $this->assertStringNotContainsString('prune-failed', $console);
        $this->assertStringNotContainsString('model:prune', $console);
    }

    public function test_destructive_artisan_commands_are_blocked_outside_tests(): void
    {
        $provider = File::get(app_path('Providers/AppServiceProvider.php'));
        $this->assertStringContainsString('DB::prohibitDestructiveCommands(', $provider);
        $this->assertFalse(config('database.allow_destructive_commands'));
    }

    public function test_reseeding_keeps_admin_edits(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@atelierhide.test')->sole();
        $passwordHash = $admin->password;
        $product = Product::query()->where('is_demo', true)->firstOrFail();
        $product->update(['name' => 'Edited by admin']);
        $product->variants()->first()?->update(['name' => 'Edited variant']);
        $tiers = $product->priceTiers()->count();
        $category = Category::query()->where('slug', 'leather-bags')->sole();
        $category->update(['name' => 'Bags (renamed)', 'is_active' => false]);
        CustomerGroup::query()->where('slug', 'distributor')->update(['discount_bps' => 900]);
        $counts = [Product::count(), Category::count(), User::count(), ProductImage::count()];

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($passwordHash, $admin->refresh()->password);
        $this->assertSame('Edited by admin', $product->refresh()->name);
        $this->assertSame($tiers, $product->priceTiers()->count());
        if ($product->variants()->exists()) {
            $this->assertSame('Edited variant', $product->variants()->first()->name);
        }
        $this->assertSame('Bags (renamed)', $category->refresh()->name);
        $this->assertFalse($category->is_active);
        $this->assertSame(900, CustomerGroup::query()->where('slug', 'distributor')->value('discount_bps'));
        $this->assertSame($counts, [Product::count(), Category::count(), User::count(), ProductImage::count()]);
    }
}
