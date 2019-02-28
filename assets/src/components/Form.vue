<template>
	<ui-modal
		ref="form"
		:title="formTitle"
		@close="resetForm"
		size="large">

		<CRMFormRow :selected="item"/>
		<WPFormRow :selected="item"/>

		<ui-switch
			class="cfc-switch row-item-2"
			v-model="item.is_active"
			true-value="1"
			false-value="0">
			Is active?
		</ui-switch>

		<div class="divider"></div>

		<template slot="footer">
			<a class="page-title-action" @click="createItem( item )">
				<span v-if="isEdit">
					Save
				</span>
				<span v-else>
					Add new redirect
				</span>
			</a>
		</template>

	</ui-modal>
</template>

<script>
	import CRMFormRow from 'Components/CRMFormRow'
	import WPFormRow from 'Components/WPFormRow'

	export default {
		components: {
			CRMFormRow,
			WPFormRow
		},
		data() {
			return {
				isEdit: false,
				isInvalidPage: false,
				item: {},
				defaults: {
					entity_id: '',
					post_id: '',
					page_title: '',
					post_title: '',
					page_type: 'contribution_page',
					post_type: 'page',
					is_active: '1'
				}
			}
		},
		mounted() {

			this.registerEvents()

		},
		computed: {
			formTitle() {

				return this.isEdit ? 'Edit redirect' : 'New redirect'

			}
		},
		methods: {
			registerEvents() {

				this.event.listen( 'open-form', ( item ) => this.openForm( item ) )
				this.event.listen( 'close', () => this.resetForm() )

				this.event.listen( 'page-selection', ( selection ) => {
					if ( selection ) {
						this.item.entity_id = selection.value
						this.item.page_title = selection.label
					} 
				} )

				this.event.listen( 'post-selection', ( selection ) => {
					if ( selection ) {
						this.item.post_id = selection.value
						this.item.post_title = selection.label
						this.item.post_type = selection.post_type
					} 
				} )

			},
			openForm( item ) {

				if ( item ) {
					this.item = item
					this.isEdit = true
				} else {
					this.item = this.defaults
				}

				this.$refs.form.open()

			},
			resetForm() {

				this.isEdit = false
				this.item = this.defaults

			},
			createItem() {

				if ( ! this.item.entity_id || ! this.item.post_id ) return

				const method = this.isEdit ? 'patch' : 'post'

				this.api.r[method]( '/', this.item )
					.then( ( result ) => {

						this.event.fire( 'add-notice', 'Redirect created succesfully.' )
						this.$refs.form.close()
						this.event.fire( 'refresh-table' )

					} )
					.catch( ( error ) => {

						console.log( error )
						this.event.fire( 'add-notice', `${error.message}. Perhaps you are trying to create more than one redirect per page/event?` )

					} )

			}
		}
	}
</script>

<style>
	.form-row {
		display: flex;
		justify-content: space-between;
		align-items: baseline;
	}
	.row-item-1 {
		flex: 2;
		order: 1;
		margin-right: 5px;
	}
	.row-item-2 {
		order: 2;
		align-self: flex-end;
		margin-bottom: 1rem;
	}
	.divider {
		margin-top: 10rem;
	}
	.spinner {
		z-index: 1000;
	}
	.multiselect__input, input[type=text], .multiselect__input:focus, input[type=text]:focus {
		border: 0px;
		box-shadow: none;
		outline: none;
	}
</style>