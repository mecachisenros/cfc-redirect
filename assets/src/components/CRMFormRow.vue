<template>
	<div class="form-row">

		<div class="row-item-1" style="width:100%;">
			<multiselect
				:value="currentSelection"
				hideSelected
				label="label"
				track-by="value"
				:internal-search="false"
				:placeholder="label"
				:options="options"
				:loading="isLoading"
				@search-change="onSearch"
				@open="onSearch"
				@input="onChange"
			></multiselect>
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
	import Multiselect from 'vue-multiselect'

	export default {
		components: {
			Multiselect
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