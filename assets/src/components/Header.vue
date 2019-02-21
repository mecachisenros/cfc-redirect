<template>
	<div style="margin-bottom: 20px;">
		<h1 class="wp-heading-inline">{{title}}</h1>
		<a class="page-title-action" @click="openForm()">{{buttonText}}</a>
		<hr class="wp-header-end">
	</div>
</template>

<script>
	export default {
		data() {
			return {
				title: 'Caldera Forms CiviCRM Redirect',
				buttonText: 'New Redirect'
			}
		},
		mounted() {
			
		},
		methods: {
			openForm() {

				this.event.fire( 'open-form' )

			}
		}
	}
</script>
