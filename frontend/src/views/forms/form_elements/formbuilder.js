
import draggable        	from 'vuedraggable';

import TextInput 			from 'FormElementTextInput'
import LongTextInput 		from 'FormElementLongTextInput'
import NumberInput 			from 'FormElementNumberInput'
import SelectList 			from 'FormElementSelectList'
import RadioButton 			from 'FormElementRadioButton'
import Checkbox 			from 'FormElementCheckbox'
import TimePicker 			from 'FormElementTimePicker'
import DatePicker 			from 'FormElementDatePicker'
import DatetimePicker 		from 'FormElementDatetimePicker'
import Rating 				from 'FormElementRating'
import Button 				from 'FormElementButton'
import TextEditor			from 'FormElementTextEditor'

import Elements         	from 'Elements'
import Properties			from 'Properties'
import Theming	 		 	from 'Theming'



// ================
// Use Element UI
// ----------------
import Element from 'element-ui'
import locale from 'element-ui/lib/locale/lang/en' // Default lang is Chinese
import './assets/scss/_var.scss'
Vue.use(Element, { locale })
import './assets/scss/main.scss'


import App from './App'

// ================
// Transitions
// ----------------
import Animate from 'vue2-animate/dist/vue2-animate.min.css'
App.use(Animate)

// ================
// Lodash
// ----------------
import VueLodash from 'vue-lodash'
App.use(VueLodash)

// ================
// Use Vue Router
// ----------------
import router from './router'


// ================
// Use Layouts
// ----------------
import Default from './layouts/Default'
App.component('default-layout', Default);


// ================
// Vue-stash aka simple vuex alternative
// ----------------
import VueStash from 'vue-stash'
import store from './store/store'
App.use(VueStash)

export default {
    components: {
        Elements,
        Properties,
        Theming,
        draggable,
        TextInput,
        LongTextInput,
        NumberInput,
        SelectList,
        RadioButton,
        Checkbox,
        TimePicker,
        DatePicker,
        DatetimePicker,
        Rating,
        Button,
        TextEditor
    },
    data() {
        return {
            fields: [
                {
                    'name': 'TextInput',
                    'text': 'Text',
                    'group': 'form', //form group
                    'hasOptions': false,
                    'isRequired': false,
                    'isHelpBlockVisible': false,
                    'isPlaceholderVisible': true,
                    'isUnique': false
                },
                {
                    'name': 'LongTextInput',
                    'text': 'Long Text',
                    'group': 'form',
                    'hasOptions': false,
                    'isRequired': false,
                    'isHelpBlockVisible': false,
                    'isPlaceholderVisible': true,
                    'isUnique': false
                },
                {
                    'name': 'NumberInput',
                    'text': 'Number',
                    'group': 'form',
                    'hasOptions': false,
                    'isRequired': false,
                    'isHelpBlockVisible': false,
                    'isPlaceholderVisible': false,
                    'isUnique': false
                },
                {
                    'name': 'SelectList',
                    'text': 'Select',
                    'group': 'form',
                    'hasOptions': true,
                    'isRequired': false,
                    'isHelpBlockVisible': false,
                    'isPlaceholderVisible': false,
                    'isUnique': false
                },
                {
                    'name': 'RadioButton',
                    'text': 'Radio',
                    'group': 'form',
                    'hasOptions': true,
                    'isRequired': false,
                    'isHelpBlockVisible': false,
                    'isPlaceholderVisible': false,
                    'isUnique': false
                },
                {
                    'name': 'Checkbox',
                    'text': 'Checkbox',
                    'group': 'form',
                    'hasOptions': true,
                    'isRequired': false,
                    'isHelpBlockVisible': false,
                    'isPlaceholderVisible': false,
                    'isUnique': false
                },
                {
                    'name': 'TimePicker',
                    'text': 'Time Picker',
                    'group': 'form',
                    'hasOptions': false,
                    'isRequired': false,
                    'isHelpBlockVisible': false,
                    'isPlaceholderVisible': false,
                    'isUnique': false
                },
                {
                    'name': 'DatePicker',
                    'text': 'Date Picker',
                    'group': 'form',
                    'hasOptions': false,
                    'isRequired': false,
                    'isHelpBlockVisible': false,
                    'isPlaceholderVisible': false,
                    'isUnique': false
                },
                {
                    'name': 'DatetimePicker',
                    'text': 'Date-Time Picker',
                    'group': 'form',
                    'hasOptions': false,
                    'isRequired': false,
                    'isHelpBlockVisible': false,
                    'isPlaceholderVisible': false,
                    'isUnique': false
                },
                {
                    'name': 'Rating',
                    'text': 'Rating',
                    'group': 'form',
                    'hasOptions': false,
                    'isRequired': false,
                    'isHelpBlockVisible': false,
                    'isPlaceholderVisible': false,
                    'isUnique': false
                },
                {
                    'name': 'Button',
                    'text': 'Button',
                    'group': 'button',
                    'hasOptions': false,
                    'isRequired': false,
                    'isHelpBlockVisible': false,
                    'isPlaceholderVisible': false,
                    'isUnique': true
                },
                {
                    'name': 'TextEditor',
                    'text': 'Text editor',
                    'group': 'static',
                    'hasOptions': false,
                    'isRequired': false,
                    'isHelpBlockVisible': false,
                    'isPlaceholderVisible': false,
                    'isUnique': false
                }
            ],

            sortElementOptions: {
                group: {name: 'formbuilder', pull: false, put: true},
                sort: true,
                handle: ".form__actionitem--move"
            },

            dropElementOptions: {
                group: {name: 'formbuilder', pull: 'clone', put: false},
                sort: false,
                ghostClass: "sortable__ghost",
                filter: ".is-disabled"
            }
        }
    },
    methods: {
        deleteElement(index) {
            this.$store.state.activeForm = [];
            this.$store.state.activeTabForFields = "elements";
            this.$store.state.forms.splice(index, 1);
        },

        cloneElement(index, form) {
            const cloned = _.cloneDeep(form); // Clonar con lodash
            this.$store.state.forms.splice(index, 0, cloned);
        },

        editElementProperties(form) {
            this.$store.state.activeForm = form;
            this.$store.state.activeTabForFields = "properties";
        }
    }
}
