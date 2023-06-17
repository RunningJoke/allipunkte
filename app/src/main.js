import '@babel/polyfill'
import 'mutationobserver-shim'
import Vue from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import { BootstrapVue, IconsPlugin , ToastPlugin} from 'bootstrap-vue'
import uniqid from '@/plugins/uniqid'
import { requestManager } from '@/plugins/requestManager.js'


// Install BootstrapVue
Vue.use(ToastPlugin, { 'BToast': { toaster: 'b-toaster-bottom-right' , solid: true}})
Vue.use(BootstrapVue)
// Optionally install the BootstrapVue icon components plugin
Vue.use(IconsPlugin)

Vue.use(uniqid)
Vue.use(requestManager)



Vue.config.productionTip = false

new Vue({
  router,
  store,
  render: h => h(App)
}).$mount('#app')

store.dispatch("loadUserData")
