import DetailField from './components/DetailField'
import FormField from './components/FormField'

Nova.booting((app, store) => {
    app.component('detail-filepond', DetailField)
    app.component('form-filepond', FormField)
})
