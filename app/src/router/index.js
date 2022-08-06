import Vue from 'vue'
import store from '../store/index.js'
import VueRouter from 'vue-router'
import Login from '../views/Login.vue'
import Sending from '../views/SendPoints.vue'
import ScoreBoard from '@/views/ScoreBoard'
import ChangePassword from '@/views/ChangePassword.vue'
import Admin from '@/views/Admin/Admin.vue'
import Docs from '@/views/Docs.vue'
import AdminUsers from '@/views/Admin/Users/Users.vue'
import AdminCycles from '@/views/Admin/Cycles/Cycles.vue'

import Petitions from '@/views/Petitions/Petitions.vue'

import { BIconExclamationSquareFill } from 'bootstrap-vue'

Vue.use(VueRouter)

const routes = [
  {
    path: '/',
    name: 'Login',
    component: Login
  },
  {
    path: '/changePassword',
    name: 'changePassword',
    component: ChangePassword
  },
  {
    path: '/score',
    name: 'Score',
    component: ScoreBoard
  },
  {
    path: '/petitions',
    name: 'Petitions',
    component: Petitions
  },
  {
    path: '/send',
    name: 'Send',
    // route level code-splitting
    // this generates a separate chunk (about.[hash].js) for this route
    // which is lazy-loaded when the route is visited.
    component: Sending
  },
  {
    path: '/docs',
    name: 'Docs',
    // route level code-splitting
    // this generates a separate chunk (about.[hash].js) for this route
    // which is lazy-loaded when the route is visited.
    component: Docs
  },
  {
    path: '/admin',
    name: 'Admin',
    component: Admin,
    children: [
      {
        path: 'users',
        component: AdminUsers
      },
      {
        path: 'cycles',
        component: AdminCycles
      }
    ]
  }
]

const router = new VueRouter({
  mode: 'history',
  base: process.env.BASE_URL,
  routes
})

router.beforeEach((to, from, next) => {
  if(!store.getters.isLoggedIn && to.name !== 'Login') {
    next('/')
  } else {
    next()
  }
})

export default router
