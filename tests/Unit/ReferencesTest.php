<?php

namespace Tests\Feature;

use Eroslover\References\Collections\ReferenceCollection;
use Eroslover\References\Reference;
use Eroslover\References\Tests\Mock\Models\ReferencedModelA;
use Eroslover\References\Tests\Mock\Models\ReferencedModelB;
use Eroslover\References\Tests\Mock\Models\ReferencingModel;
use Eroslover\References\Tests\TestCase;
use Eroslover\References\Tests\Traits\TestReferenceTrait;

class ReferencesTest extends TestCase
{
    use TestReferenceTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->crateDbTables();
        $this->defineFactories();
    }

    /**
     * @group references
     */
    public function testReferencesAttachOne()
    {
        $referencing = factory(ReferencingModel::class)->create();
        $referencedA = factory(ReferencedModelA::class)->create();

        $referencing->ref($referencedA);
        $refs = $referencing->references()->get();

        $contains = $refs->contains(function($ref) use ($referencedA) {
            return (
                (int) $ref->reference_id === (int) $referencedA->id &&
                $ref->reference_type === get_class($referencedA)
            );
        });

        $this->assertTrue($contains, 'Single reference was successfully created');
    }

    /**
     * @group references
     */
    public function testReferencesAttachMany()
    {
        $referencing = factory(ReferencingModel::class)->create();
        $referencedACollection = factory(ReferencedModelA::class, 5)->create();
        $referencedBCollection = factory(ReferencedModelB::class, 5)->create();

        $referencing->ref($referencedACollection);
        $referencing->ref($referencedBCollection);

        $refs = $referencing->references()->get();

        // Check if references returned as Reference Collection after persist
        $this->assertInstanceOf(
            ReferenceCollection::class,
            $refs,
            'Got all references in the ' . ReferenceCollection::class
        );

        // Test if the references were saved properly

        $allReferenced = $referencedACollection->merge($referencedBCollection);
        $result = false;

        if ($refs instanceof ReferenceCollection) {
            $result = true;

            foreach($allReferenced as $referenced) {
                $contains = $refs->contains(function($ref) use ($referenced) {
                    return (
                        (int) $ref->reference_id === (int) $referenced->id &&
                        $ref->reference_type === get_class($referenced)
                    );
                });

                if (!$contains) {
                    $result = false;
                    break;
                }
            }
        }

        $this->assertTrue($result, 'All references were successfully created');
    }


    /**
     * @group references
     */
    public function testReferencesDetachOne()
    {
        $referencing = factory(ReferencingModel::class)->create();
        $referencedA = factory(ReferencedModelA::class)->create();

        $referencing->ref($referencedA);
        $refs = $referencing->references()->get();

        $contains = $refs->contains(function($ref) use ($referencedA) {
            return (
                (int) $ref->reference_id === (int) $referencedA->id &&
                $ref->reference_type === get_class($referencedA)
            );
        });

        $this->assertTrue($contains, 'Single reference was successfully created');

        $referencing->unref($referencedA);
        $refs = $referencing->references()->get();

        $contains = $refs->contains(function($ref) use ($referencedA) {
            return (
                (int) $ref->reference_id === (int) $referencedA->id &&
                $ref->reference_type === get_class($referencedA)
            );
        });

        $this->assertFalse($contains, 'Single reference was successfully detached');
    }

    /**
     * @group references
     */
    public function testReferencesDetachMany()
    {
        $referencing = factory(ReferencingModel::class)->create();

        $referencedACollection = factory(ReferencedModelA::class, 5)->create();
        $referencedBCollection = factory(ReferencedModelB::class, 5)->create();

        $referencing->ref($referencedACollection);
        $referencing->ref($referencedBCollection);

        $refs = $referencing->references()->get();

        // Check if references returned as Reference Collection after persist
        $this->assertInstanceOf(
            ReferenceCollection::class,
            $refs,
            'Got all references in the ' . ReferenceCollection::class
        );


        // Test if the references were saved properly

        $allReferenced = $referencedACollection->merge($referencedBCollection);
        $result = false;

        if ($refs instanceof ReferenceCollection) {
            $result = true;

            foreach($allReferenced as $referenced) {
                $contains = $refs->contains(function($ref) use ($referenced) {
                    return (
                        (int) $ref->reference_id === (int) $referenced->id &&
                        $ref->reference_type === get_class($referenced)
                    );
                });

                if (!$contains) {
                    $result = false;
                    break;
                }
            }
        }

        $this->assertTrue($result, 'All references were successfully created');

        $referencing->unref($referencedBCollection);

        $unrefResult = false;
        $refs = $referencing->references()->get();

        if ($refs instanceof ReferenceCollection) {
            $unrefResult = true;

            foreach($referencedBCollection as $referenced) {
                $contains = $refs->contains(function($ref) use ($referenced) {
                    return (
                        (int) $ref->reference_id === (int) $referenced->id &&
                        $ref->reference_type === get_class($referenced)
                    );
                });

                if ($contains) {
                    $unrefResult = false;
                    break;
                }
            }
        }

        $this->assertTrue($unrefResult, 'All references were successfully deleted');
    }

    /**
     * @group references
     */
    public function testReferencesSyncOne()
    {
        $referencing = factory(ReferencingModel::class)->create();

        $referencedACollection = factory(ReferencedModelA::class, 5)->create();
        $referencedBCollection = factory(ReferencedModelB::class, 5)->create();

        $referencing->ref($referencedACollection);
        $referencing->ref($referencedBCollection);

        $referencing->syncRefs($referencedACollection->first());

        $this->assertCount(
            1,
            $referencing->references()->get(),
            'References were successfully synchronized. All the reference were deleted except the one'
        );
    }

    /**
     * @group references
     */
    public function testReferencesSyncMany()
    {
        $referencing = factory(ReferencingModel::class)->create();

        $referencedACollection = factory(ReferencedModelA::class, 5)->create();
        $referencedBCollection = factory(ReferencedModelB::class, 5)->create();

        $anotherReferencedBCollection = factory(ReferencedModelB::class, 2)->create();

        $referencing->ref($referencedACollection);
        $referencing->ref($referencedBCollection);

        $referencing->syncRefs($anotherReferencedBCollection);

        $this->assertCount(
            2,
            $referencing->references()->get(),
            'References were successfully synchronized. All the reference were deleted except the one'
        );
    }

    /**
     * @group references
     */
    public function testReferencesClearedCorrectly()
    {
        $referencing = factory(ReferencingModel::class)->create();

        $anotherReferencedBCollection = factory(ReferencedModelB::class, 5)->create();

        $referencing->syncRefs($anotherReferencedBCollection);

        $this->assertCount(
            $anotherReferencedBCollection->count(),
            $referencing->references()->get()
        );

        $referencing->delete();

        $this->assertEmpty(
            Reference::where('model_type', get_class($referencing))->where('model_id', $referencing->id)->get()
        );

        $this->assertEmpty(
            Reference::where('reference_type', get_class($referencing))->where('reference_id', $referencing->id)->get()
        );
    }
}
