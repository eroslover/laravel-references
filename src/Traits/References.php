<?php

namespace Eroslover\References\Traits;

use Eroslover\References\Reference;
use Eroslover\References\Interfaces\ReferenceInterface;
use Eroslover\References\Services\References\ReferenceManagerFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

trait References
{
    public static function bootReferences()
    {
        static::deleting(function ($model) {
            //Remove references
            if ($model instanceof ReferenceInterface) {
                Reference::where('model_type', get_class($model))->where('model_id', $model->id)->delete();
                Reference::where('reference_type', get_class($model))->where('reference_id', $model->id)->delete();
            }
        });
    }

    /**
     * Set the polymorphic relation.
     *
     * @return Relation
     */
    public function references(): Relation
    {
        return $this->morphMany(Reference::class, 'model');
    }
    
    /**
     * Makes a new reference
     * 
     * @param Model|EloquentCollection $referencable
     *
     * @return Collection|EloquentCollection|Reference
     */
    public function ref($referencable)
    {
        return app(ReferenceManagerFactory::class)
            ->create($this)
            ->attach($referencable);
    }

    /**
     * Remove references
     *
     * @param Model|EloquentCollection $referencable
     *
     * @return mixed
     */
    public function unref($referencable)
    {
        return app(ReferenceManagerFactory::class)
            ->create($this)
            ->detach($referencable);
    }

    /**
     * Syncing references
     *
     * @param Model|EloquentCollection|null $referencable
     *
     * @return mixed
     */
    public function syncRefs($referencable)
    {
        return app(ReferenceManagerFactory::class)
            ->create($this)
            ->sync($referencable);
    }

    /**
     * Load entities related via references
     *
     * @param bool $grouped
     *
     * @return Collection
     */
    public function loadReferences($grouped = true): Collection
    {
        $loadedRefs = new Collection();

        if ($references = $this->references()->get()) {
            $loadedRefs = $references->groupBy('reference_type')
                ->mapWithKeys(function($items, $key) {
                    $ids = $items->pluck('reference_id')->toArray();

                    return [$key => $key::whereIn('id', $ids)->get()];
                });
        }

        return $grouped ? $loadedRefs : $loadedRefs->flatten();
    }
}
