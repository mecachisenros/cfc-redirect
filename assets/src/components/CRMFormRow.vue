<template>
	<div class="form-row">

		<div class="row-item-1" style="width:100%;">
			<v-select
				:value="currentSelection"
				:options="options"
				:loading="isLoading"
				:placeholder="label"
				:onChange="onChange"
				@search="onSearch"
				@search:focus="onSearch"
				>
					<template slot="spinner">
						<ui-progress-circular color="primary" v-show="isLoading"></ui-progress-circular>
					</template>
				</v-select>
		</div>

		<ui-switch
			class="cfc-switch row-item-2"
			v-model="isPageOrEvent"
			true-value="1"
			false-value="0">
			Event | Contribution
		</ui-switch>

	</div>
</template>

<script>
	import vSelect from 'vue-select'

	export default {
		components: {
			vSelect
		},
		props: {
			selected: {
				type: Object
			}
		},
		data() {
			return {
				isLoading: false,
				label: 'Contribution page to redirect from',
				isPageOrEvent: 1,
				options: []
			}
		},
		computed: {
			currentSelection: {
				get: function() {
					if ( this.selected.entity_id ) {
						return { value: this.selected.entity_id, label: this.selected.page_title }
					} else {
						return null
					}
				},
				set: value => value
			}
		},
		methods: {
			onSearch( term ) {

				const entity = parseInt( this.isPageOrEvent ) ? 'ContributionPage' : 'Event'

				const search = term ? `%${term}%` : ''

				const params = {
					entity,
					action: 'get',
					json: {}
				}

				if ( search ) params.json.title = { 'LIKE': search }

				if ( entity == 'Event' ) params.json.is_template = 0

				this.isLoading = true

				this.api.crm.post( '/', params )
					.then( ( result ) => {

						this.options = result.map( page => {

							return { value: page.id, label: page.title }

						} )

						this.isLoading = false

					} )
					.catch( error => {

						console.log( error )
						this.event.fire( 'add-notice', error.message )

					} )

			},
			onChange( selection ) {

				this.event.fire( 'page-selection', selection )

			}
		},
		watch: {
			isPageOrEvent( value ) {

				this.options = []
				this.currentSelection = {}

				let newLabel = 'page to redirect from'
				this.label = parseInt( value ) ? `Contribution ${newLabel}` : `Event ${newLabel}`
				
				this.selected.page_type = parseInt( value ) ? 'contribution_page' : 'event'


			},
		}
	}
</script>