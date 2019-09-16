<template>

    <default-field :field="field" :errors="errors">

        <template slot="field">

            <file-pond
                :name="field.attribute"
                :ref="`${field.attribute}Filepond`"
                label-idle="Drop files here..."
                :allow-multiple="field.multiple"
                :disabled="false"
                :required="true"
                :instant-upload="true"
                :accepted-file-types="field.mimesTypes"
                :server="serverOptions"
                :files="files"
                :onprocessfile="updateFiles"
                :onremovefile="updateFiles"/>

        </template>

    </default-field>

</template>

<script>

    import {FormField, HandlesValidationErrors} from 'laravel-nova'
    import vueFilePond from 'vue-filepond'
    import 'filepond/dist/filepond.min.css'
    import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css'
    import 'filepond-plugin-image-edit/dist/filepond-plugin-image-edit.css'
    import 'filepond-plugin-media-preview/dist/filepond-plugin-media-preview.css'
    import 'filepond-plugin-image-overlay/dist/filepond-plugin-image-overlay.css'
    import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type'
    import FilePondPluginImagePreview from 'filepond-plugin-image-preview'
    import FilePondPluginImageOverlay from 'filepond-plugin-image-overlay'
    import FilePondPluginMediaPreview from 'filepond-plugin-media-preview'
    import FilePondPluginImageExifOrientation from 'filepond-plugin-image-exif-orientation'

    const FilePond = vueFilePond(
        FilePondPluginImageExifOrientation,
        FilePondPluginFileValidateType,
        FilePondPluginImagePreview,
        // FilePondPluginImageEdit,
        // FilePondPluginImageCrop,
        // FilePondPluginImageTransform,
        // FilePondPluginImageResize,
        FilePondPluginImageOverlay,
        FilePondPluginMediaPreview
    )

    export default {
        components: {FilePond},
        mixins: [FormField, HandlesValidationErrors],

        props: ['resourceName', 'resourceId', 'field'],

        data() {
            return {
                files: [...this.field.value],
                serverOptions: {
                    url: '/nova-vendor/nova-filepond',
                    revert: '/revert',
                    load: `/load/?disk=${this.field.disk}&serverId=`,
                    process: {
                        url: '/process',
                        ondata: formData => {
                            formData.append('attribute', this.field.attribute)
                            formData.append('tempDirectory', this.field.tempDirectory)
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
            filepondInstance() {

                return this.$refs[`${this.field.attribute}Filepond`]

            }
        },

        methods: {

            getActiveFiles() {

                return this.filepondInstance.getFiles()
                    .filter(file => {
                        // https://pqina.nl/filepond/docs/patterns/api/filepond-object/#filestatus-enum
                        return file.status === 2 || file.status === 5
                    })
                    .map(file => file.serverId)

            },
            updateFiles() {

                this.value = this.getActiveFiles()

            },

            /*
             * Set the initial, internal value for the field.
             */
            setInitialValue() {
                this.value = this.getActiveFiles()
            },

            /**
             * Fill the given FormData object with the field's internal value.
             */
            fill(formData) {

                if (this.value.length) {

                    formData.append(this.field.attribute, this.value)

                }

            },

            /**
             * Update the field's internal value.
             */
            handleChange(value) {
                console.log('handlechange')
                this.value = value
            }
        }
    }
</script>

<style>
    .filepond--fullsize-overlay {
        position: fixed;
        z-index: 20;
    }
</style>
