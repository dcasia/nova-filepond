<?php

namespace DigitalCreative\Filepond\Http\Controllers;

use DigitalCreative\Filepond\Filepond;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Resource;

class FilepondController extends BaseController
{
	use ValidatesRequests;

	/**
	 * Uploads the file to the temporary directory
	 * and returns an encrypted path to the file
	 *
	 * @param NovaRequest $request
	 *
	 * @return Response
	 */
	public function process(NovaRequest $request)
	{

		$attribute = $request->input('attribute');
		$prefixedAttribute = '__' . $attribute;
		$file = $request->file($prefixedAttribute);
		$resourceName = $request->input('resourceName');
		$request->offsetSet($attribute, $file);

		try {
			$resourceClass = Nova::resourceForKey($resourceName);

			/**
			 * @var Resource $resource
			 */
			$rules = $this->getCreationRules($resourceClass, $request);

			$this->validate($request, Arr::only($rules, $attribute));

		} catch(ValidationException $exception) {

			return response()->json([
				'message' => $exception->getMessage(),
				'errors'  => $exception->errors(),
			], $exception->status);

		}

		$time = date('U');
		$tempPath = storage_path('tmp');
		$originalName = $file->getClientOriginalName();
		$originalName = Str::beforeLast($originalName, '.');
		$originalName = str_replace(' ', '_', $originalName);
		$fileName = "{$originalName}-{$resourceName}-{$time}." . $file->guessClientExtension();

		try {
			$newFile = $file->move($tempPath, $fileName);
		} catch(\Exception $exception) {
			return response()->make('Could not save file', 500);
		}

		return response()->make(
			Filepond::getServerIdFromPath($newFile->getRealPath())
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

		if(unlink($filePath)) {
			return response()->make();
		}

		return response()->setStatusCode(500);
	}

	public function load(Request $request)
	{
		$disk = decrypt($request->input('disk'));

		$serverId = Filepond::getPathFromServerId($request->input('serverId'));

		$pathInfo = pathinfo($serverId);
		$filename = $pathInfo['filename'];
		$basename = $pathInfo['basename'];
		$extension = $pathInfo['extension'];

		$response = response(Storage::disk($disk)->get($serverId))
			->header('Content-Disposition', "inline; name=\"$filename\"; filename=\"$basename\"")
			->header('Content-Length', Storage::disk($disk)->size($serverId));

		if($mimeType = Filepond::guessMimeType($extension)) {
			$response->header('Content-Type', $mimeType);
		}

		return $response;
	}

	public function download(NovaRequest $request)
	{
		$resource = $request->findResourceOrFail();

		$file = $request->route()->file;

		$resource->authorizeToView($request);

		$filePath = Filepond::getPathFromServerId($file);

		return $resource->detailFields($request)
		                ->whereInstanceOf(Filepond::class)
		                ->findFieldByAttribute($request->field, function () {
			                abort(404);
		                })->toDownloadResponse($request, $resource, $filePath);
	}

	private function getCreationRules(string $resource, NovaRequest $request): array
	{
		$creatingFields = ( new $resource($resource::newModel()) )
			->creationFields($request);

		if(isset($creatingFields['Tabs']) && isset($creatingFields['Tabs']['fields'])) {
			$creatingFields = $creatingFields['Tabs']['fields'];
		} else {
			throw new \Exception('Error Getting Fields');
		}

		return $creatingFields->reject(function($field) use ($request) {
			return $field->isReadonly($request) || $field instanceof RelatableField;
		})->mapWithKeys(function($field) use ($request) {
			return $field->getCreationRules($request);
		})->all();
	}

}
