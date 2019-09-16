Nova.booting((Vue, router, store) => {
    Vue.component('index-filepond', require('./components/IndexField'))
    Vue.component('detail-filepond', require('./components/DetailField'))
    Vue.component('form-filepond', require('./components/FormField'))
})
