<?php

namespace Eroslover\References\Interfaces;

use Illuminate\Database\Eloquent\Relations\Relation;

interface ReferenceInterface
{
    /**
     * References relation
     *
     * @return mixed
     */
    public function references(): Relation;
}