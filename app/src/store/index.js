import Vue from 'vue'
import Vuex from 'vuex'
import config from '@/config.json'
import router from '@/router'

const axios = require('axios')

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
		let response = await fetch(config.baseUrl+"jlogin", {
			method: 'POST',
			credentials: 'include',
			headers: {
				'Accept' : 'application/json',
				'Content-Type' : 'application/json'
			},
			body: JSON.stringify({ "username" : payload.username , "password" : payload.password })

		})
		if(!response.ok) {
			throw new Error('login failed')
		}
	},
	loadUserData: async function(context) {
		try {
			//axiosRequest = axios.get(config.baseUrl+"updateUserData", {withCredentials: true})
			let response = await fetch(config.baseUrl+"updateUserData", {
				method: 'POST',
				credentials: 'include',
				headers: {
					'Accept' : 'application/json',
					'Content-Type' : 'application/json'
				}
	
	
				})
			
			context.commit("setLoggedInUser", response.data.user)
			context.commit("setCurrentSeason", response.data.cycle)
			context.commit("setUserScore", response.data.userScore)
			context.commit("setTargetScore", response.data.targetScore)
			await Promise.all([context.dispatch("getTransactions"), context.dispatch("getUsers")])
		} catch {

		}
	},
	logoutUser: async function(context) {
		try {
			await axios.get(config.baseUrl+"jlogout", {withCredentials: true})
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
			let response = await axios.get(config.baseUrl+"api/myScore", {headers: {"Accept" : "application/json"}, withCredentials: true})
			context.commit('setUserScore',response.data.userScore)
		} catch {
			context.commit('setUserScore',0)
		}
	},
	getTransactions: async function(context, payload) {
		try {
			let response = await axios.get(config.baseUrl+"api/transfers", {headers: {"Accept" : "application/json"}, withCredentials: true})
			context.commit('setTransactions',response.data)
		} catch {
			context.commit('setTransactions',[])
		}
	},
	getUsers: async function(context) {
		try {
			let response = axios.get(config.baseUrl+"api/users", {headers: {"Accept" : "application/json"}, withCredentials: true})
			context.commit('setUserList',response.data)
		} catch {
			context.commit('setUserList',[])
		}
	}
	  
  },
  getters: {
	  getUserScore: state => state.userScore,
	  getTargetScore: state => state.targetScore,
	  isLoggedIn: state => state.loggedInUser.username !== undefined,
	  getUser: state => state.loggedInUser,
	  getUsers: state => state.userList,
	  getUserIRI: state => "/api/users/"+state.loggedInUser.id,
	  isLoggedInUserAdmin: state => state.loggedInUser && state.loggedInUser.roles && state.loggedInUser.roles.includes("ROLE_ADMIN"),
	  getTransactions: state => state.transactions,
	  getSentTransactions: (state) => {
		  return state.transactions && state.transactions.filter((item) => (item.origin === "/api/users/"+state.loggedInUser.id))
	  },
	  getReceivedTransactions: (state) => {
		  return state.transactions && state.transactions.filter((item) => (item.target === "/api/users/"+state.loggedInUser.id))
	  },
	  getUserFromIRI: (state) => (item) =>  state.userList.filter((iter) => "/api/users/"+iter.id === item )[0],
	  getUserFromName: (state) => (userName) => {
			if(state.userList.length == 0) { return "" }
			let selectedUser = state.userList.filter((user) => {
				return (user.firstname +" "+ user.lastname) === userName })
			if(selectedUser.length === 1) {
				return "/api/users/"+selectedUser[0].id
			}
			return false
		}
  },
  modules: {
  }
})

export default store