<template>

    <DefaultField
        :field="field"
        :errors="errors"
        :show-help-text="showHelpText"
        :full-width-content="fullWidthContent">

        <template #field>

            <FilePondWrapper
                ref="instance"
                :field="field"
                :resourceName="resourceName"
                :errors="errors"
                :onChange="updateFiles"
                :onprocessfile="updateFiles"
                :onremovefile="updateFiles"/>

        </template>

    </DefaultField>

</template>

<script>

    import { ref } from 'vue'
    import { FormField, HandlesValidationErrors } from 'laravel-nova'
    import FilePondWrapper from './FilePondWrapper'

    export default {
        components: {
            FilePondWrapper
        },
        mixins: [ FormField, HandlesValidationErrors ],
        // props: [ 'resourceName', 'resourceId', 'field' ],
        props: {
            resourceName: String,
            resourceId: String,
            field: Object,
        },
        setup(props) {

            const instance = ref()
            const files = ref([])

            function fill(formData) {

                for (const file of files.value) {
                    formData.append(`${ props.field.attribute }[]`, file)
                }

            }

            function updateFiles() {
                files.value = getActiveFiles()
            }

            function getActiveFiles() {

                return instance.value.instance.getFiles()
                    .filter(file => {
                        // https://pqina.nl/filepond/docs/patterns/api/filepond-object/#filestatus-enum
                        return file.status === 2 || file.status === 5
                    })
                    .map(file => file.serverId)

            }

            return {
                fill,
                instance,
                updateFiles
            }

        },
        // computed: {
        //
        //     filepondInstance() {
        //
        //         return this.$refs[ `${ this.field.attribute }Filepond` ].instance
        //
        //     }
        //
        // },
        //
        // methods: {
        //
        //     getActiveFiles() {
        //
        //         return this.filepondInstance.getFiles()
        //             .filter(file => {
        //                 // https://pqina.nl/filepond/docs/patterns/api/filepond-object/#filestatus-enum
        //                 return file.status === 2 || file.status === 5
        //             })
        //             .map(file => file.serverId)
        //             .join(',')
        //
        //     },
        //
        //     updateFiles() {
        //
        //         this.value = this.getActiveFiles()
        //
        //     },
        //
        //     /*
        //      * Set the initial, internal value for the field.
        //      */
        //     setInitialValue() {
        //
        //         this.value = this.getActiveFiles()
        //
        //     },
        //
        //     /**
        //      * Fill the given FormData object with the field's internal value.
        //      */
        //     fill(formData) {
        //
        //         if (this.value.length) {
        //
        //             formData.append(this.field.attribute, this.value)
        //
        //         }
        //
        //     },
        //
        //     /**
        //      * Update the field's internal value.
        //      */
        //     handleChange(value) {
        //         this.value = value
        //     }
        // }
    }

</script>
