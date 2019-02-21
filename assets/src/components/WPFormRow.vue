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
			v-model="isPageOrPost"
			true-value="1"
			false-value="0">
			Post | Page
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
				label: 'Page to redirect to',
				isPageOrPost: 1,
				options: []
			}
		},
		computed: {
			currentSelection: {
				get: function() {
					if ( this.selected.post_id ) {
						return { value: this.selected.post_id, label: this.selected.post_title }
					} else {
						return null
					}
				},
				set: value => value
			}
		},
		methods: {
			onSearch( term ) {

				const entity = parseInt( this.isPageOrPost ) ? 'pages' : 'posts'

				const path = term ? `/${entity}?search=${term}` : `/${entity}`

				this.isLoading = true

				this.api.wp.get( path )
					.then( result => {

						this.options = result.map( post => {

							return { value: post.id, label: post.title.rendered }

						} )

						this.isLoading = false

					} )
					.catch( error => {

						console.log( error )
						this.event.fire( 'add-notice', error.message )

					} )

			},
			onChange( selection ) {

				this.event.fire( 'post-selection', selection )

			}
		},
		watch: {
			isPageOrPost( value ) {

				this.options = []
				this.currentSelection = {}

				let newLabel = 'to redirect to'
				this.label = parseInt( value ) ? `Page ${newLabel}` : `Post ${newLabel}`

				this.selected.post_type = parseInt( value ) ? 'page' : 'post'

			}
		}
	}
</script>