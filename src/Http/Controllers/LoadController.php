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
        $disk = $request->input('disk');

        return response()->file(
            file: Storage::disk($disk)->path($serverId),
            headers: [
                'Content-Disposition' => sprintf('inline; filename="%s"', basename($serverId)),
            ],
        );
    }
}
