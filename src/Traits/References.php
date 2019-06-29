<?php

namespace Eroslover\References\Traits;

use Eroslover\References\Reference;
use Eroslover\References\Interfaces\ReferenceInterface;
use Eroslover\References\Services\References\ReferenceManagerFactory;
use Eroslover\References\Services\Registries\EntityRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

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
     * Makes a new reference
     * 
     * @param Model|EloquentCollection $referencable
     * @param string $collectionName
     * @return mixed
     */
    public function ref($referencable, $collectionName = 'default')
    {
        return app(ReferenceManagerFactory::class)
            ->create($this)
            ->attach($referencable, $collectionName);
    }

    /**
     * Set the polymorphic relation.
     *
     * @return mixed
     */
    public function references(): Relation
    {
        return $this->morphMany(Reference::class, 'model');
    }

    /**
     * Load entities related via references
     * @param bool $grouped
     * @return Collection
     */
    public function loadReferences($grouped = true): Collection
    {
        $loadedRefs = new Collection();

        if ($references = $this->references()->get()) {
            if ($grouped) {
                $groupedRefs = $references->groupBy('reference_type');

                // Mapped array where key is namespace and value is a collection of entities
                $loadedRefs = $groupedRefs->mapWithKeys(function($items, $key) {

                    $ids = $items->pluck('reference_id')->toArray();

                    return [$key => $key::whereIn('id', $ids)->get()];
                });
            } else {
                $loadedRefs = $references->map(function ($item, $key) {
                    $contentType = $item->reference_type;

                    return $contentType::find($item->reference_id);
                });
            }
        }

        return $loadedRefs;
    }

    /**
     * Remove references
     *
     * @param Model|EloquentCollection $referencable
     * @param string $collectionName
     * @return mixed
     */
    public function unref($referencable, $collectionName = 'default')
    {
        return app(ReferenceManagerFactory::class)
            ->create($this)
            ->detach($referencable, $collectionName);
    }

    /**
     * @param $referencable
     * @param string $collectionName
     * @return mixed
     */
    public function syncRefs($referencable, $collectionName = 'default')
    {
        return app(ReferenceManagerFactory::class)
            ->create($this)
            ->sync($referencable, $collectionName);
    }

    /**
     * @param Request $request
     * @param string $field
     */
    public function syncRefsRequest(Request $request, string $field = 'references')
    {
        $items = json_encode([
            'items' => [
                ['id' => 4, 'type' => 'news'],
                ['id' => 5, 'type' => 'news'],
                ['id' => 6, 'type' => 'news'],
            ]
        ]);

        $references = json_decode($items);
        $referencable = null;

        if ($items = $references->items ?? null) {
            $groupedItems = collect($items)->groupBy('type');

            $referencable = new Collection();

            $groupedItems->each(function($item, $alias) use(&$referencable) {
                if ($model = app(EntityRegistry::class)->get($alias)) {
                    $models = $model->query()
                        ->whereIn('id', $item->pluck('id')->toArray())
                        ->get();

                    $referencable = $referencable->merge($models);
                }
            });
        }

        app(ReferenceManagerFactory::class)
            ->create($this)
            ->sync($referencable);
    }
}
