<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $sku
 * @property int $price_cents
 * @property bool $is_published
 * @property bool $is_featured
 * @property bool $is_archived
 */
class Product extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'is_featured' => 'boolean', 'is_archived' => 'boolean'];
    }
}
