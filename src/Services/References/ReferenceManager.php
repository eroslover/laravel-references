<?php

namespace Eroslover\References\Services\References;

use Eroslover\References\Exceptions\ReferenceException;
use Eroslover\References\Interfaces\ReferenceInterface;
use Eroslover\References\Collections\ReferenceCollection;
use Eroslover\References\Reference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Class ReferenceManager for making references between entities.
 * @package App\Services\References
 */
class ReferenceManager
{
    /**
     * @var ReferenceInterface|Model
     */
    protected $model;

    /**
     * ReferenceManager constructor.
     *
     * ReferenceManager constructor.
     * @param ReferenceInterface $model
     * @throws ReferenceException
     */
    public function __construct(ReferenceInterface $model)
    {
        if ($model instanceof Model) {
            $this->model = $model;
        } else {
            throw ReferenceException::invalidModelInstance(Model::class);
        }
    }

    /**
     * Searches reference by restriction.
     *
     * @param array $restriction
     *
     * @return ReferenceCollection|null
     */
    public function find(array $restriction = []): ?ReferenceCollection
    {
        return $this->model->references()->where($restriction)->get();
    }

    /**
     * Creates a new references.
     *
     * @param Model|EloquentCollection $referencable
     * @param string $collectionName
     * @return ReferenceManager|EloquentCollection|Model
     * @throws ReferenceException
     */
    public function attach($referencable, string $collectionName = 'default')
    {
        if ($referencable instanceof Model) {
            $result = $this->createReference($referencable, $collectionName);
        } elseif ($referencable instanceof EloquentCollection || $referencable instanceof Collection) {
            $result = $this->createReferences($referencable, $collectionName);
        } else {
            throw ReferenceException::invalidArgument();
        }

        return $result;
    }

    /**
     * Get rid of existing references.
     *
     * @param Model|EloquentCollection $referencable
     * @param string $collectionName
     * @throws ReferenceException
     */
    public function detach($referencable, $collectionName = 'default'): void
    {
        if ($referencable instanceof Model) {
            $this->detachSingleModel($referencable, $collectionName);
        } elseif ($referencable instanceof EloquentCollection || $referencable instanceof Collection) {
            $this->detachMultipleModels($referencable, $collectionName);
        } else {
            throw ReferenceException::invalidArgument();
        }
    }

    /**
     * Detach all references.
     */
    public function detachAll(): void
    {
        $ids = $this->model->references()->pluck('id')->toArray();
        Reference::destroy($ids);
    }

    /**
     * Sync references for current entity.
     *
     * @param Model|EloquentCollection|null $referencable
     * @param string $collectionName
     * @throws ReferenceException
     */
    public function sync($referencable = null, $collectionName = 'default'): void
    {
        if (!$referencable) {
            $this->detachAll();
        } elseif ($referencable instanceof Model) {
            $this->syncOne($referencable, $collectionName);
        } elseif ($referencable instanceof EloquentCollection || $referencable instanceof Collection) {
            $this->syncMorphMany($referencable, $collectionName);
        } else {
            throw ReferenceException::invalidArgument();
        }
    }

    /**
     * Sync one reference by given entity.
     *
     * @param Model $referencable
     * @param string $collectionName
     */
    public function syncOne(Model $referencable, $collectionName = 'default'): void
    {
        // Detach every reference except referencable
        $class = get_class($referencable);
        $referencableId = $referencable->id;

        $referencesOfClass = $this->getModelReferences();

        if ($referencesOfClass instanceof ReferenceCollection) {
            $delete = $referencesOfClass->filter(function ($item) use ($referencableId, $collectionName, $class) {
                return !(
                    (int) $item->reference_id === (int) $referencableId &&
                    $item->reference_type === $class &&
                    $item->collection === $collectionName
                );
            });

            Reference::destroy($delete->pluck('id')->toArray());
        } else {
            $this->detachAll();
            $this->createReference($referencable, $collectionName);
        }
    }

    /**
     * Sync for morphMany relation.
     *
     * @param Collection|EloquentCollection $refModels
     * @param string $collectionName
     */
    public function syncMorphMany($refModels, $collectionName = 'default'): void
    {
        $references = $this->getModelReferences();

        // Look for reference ids to detach
        $detachIds = $references->filter(function ($reference) use ($refModels, $collectionName) {
            return !$refModels->contains(function ($refModel) use ($reference, $collectionName) {
                return (
                    (int) $refModel->id === (int) $reference->reference_id &&
                    get_class($refModel) === $reference->reference_type &&
                    $collectionName === $reference->collection
                );
            });
        })->pluck('id')->toArray();

        // Look for models data to attach
        $attachData = $refModels->filter(function ($refModel) use ($references, $collectionName) {
            return !$references->contains(function ($reference) use ($refModel, $collectionName) {
                return (
                    (int) $refModel->id === (int) $reference->reference_id &&
                    get_class($refModel) === $reference->reference_type &&
                    $collectionName === $reference->collection
                );
            });
        });

        Reference::destroy($detachIds);

        $attachData->each(function ($item) use ($collectionName) {
            $this->createReference($item, $collectionName);
        });
    }

    /**
     * Creates single reference.
     *
     * @param Model $referencable
     * @param string $collectionName
     *
     * @return $this|Model
     */
    protected function createReference(Model $referencable, string $collectionName = 'default'): Model
    {
        return $this->model->references()->updateOrCreate([
            'reference_id'   => $referencable->id,
            'reference_type' => get_class($referencable),
            'collection'     => $collectionName ?: null,
        ]);
    }

    /**
     * Creates multiple references.
     *
     * @param EloquentCollection|Collection $collection
     * @param string $collectionName
     *
     * @return EloquentCollection
     */
    protected function createReferences($collection, string $collectionName = 'default'): EloquentCollection
    {
        $data = $collection->map(function ($referencable, $key) use ($collectionName) {
            return [
                'reference_id'   => $referencable->id,
                'reference_type' => get_class($referencable),
                'collection'     => $collectionName ?: null,
            ];
        });

        return $this->model->references()->createMany($data->toArray());
    }

    /**
     * Finds reference by model and removes it.
     *
     * @param Model $referencable
     * @param string $collectionName
     */
    public function detachSingleModel(Model $referencable, string $collectionName = 'default')
    {
        $reference = $this->find([
            ['reference_id', '=', $referencable->id],
            ['reference_type', '=', get_class($referencable)],
            ['collection', '=', $collectionName],
        ])->first();

        if ($reference) {
            $reference->delete();
        }
    }

    /**
     * Find references by given models and removes them.
     *
     * @param EloquentCollection|Collection $referencedCollection
     * @param string $collectionName
     */
    protected function detachMultipleModels($referencedCollection, string $collectionName = 'default'): void
    {
        $refs = $this->getModelReferences();

        if ($refs->isNotEmpty()) {
            // find reference in referencable and delete reference entry if exists
            $referencedCollection->each(function ($referencedModel) use ($refs, $collectionName) {
                $reference = $refs->first(function ($reference) use ($referencedModel, $collectionName) {
                    return (
                        (int) $reference->reference_id === (int) $referencedModel->id &&
                        $reference->reference_type === get_class($referencedModel) &&
                        $reference->collection === $collectionName
                    );
                });

                if ($reference) {
                    $reference->delete();
                }
            });
        }
    }

    /**
     * Retrieves references of model.
     *
     * @return ReferenceCollection
     */
    public function getModelReferences(): ReferenceCollection
    {
        $references = $this->model->references()->get();

        return $references instanceof ReferenceCollection ? $references : new ReferenceCollection();
    }
}