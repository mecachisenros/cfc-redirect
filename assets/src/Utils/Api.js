import qs from 'qs'
import axios from 'axios'

export default ( endpoint ) => {

	// instance options
	const options = {
		baseURL: endpoint,
		headers: { 'X-WP-Nonce': State.nonce }
	}

	// create instance
	const http = axios.create( options )

	// query string helper
	const getQueryString = ( ...args ) => {

		let [ path = '/', params ] = args

		params.json = params.json ? JSON.stringify( params.json ) : params.json

		return `${ path }?${ qs.stringify( params ) }`

	}

	return {

		// get helper
		get: ( path ) => http.get( path ).then( result => result.data ),

		// post helper
		post: ( ...args ) => http.post( getQueryString( ...args ) ).then( result => {

				if ( result.data && result.data.values ) return result.data.values

				return result.data

			} ),

		// patch helper
		patch: ( ...args ) => http.patch( getQueryString( ...args ) ).then( result => result.data ),

		// delete helper
		delete: ( ...args ) => http.delete( getQueryString( ...args ) ).then( result => result.data ),

		// query string helper
		qs: getQueryString

	}

}
