<?php

declare(strict_types = 1);

namespace DigitalCreative\Filepond\Http\Controllers;

use DigitalCreative\Filepond\Filepond;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Symfony\Component\HttpFoundation\Response;

class ProcessController extends BaseController
{
    use ValidatesRequests;

    /**
     * @throws BindingResolutionException
     */
    public function __invoke(NovaRequest $request): Response
    {
        $attribute = $request->input('attribute');
        $resourceName = $request->input('resourceName');
        $file = $request->file($attribute);

        if (!$file->isValid()) {

            return response()->json(
                data: [ $attribute => [ $file->getErrorMessage() ] ],
                status: 500,
            );

        }

        try {

            $resource = Nova::resourceInstanceForKey($resourceName);

            $rules = $resource
                ->creationFields($request)
                ->firstWhere('attribute', $attribute)
                ->getCreationRules($request);

            $this->validate($request, Arr::only($rules, $attribute));

        } catch (ValidationException $exception) {

            return response()->json(
                data: $exception->errors(),
                status: $exception->status,
            );

        }

        $fileName = $file->getClientOriginalName();
        $location = sprintf('%s/%s', config('nova-filepond.temp_path'), Str::random());

        if (!$path = $file->storeAs($location, $fileName, [ 'disk' => config('nova-filepond.temp_disk') ])) {

            return response()->json(
                data: [ $attribute => [ __('Could not save file.') ] ],
                status: 500,
            );

        }

        return response()->make(
            Filepond::getServerIdFromPath($path,$fileName ),
        );
    }
}
