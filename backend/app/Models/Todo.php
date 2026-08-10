<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Todo extends Model
{
    use HasFactory;

    public const FIELD_SOURCE = 'source';
    public const FIELD_EXTERNAL_ID = 'external_id';
    public const FIELD_TITLE = 'title';
    public const FIELD_DESCRIPTION = 'description';
    public const FIELD_IS_COMPLETED = 'is_completed';
    public const FIELD_USER_ID = 'user_id';

    protected $fillable = [
        self::FIELD_SOURCE,
        self::FIELD_EXTERNAL_ID,
        self::FIELD_TITLE,
        self::FIELD_DESCRIPTION,
        self::FIELD_IS_COMPLETED,
        self::FIELD_USER_ID,
    ];

    protected function casts(): array
    {
        return [
            self::FIELD_IS_COMPLETED => 'boolean',
        ];
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
