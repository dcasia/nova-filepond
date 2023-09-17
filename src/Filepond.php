<?php

declare(strict_types = 1);

namespace DigitalCreative\Filepond;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File as SymfonyFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Http\Controllers\ResourceShowController;
use Laravel\Nova\Http\Requests\NovaRequest;

class Filepond extends File
{
    public $component = 'filepond';

//    public $storeAsCallback;
//
//    /**
//     * @var string
//     */
//    private $disk = 'public';
//
//    /**
//     * @var bool
//     */
    private bool $multiple = false;
//
//    /**
//     * @var null
//     */
//    private $directory = null;

//    /**
//     * Create a new field.
//     *
//     * @param string $name
//     * @param callable|string|null $attribute
//     */
//    public function __construct($name, $attribute = null, ?callable $resolveCallback = null)
//    {
//        parent::__construct($name, $attribute, $resolveCallback);
//
//        /**
//         * Temporarily as it currently only supports image and it`s not very pretty yet
//         */
//        $this->showOnIndex = false;
//    }

//    public function disable(): self
//    {
//        return $this->withMeta([ 'disabled' => true ]);
//    }
//
//    public function fullWidth(): self
//    {
//        return $this->withMeta([ 'fullWidth' => true ]);
//    }
//
//    public function columns(int $columns): self
//    {
//        return $this->withMeta([ 'columns' => $columns ]);
//    }
//
//    public function limit(int $amount): self
//    {
//        return $this->withMeta([ 'limit' => $amount ]);
//    }
//
//    public function mimesTypes($mimesTypes): self
//    {
//        $mimesTypes = is_array($mimesTypes) ? $mimesTypes : func_get_args();
//
//        return $this->withMeta(
//            [ 'mimesTypes' => array_merge($this->meta[ 'mimesTypes' ] ?? [], $mimesTypes) ],
//        );
//    }
//
//    public function maxHeight(string $heightWithUnit): self
//    {
//        return $this->withMeta([ 'maxHeight' => $heightWithUnit ]);
//    }
//
//    public static function guessMimeType(string $extension): ?string
//    {
//        return MimeTypes::getDefault()->getMimeTypes($extension)[ 0 ] ?? null;
//    }
//
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

//    public function image(): self
//    {
//        return $this->mimesTypes('image/jpeg', 'image/png', 'image/svg+xml');
//    }
//
//    public function video(): self
//    {
//        return $this->mimesTypes('video/mp4', 'video/webm', 'video/ogg');
//    }
//
//    public function audio(): self
//    {
//        return $this->mimesTypes('audio/wav', 'audio/mp3', 'audio/ogg', 'audio/webm');
//    }
//
//    public function labels(array $labels): self
//    {
//        return $this->withMeta([ 'labels' => $labels ]);
//    }

    /**
     * @return $this
     */
//    public function disk(string $disk, ?string $directory = null)
//    {
//        $this->disk = $disk;
//        $this->directory = $directory;
//
//        return $this;
//    }
//
//    public function storeAs(callable $callback)
//    {
//        $this->storeAsCallback = $callback;
//
//        return $this;
//    }
//
//    public function updateRules($rules)
//    {
//        if ($this->shouldApplyRules()) {
//            $this->updateRules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;
//        }
//
//        return $this;
//    }
//
//    public function creationRules($rules)
//    {
//        if ($this->shouldApplyRules()) {
//            $this->creationRules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;
//        }
//
//        return $this;
//    }
//
//    public function rules($rules)
//    {
//        if ($this->shouldApplyRules()) {
//            $this->rules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;
//        }
//
//        return $this;
//    }
//
//    private function shouldApplyRules(): bool
//    {
//        return request()->routeIs('nova.filepond.process') || request()->input($this->attribute) === null;
//    }

    protected function fillAttribute(NovaRequest $request, $requestAttribute, $model, $attribute): Closure
    {
        $callbacks = $request->collect($this->attribute)
            ->map(fn (string $file) => static::getPathFromServerId($file))
            ->map(function (string $file, int $index) use ($request, $requestAttribute, $model, $attribute) {

                $storage = Storage::disk($this->disk)->path($file);
                $file = new SymfonyFile($storage);
                $file = new UploadedFile($file->getRealPath(), $file->getFilename(), $file->getMimeType(), null, true);

                $clonedRequest = $request::createFromBase($request);
                $clonedRequest->files->add([ $requestAttribute => $file ]);

                $fakeModel = new class extends Model {
                };
                $fakeModel->{$attribute} = $model->{$attribute};

                $modelAttribute = $attribute;

                $callback = parent::fillAttribute($clonedRequest, $requestAttribute, $fakeModel, $attribute);

                if ($this->multiple) {

                    /**
                     * If multiple files are uploaded we need to make sure that the attribute is an array
                     */
                    if ($index === 0) {
                        $model->$attribute = [];
                    }

                    $modelAttribute = "$attribute->$index";

                }

                data_set($model, $modelAttribute, $fakeModel->$attribute);

                collect($fakeModel->getAttributes())->except($attribute)
                    ->each(fn (mixed $value, string $key) => data_set($model, $key, $value));

                return $callback;

            })
            ->filter();

        return function () use ($callbacks) {

            foreach ($callbacks as $callback) {
                $callback();
            }

        };

//        $currentImages = collect($model->{$requestAttribute});
//
//        /**
//         * null when all images are removed
//         */
//        if ($request->input($requestAttribute) === null) {
//
//            $this->removeImages($currentImages);
//
//            $model->setAttribute($requestAttribute, null);
//
//            return;
//
//        }
//
//        if ($this->multiple === false) {
//
//            $serverId = static::getPathFromServerId($request->input($requestAttribute));
//
//            /**
//             * If no changes were made the first image should match the given serverId
//             */
//            if ($currentImages->first() === $serverId) {
//                return;
//            }
//
//            $this->removeImages($currentImages);
//
//            $file = Storage::disk($this->disk)->move($serverId, $this->moveFile(new File($serverId)));
//
//            $model->setAttribute($attribute, $this->moveFile($file));
//
//            return;
//
//        }
//
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

    private function trimSlashes(string $path): string
    {
        return trim(rtrim($path, '/'), '/');
    }

    private function moveFile(File $file): string
    {
        $name = $this->storeAsCallback ? call_user_func($this->storeAsCallback, $file) : $file->getBasename();
        $fullPath = $this->trimSlashes($this->directory ?? '') . '/' . $this->trimSlashes($name);

        $response = Storage::disk($this->disk)->put($fullPath, file_get_contents($file->getRealPath()));

        if ($response) {
            return $this->trimSlashes($fullPath);
        }

        throw new Exception(__('Failed to upload file.'));
    }

    private function removeImages(Collection $images): void
    {
        foreach ($images as $image) {
            Storage::disk($this->disk)->delete($image);
        }
    }

    /**
     * Resolve the given attribute from the given resource.
     *
     * @param string $attribute
     *
     * @return mixed
     */
//    protected function resolveAttribute($resource, $attribute): Collection
//    {
//        $value = parent::resolveAttribute($resource, $attribute);
//
//        return collect($value)->map(function ($value) {
//
//            return [
//                'source' => $this->getServerIdFromPath($value),
//                'options' => [
//                    'type' => 'local',
//                ],
//            ];
//
//        });
//    }

    private function getThumbnails(): Collection
    {
        if (blank($this->value)) {
            return collect();
        }

        return $this->value->map(function ($value) {
            return Storage::disk($this->disk)->url(self::getPathFromServerId($value[ 'source' ]));
        });
    }

    /**
     * Converts the given path into a filepond server id
     */
    public static function getServerIdFromPath(string $path): string
    {
        return encrypt($path);
    }

    /**
     * Converts the given filepond server id into a path
     */
    public static function getPathFromServerId(string $serverId): string
    {
        return decrypt($serverId);
    }

    private function getLabels(): Collection
    {
        return collect(config('nova-filepond.labels', []))
            ->merge($this->meta[ 'labels' ] ?? [])
            ->mapWithKeys(function ($label, $key) {
                return [ 'label' . Str::title($key) => trans($label) ];
            });
    }

    /**
     * Prepare the field for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return array_merge([
            'multiple' => $this->multiple,
        ], parent::jsonSerialize());
    }
}
