import Vue from 'vue'

export default ( () => {
	
	const vue = new Vue()

	return {

		// fire an event
		fire: ( event, data = null ) => {
			vue.$emit( event, data )
		},

		// listen for an event
		listen: ( event, callback ) => {
			vue.$on( event, callback )
		}
		
	}

} )()
