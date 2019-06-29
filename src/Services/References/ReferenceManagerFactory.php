<?php

namespace Eroslover\References\Services\References;

use Eroslover\References\Interfaces\ReferenceInterface;

class ReferenceManagerFactory
{
    /**
     * @param ReferenceInterface $model
     * @return ReferenceManager
     * @throws \Exception
     */
    public function create(ReferenceInterface $model): ReferenceManager
    {
        return new ReferenceManager($model);
    }
}