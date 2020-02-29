<template>
    <p>
        <span v-if="!field.thumbnails.length">&mdash;</span>
        <span v-else>{{ field.thumbnails.length }}</span>
    </p>
</template>

<script>
    export default {
        props: ['resourceName', 'field'],
    }
</script>

<style scoped>

    img:not(:last-child) {
        margin-right: 0.4rem;
    }

</style>
