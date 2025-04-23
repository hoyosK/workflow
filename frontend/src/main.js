import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'

import CoreuiVue from '@coreui/vue'
import CIcon from '@coreui/icons-vue'
import { iconsSet as icons } from '@/assets/icons'
//import DocsExample from '@/components/DocsExample'
import VTooltipPlugin from 'v-tooltip'
import 'v-tooltip/dist/v-tooltip.css'

import Vue3EasyDataTable from 'vue3-easy-data-table';
import VueGoogleMaps from '@fawmi/vue-google-maps';


import 'vue3-easy-data-table/dist/style.css';
import "toastify-js/src/toastify.css";
import "@vueform/multiselect/themes/default.css";
import { VueSignaturePad } from 'vue-signature-pad';
const app = createApp(App)
app.use(store)
app.use(router)
app.use(CoreuiVue)
app.use(VTooltipPlugin);
app.provide('icons', icons)
app.component('CIcon', CIcon)
app.component('EasyDataTable', Vue3EasyDataTable);
app.component("VueSignaturePad", VueSignaturePad);
app.use(VueGoogleMaps, {
    load: {
        key: 'AIzaSyBTZHm6s5MibHnGHRHZvMTuyXgGhoQvNEw',
    },
})

app.mount('#app')
