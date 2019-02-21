<template>
	<div>

		<ui-progress-linear v-show="isLoading"></ui-progress-linear>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th class="manage-column column-primary" v-for="header in headers">
						<strong>{{ header.label }}</strong>
					</th>
				</tr>
			</thead>
			<tbody class="the-list">
				<BodyRow v-for="item in redirects" :item="item" :key="item.id"/>
				<tr v-if="! redirects.length && ! isLoading">
					<td colspan="3">No redirects have been setup.</td>
				</tr>
			</tbody>
		</table>

	</div>
</template>

<script>
	import BodyRow from './BodyRow'

	export default {
		components: {
			BodyRow
		},
		data() {
			return {
				isLoading: false,
				redirects: [],
				headers: [
					{ label: 'CiviCRM Page/Event' },
					{ label: '' },
					{ label: 'Page/Post' },
					{ label: 'Active?' }
				]
			}
		},
		mounted() {

			this.registerEvents()
			this.getData()

		},
		methods: {
			registerEvents() {

				this.event.listen( 'refresh-table', this.getData )

			},
			getData() {

				this.isLoading = true

				this.api.r.get()
					.then( ( result ) => {
						this.redirects = result
						this.isLoading = false
					} )

			}
		}
	}
</script>