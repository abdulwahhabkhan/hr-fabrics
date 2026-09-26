<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'action', 'module', 'section'];

    /**
     * List permission for for form display
     *
     * @return Collection<array-key, mixed>
     */
    public static function listPermissions(): Collection
    {
        $section = ['pos' => 'Fabrics', 'fabric-receivings' => 'Vouchers',
            'por' => 'Fabric Returns'];

        return self::all()->groupBy(['section', 'module'])->map(function ($item, $moduleKey) use (
            $section
        ) {
            $children = $item->map(function ($item, $key) use ($section, $moduleKey) {
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
                    $section_lbl = (string) $key;
                } else {
                    $section_lbl = Arr::get($section, $key, (string) $key);
                }
                // Prefixed with the module: the same section name (e.g. customers) exists in several modules,
                // and the checkbox tree requires unique node values.
                $section_val = "{$moduleKey}_{$section_lbl}_section";

                return [
                    'label' => ucfirst($section_lbl),
                    'value' => $section_val,
                    'children' => array_values($children),
                ];
            })->toArray();

            return ['label' => ucfirst((string) $moduleKey), 'value' => "{$moduleKey}_module",
                'children' => array_values($children)];
        });
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
