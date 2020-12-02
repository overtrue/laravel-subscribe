<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelSubscribe\Traits\Subscribable;

class Book extends Model
{
    use Subscribable;

    protected $fillable = ['title'];
}
