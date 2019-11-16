<?php

namespace DigitalCreative\Filepond;

use Exception;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Controllers\ResourceShowController;
use Laravel\Nova\Http\Requests\NovaRequest;
use Symfony\Component\Mime\MimeTypes;

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
     *
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

    public function mimesTypes($mimesTypes): self
    {
        $mimesTypes = is_array($mimesTypes) ? $mimesTypes : func_get_args();

        return $this->withMeta(
            [ 'mimesTypes' => array_merge($this->meta[ 'mimesTypes' ] ?? [], $mimesTypes) ]
        );
    }

    public function maxHeight(string $heightWithUnit): self
    {
        return $this->withMeta([ 'maxHeight' => $heightWithUnit ]);
    }

    public static function guessMimeType(string $extension): ?string
    {
        return MimeTypes::getDefault()->getMimeTypes($extension)[ 0 ] ?? null;
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

    public function image(): self
    {
        return $this->mimesTypes('image/jpeg', 'image/png', 'image/svg+xml');
    }

    public function video(): self
    {
        return $this->mimesTypes('video/mp4', 'video/webm', 'video/ogg');
    }

    public function audio(): self
    {
        return $this->mimesTypes('audio/wav', 'audio/mp3', 'audio/ogg', 'audio/webm');
    }

    public function withDoka(array $options = []): self
    {
        return $this->withMeta([
            'dokaEnabled' => true,
            'dokaOptions' => array_merge(config('nova-filepond.doka.options', []), $options)
        ]);
    }

    public function labels(array $labels): self
    {
        return $this->withMeta([ 'labels' => $labels ]);
    }

    /**
     * Disable Doka, you dont need to call this method if you haven't globally enabled it from the config file
     *
     * @return $this
     */
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
     * @throws Exception
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

            $serverId = static::getPathFromServerId($request->input($requestAttribute));

            /**
             * If no changes were made the first image should match the given serverId
             */
            if ($currentImages->first() === $serverId) {

                return;

            }

            $this->removeImages($currentImages);

            $file = new File($serverId);

            $model->setAttribute($attribute, $this->moveFile($file));

            return;

        }

        /**
         * If it`s a multiple files request
         */
        $files = collect(explode(',', $request->input($requestAttribute)))->map(function ($file) {
            return static::getPathFromServerId($file);
        });

        $toKeep = $files->intersect($currentImages); // files that exist on the request and on the model
        $toAppend = $files->diff($currentImages); // files that exist only on the request
        $toDelete = $currentImages->diff($files); // files that doest exist on the request but exist on the model

        $this->removeImages($toDelete);

        foreach ($toAppend as $serverId) {

            $file = new File($serverId);

            $toKeep->push($this->moveFile($file));

        }

        $model->setAttribute($attribute, $toKeep->values());

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
                'source' => $this->getServerIdFromPath($value),
                'options' => [
                    'type' => 'local'
                ]
            ];
        });

    }

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

    private function getLabels(): Collection
    {

        $labels = collect(config('nova-filepond.labels', []))
            ->merge($this->meta[ 'labels' ] ?? [])
            ->mapWithKeys(function ($label, $key) {
                return [ "label" . Str::title($key) => trans($label) ];
            });

        return $labels;

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
            'maxHeight' => 'auto',
            'limit' => null,
            'dokaOptions' => config('nova-filepond.doka.options'),
            'dokaEnabled' => config('nova-filepond.doka.enabled'),
            'labels' => $this->getLabels(),
        ], $this->meta(), parent::jsonSerialize());
    }

}
