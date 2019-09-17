<template>

    <panel-item :field="field">

        <file-pond
            slot="value"
            :disabled="true"
            :allow-image-preview="!field.multiple"
            :field="field"/>

    </panel-item>

</template>

<script>

    import FilePond from './FilePond'

    export default {
        components: {FilePond},
        props: ['resource', 'resourceName', 'resourceId', 'field']
    }

</script>
