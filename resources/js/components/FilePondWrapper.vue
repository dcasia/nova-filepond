<template>

    <file-pond
            v-bind="{ ...$attrs, ...field.labels }"
            ref="filepond"
            :style="cssVars"
            :name="nameField"
            :image-preview-height="field.multiple ? 150 : null"
            :allow-multiple="field.multiple"
            :accepted-file-types="field.mimesTypes"
            :instant-upload="true"
            :max-files="limit || field.limit"
            :server="serverOptions"
            :allow-image-edit="field.dokaEnabled"
            :image-edit-editor="editorInstance"
            :image-edit-instant-edit="true"
            :disabled="field.disabled"
            :allow-paste="false"
            :files="files"/>

</template>

<script>

    import vueFilePond from 'vue-filepond'
    import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type'
    import FilePondPluginImagePreview from 'filepond-plugin-image-preview'
    import FilePondPluginMediaPreview from 'filepond-plugin-media-preview'
    import FilePondPluginImageOverlay from 'filepond-plugin-image-overlay'
    import FilePondPluginImageExifOrientation from 'filepond-plugin-image-exif-orientation'

    import 'filepond/dist/filepond.min.css'
    import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css'
    import 'filepond-plugin-media-preview/dist/filepond-plugin-media-preview.css'
    import 'filepond-plugin-image-overlay/dist/filepond-plugin-image-overlay.css'
    import 'filepond-plugin-image-edit/dist/filepond-plugin-image-edit.min.css'

    import FilePondPluginImageEdit from 'filepond-plugin-image-edit'
    import FilePondPluginImageCrop from 'filepond-plugin-image-crop'
    import FilePondPluginImageResize from 'filepond-plugin-image-resize'
    import FilePondPluginImageTransform from 'filepond-plugin-image-transform'

    const FilePond = vueFilePond(
        FilePondPluginImageExifOrientation,
        FilePondPluginFileValidateType,
        FilePondPluginImagePreview,
        FilePondPluginImageOverlay,
        FilePondPluginMediaPreview,
        FilePondPluginImageEdit,
        FilePondPluginImageCrop,
        FilePondPluginImageResize,
        FilePondPluginImageTransform
    )

    export default {
        inheritAttrs: false,
        components: {FilePond},
        props: ['field', 'errors', 'columns', 'limit'],
        data() {

            let editorInstance = null

            if (this.field.dokaEnabled) {

                if (typeof Doka !== 'object') {

                    console.log('Doka not found!, please read the documentation: https://github.com/dcasia/nova-filepond')

                } else {

                    if (this.field.dokaOptions.cropMask) {

                        this.field.dokaOptions.cropMask = eval(this.field.dokaOptions.cropMask)

                    }

                    editorInstance = Doka.create(this.field.dokaOptions || {})

                }

            }

            return {
                editorInstance: editorInstance,
                files: [...this.field.value],
                nameField: `__${this.field.attribute}`,
                serverOptions: {
                    url: '/nova-vendor/nova-filepond',
                    revert: '/revert',
                    load: `/load/?disk=${this.field.disk}&serverId=`,
                    process: {
                        url: '/process',
                        ondata: formData => {
                            formData.append('attribute', this.field.attribute)
                            formData.append('resourceName', this.$route.params.resourceName)
                            return formData
                        },
                        onerror: data => {
                            this.errors.record(JSON.parse(data).errors)
                        }
                    },
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                }
            }

        },
        computed: {
            instance() {
                return this.$refs.filepond
            },
            cssVars() {
                return {
                    '--filepond-column': (100 / (this.columns || this.field.columns)) + '%',
                    '--filepond-max-height': this.field.maxHeight
                }
            }
        }
    }
</script>

<style>

    :root {
        --filepond-column: 100%;
        --filepond-max-height: auto;
    }

    .filepond--root {
        transition: all 250ms;
        max-height: var(--filepond-max-height);
    }

    .filepond--fullsize-overlay {
        position: fixed;
        z-index: 20;
    }

    .filepond--item {
        width: calc(var(--filepond-column) - .5em);
    }

</style>
