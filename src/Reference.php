<?php

namespace Eroslover\References;

use Eroslover\References\Collections\ReferenceCollection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Reference
 *
 * @property int    model_id
 * @property string model_type
 * @property int    reference_id
 * @property string reference_type
 */
class Reference extends Model
{
    protected $fillable = [
        'model_id',
        'model_type',
        'reference_id',
        'reference_type',
    ];

    protected $table = 'references';

    /**
     * Create the polymorphic relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function model()
    {
        return $this->morphTo();
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new ReferenceCollection($models);
    }
}
