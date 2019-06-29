<?php

namespace Eroslover\References\Tests\Traits;

use Eroslover\References\Services\Registries\EntityRegistry;
use Eroslover\References\Tests\Mock\Models\ReferencedModelA;
use Eroslover\References\Tests\Mock\Models\ReferencedModelB;
use Eroslover\References\Tests\Mock\Models\ReferencingModel;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Facades\Schema;

trait TestReferenceTrait
{
    public function crateDbTables()
    {
        Schema::create('referencing_model', function($table)
        {
            $table->increments('id');
            $table->timestamps();
        });

        Schema::create('referenced_model_a', function($table)
        {
            $table->increments('id');
            $table->timestamps();
        });

        Schema::create('referenced_model_b', function($table)
        {
            $table->increments('id');
            $table->timestamps();
        });
    }

    public function defineFactories()
    {
        $factory = app()->make(Factory::class);

        $returnArray = function () {
            return [];
        };

        $factory->define(ReferencingModel::class, $returnArray);
        $factory->define(ReferencedModelA::class, $returnArray);
        $factory->define(ReferencedModelB::class, $returnArray);
    }

    public function references()
    {
        $this->app->make(EntityRegistry::class)->add(ReferencedModelA::class, 'referenced_model_a');

        $referencedACollection = factory(ReferencedModelA::class, 5)->create();

        return $referencedACollection->map(function ($referencedModel) {
            return [
                'id' => $referencedModel->id,
                'type' => 'referenced_model_a'
            ];
        });
    }
}