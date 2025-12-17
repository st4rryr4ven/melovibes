import {createRouter, createWebHistory} from 'vue-router'
import Melovibes from '@/views/MelovibesMain.vue'
import AllUsers from '@/views/AllUsers.vue'
import Login from '@/views/Login.vue'
import Register from '@/views/Register.vue'

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: '/',
            redirect: {name: 'melovibes'}
        },
        {
            path: '/melovibes',
            name: 'melovibes',
            component: Melovibes
        },
        {
            path: '/register',
            name: 'register',
            component: Register
        },
        {
            path: '/login',
            name: 'login',
            component: Login
        },
        {
            path: '/users',
            name: 'allUsers',
            component: AllUsers,
        }
    ]
})

export default  router
