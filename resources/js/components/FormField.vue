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
                :onChange="updateFiles"/>

        </template>

    </DefaultField>

</template>

<script>

    import { ref } from 'vue'
    import { FormField, HandlesValidationErrors } from 'laravel-nova'
    import FilePondWrapper from './FilePondWrapper'

    export default {
        components: { FilePondWrapper },
        mixins: [ FormField, HandlesValidationErrors ],
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

            function setInitialValue() {
                files.value = props.field.value
            }

            function updateFiles() {
                files.value = getActiveFiles()
            }

            function getActiveFiles() {

                /**
                 * https://pqina.nl/filepond/docs/patterns/api/filepond-object/#filestatus-enum
                 */
                return instance.value.instance.getFiles()
                    .filter(file => file.status === 2 || file.status === 5)
                    .map(file => file.serverId)

            }

            return {
                fill,
                instance,
                updateFiles,
                setInitialValue
            }

        },
    }

</script>
