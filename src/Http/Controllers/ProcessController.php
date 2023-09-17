<?php

declare(strict_types = 1);

namespace DigitalCreative\Filepond\Http\Controllers;

use DigitalCreative\Filepond\Filepond;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;

class ProcessController extends BaseController
{
    use ValidatesRequests;

    /**
     * Uploads the file to the temporary directory and returns an encrypted path to the file
     *
     * @throws BindingResolutionException
     */
    public function __invoke(NovaRequest $request)
    {
        $attribute = $request->input('attribute');
        $resourceName = $request->input('resourceName');

        try {

            $resource = Nova::resourceInstanceForKey($resourceName);

            $rules = $resource
                ->creationFields($request)
                ->firstWhere('attribute', $attribute)
                ->getCreationRules($request);

            $this->validate($request, Arr::only($rules, $attribute));

        } catch (ValidationException $exception) {

            return response()->json(
                data: [
                    'message' => $exception->getMessage(),
                    'errors' => $exception->errors(),
                ],
                status: $exception->status,
            );

        }

        $file = $request->file($attribute);
        $fileName = $file->getClientOriginalName();

        if (!$path = $file->storeAs('temp', $fileName)) {

            return response()->json(
                data: [ 'message' => 'Could not save file.' ],
                status: 500,
            );

        }

//        $tempPath = '/tmp';
//        $filePath = tempnam($tempPath, 'nova-filepond-');
//        $filePath .= '.' . $file->guessClientExtension();
//        $filePathParts = pathinfo($filePath);
//        $finalPath = $file->move($filePathParts[ 'dirname' ], $filePathParts[ 'basename' ]);
//
//        if (!$finalPath) {
//            return response()->make('Could not save file', 500);
//        }

        return response()->make(
            Filepond::getServerIdFromPath($path),
        );
    }

    /**
     * Takes the given encrypted filepath and deletes
     * it if it hasn't been tampered with
     */
    public function revert(Request $request)
    {
        $filePath = Filepond::getPathFromServerId($request->getContent());

        if (unlink($filePath)) {
            return response()->make();
        }

        return response()->setStatusCode(500);
    }

    public function load(Request $request)
    {
        $disk = $request->input('disk');

        $serverId = Filepond::getPathFromServerId($request->input('serverId'));

        $pathInfo = pathinfo($serverId);
        $filename = $pathInfo[ 'filename' ];
        $basename = $pathInfo[ 'basename' ];
        $extension = $pathInfo[ 'extension' ];

        $response = response(Storage::disk($disk)->get($serverId))
            ->header('Content-Disposition', "inline; name=\"$filename\"; filename=\"$basename\"")
            ->header('Content-Length', Storage::disk($disk)->size($serverId));

        if ($mimeType = Filepond::guessMimeType($extension)) {
            $response->header('Content-Type', $mimeType);
        }

        return $response;
    }

    private function getCreationRules(string $resource, NovaRequest $request): array
    {
        return (new $resource($resource::newModel()))
            ->creationFields($request)
            ->reject(function ($field) use ($request) {
                return $field->isReadonly($request) || $field instanceof RelatableField;
            })
            ->mapWithKeys(function ($field) use ($request) {
                return $field->getCreationRules($request);
            })->all();
    }
}
