<?php

namespace Eroslover\References\Exceptions;

class ReferenceException extends \Exception
{
    /**
     * @param string $model
     * @return ReferenceException
     */
    public static function invalidModelInstance(string $model)
    {
        return new static("Model must be an instance of the {$model} class");
    }

    /**
     * @return ReferenceException
     */
    public static function invalidArgument()
    {
        return new static('The given argument is not referencable');
    }
}