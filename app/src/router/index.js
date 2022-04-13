import Vue from 'vue'
import VueRouter from 'vue-router'
import Login from '../views/Login.vue'
import Sending from '../views/SendPoints.vue'
import ScoreBoard from '@/views/ScoreBoard'
import Admin from '@/views/Admin/Admin.vue'
import AdminUsers from '@/views/Admin/Users/Users.vue'

Vue.use(VueRouter)

const routes = [
  {
    path: '/',
    name: 'Login',
    component: Login
  },
  {
    path: '/score',
    name: 'Score',
    component: ScoreBoard
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
    path: '/admin',
    name: 'Admin',
    component: Admin,
    children: [
      {
        path: 'users',
        component: AdminUsers
      }
    ]
  }
]

const router = new VueRouter({
  mode: 'history',
  base: process.env.BASE_URL,
  routes
})

export default router
