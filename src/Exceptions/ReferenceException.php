<?php

namespace Eroslover\References\Exceptions;

class ReferenceException extends \Exception
{
    public static function invalidModelInstance(string $model)
    {
        return new static("Model must be an instance of the {$model} class");
    }

    public static function invalidArgument()
    {
        return new static('The given argument is not referencable');
    }
}