<?php

namespace App;

use App\Casts\CategoryType;
use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Category extends Model
{

    use QueryCacheable;

    protected $cacheFor = 180;

    protected $fillable = [
        'deleted',
        'name',
        'type'
    ];

    protected $casts = [
        'name' => 'string',
        'type' => CategoryType::class, // $category->type->name (ie: "Products Only") + $category->type->id (ie: 2)
        'deleted' => 'boolean'
    ];
}