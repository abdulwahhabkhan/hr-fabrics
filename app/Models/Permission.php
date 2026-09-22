<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'action', 'module', 'section'];

    /**
     * List permission for for form display
     *
     * @return Permission[]|\Illuminate\Database\Eloquent\Collection|Collection
     */
    public static function listPermissions()
    {
        $section = ['pos' => 'Fabrics', 'fabric-receivings' => 'Vouchers',
            'por' => 'Fabric Returns'];

        return self::all()->groupBy(['section', 'module'])->map(function ($item, $key) use (
            $section
        ) {
            $children = $item->map(function ($item, $key) use ($section) {
                $children = $item->map(function ($item) use ($section) {
                    $name = $item['name'];
                    $sec = $section[$item['module']] ?? null;
                    if ($sec) {
                        $name = str_replace($item['module'], $sec, $name);
                    }

                    return ['label' => ucwords(str_replace('.', ' ', $name)),
                        'value' => $item['id']];
                })->toArray();
                $label = $children[0]['label'];
                if (str_contains($label, 'Cutpiece')) {
                    $section_lbl = $key;
                } else {
                    $section_lbl = $section[$key] ?? $key;
                }
                $section_val = "{$section_lbl}_section";

                return [
                    'label' => ucfirst($section_lbl),
                    'value' => $section_val,
                    'children' => array_values($children),
                ];
            })->toArray();

            return ['label' => ucfirst($key), 'value' => "{$key}_module",
                'children' => array_values($children)];
        });
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
