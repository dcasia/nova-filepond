# Nova Filepond

[![Latest Version on Packagist](https://img.shields.io/packagist/v/digital-creative/nova-filepond)](https://packagist.org/packages/digital-creative/nova-filepond)
[![Total Downloads](https://img.shields.io/packagist/dt/digital-creative/nova-filepond)](https://packagist.org/packages/digital-creative/nova-filepond)
[![License](https://img.shields.io/packagist/l/digital-creative/nova-filepond)](https://github.com/dcasia/nova-filepond/blob/master/LICENSE)

<picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/dcasia/nova-filepond/main/screenshots/dark.png">
  <img alt="Laravel Nova Filepond in action" src="https://raw.githubusercontent.com/dcasia/nova-filepond/main/screenshots/light.png">
</picture>

A Nova field for uploading File, Image and Video using [Filepond](https://github.com/pqina/filepond).

# Installation

You can install the package via composer:

```
composer require digital-creative/nova-filepond
```

# Features

- Single/Multiple files upload
- Sortable files
- Preview images, videos and audio
- Enable / Disable preview
- Extends the original Laravel Nova File field giving you access to all the methods/functionality of the default file upload.
- Drag and drop files
- Paste files directly from the clipboard
- Store custom attributes (original file name, size, etc)
- Prunable files (Auto delete files when the model is deleted)
- Dark mode support

# Usage

The field extends the original Laravel Nova File field, so you can use all the methods available in the original field.

Basic usage:

```php
use DigitalCreative\Filepond\Filepond;

class Post extends Resource
{
    public function fields(NovaRequest $request): array
    {
        return [
            Filepond::make('Images', 'images')
                ->rules('required')
                ->prunable()
                ->disablePreview()
                ->multiple() 
                ->limit(4),
        ];
    }
}
```

When uploading multiple files you will need to cast the attribute to an array in your model class

```php
class Post extends Model {
 
    protected $casts = [
        'images' => 'array'
    ];

}
```

You can also store original file name / size by using `storeOriginalName` and `storeOriginalSize` methods.

```php
use DigitalCreative\Filepond\Filepond;

class Post extends Resource
{
    public function fields(NovaRequest $request): array
    {
        return [
            Filepond::make('Images', 'images')
                ->storeOriginalName('name')
                ->storeSize('size')
                ->multiple(),
            
            // or you can manually decide how to store the data
            // Note: the store method will be called for each file uploaded and the output will be stored into a single json column
            Filepond::make('Images', 'images')
                ->multiple()
                ->store(function (NovaRequest $request, Model $model, string $attribute): array {
                    return [
                        $attribute => $request->images->store('/', 's3'),
                        'name' => $request->images->getClientOriginalName(),
                        'size' => $request->images->getSize(),
                        'metadata' => '...'
                    ];
                })
        ];
    }
}
```

Note when using `storeOriginalName` and `storeSize` methods, you will need to add the columns to your database table if you are in "single" file mode.

```php

## License

The MIT License (MIT). Please see [License File](https://raw.githubusercontent.com/dcasia/nova-filepond/master/LICENSE) for more information.
