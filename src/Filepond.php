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

    public function disable(): self
    {
        return $this->withMeta([ 'disabled' => true ]);
    }

    public function withoutCredits(): self
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
        $files = $request->collect($this->attribute);
        $currentFiles = $model->{$this->attribute};
        $model->{$this->attribute} = $this->multiple ? [] : null;

        $callbacks = $files
            ->map(fn (string $file) => static::getPathFromServerId($file))
            ->map(function (string $file, int $index) use ($request, $requestAttribute, $model, $attribute, $currentFiles) {

                /**
                 * If the file already exists on the model, means user want to keep this file, therefore we skip it
                 */
                if (is_array($currentFiles) && in_array($file, $currentFiles)) {

                    $attribute = sprintf('%s->%s', $attribute, $index);

                    $model->$attribute = $file;

                    return null;

                }

                $storage = Storage::disk($this->disk)->path($file);
                $file = new SymfonyFile($storage);
                $file = new UploadedFile($file->getRealPath(), $file->getFilename(), $file->getMimeType(), null, true);

                $clonedRequest = $request::createFromBase($request);
                $clonedRequest->files->add([ $requestAttribute => $file ]);

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

                return $callback;

            })
            ->filter();

//        dd(array_diff($currentFiles, $model->{$this->attribute}));

        return function () use ($callbacks): void {

            foreach ($callbacks as $callback) {

                if ($callback instanceof Closure) {
                    $callback();
                }

            }

        };

        //        /**
        //         * If it`s a multiple files request
        //         */
        //
        //        $files = collect(explode(',', $request->input($requestAttribute)))->map(function ($file) {
        //            return static::getPathFromServerId($file);
        //        });
        //
        //        dd($files);
        //
        //        $toKeep = $files->intersect($currentImages); // files that exist on the request and on the model
        //        $toAppend = $files->diff($currentImages); // files that exist only on the request
        //        $toDelete = $currentImages->diff($files); // files that doest exist on the request but exist on the model
        //
        //        $this->removeImages($toDelete);
        //
        //        foreach ($toAppend as $serverId) {
        //
        //            $file = new File($serverId);
        //
        //            $toKeep->push($this->moveFile($file));
        //
        //        }
        //
        //        $model->setAttribute($attribute, $toKeep->values());
    }

    protected function resolveAttribute($resource, $attribute): Collection
    {
        return collect(parent::resolveAttribute($resource, $attribute))
            ->map(fn (string $value) => $this->getServerIdFromPath($value));
    }

    private function getThumbnails(): Collection
    {
        if (blank($this->value)) {
            return collect();
        }

        return $this->value->map(function ($value) {
            return Storage::disk($this->disk)->url(self::getPathFromServerId($value));
        });
    }

    public static function getServerIdFromPath(string $path): string
    {
        return encrypt($path);
    }

    public static function getPathFromServerId(string $serverId): string
    {
        return decrypt($serverId);
    }

    /**
     * Prepare the field for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(),[
            'multiple' => $this->multiple,
            'thumbnails' => $this->getThumbnails(),
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

    private function removeImages(Collection $images): void
    {
        foreach ($images as $image) {
            Storage::disk($this->disk)->delete($image);
        }
    }
}
