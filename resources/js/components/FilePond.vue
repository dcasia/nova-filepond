<template>

    <file-pond
        v-bind="$attrs"
        :name="field.attribute"
        label-idle="Drop files here..."
        :allow-multiple="field.multiple"
        :instant-upload="true"
        :server="serverOptions"
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
        FilePondPluginMediaPreview
    )

    export default {
        inheritAttrs: false,
        components: {FilePond},
        props: ['field'],
        mounted() {
            console.log('mounted', this.field)
        },
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

        }
    }
</script>

<style>
    .filepond--root {
        transition: all 250ms;
    }

    .filepond--fullsize-overlay {
        position: fixed;
        z-index: 20;
    }

    .filepond--item {
        width: calc(50% - .5em);
    }
</style>
