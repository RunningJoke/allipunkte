import Vue from 'vue'
import Vuex from 'vuex'
import config from '@/config.json'
import router from '@/router'

import { requestManager } from '@/plugins/requestManager'

Vue.use(Vuex)
const store = new Vuex.Store({
  state: {
	  currentSeason: {},
	  loggedInUser: {},
	  userList: [],
	  transactions: [],
	  userScore: 0,
	  targetScore: 0
  },
  mutations: {
	  setTransactions(state, value) { state.transactions = value },
	  setUserList(state, value) { state.userList = value },
	  setCurrentSeason(state, value) { state.currentSeason = value },
	  setLoggedInUser(state, value) { state.loggedInUser = value },
	  setUserScore(state, value) { state.userScore = value },
	  setTargetScore(state, value) { state.targetScore = value }
  },
  actions: {
	logInUser: async function(context, payload) {
		if(!payload?.username || !payload?.password) return false
		try {
		let response = await requestManager.sendJsonRequest(config.baseUrl+"jlogin", 'POST', { "username" : payload.username , "password" : payload.password })

		} catch {
			throw new Error('login failed')
		}
	},
	loadUserData: async function(context) {
		try {
			let responseBody = await requestManager.sendJsonRequest(config.baseUrl+"updateUserData")
			//add field validation
			context.commit("setLoggedInUser", responseBody)
			context.commit("setCurrentSeason", responseBody.cycle)
			context.commit("setUserScore", responseBody.userScore)
			context.commit("setTargetScore", responseBody.targetScore)
			await Promise.all([context.dispatch("getTransactions"), context.dispatch("getUsers")])
		} catch {
			//add error handling
			this._vm.$bvToast.toast('Laden der Daten fehlgeschlagen', {variant: 'danger'})

		}
	},
	logoutUser: async function(context) {
		try {
			await requestManager.sendJsonRequest(config.baseUrl+"jlogout")
		} finally {	
			//always clear the store even if the logout fails	
			context.commit("setLoggedInUser", {})
			context.commit("setCurrentSeason", {})
			context.commit("setUserScore", 0)
			router.push('/')
		}
	},
	updateScoreFromDB: async function(context) {
		try {
			let response = await requestManager.sendJsonRequest(config.baseUrl+"myScore")
			context.commit('setUserScore',response.data.userScore)
		} catch {
			context.commit('setUserScore',0)
			this._vm.$bvToast.toast('Benutzerdaten konnten nicht geladen werden', {variant: 'danger'})
		}
	},
	getTransactions: async function(context, payload) {
		try {
			let response = await requestManager.sendJsonRequest(config.baseUrl+"transfers")
			context.commit('setTransactions',response)
		} catch {
			context.commit('setTransactions',[])
			this._vm.$bvToast.toast('Transaktionen konnten nicht geladen werden', {variant: 'danger'})
		}
	},
	getUsers: async function(context) {
		try {
			let data = await requestManager.sendJsonRequest(config.baseUrl+"users")
			context.commit('setUserList',data)
		} catch {
			context.commit('setUserList',[])
			this._vm.$bvToast.toast('Benutzer konnten nicht geladen werden', {variant: 'danger'})
		}
	}
	  
  },
  getters: {
	  getUserScore: state => state.userScore,
	  getTargetScore: state => state.targetScore,
	  isLoggedIn: state => state.loggedInUser.username !== undefined,
	  getUser: state => state.loggedInUser,
	  getUsers: state => state.userList,
	  getUserIRI: state => "/users/"+state.loggedInUser.id,
	  isLoggedInUserAdmin: state => state.loggedInUser && state.loggedInUser.roles && state.loggedInUser.roles.includes("ROLE_ADMIN"),
	  getTransactions: state => state.transactions,
	  getSentTransactions: (state) => {
		  return state.transactions && state.transactions.filter((item) => (item.origin === "/users/"+state.loggedInUser.id))
	  },
	  getReceivedTransactions: (state) => {
		  return state.transactions && state.transactions.filter((item) => (item.target === "/users/"+state.loggedInUser.id))
	  },
	  getUserFromIRI: (state) => (item) =>  state.userList.filter((iter) => "/users/"+iter.id === item )[0],
	  getUserFromName: (state) => (userName) => {
			if(state.userList.length == 0) { return "" }
			let selectedUser = state.userList.filter((user) => {
				return (user.firstname +" "+ user.lastname) === userName })
			if(selectedUser.length === 1) {
				return "/users/"+selectedUser[0].id
			}
			return false
		}
  },
  modules: {
  }
})

export default store