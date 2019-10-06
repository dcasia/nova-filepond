<?php

namespace DigitalCreative\Filepond\Http\Controllers;

use DigitalCreative\Filepond\Filepond;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;

class FilepondController extends BaseController
{
    use ValidatesRequests;

    /**
     * Uploads the file to the temporary directory
     * and returns an encrypted path to the file
     *
     * @param Request $request
     *
     * @return Response
     */
    public function process(Request $request)
    {

        $file = $request->file($request->input('attribute'));
        $resourceName = $request->input('resourceName');

        try {

            $resourceClass = Nova::resourceForKey($resourceName);
            $rules = $resourceClass::rulesForCreation(app(NovaRequest::class));

            $this->validate($request, Arr::only($rules, $request->input('attribute')));

        } catch (ValidationException $exception) {

            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], $exception->status);

        }

        $tempPath = '/tmp';
        $filePath = tempnam($tempPath, 'nova-filepond-');
        $filePath .= '.' . $file->guessClientExtension();
        $filePathParts = pathinfo($filePath);
        $finalPath = $file->move($filePathParts[ 'dirname' ], $filePathParts[ 'basename' ]);

        if (!$finalPath) {

            return response()->make('Could not save file', 500);

        }

        return response()->make(
            Filepond::getServerIdFromPath($finalPath->getRealPath())
        );

    }

    /**
     * Takes the given encrypted filepath and deletes
     * it if it hasn't been tampered with
     *
     * @param Request $request
     *
     * @return mixed
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
        $filePath = Storage::disk($disk)->path($serverId);

        $pathInfo = pathinfo($serverId);
        $filename = $pathInfo[ 'filename' ];
        $basename = $pathInfo[ 'basename' ];

        return response(Storage::disk($disk)->get($serverId))
            ->header('Content-Type', mime_content_type($filePath))
            ->header('Content-Length', filesize($filePath))
            ->header('Content-Disposition', "inline; name=\"$filename\"; filename=\"$basename\"");

    }
}
