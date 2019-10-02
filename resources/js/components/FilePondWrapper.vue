<template>

    <file-pond
        v-bind="$attrs"
        ref="filepond"
        :style="cssVars"
        :name="nameField"
        :image-preview-height="field.multiple ? 150 : null"
        label-idle="Drop files here..."
        :allow-multiple="field.multiple"
        :accepted-file-types="field.mimesTypes"
        :instant-upload="true"
        :max-files="limit || field.limit"
        :server="serverOptions"
        :disabled="field.disabled"
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

    const FilePond = vueFilePond(
        FilePondPluginImageExifOrientation,
        FilePondPluginFileValidateType,
        FilePondPluginImagePreview,
        FilePondPluginImageOverlay,
        FilePondPluginMediaPreview,
    )

    export default {
        inheritAttrs: false,
        components: {FilePond},
        props: ['field', 'errors', 'columns', 'limit'],
        data() {

            return {
                files: [...this.field.value],
                nameField: this.field.attribute,
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
                    '--filepond-column': (100 / (this.columns || this.field.columns)) + '%'
                }
            }
        }
    }
</script>

<style>

    :root {
        --filepond-column: 100%
    }

    .filepond--root {
        transition: all 250ms;
    }

    .filepond--fullsize-overlay {
        position: fixed;
        z-index: 20;
    }

    .filepond--item {
        width: calc(var(--filepond-column) - .5em);
    }

</style>
