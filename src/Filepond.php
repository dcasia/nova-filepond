<?php

declare(strict_types = 1);

namespace DigitalCreative\Filepond;

use Closure;
use DigitalCreative\Filepond\Data\Data;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File as SymfonyFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use RuntimeException;

class Filepond extends File
{
    public $component = 'filepond';

    private bool $multiple = false;

    public function __construct($name, $attribute = null, $disk = null, $storageCallback = null)
    {
        parent::__construct($name, $attribute, $disk, $storageCallback);

        $this->delete(function (NovaRequest $request, Model $model, string $disk, string $path) {

            return Collection::wrap($this->value)
                ->map(fn (array $encryptedData) => Data::fromEncrypted($encryptedData[ 'source' ]))
                ->map(function (Data $data) {

                    $data->deleteFile();

                    return $this->columnsThatShouldBeDeleted();

                });

        });
    }

    public function disable(): self
    {
        return $this->withMeta([ 'disabled' => true ]);
    }

    public function disableCredits(): self
    {
        return $this->withMeta([ 'credits' => false ]);
    }

    public function columns(int $columns): self
    {
        return $this->withMeta([ 'columns' => $columns ]);
    }

    public function limit(int $amount): self
    {
        return $this->withMeta([ 'limit' => $amount ]);
    }

    public function mimesTypes(array $mimesTypes): self
    {
        return $this->withMeta(
            [ 'mimesTypes' => array_merge($this->meta[ 'mimesTypes' ] ?? [], $mimesTypes) ],
        );
    }

    public function maxHeight(string $heightWithUnit): self
    {
        return $this->withMeta([ 'maxHeight' => $heightWithUnit ]);
    }

    public function single(): self
    {
        $this->multiple = false;

        return $this;
    }

    public function multiple(): self
    {
        $this->multiple = true;

        return $this;
    }

    public function disablePreview(): self
    {
        return $this->withMeta([ 'preview' => false ]);
    }

    public function allowReorder(): self
    {
        return $this->withMeta([ 'allowReorder' => true ]);
    }

    public function disallowPaste(): self
    {
        return $this->withMeta([ 'allowPaste' => false ]);
    }

    public function disallowDrop(): self
    {
        return $this->withMeta([ 'allowDrop' => false ]);
    }

    public function disallowBrowse(): self
    {
        return $this->withMeta([ 'allowBrowse' => false ]);
    }

    public function image(): self
    {
        return $this->mimesTypes([ 'image/jpeg', 'image/png', 'image/svg+xml', 'image/webp' ]);
    }

    public function video(): self
    {
        return $this->mimesTypes([ 'video/mp4', 'video/webm', 'video/ogg' ]);
    }

    public function audio(): self
    {
        return $this->mimesTypes([ 'audio/wav', 'audio/mp3', 'audio/ogg', 'audio/webm' ]);
    }

    public function labels(array $labels): self
    {
        return $this->withMeta([ 'labels' => $labels ]);
    }

    protected function fillAttribute(NovaRequest $request, $requestAttribute, $model, $attribute): Closure
    {
        $original = Collection::wrap($model->{$this->attribute});
        $currentFiles = $original->map(fn (string|array $value) => $this->resolveFileValue($value));

        $model->{$this->attribute} = $this->multiple ? [] : null;

        $callbacks = $request->collect($this->attribute)
            ->map(fn (string $encryptedData) => Data::fromEncrypted($encryptedData))
            ->map(function (Data $file, int $index) use ($request, $requestAttribute, $model, $attribute, $currentFiles, $original) {

                /**
                 * If the file already exists on the model, means user want to keep this file, therefore we skip it
                 */
                if ($currentFiles->contains($file->path)) {

                    $data = $original->first(function (string|array $value) use ($file) {
                        return $this->resolveFileValue($value) === $file->path;
                    });

                    if ($this->multiple) {
                        $attribute = sprintf('%s->%s', $attribute, $index);
                    }

                    $model->{$attribute} = $data;

                    return null;

                }

                $clonedRequest = $request::createFromBase($request);
                $clonedRequest->files->add([ $requestAttribute => $this->toUploadedFile($file->path) ]);

                $fakeModel = new class() extends Model {
                };

                $callback = parent::fillAttribute($clonedRequest, $requestAttribute, $fakeModel, $attribute);

                if ($this->multiple) {

                    /**
                     * If multiple files are uploaded we need to make sure that the attribute is an array
                     */
                    if ($index === 0) {
                        $model->{$attribute} = [];
                    }

                    $data = $fakeModel->getAttributes();
                    $modelAttribute = sprintf('%s->%s', $attribute, $index);

                    /**
                     * if the user called ->store() and returned [ $attribute => string ] or string
                     * we assume the user wants to save it in a flat array
                     */
                    if (count($data) === 1 && isset($data[ $attribute ])) {
                        $data = $data[ $attribute ];
                    }

                    $model->{$modelAttribute} = $data;

                } else {

                    foreach ($fakeModel->getAttributes() as $key => $value) {
                        $model->{$key} = $value;
                    }

                }

                /**
                 * Cleanup the temp directory
                 */
                return fn () => $file->deleteDirectory();

            });

        $finalFiles = Collection::wrap($model->{$this->attribute})->map(fn (string|array $value) => $this->resolveFileValue($value));
        $toDelete = $currentFiles->diff($finalFiles);

        return function () use ($callbacks, $toDelete): void {

            /**
             * Delete every file that is not in the new result
             */
            foreach ($toDelete as $file) {
                Storage::disk($this->getStorageDisk())->delete($file);
            }

            /**
             * Delete all temp files that were uploaded
             */
            foreach ($callbacks as $callback) {

                if ($callback instanceof Closure) {
                    $callback();
                }

            }

        };
    }

    protected function resolveAttribute($resource, $attribute): Collection
    {
        $preview = $this->meta[ 'preview' ] ?? true;

        $data = parent::resolveAttribute($resource, $attribute);

        if ($data === null) {
            return collect();
        }

        if (!is_array($data)) {

            $data = [
                [
                    $attribute => $data,
                    $this->originalNameColumn => parent::resolveAttribute($resource, $this->originalNameColumn),
                    $this->sizeColumn => parent::resolveAttribute($resource, $this->sizeColumn),
                ],
            ];

        }

        return collect($data)
            ->when($preview === true, function (Collection $collection) {

                return $collection->map(fn (string|array $value) => [
                    'source' => $this->resolveEncryptedData($value),
                    'options' => [
                        'type' => 'local',
                    ],
                ]);

            })
            ->when($preview === false, function (Collection $collection) {

                return $collection->map(fn (string|array $value) => [
                    'source' => $this->resolveEncryptedData($value),
                    'options' => [
                        'type' => 'local',
                        'file' => [
                            'name' => $this->resolveOriginalName($value),
                            'size' => $this->resolveFileSize($value),
                        ],
                    ],
                ]);

            });
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'multiple' => $this->multiple,
            'disk' => $this->getStorageDisk(),
            'labels' => $this->getLabels(),
            'isFormView' => resolve(NovaRequest::class)->isFormRequest(),
            'isDetailView' => resolve(NovaRequest::class)->isResourceDetailRequest(),
        ]);
    }

    private function resolveEncryptedData(string|array $value): string
    {
        return Data::make(
            path: $this->resolveFileValue($value),
            filename: $this->resolveOriginalName($value),
            disk: $this->getStorageDisk(),
        )->encrypt();
    }

    private function resolveFileValue(string|array $value): string
    {
        if (is_array($value) && isset($value[ $this->attribute ])) {
            return $value[ $this->attribute ];
        }

        if (is_string($value)) {
            return $value;
        }

        throw new RuntimeException('Unable to resolve file value');
    }

    private function resolveOriginalName(string|array $value): string
    {
        if (is_array($value) && isset($value[ $this->originalNameColumn ])) {
            return $value[ $this->originalNameColumn ];
        }

        if (is_string($value)) {
            return $value;
        }

        return $value[ $this->attribute ];
    }

    private function resolveFileSize(string|array $value): int
    {
        if (is_array($value) && isset($value[ $this->sizeColumn ])) {
            return (int) $value[ $this->sizeColumn ];
        }

        try {

            return Storage::disk($this->getStorageDisk())->size($this->resolveFileValue($value));

        } catch (Exception) {

            return 0;

        }
    }

    private function getLabels(): Collection
    {
        return collect(config('nova-filepond.labels', []))
            ->merge($this->meta[ 'labels' ] ?? [])
            ->mapWithKeys(fn (string $label, string $key) => [
                Str::of($key)->prepend('label_')->camel()->value() => Nova::__($label),
            ]);
    }

    private function toUploadedFile(string $file): UploadedFile
    {
        $storage = Storage::disk(config('nova-filepond.temp_disk'))->path($file);
        $file = new SymfonyFile($storage);

        return new UploadedFile($file->getRealPath(), $file->getFilename(), $file->getMimeType(), null, true);
    }
}
