<template>
	<tr class="iedit">
		<td class="has-row-actions">
			<strong>{{ item.page_title }}</strong>
			<div class="row-actions">
				<span class="edit" @click="editItem( item )"><a>Edit</a></span> | 
				<span class="trash" @click="deleteItem( item.id )"><a>Delete</a></span>
			</div>
		</td>
		<td align="center">
			<em>redirects to</em>
		</td>
		<td>
			<strong>{{ item.post_title }}</strong>
		</td>
		<td>
			<ui-switch
				class="cfc-switch"
				v-model="item.is_active"
				true-value="1"
				false-value="0"
				@change="updateItem( item )">
			</ui-switch>
		</td>
	</tr>
</template>
<script>
	export default {
		props: {
			item: Object,
			required: true
		},
		data() {
			return {
				success: 'Redirect updated succesfully.',
				delete: 'Redirect deleted succesfully.'
			}
		},
		methods: {
			updateItem( item ) {

				this.api.r.patch( '/', item )
					.then( ( result ) => {

						this.event.fire( 'add-notice', this.success )

					} )
					.catch( ( error ) => {

						console.log( error )
						this.event.fire( 'add-notice', error.message )

					} )

			},
			deleteItem( id ) {

				this.api.r.delete( '/', { id } )
					.then( ( result ) => {

						this.event.fire( 'add-notice', this.delete )
						this.event.fire( 'refresh-table' )

					} )
					.catch( ( error ) => {

						console.log( error )
						this.event.fire( 'add-notice', error.message )

					} )

			},
			editItem( item ) {

				this.event.fire( 'open-form', item )

			}
		}
	}
</script>

<style>
	a {
		cursor: pointer;
	}
	.cfc-switch {
		z-index: 0;
	}
</style>