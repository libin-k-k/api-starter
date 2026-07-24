<?php

namespace Libinkk\ApiStarter\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Libinkk\ApiStarter\Traits\ApiQueryable;

class TestUser extends Model
{
    use ApiQueryable;

    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price' => 'float',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(TestPost::class, 'user_id');
    }
}

class TestPost extends Model
{
    protected $table = 'posts';

    protected $guarded = [];

    public $timestamps = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(TestUser::class, 'user_id');
    }
}
