<template>
	<div>
		<Notice/>
		<Header/>
		<Table/>
		<Form/>
	</div>
</template>

<script>
	import Header from 'Components/Header'
	import Table from 'Components/Table'
	import Notice from 'Components/Notice'
	import Form from 'Components/Form'

	export default {
		components: {
			Header,
			Table,
			Notice,
			Form
		}
	}
</script>
