<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $title
 * @property bool $is_done
 * @property bool $is_locked
 * @property array<string, mixed>|null $settings
 */
class Task extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_done' => 'boolean', 'is_locked' => 'boolean', 'settings' => 'array'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
