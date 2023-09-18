<?php

declare(strict_types = 1);

return [

    'disk' => config('nova.storage_disk', 'public'),
    'temp_disk' => 'local',
    'temp_path' => 'nova-filepond/temp',

    /**
     * All the values will pass through the Nova::__() function
     */
    'labels' => [
        'decimalSeparator' => 'auto',
        'thousandsSeparator' => 'auto',
        'idle' => 'Drag & Drop your files or <span class="filepond--label-action"> Browse </span>',
        'invalidField' => 'Field contains invalid files',
        'fileWaitingForSize' => 'Waiting for size',
        'fileSizeNotAvailable' => 'Size not available',
        'fileLoading' => 'Loading',
        'fileLoadError' => 'Error during load',
        'fileProcessing' => 'Uploading',
        'fileProcessingComplete' => 'Upload complete',
        'fileProcessingAborted' => 'Upload cancelled',
        'fileProcessingError' => 'Error during upload',
        'fileProcessingRevertError' => 'Error during revert',
        'fileRemoveError' => 'Error during remove',
        'tapToCancel' => 'tap to cancel',
        'tapToRetry' => 'tap to retry',
        'tapToUndo' => 'tap to undo',
        'buttonRemoveItem' => 'Remove',
        'buttonAbortItemLoad' => 'Abort',
        'buttonRetryItemLoad' => 'Retry',
        'buttonAbortItemProcessing' => 'Cancel',
        'buttonUndoItemProcessing' => 'Undo',
        'buttonRetryItemProcessing' => 'Retry',
        'buttonProcessItem' => 'Upload',
    ],
];
