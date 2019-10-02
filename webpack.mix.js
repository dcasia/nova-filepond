const mix = require('laravel-mix')

mix.setPublicPath('dist')
    .js('resources/js/field.js', 'js')

// mix.webpackConfig({
//     resolve: {
//         alias: {
//             'vue$': 'vue/dist/vue.esm.js',
//             'vue-filepond': 'vue-filepond/dist/vue-filepond.esm.js'
//         }
//     }
// })
