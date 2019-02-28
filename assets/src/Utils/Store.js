import Vue from 'vue'

export const store = Vue.observable( {
	postsAndPages: []
} )

export const mutations = {
	addPostsAndPages( postsAndPages ) {

		store.postsAndPages.push( ...postsAndPages )

	}
}