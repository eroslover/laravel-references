<?php

namespace Eroslover\References\Services\Registries;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class EntityRegistry implements Arrayable
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * EntityRegistry constructor.
     */
    public function __construct()
    {
        $this->collection = new Collection();
    }

    /**
     * @param Model|string $entity
     * @param string $alias
     *
     * @return static
     */
    public function add($entity, $alias = null)
    {
        $entity = $this->resolve($entity);

        if (!$alias) {
            $alias = $entity->getTable();
        }

        $this->collection->put($alias, $entity);

        return $this;
    }

    /**
     * Get entity by alias
     * @param $alias
     *
     * @return mixed
     */
    public function get($alias)
    {
        return $this->collection->get($alias);
    }

    /**
     * List entities
     *
     * @return array
     */
    public function list(): array
    {
        return $this->collection->all();
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        return $this->collection;
    }

    /**
     * @param $entity
     *
     * @return Model
     */
    private function resolve($entity): Model
    {
        if (is_string($entity) && class_exists($entity)) {
            $entity = new $entity;
        }

        if (!($entity instanceof Model)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid referencable value. Value must be an object or a namespace string of the %s class',
                Model::class));
        }

        return $entity;
    }

    /**
     * @return array|mixed
     */
    public function toArray()
    {
        return $this->collection->map(function($entity, $alias) {
            return get_class($entity);
        })->toArray();
    }
}
