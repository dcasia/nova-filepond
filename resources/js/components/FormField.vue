<template>

    <default-field :field="field" :errors="errors" :full-width-content="field.fullWidth">

        <template slot="field">

            <file-pond-wrapper
                :ref="`${field.attribute}Filepond`"
                :field="field"
                :errors="errors"
                :onprocessfile="updateFiles"
                :onremovefile="updateFiles"/>

        </template>

    </default-field>

</template>

<script>

    import {FormField, HandlesValidationErrors} from 'laravel-nova'
    import FilePondWrapper from './FilePondWrapper'

    export default {
        components: {FilePondWrapper},

        mixins: [FormField, HandlesValidationErrors],

        props: ['resourceName', 'resourceId', 'field'],

        computed: {

            filepondInstance() {

                return this.$refs[`${this.field.attribute}Filepond`].instance

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
                    .join(',')

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
                this.value = value
            }
        }
    }
</script>
