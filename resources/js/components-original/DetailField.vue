<template>

    <panel-item :field="field">

        <file-pond-wrapper
            slot="value"
            :disabled="true"
            :allow-image-preview="!field.multiple"
            :limit="field.value.length"
            :field="field"/>

    </panel-item>

</template>

<script>

    import FilePondWrapper from './FilePondWrapper'

    export default {
        components: {FilePondWrapper},
        props: ['resource', 'resourceName', 'resourceId', 'field']
    }

</script>
