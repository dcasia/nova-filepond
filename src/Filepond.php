<?php

namespace DigitalCreative\Filepond;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class Filepond extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'filepond';

    private $disk = 'public';
    private $directory = null;

    public function __construct($name, $attribute = null, callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->withMeta([
            'multiple' => true,
            'mimesTypes' => [ 'image/jpeg', 'image/png', 'image/svg+xml' ]
        ]);
    }

    public function single(): self
    {

        $this->withMeta([ 'multiple' => false ]);

        return $this;

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
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
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

        if ($this->meta[ 'multiple' ] === false) {

            $serverId = $request->input($requestAttribute);

            if ($currentImages->first() === $serverId) {

                return;

            }

            $this->removeImages($currentImages);

            $file = new File(static::getPathFromServerId($serverId));
            $path = $file->move(Storage::disk($this->disk)->path($this->directory))
                         ->getBasename();


            $model->setAttribute($attribute, $path);

            return;

        }

        $files = collect(explode(',', $request->input($requestAttribute)));

        $toKeep = $files->intersect($currentImages); // files that exist on the request and on the model
        $toAppend = $files->diff($currentImages); // files that exist only on the request
        $toDelete = $currentImages->diff($files); // files that doest exist on the request but exist on the model

        $this->removeImages($toDelete);

        foreach ($toAppend as $serverId) {

            $file = new File(static::getPathFromServerId($serverId));

            $toKeep->push(

                $file->move(Storage::disk($this->disk)->path($this->directory))
                     ->getBasename()

            );

        }

        $model->setAttribute($attribute, $toKeep->values());

    }

    private function removeImages(Collection $images)
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
    protected function resolveAttribute($resource, $attribute)
    {
        $value = parent::resolveAttribute($resource, $attribute);

        return collect($value)->map(function ($value) {
            return [
                'source' => $value,
                'options' => [
                    'type' => 'local'
                ]
            ];
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
        return $path;
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
        return $serverId;
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
        ], $this->meta(), parent::jsonSerialize());
    }

}
