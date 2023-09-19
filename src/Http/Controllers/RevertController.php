<?php

declare(strict_types = 1);

namespace DigitalCreative\Filepond\Http\Controllers;

use DigitalCreative\Filepond\Data\Data;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Response;
use Laravel\Nova\Http\Requests\NovaRequest;

class RevertController
{
    /**
     * @throws BindingResolutionException
     */
    public function __invoke(NovaRequest $request): Response
    {
        if (Data::fromEncrypted($request->getContent())->deleteDirectory()) {
            return response()->make();
        }

        return response()->setStatusCode(500);
    }
}
