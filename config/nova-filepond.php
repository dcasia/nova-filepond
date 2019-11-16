<?php

return [
    /**
     * All the values will pass through the trans() function
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
        'buttonProcessItem' => 'Upload'
    ],
    'doka' => [

        /**
         * Only enable if you have setup doka.min.js correctly, you can find more details how to obtain these files here: https://pqina.nl/doka/?ref=nova-filepond#pricing
         * Please use the browser version of doka.min.js.js
         */
        'enabled' => false,
        'js_path' => public_path('doka.min.js'),
        'css_path' => public_path('doka.min.css'),

        /**
         * Global options, every options contained in here will be merged with every instance of Filepond you create
         */
        'options' => [
            /**
             * Uncomment this to use a circular mask instead of a rectangular one
             */
            //            'cropMask' => <<<JAVASCRIPT
            //                            (root, setInnerHTML) => {
            //                                setInnerHTML(root, `
            //                                    <mask id="circular-mask">
            //                                        <rect x="0" y="0" width="100%" height="100%" fill="white"/>
            //                                        <circle cx="50%" cy="50%" r="50%" fill="black"/>
            //                                    </mask>
            //                                    <rect fill="rgba(255,255,255,.3125)" x="0" y="0" width="100%" height="100%" mask="url(#circular-mask)"/>
            //                                    <circle cx="50%" cy="50%" r="50%" fill="transparent" stroke-width="1" stroke="#fff"/>
            //                                `);
            //                            }
            //                            JAVASCRIPT,
            'cropShowSize' => true,
            'cropAspectRatioOptions' => [
                [
                    'label' => 'Free',
                    'value' => null
                ],
                [
                    'label' => 'Portrait',
                    'value' => 1.25
                ],
                [
                    'label' => 'Square',
                    'value' => 1
                ],
                [
                    'label' => 'Landscape',
                    'value' => .75
                ]
            ]
        ]
    ]
];
