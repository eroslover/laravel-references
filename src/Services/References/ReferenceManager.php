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
 *
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
     * @param ReferenceInterface $model
     *
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
     *
     * @return Collection|EloquentCollection|Reference
     * @throws ReferenceException
     */
    public function attach($referencable)
    {
        if ($referencable instanceof Model) {
            $result = $this->createReference($referencable);
        } elseif ($referencable instanceof EloquentCollection || $referencable instanceof Collection) {
            $result = $this->createReferences($referencable);
        } else {
            throw ReferenceException::invalidArgument();
        }

        return $result;
    }

    /**
     * Get rid of existing references.
     *
     * @param Model|EloquentCollection $referencable
     *
     * @throws ReferenceException
     */
    public function detach($referencable): void
    {
        if ($referencable instanceof Model) {
            $this->detachSingleModel($referencable);
        } elseif ($referencable instanceof EloquentCollection || $referencable instanceof Collection) {
            $this->detachMultipleModels($referencable);
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
     *
     * @throws ReferenceException
     */
    public function sync($referencable = null): void
    {
        if (!$referencable) {
            $this->detachAll();
        } elseif ($referencable instanceof Model) {
            $this->syncOne($referencable);
        } elseif ($referencable instanceof EloquentCollection || $referencable instanceof Collection) {
            $this->syncMorphMany($referencable);
        } else {
            throw ReferenceException::invalidArgument();
        }
    }

    /**
     * Sync one reference by given entity.
     *
     * @param Model $referencable
     */
    public function syncOne(Model $referencable): void
    {
        // Detach every reference except referencable
        $class = get_class($referencable);
        $referencableId = $referencable->id;

        $referencesOfClass = $this->getModelReferences();

        if ($referencesOfClass instanceof ReferenceCollection) {
            $delete = $referencesOfClass->filter(function ($item) use ($referencableId, $class) {
                return !(
                    (int) $item->reference_id === (int) $referencableId &&
                    $item->reference_type === $class
                );
            });

            Reference::destroy($delete->pluck('id')->toArray());
        } else {
            $this->detachAll();
            $this->createReference($referencable);
        }
    }

    /**
     * Sync for morphMany relation.
     *
     * @param Collection|EloquentCollection $refModels
     */
    public function syncMorphMany($refModels): void
    {
        $references = $this->getModelReferences();

        // Look for reference ids to detach
        $detachIds = $references->filter(function ($reference) use ($refModels) {
            return !$refModels->contains(function ($refModel) use ($reference) {
                return (
                    (int) $refModel->id === (int) $reference->reference_id &&
                    get_class($refModel) === $reference->reference_type
                );
            });
        })->pluck('id')->toArray();

        // Look for models data to attach
        $attachData = $refModels->filter(function ($refModel) use ($references) {
            return !$references->contains(function ($reference) use ($refModel) {
                return (
                    (int) $refModel->id === (int) $reference->reference_id &&
                    get_class($refModel) === $reference->reference_type
                );
            });
        });

        Reference::destroy($detachIds);

        $attachData->each(function ($item) {
            $this->createReference($item);
        });
    }

    /**
     * Load entities related via references
     *
     * @param bool $grouped
     *
     * @return Collection
     */
    public function load(bool $grouped = true): Collection
    {
        $loadedRefs = new Collection();

        if ($references = $this->getModelReferences()) {
            $loadedRefs = $references->loadGrouped();
        }

        return $grouped ? $loadedRefs : $loadedRefs->flatten();
    }

    /**
     * Creates single reference.
     *
     * @param Model $referencable
     *
     * @return Reference
     */
    protected function createReference(Model $referencable): Reference
    {
        return $this->model->references()->updateOrCreate([
            'reference_id'   => $referencable->id,
            'reference_type' => get_class($referencable)
        ]);
    }

    /**
     * Creates multiple references.
     *
     * @param EloquentCollection|Collection $collection
     *
     * @return EloquentCollection
     */
    protected function createReferences($collection): EloquentCollection
    {
        $availableReferences = $this->model->references()->get();

        $data = $collection->map(function ($referencable) {
            return [
                'reference_id'   => $referencable->id,
                'reference_type' => get_class($referencable)
            ];
        })->filter(function ($item) use ($availableReferences) {
            return $availableReferences
                ->where('reference_type', $item['reference_type'])
                ->where('reference_id', $item['reference_id'])
                ->isEmpty();
        });

        return $this->model->references()->createMany($data->toArray());
    }

    /**
     * Finds reference by model and removes it.
     *
     * @param Model $referencable
     */
    protected function detachSingleModel(Model $referencable)
    {
        $reference = $this->find([
            ['reference_id', '=', $referencable->id],
            ['reference_type', '=', get_class($referencable)]
        ])->first();

        if ($reference) {
            $reference->delete();
        }
    }

    /**
     * Find references by given models and removes them.
     *
     * @param EloquentCollection|Collection $referencedCollection
     */
    protected function detachMultipleModels($referencedCollection): void
    {
        $refs = $this->getModelReferences();

        if ($refs->isNotEmpty()) {
            // find reference in referencable and delete reference entry if exists
            $referencedCollection->each(function ($referencedModel) use ($refs) {
                $reference = $refs->first(function ($reference) use ($referencedModel) {
                    return (
                        (int) $reference->reference_id === (int) $referencedModel->id &&
                        $reference->reference_type === get_class($referencedModel)
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
    protected function getModelReferences(): ReferenceCollection
    {
        $references = $this->model->references()->get();

        return $references instanceof ReferenceCollection ? $references : new ReferenceCollection();
    }
}