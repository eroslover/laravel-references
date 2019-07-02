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


You can install this package via composer using this command:

``` bash
$ composer require eroslover/laravel-references
```

**If you're using Laravel 5.5 or greater this package will be auto-discovered, however if you're using anything lower than 5.5 you will need to register it the old way:**

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

Choose the model you want to add references to. As in example above I'll choose `Photo`. This class should implement `ReferencesInterface` and import `References` trait.

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

## Testing

``` bash
$ phpunit
```

## License

The MIT License (MIT). Please see [License File](https://github.com/eroslover/laravel-references/blob/master/LICENSE.md) for more information.
