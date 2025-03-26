import Vue from 'vue'
import Vuex from 'vuex'
import config from '@/config.json'
import router from '@/router'

import { requestManager } from '@/plugins/requestManager'

import moment from 'moment'

Vue.use(Vuex)
const store = new Vuex.Store({
  state: {
	  isLoading: true,
	  currentCycle: {},
	  loggedInUser: {},
	  userList: [],
	  cycleList: [],
	  transactions: [],
	  pastCycles: [],
	  userScore: 0,
	  targetScore: 0
  },
  mutations: {
	  setIsLoadingData(state, value) { state.isLoading = value },
	  setTransactions(state, value) { state.transactions = value },
	  setPastCycles(state, value) { state.pastCycles = value },
	  setUserList(state, value) { state.userList = value },
	  setCurrentCycle(state, value) { state.currentCycle = value },
	  setLoggedInUser(state, value) { state.loggedInUser = value },
	  setUserScore(state, value) { state.userScore = value },
	  setTargetScore(state, value) { state.targetScore = value },
	  setCycles(state, value) { 
		  state.cycleList = value.map(v => {
			  let fromDateObj = moment(v.fromDate)
			  let toDateObj = moment(v.toDate)
			return {
			  ...v,
			  toDate: toDateObj.format('YYYY-MM-DD'),
			  fromDate: fromDateObj.format('YYYY-MM-DD')
		  } })
		}
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
		context.commit('setIsLoadingData', true)
		try {
			let responseBody = await requestManager.sendJsonRequest(config.baseUrl+"updateUserData")
			//add field validation
			context.commit("setLoggedInUser", responseBody)
			context.commit("setCurrentCycle", responseBody.cycle)
			context.commit("setUserScore", responseBody.userScore)
			context.commit("setTargetScore", responseBody.targetScore)
			
			let promiseArray = [context.dispatch("getTransactions"), context.dispatch("getUsers")]
			

			if(context.getters.isLoggedInUserAdmin) {
				promiseArray.push(context.dispatch('loadAdminData'))
			}
			
			
			await Promise.all(promiseArray)

			if(router.currentRoute.name == "Login") {
				router.push('/score')
			}

		} catch {
			//add error handling
			context.commit("setLoggedInUser", {})
			context.commit("setCurrentCycle", {})
			context.commit("setUserScore", 0)
			context.commit("setTargetScore", 0)
			router.push('/')
			window.$globalVue.$bvToast.toast('Bitte neu einloggen', {variant: 'primary'})

		} finally {
			context.commit('setIsLoadingData', false)
		}
	},
	loadAdminData: async function(context) {
		if(!context.getters.isLoggedInUserAdmin) return
		try {
			let responseBody = await requestManager.sendJsonRequest(config.baseUrl+"cycles")
			//add field validation
			context.commit("setCycles", responseBody)
		} catch {
			//add error handling
			window.$globalVue.$bvToast.toast('Laden der Daten fehlgeschlagen', {variant: 'danger'})
		}
	},
	logoutUser: async function(context) {
		try {
			await requestManager.sendJsonRequest(config.baseUrl+"jlogout")
		} finally {	
			//always clear the store even if the logout fails	
			context.commit("setLoggedInUser", {})
			context.commit("setCurrentCycle", {})
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
			window.$globalVue.$bvToast.toast('Benutzerdaten konnten nicht geladen werden', {variant: 'danger'})
		}
	},
	getTransactions: async function(context, payload) {
		try {
			let response = await requestManager.sendJsonRequest(config.baseUrl+"transfers")
			context.commit('setTransactions',response)
		} catch {
			context.commit('setTransactions',[])
			window.$globalVue.$bvToast.toast('Transaktionen konnten nicht geladen werden', {variant: 'danger'})
		}
	},
	getPastCycles: async function(context, payload) {
		try {
			let response = await requestManager.sendJsonRequest(config.baseUrl+"pastCycles")
			context.commit('setPastCycles',response)
		} catch {
			context.commit('setPastCycles',[])
			window.$globalVue.$bvToast.toast('Vergangene Saisons konnten nicht geladen werden', {variant: 'danger'})
		}
	},
	getUsers: async function(context) {
		try {
			let data = await requestManager.sendJsonRequest(config.baseUrl+"users")
			context.commit('setUserList',data)
		} catch {
			context.commit('setUserList',[])
			window.$globalVue.$bvToast.toast('Benutzer konnten nicht geladen werden', {variant: 'danger'})
		}
	}
	  
  },
  getters: {
	  baseUrl: state => config.baseUrl,
	  getUserScore: state => state.userScore,
	  getTargetScore: state => state.targetScore,
	  isLoggedIn: state => state.loggedInUser.username !== undefined,
	  getUser: state => state.loggedInUser,
	  getUsers: state => state.userList,
	  getCycles: state => state.cycleList,
	  getUserIRI: state => "/users/"+state.loggedInUser.id,
	  isLoading: state => state?.isLoading ?? false,
	  isLoggedInUserAdmin: state => state?.loggedInUser?.isAdmin ?? false,
	  isLoggedInUserCreator: state => state?.loggedInUser?.isCreator ?? false,
	  getTransactions: state => state.transactions,
	  getPastCycles: state => state.pastCycles,
	  getSentTransactions: (state) => {
		  return state.transactions && state.transactions.filter((item) => (item.origin === "/users/"+state.loggedInUser.id))
	  },
	  getCurrentCycle: (state) => state.currentCycle,
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