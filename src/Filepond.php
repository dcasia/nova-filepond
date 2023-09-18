<?php

declare(strict_types = 1);

namespace DigitalCreative\Filepond;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File as SymfonyFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;

class Filepond extends File
{
    public $component = 'filepond';

    private bool $multiple = false;

    public function __construct($name, $attribute = null, $disk = null, $storageCallback = null)
    {
        parent::__construct($name, $attribute, $disk, $storageCallback);

        $this->delete(function (NovaRequest $request, Model $model, string $disk, string $path) {

            return Collection::wrap($this->value)->map(function (string $file) {

                Storage::disk($this->getStorageDisk())->delete(static::getPathFromServerId($file)[ 'path' ]);

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
            ->map(fn (string $file) => static::getPathFromServerId($file))
            ->map(function (array $file, int $index) use ($request, $requestAttribute, $model, $attribute, $currentFiles, $original) {

                /**
                 * If the file already exists on the model, means user want to keep this file, therefore we skip it
                 */
                if ($currentFiles->contains($file[ 'path' ])) {

                    $data = $original->firstWhere($attribute, $file[ 'path' ]);

                    $attribute = sprintf('%s->%s', $attribute, $index);

                    $model->{$attribute} = $data;

                    return null;

                }

                $clonedRequest = $request::createFromBase($request);
                $clonedRequest->files->add([ $requestAttribute => $this->toUploadedFile($file[ 'path' ]) ]);

                $fakeModel = new class() extends Model {
                };

                $callback = parent::fillAttribute($clonedRequest, $requestAttribute, $fakeModel, $attribute);

                $modelAttribute = $attribute;
                $data = $fakeModel->getAttributes();

                if ($this->multiple) {

                    /**
                     * If multiple files are uploaded we need to make sure that the attribute is an array
                     */
                    if ($index === 0) {
                        $model->{$attribute} = [];
                    }

                    $modelAttribute .= "->$index";

                    /**
                     * if the user called ->store() and returned [ $attribute => string ] or string
                     * we assume the user wants to save it in a flat array
                     */
                    if (count($data) === 1 && isset($data[ $attribute ])) {
                        $data = $data[ $attribute ];
                    }

                } else {

                    $data = $data[ $attribute ];

                }

                $model->{$modelAttribute} = $data;

                /**
                 * Cleanup the temp directory
                 */
                return function () use ($file) {
                    Storage::disk(config('nova-filepond.temp_disk'))->deleteDirectory(dirname($file[ 'path' ]));
                };

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
        return collect(parent::resolveAttribute($resource, $attribute))->map(function (string|array $value) {
            return $this->resolveEncryptedServerId($value);
        });
    }

    private function resolveEncryptedServerId(string|array $value): string
    {
        return $this->getServerIdFromPath(
            $this->resolveFileValue($value),
            $this->resolveOriginalName($value),
        );
    }

    private function resolveFileValue(string|array $value): string
    {
        if (is_array($value)) {
            $value = $value[ $this->attribute ];
        }

        return $value;
    }

    private function resolveOriginalName(string|array $value): string
    {
        if (is_array($value)) {
            $value = $value[ $this->originalNameColumn ];
        }

        return $value;
    }

    public static function getServerIdFromPath(string $path, string $filename): string
    {
        return encrypt([
            'path' => $path,
            'filename' => $filename,
            'disk' => config('nova-filepond.disk'),
        ]);
    }

    public static function getPathFromServerId(string $serverId): array
    {
        return decrypt($serverId);
    }

    /**
     * Prepare the field for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'multiple' => $this->multiple,
            'disk' => $this->getStorageDisk(),
            'labels' => $this->getLabels(),
        ]);
    }

    private function getLabels(): Collection
    {
        return collect(config('nova-filepond.labels', []))
            ->merge($this->meta[ 'labels' ] ?? [])
            ->mapWithKeys(fn (string $label, string $key) => [
                sprintf('label%s', Str::title($key)) => Nova::__($label),
            ]);
    }

    private function toUploadedFile(string $file): UploadedFile
    {
        $storage = Storage::disk(config('nova-filepond.temp_disk'))->path($file);
        $file = new SymfonyFile($storage);

        return new UploadedFile($file->getRealPath(), $file->getFilename(), $file->getMimeType(), null, true);
    }
}
