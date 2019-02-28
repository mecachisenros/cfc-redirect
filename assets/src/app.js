import Vue from 'vue'
import KeenUI from 'keen-ui'
import { 
	UiSnackbarContainer,
	UiProgressLinear,
	UiProgressCircular,
	UiSwitch,
	UiModal
} from 'keen-ui';

import 'keen-ui/dist/keen-ui.css'
import 'vue-multiselect/dist/vue-multiselect.min.css'

import Api from 'Utils/Api'
import Event from 'Utils/Event'

import App from './App'

Vue.use( KeenUI )

// rest api helper 
Vue.prototype.api = {
	r: Api( State.redirectBase ),
	wp: Api( State.wpBase ),
	crm: Api( State.crmBase )
}

// event helper
Vue.prototype.event = Event

new Vue( {
	components: {
		UiSnackbarContainer,
		UiProgressLinear,
		UiProgressCircular,
		UiSwitch,
		UiModal
    },
	el: '#cfcr-app',
	render: h => h( App )
} )
