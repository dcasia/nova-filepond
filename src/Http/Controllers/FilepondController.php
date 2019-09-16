<?php

namespace DigitalCreative\Filepond\Http\Controllers;

use App\Nova\BloodTube;
use DigitalCreative\Filepond\Filepond;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Nova\Http\Requests\NovaRequest;

class FilepondController extends BaseController
{
    use ValidatesRequests;

    /**
     * Uploads the file to the temporary directory
     * and returns an encrypted path to the file
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function process(Request $request)
    {

        $file = $request->file($request->input('attribute'));

        try {

            $rules = BloodTube::rulesForCreation(app(NovaRequest::class));

            $this->validate($request, $rules);

        } catch (ValidationException $exception) {

            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], $exception->status);

        }

        $tempPath = '/tmp';
        $filePath = tempnam($tempPath, 'nova-filepond');
        $filePath .= '.' . $file->extension();
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
        $serverId = $request->input('serverId');

        $filePath = Storage::disk($disk)->path($serverId);

        return response(Storage::disk($disk)->get($serverId))->header('Content-Type', mime_content_type($filePath));

    }
}
