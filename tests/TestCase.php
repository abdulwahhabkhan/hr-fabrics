<?php

namespace Tests;

use App\Enums\PackingType;
use App\Facades\Permission as PermissionFacade;
use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use App\Models\Catalog\Product;
use App\Models\Permission;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\FabricReceivingItem;
use App\Models\Role;
use App\Models\Stock\Inventory;
use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

use function Pest\Laravel\assertDatabaseHas;

abstract class TestCase extends BaseTestCase
{
    protected int $adminRole = 2;

    /** @deprecated
     * use the permission at each test or file level.
     * */
    protected function fakeHavePermission(): void
    {
        PermissionFacade::fake(['*' => true]);
    }

    protected function attachPermissions(User $user, array|string $uri_name): void
    {
        $uris = [];
        if (is_array($uri_name) || $uri_name instanceof Arrayable) {
            $uris = $uri_name;
        } else {
            $uris[] = $uri_name;
        }
        $permissions = Permission::query()->whereIn('name', $uris)->get();
        if ($permissions->count() === 0) {
            foreach ($uris as $uri) {
                /** @var Permission $permission */
                $permission = Permission::factory()->create(['name' => $uri]);
                $permissions->push($permission);
            }
        }
        $role = Role::query()->find($user->role_id);
        // foreach ($permissions as $permission) {
        $role->permissions()->sync($permissions);
        // }
        Role::clearCache($role->id);
    }

    protected function verifyJournalDetail($account_id, int $cr, int $dr): void
    {
        assertDatabaseHas(JournalDetail::class, [
            'account_id' => $account_id,
            'cr' => $cr,
            'dr' => $dr,
        ]);
    }

    protected function verifyJournal($resource_id, $account_id, int $dr, int $cr, ?string $detail = null): void
    {
        $journal = ['resource_id' => $resource_id];
        if ($detail) {
            $journal['detail'] = $detail;
        }
        assertDatabaseHas(Journal::class, $journal);
        $this->verifyJournalDetail($account_id, $cr, $dr);
    }

    protected function userWithoutPermissions(): User
    {
        $role = Role::factory()->create();

        return User::factory()->create(['role_id' => $role->id]);
    }

    protected function getAdmin(): User
    {
        /** @var User $user */
        $user = once(function () {
            Role::factory()->create([
                'id' => $this->adminRole,
                'name' => 'admin',
                'description' => 'Super Admin',
            ]);

            return User::factory()->create(['role_id' => $this->adminRole]);
        });

        return $user;
    }

    protected function creatCashAccount(): Account
    {
        return Account::factory()->cash()->create();
    }

    protected function addInventory(Product $product, PackingType $unit, int $qty = 1, float $size = 5.5): void
    {
        $stockItem = FabricReceivingItem::factory()->create([
            'product_id' => $product->id,
            'unit' => $unit,
            'qty' => $qty,
            'size' => $size,
            'total_qty' => $qty * $size,
        ]);
        Inventory::factory()->create([
            'stockable_id' => $stockItem->fabric_receiving_id,
            'stockable_type' => FabricReceiving::morphClass(),
            'stockable_item_id' => $stockItem->id,
            'product_id' => $product->id,
            'unit' => $unit,
            'qty' => $qty,
            'size' => $unit === PackingType::Thaan ? 0 : $size,
            'meters' => $qty * $size,
            'transaction_date' => fake()->dateTimeThisMonth(),
        ]);
    }
}
