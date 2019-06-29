<?php

namespace Eroslover\References\Tests\Mock\Models;

use Eroslover\References\Interfaces\ReferenceInterface;
use Eroslover\References\Traits\References;
use Illuminate\Database\Eloquent\Model;

class ReferencedModelB extends Model implements ReferenceInterface
{
    use References;

    protected $table = 'referenced_model_b';
}