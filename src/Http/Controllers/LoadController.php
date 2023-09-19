<?php

declare(strict_types = 1);

namespace DigitalCreative\Filepond\Http\Controllers;

use DigitalCreative\Filepond\Data\Data;
use Laravel\Nova\Http\Requests\NovaRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LoadController
{
    public function __invoke(NovaRequest $request): BinaryFileResponse
    {
        $data = Data::fromEncrypted($request->input('serverId'));

        return response()->file(
            file: $data->absolutePath(),
            headers: [
                'Content-Disposition' => sprintf('inline; filename="%s"', basename($data->filename)),
            ],
        );
    }
}
