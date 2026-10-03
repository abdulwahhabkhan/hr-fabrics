<?php

namespace App\Policies;

use App\Models\File;
use App\Models\Purchase\PurchaseReturn;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class FilePolicy
{
    use HandlesAuthorization;

    /**
     * Abilities checked on the owning record, per access mode.
     *
     * @var array<string, array{view: string, update: string}>
     */
    private const array FILEABLE_ABILITIES = [
        'stock.grn' => ['view' => 'view', 'update' => 'edit'],
        'stock.po' => ['view' => 'view', 'update' => 'update'],
        'stock.por' => ['view' => 'view', 'update' => 'update'],
        'sales.so' => ['view' => 'view', 'update' => 'bilti'],
        'sales.sor' => ['view' => 'view', 'update' => 'update'],
        'stock.transfer' => ['view' => 'view', 'update' => 'update'],
    ];

    /**
     * Permissions checked for owning records that have no policy.
     *
     * @var array<string, array{view: string, update: string}>
     */
    private const array FILEABLE_PERMISSIONS = [
        'journal' => ['view' => 'accounts.journals.show', 'update' => 'accounts.journals.attachment.store'],
    ];

    public function view(User $user, File $file): bool
    {
        return $this->canAccessFileOwner($user, $file, 'view');
    }

    public function delete(User $user, File $file): bool
    {
        return $this->canAccessFileOwner($user, $file, 'update');
    }

    /**
     * Attach a newly uploaded file to the given record.
     */
    public function attach(User $user, Model $fileable): bool
    {
        return $this->canAccessFileable($user, $fileable, 'update');
    }

    /**
     * View a stored file by its raw storage path. Only paths that belong to a known record are served.
     */
    public function viewPath(User $user, string $path): bool
    {
        $file = File::query()->where('path', $path)->first();
        if ($file) {
            return $this->view($user, $file);
        }

        $purchaseReturn = PurchaseReturn::query()->where('info->file->file_path', $path)->first();
        if ($purchaseReturn) {
            return $this->canAccessFileable($user, $purchaseReturn, 'view');
        }

        return false;
    }

    /**
     * Unattached files are only accessible to their uploader; attached files follow the owning record.
     */
    private function canAccessFileOwner(User $user, File $file, string $mode): bool
    {
        if (! $file->fileable_type || ! $file->fileable_id) {
            return (int) $file->created_by === $user->id;
        }

        $fileable = $file->fileable;
        if (! $fileable) {
            return false;
        }

        return $this->canAccessFileable($user, $fileable, $mode);
    }

    /**
     * @param  'view'|'update'  $mode
     */
    private function canAccessFileable(User $user, Model $fileable, string $mode): bool
    {
        $morphClass = $fileable->getMorphClass();

        if ($ability = self::FILEABLE_ABILITIES[$morphClass][$mode] ?? null) {
            return Gate::forUser($user)->allows($ability, $fileable);
        }

        if ($permission = self::FILEABLE_PERMISSIONS[$morphClass][$mode] ?? null) {
            return hasPermission($permission, $user);
        }

        return false;
    }
}
