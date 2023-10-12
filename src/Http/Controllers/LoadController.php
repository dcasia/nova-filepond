<?php

declare(strict_types = 1);

namespace DigitalCreative\Filepond\Http\Controllers;

use DigitalCreative\Filepond\Data\Data;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Http\Requests\NovaRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LoadController
{
    public function __invoke(NovaRequest $request): StreamedResponse
    {
        $data = Data::fromEncrypted($request->input('serverId'));

        return Storage::disk($data->disk)->response($data->path);
    }
}
