<template>

    <p>

        <img v-for="(url, index) of field.thumbnails"
             :key="index"
             :src="url"
             style="object-fit: cover;"
             class="align-bottom w-8 h-8"
             :class="{ 'rounded-full': field.rounded, rounded: !field.rounded }"/>

        <span v-if="!field.thumbnails.length">&mdash;</span>

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
