<template>

    <PanelItem :index="index" :field="field">

        <template #value>

            <FilePondWrapper
                :disabled="true"
                :allow-reorder="false"
                :allow-remove="false"
                :limit="field.value.length"
                :field="field"/>

        </template>

    </PanelItem>

</template>

<script>

    import FilePondWrapper from './FilePondWrapper'

    export default {
        components: { FilePondWrapper },
        props: [ 'index', 'resource', 'resourceName', 'resourceId', 'field' ],
    }

</script>
