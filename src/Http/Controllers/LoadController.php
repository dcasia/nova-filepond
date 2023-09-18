<?php

declare(strict_types = 1);

namespace DigitalCreative\Filepond\Http\Controllers;

use DigitalCreative\Filepond\Filepond;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Http\Requests\NovaRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LoadController
{
    public function __invoke(NovaRequest $request): BinaryFileResponse
    {
        $serverId = Filepond::getPathFromServerId($request->input('serverId'));

        return response()->file(
            file: Storage::disk($serverId[ 'disk' ])->path($serverId[ 'path' ]),
            headers: [
                'Content-Disposition' => sprintf('inline; filename="%s"', basename($serverId[ 'filename' ])),
            ],
        );
    }
}
