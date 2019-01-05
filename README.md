# dryspell-models

Framework to help with quickly creating models to use in any PHP application. Properties are defined through Docblock and migrations can be automatically created through the CLI.

It is pretty similar to Doctrine and in fact currently uses Doctrine as a backend. It can also be extended to use other backends.

## Installation

```
composer require dryspell/models
```

To generate and use migrations you should copy `vendor/dryspell/models/src/bootstrap.php` to `src/bootstrap.php` and edit it to suit your environment. Create the directory `migrations` or whatever you've setup in your `bootstrap.php` It should return an instance of `Psr\Container\ContainerInterface` configured to use the correct Doctrine classes. I recommend to at least put the definitions into their own files for convenience.

## Usage

### Create models

Create models by extending `Dryspell\Models\BaseObject`. By default every model has an id, created_at and updated_at property. Add more properties, by adding proper Docblock entries for them.

### Create migrations

Run

```
vendor/bin/dryspell migrations:diff --models-path=src/path/to/your/models
```

to generate migrations and

```
vendor/bin/dryspell migrations:migrate
```

to add the necessary tables to your database.

### Using models

Simply assign the values to the properties and call `$model->save()` to persist them in the database.

To load from the database call `$model->load($id)`.

To find models iterate over `$model->find(['foo' => 'value1', 'bar' => 'value2'])` to find all items where the property `foo` has the value `value1` and the property `bar` has the value `value2`.

## Todos

* Create better documentation
* Add helpers for validation and form creation
* Add more options for finding items
* Add more backends
* Collect and support corner cases