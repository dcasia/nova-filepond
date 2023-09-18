<?php

declare(strict_types = 1);

namespace DigitalCreative\Filepond\Http\Controllers;

use DigitalCreative\Filepond\Filepond;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Http\Requests\NovaRequest;

class RevertController
{
    /**
     * @throws BindingResolutionException
     */
    public function __invoke(NovaRequest $request): Response
    {
        $filePath = Filepond::getPathFromServerId($request->getContent())[ 'path' ];

        if (Storage::disk(config('nova-filepond.temp_disk'))->deleteDirectory(dirname($filePath))) {
            return response()->make();
        }

        return response()->setStatusCode(500);
    }
}
