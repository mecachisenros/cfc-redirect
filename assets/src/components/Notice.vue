<template>
	<ui-snackbar-container
		ref="snackbarContainer"
		position="right"
		transition="slide"
		:duration="noticeDuration"
		class="notices"
	></ui-snackbar-container>
</template>

<script>
	export default {
		data() {
			return {
				noticeDuration: 3500
			}
		},
		mounted() {

			this.event.listen( 'add-notice', ( message ) => {

				this.$refs.snackbarContainer.createSnackbar( { message } )

			} )
		}
	}
</script>

<style>
	.notices {
		position: fixed;
		top: 3rem;
		bottom: initial;
		z-index: 1;
	}
</style>