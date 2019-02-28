<template>
	<div class="form-row">

		<div class="row-item-1" style="width:100%;">
			<multiselect
				:value="currentSelection"
				hideSelected
				label="label"
				track-by="value"
				:placeholder="label"
				:options="postsAndPages"
				@input="onChange">
				<template slot="option" slot-scope="props">
					<div class="option__desc">
						<div class="option__title">{{ props.option.label }}</div>
						<div class="option__small" style="font-size: 12px; line-height: 2.5;"><em>{{ props.option.link }}</em></div>
					</div>
				</template>
			</multiselect>
		</div>

	</div>
</template>

<script>
	import Multiselect from 'vue-multiselect'
	import { store, mutations } from 'Utils/Store'

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
				label: 'Post to redirect to',
				postsAndPages: store.postsAndPages
			}
		},
		created() {

			const pagesPath = '/pages?per_page=100'
			const postsPath = '/posts?per_page=100'

			this.getPostsAndPages( pagesPath )
			this.getPostsAndPages( postsPath )

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
			getPostsAndPages( path, page = 1 ) {

				let postsAndPages = []
				let queryString = `${path}&page=${page}`

				this.api.wp.get( queryString )
					.then( result => {

						this.isLoading = true

						const posts = result.map( post => {
							return { value: post.id, label: post.title.rendered, post_type: post.type, link: post.link }
						} )

						mutations.addPostsAndPages( posts )
						this.getPostsAndPages( path, ++page )
						this.isLoading = false

					} )
					.catch( error => {
						this.isLoading = false
						console.log( error )
					} )

			},
			onChange( selection ) {

				this.event.fire( 'post-selection', selection )

			}
		}
	}
</script>