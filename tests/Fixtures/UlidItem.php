<?php

declare(strict_types=1);

namespace Asignua\FilamentToggleIconColumn\Tests\Fixtures;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property bool $is_active
 */
class UlidItem extends Model
{
    use HasUlids;

    protected $table = 'review_ulid_items';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
