# Laravel Model References

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

This Laravel package provides a quick and simple way to make references between any Eloquent models.

Here are a few short examples of what you can do:

```php
// photo of some persons
$photo = Photo::find(1);

// persons you want to refer with this photo
$person1 = Person::find(1);
$person2 = Person::find(2);

// making a reference
$photo->ref($person1);
$photo->ref($person2);

// you are able to refer a collection of persons
$persons = Person::find([1, 2]);
$photo->ref($persons);
```

## Installation


You can install the package via composer:

``` bash
$ composer require eroslover/laravel-references
```

The service provider will automatically get registered. Or you may manually add the service provider in your config/app.php file:

```php
'providers' => [
    ...
    Eroslover\References\ReferencesServiceProvider::class,
];
```

Now publish the migration and config with:

```bash
php artisan vendor:publish --provider="Eroslover\References\ReferencesServiceProvider"
```

This is the contents of the published config file:

```php
return [
    /*
     * Name of the database table that will store model references.
     */
    'table_name' => 'references'
];
```

Here you can just change the name of the table that will be used for references.

After the migration has been published you can create the references-table by running the migrations:

```bash
php artisan migrate
```

## Usage

Choose the model you want to add references to. As in example above, I'll choose `Photo`. This class should implement `ReferencesInterface` and import `References` trait.

```php
namespace App;

use Eroslover\References\Traits\References;
use Eroslover\References\Interfaces\ReferenceInterface;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model implements ReferenceInterface
{
    use References;
}
```

Choose the models you want to refer to the photo. For example `Person`, `Location`and `Event`.

```php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Person extends Model {}
class Location extends Model {}
class Event extends Model {}
```

##### Making references

The `ref` method accepts `Model` or `Collection` of models to put data in a references table:

```php
$photo = Photo::find(1);

$location = Location::find(3);
$persons = Person::whereLocation($location->id)->get();
$event = Event::first();

$photo->ref($location);
$photo->ref($persons);
$photo->ref($event);
```

##### Removing references

The `unref` method accepts `Model` or `Collection` of models to remove them from the references table:

```php
$photo->unref($location);
```

##### Syncing references

The `syncRefs` method accepts `null`, `Model` or `Collection` of models to put data or remove data from the references table. Any models that are not in the given collection will be removed from the references table. So, when this operation is complete, only models in the given collection will exist in the reference table for chosen model:

```php
$photo->syncRefs($referencable);
```

##### Retrieving references

The `loadReferences` method returns the collection of referenced models. Accepts boolean `$grouped` parameter. By default, method returns mapped collection where the key is namespace and value is a collection of entities. If you need to get a collection of referenced entities only, you'll need to pass `false` to method as an argument:

```php
$photo->loadReferences();

Output:

ReferenceCollection {#1715 ▼
  #items: array:3 [▼
    "App\Modules\Location" => Collection {#1730 ▼
      #items: array:1 [▼
        0 => Location {#1731 ▶}
      ]
    }
    "App\Modules\Person" => Collection {#1733 ▼
      #items: array:2 [▼
        0 => Person {#1734 ▶}
        1 => Person {#1735 ▶}
      ]
    }
    "App\Modules\Event" => Collection {#1737 ▼
      #items: array:1 [▼
        0 => Event {#1738 ▶}
      ]
    }
  ]
}
```

```php
$photo->loadReferences(false);

Output:

Collection {#1716 ▼
  #items: array:4 [▼
    0 => Location {#1731 ▶}
    1 => Person {#1732 ▶}
    2 => Person {#1734 ▶}
    3 => Event {#1735 ▶}
  ]
}
```

## Testing

You can run tests with:

``` bash
$ vendor/bin/phpunit
```

## License

The MIT License (MIT). Please see [License File](https://github.com/eroslover/laravel-references/blob/master/LICENSE.md) for more information.
