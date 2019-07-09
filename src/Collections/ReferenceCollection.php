<?php

namespace Eroslover\References\Collections;

use Illuminate\Database\Eloquent\Collection;

class ReferenceCollection extends Collection
{
    /**
     * Croups the references by namespace
     *
     * @return static
     */
    public function grouped()
    {
        return $this->groupBy(function ($item) {
            return $item->reference_type;
        });
    }

    /**
     * Groups references by the namespace and loads models
     *
     * @return \Illuminate\Support\Collection|static
     */
    public function loadGrouped()
    {
        return $this->grouped()->mapWithKeys(function ($collection, $namespace) {
            $ids = $collection->pluck('reference_id')->all();

            return [$namespace => $namespace::whereIn('id', $ids)->get()];
        });
    }
}