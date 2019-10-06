<?php

namespace DigitalCreative\Filepond;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Controllers\ResourceShowController;
use Laravel\Nova\Http\Requests\NovaRequest;

class Filepond extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'filepond';

    /**
     * @var callable
     */
    public $storeAsCallback;

    /**
     * @var string
     */
    private $disk = 'public';

    /**
     * @var bool
     */
    private $multiple = false;

    /**
     * @var null
     */
    private $directory = null;

    /**
     * Create a new field.
     *
     * @param string $name
     * @param string|callable|null $attribute
     * @param callable|null $resolveCallback
     * @return void
     */
    public function __construct($name, $attribute = null, callable $resolveCallback = null)
    {

        parent::__construct($name, $attribute, $resolveCallback);

        /**
         * Temporarily as it currently only supports image and it`s not very pretty yet
         */
        $this->showOnIndex = false;

    }

    public function disable(): self
    {
        return $this->withMeta([ 'disabled' => true ]);
    }

    public function fullWidth(): self
    {
        return $this->withMeta([ 'fullWidth' => true ]);
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
        return $this->withMeta([ 'mimesTypes' => is_array($mimesTypes) ? $mimesTypes : func_get_args() ]);
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

    public function withDoka(array $options = []): self
    {
        return $this->withMeta([
            'dokaEnabled' => true,
            'dokaOptions' => array_merge(config('nova-filepond.doka.options', []), $options)
        ]);
    }

    public function withoutDoka(): self
    {
        return $this->withMeta([ 'dokaEnabled' => false ]);
    }

    /**
     * @param string $disk
     * @param string|null $directory
     *
     * @return $this
     */
    public function disk(string $disk, string $directory = null)
    {
        $this->disk = $disk;
        $this->directory = $directory;

        return $this;
    }

    public function storeAs(callable $callback)
    {

        $this->storeAsCallback = $callback;

        return $this;

    }

    public function updateRules($rules)
    {

        if ($this->shouldApplyRules()) {

            $this->updateRules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

        }

        return $this;

    }

    public function creationRules($rules)
    {

        if ($this->shouldApplyRules()) {

            $this->creationRules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

        }

        return $this;

    }

    public function rules($rules)
    {

        if ($this->shouldApplyRules()) {

            $this->rules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

        }

        return $this;

    }

    private function shouldApplyRules(): bool
    {
        return request()->routeIs('nova.filepond.process') || request()->input($this->attribute) === null;
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param NovaRequest $request
     * @param string $requestAttribute
     * @param object $model
     * @param string $attribute
     *
     * @return mixed
     */
    protected function fillAttributeFromRequest(NovaRequest $request, $requestAttribute, $model, $attribute)
    {

        $currentImages = collect($model->{$requestAttribute});

        /**
         * null when all images are removed
         */
        if ($request->input($requestAttribute) === null) {

            $this->removeImages($currentImages);

            $model->setAttribute($requestAttribute, null);

            return;

        }

        if ($this->multiple === false) {

            $serverId = $request->input($requestAttribute);

            /**
             * If no changes were made the first image should match the given serverId
             */
            if ($currentImages->first() === $serverId) {

                return;

            }

            $this->removeImages($currentImages);

            $file = new File(static::getPathFromServerId($serverId));

            $model->setAttribute($attribute, $this->moveFile($file));

            return;

        }

        /**
         * If it`s a multiple files request
         */
        $files = collect(explode(',', $request->input($requestAttribute)));

        $toKeep = $files->intersect($currentImages); // files that exist on the request and on the model
        $toAppend = $files->diff($currentImages); // files that exist only on the request
        $toDelete = $currentImages->diff($files); // files that doest exist on the request but exist on the model

        $this->removeImages($toDelete);

        foreach ($toAppend as $serverId) {

            $file = new File(static::getPathFromServerId($serverId));

            $toKeep->push($this->moveFile($file));

        }

        $model->setAttribute($attribute, $toKeep->values());

    }

    private function moveFile(File $file): string
    {

        $name = $this->storeAsCallback ? call_user_func($this->storeAsCallback, $file) : null;

        return $file->move(Storage::disk($this->disk)->path($this->directory), $name)
                    ->getBasename();

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
     * @param mixed $resource
     * @param string $attribute
     *
     * @return mixed
     */
    protected function resolveAttribute($resource, $attribute): Collection
    {

        $value = parent::resolveAttribute($resource, $attribute);

        return collect($value)->map(function ($value) {
            return [
                'source' => $this->getServerIdFromPath(
                    trim(rtrim($this->directory, '/') . '/' . $value, '/')
                ),
                'options' => [
                    'type' => 'local'
                ]
            ];
        });

    }

    private function getThumbnails(): Collection
    {

        if ($this->value->isEmpty()) {

            return collect();

        }

        return $this->value->map(function ($value) {

            return Storage::disk($this->disk)->url($value[ 'source' ]);

        });

    }

    /**
     * Converts the given path into a filepond server id
     *
     * @param string $path
     *
     * @return string
     */
    public static function getServerIdFromPath(string $path): string
    {
        return encrypt($path);
    }

    /**
     * Converts the given filepond server id into a path
     *
     * @param string $serverId
     *
     * @return string
     */
    public static function getPathFromServerId(string $serverId): string
    {
        return decrypt($serverId);
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return array_merge([
            'disk' => $this->disk,
            'multiple' => $this->multiple,
            'disabled' => request()->route()->controller instanceof ResourceShowController,
            'thumbnails' => $this->getThumbnails(),
            'columns' => 1,
            'fullWidth' => false,
            'limit' => null,
            'resourceClass' => '',
            'dokaOptions' => config('nova-filepond.doka.options'),
            'dokaEnabled' => config('nova-filepond.doka.enabled'),
        ], $this->meta(), parent::jsonSerialize());
    }

}
