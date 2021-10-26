# dryspell-models

Framework to help with quickly creating models to use in any PHP application. Properties are defined as typed properties and use PHP 8 attributes to set options. Refer to the `Dryspell\Models\Property` class for available options.

## Installation

```
composer require dryspell/models
```

## Usage

### Create models

Create models by extending `Dryspell\Models\BaseObject`. By default every model has an id, created_at and updated_at property.

### Using models

Simply assign the values to the properties ans save the using an instance of [dryspell/storage-interface](https://github.com/bjoern-tantau/dryspell-storage-interface)

## Todos

* Create better documentation
* Add helpers for validation and form creation
* Add more options for finding items
* Add more storage implementations
* Collect and support corner cases