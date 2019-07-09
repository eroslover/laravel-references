<?php

namespace Eroslover\References\Services\References;

use Eroslover\References\Exceptions\ReferenceException;
use Eroslover\References\Interfaces\ReferenceInterface;

class ReferenceManagerFactory
{
    /**
     * Create a ReferenceManager
     *
     * @param ReferenceInterface $model
     *
     * @return ReferenceManager
     * @throws ReferenceException
     */
    public function create(ReferenceInterface $model): ReferenceManager
    {
        return new ReferenceManager($model);
    }
}