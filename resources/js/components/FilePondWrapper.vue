<template>

    <FilePond
        ref="instance"
        :style="cssVars"
        :name="nameField"
        :image-preview-height="field.multiple ? 150 : null"
        :image-edit-instant-edit="true"
        :accepted-file-types="field.mimesTypes"
        :instant-upload="true"
        :check-validity="true"
        :max-files="limit || field.limit"
        :server="serverOptions"
        :files="files"
        :disabled="field.disabled"
        :allow-multiple="field.multiple"
        :allow-reorder="field.allowReorder"
        :allow-paste="field.allowPaste"
        :allow-drop="field.allowDrop"
        :allow-browse="field.allowBrowse"
        :allow-image-preview="field.preview === undefined ? true : field.preview"
        :credits="field.credits"
        v-bind="field.labels"
        @updatefiles="onChange"
        @reorderfiles="onChange"
        @processfile="onChange"
        @removefile="onChange"
    />

</template>

<script>

    import { ref } from 'vue'
    import vueFilePond from 'vue-filepond'
    import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type'
    import FilePondPluginImagePreview from 'filepond-plugin-image-preview'
    import FilePondPluginMediaPreview from 'filepond-plugin-media-preview'
    import FilePondPluginImageExifOrientation from 'filepond-plugin-image-exif-orientation'
    import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size'

    import 'filepond/dist/filepond.min.css'
    import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css'
    import 'filepond-plugin-media-preview/dist/filepond-plugin-media-preview.css'

    const FilePond = vueFilePond(
        FilePondPluginImageExifOrientation,
        FilePondPluginFileValidateType,
        FilePondPluginFileValidateSize,
        FilePondPluginImagePreview,
        FilePondPluginMediaPreview,
    )

    export default {
        components: { FilePond },
        props: [ 'field', 'resourceName', 'onChange', 'errors', 'columns', 'limit' ],
        setup(props) {

            return {
                instance: ref(),
                nameField: props.field.attribute,
                files: [ ...props.field.value ].map(file => ({
                    source: file,
                    options: {
                        type: 'local',
                    }
                })),
                cssVars: {
                    '--filepond-column': (100 / (props.columns || props.field.columns)) + '%',
                    '--filepond-max-height': props.field.maxHeight
                },
                serverOptions: {
                    url: '/nova-vendor/nova-filepond',
                    revert: '/revert',
                    load: `/load/?disk=${ props.field.disk }&serverId=`,
                    process: {
                        url: '/process',
                        ondata: formData => {
                            formData.append('attribute', props.field.attribute)
                            formData.append('resourceName', props.resourceName)

                            return formData
                        },
                        onerror: errors => {
                            props.errors.record(JSON.parse(errors))
                        }
                    },
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                }
            }

        },

    }

</script>

<style lang="scss">

    :root {
        --filepond-column: 100%;
        --filepond-max-height: auto;
    }

    .dark {

        .filepond--drop-label {
            color: rgba(var(--colors-gray-400));
        }

        .filepond--panel-root {
            background-color: rgba(var(--colors-gray-900));
            border-color: rgba(var(--colors-gray-700));
        }

        .filepond--image-preview-wrapper {
            border-color: rgba(var(--colors-gray-700));
        }

        .filepond--image-preview {
            background-color: rgba(var(--colors-gray-800));
        }

        .filepond--list-scroller::-webkit-scrollbar-thumb {
            background-color: rgba(var(--colors-gray-700));
        }

        .filepond--image-preview-overlay {
            color: rgba(var(--colors-gray-400))
        }

        .filepond--item-panel {
            background: rgba(var(--colors-gray-800));
        }

    }

    [data-filepond-item-state*='error'] .filepond--item-panel,
    [data-filepond-item-state*='invalid'] .filepond--item-panel {
        background-color: rgba(var(--colors-red-500));
    }

    [data-filepond-item-state='processing-complete'] .filepond--item-panel {
        background-color: rgba(var(--colors-green-500));
    }

    .filepond--file-wrapper {
        background: transparent;
    }

    .filepond--item-panel {
        background: rgba(var(--colors-gray-400));
    }

    .filepond--image-preview-overlay {
        color: rgba(var(--colors-gray-500))
    }

    .filepond--image-preview {
        background-color: rgba(var(--colors-gray-100));
    }

    .filepond--image-preview-wrapper {
        border-color: rgba(var(--colors-gray-300));
    }

    .filepond--drop-label {
        color: rgba(var(--colors-gray-600))
    }

    .filepond--root {
        max-height: var(--filepond-max-height);
    }

    .filepond--panel-root {
        border-radius: .25rem;
        border-width: 1px;
        border-color: rgba(var(--colors-gray-300));
        background-color: rgba(var(--colors-gray-100));
    }

    .filepond--image-preview-wrapper {
        border-radius: .25rem;
        border-width: 1px;
    }

    .filepond--fullsize-overlay {
        position: fixed;
        z-index: 20;
    }

    .filepond--item {
        width: calc(var(--filepond-column) - .5em);
    }

</style>
