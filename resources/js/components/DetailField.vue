<template>

    <panel-item :field="field">

        <template slot="value">
            <span v-if="!field.thumbnails.length">-</span>
            <div class="gallery" v-else>
                <div class="gallery-list clearfix">
                    <div class="gallery-item gallery-item-file mb-3 p-3 mr-3" v-for="item in field.thumbnails">
                        <div class="gallery-item-info">
                            <a :href="downloadPath(item.raw)" class="download mr-2" target="_blank" v-if="field.downloadable">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 22" aria-labelledby="download" role="presentation" class="fill-current">
                                    <path d="M11 14.59V3a1 1 0 0 1 2 0v11.59l3.3-3.3a1 1 0 0 1 1.4 1.42l-5 5a1 1 0 0 1-1.4 0l-5-5a1 1 0 0 1 1.4-1.42l3.3 3.3zM3 17a1 1 0 0 1 2 0v3h14v-3a1 1 0 0 1 2 0v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-3z" class="heroicon-ui"></path>
                                </svg>
                            </a>
                            <span class="label">{{ item.name }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

    </panel-item>

</template>

<script>
    export default {
        props: ['resource', 'resourceName', 'resourceId', 'field'],

        methods: {
            downloadPath(file) {
                return `/nova-vendor/nova-filepond/download/${this.resourceName}/${this.resourceId}/${file}/${this.field.attribute}`;
            }
        },
    }

</script>
<style lang="scss" scoped>
    $bg-color: #e8f5fb;
    $border-radius: 10px;

    .gallery {
        &.editable {
            .gallery-item {
                cursor: grab;
            }
        }
    }

    .gallery {
        &.editable {
            .gallery-item {
                cursor: grab;
            }
        }

        .gallery-item {
            float: left;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            border-radius: $border-radius;
            background-color: $bg-color;

            .gallery-item-info {
                display: flex;
                background-color: transparentize($bg-color, .2);
                border-radius: $border-radius;
                z-index: 10;
            }
        }
    }

    .gallery .edit.edit--file {
        position: relative;
        top: auto;
        right: auto;
    }

    .gallery-item-file {
        &.gallery-item {
            width: 100%;

            .gallery-item-info {
                display: flex;

                .label {
                    flex-grow: 1;
                }

                .download {
                    color: var(--primary-dark);
                }

                .delete {
                    align-self: flex-end;
                    color: var(--danger);
                }
            }
        }
    }
</style>
