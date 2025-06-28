// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import StudentLogin from '../views/StudentLogin.vue'
import StudentDashboard from '../views/StudentDashboard.vue'
import StudentRegister from '../views/StudentRegister.vue'
import LecturerLogin from '../views/LecturerLogin.vue'
import LecturerDashboard from '../views/LecturerDashboard.vue'
import LecturerRegister from '../views/LecturerRegister.vue'
import AdminDashboard from '../views/AdminDashboard.vue'
import AdvisorDashboard from '../views/AdvisorDashboard.vue'

const routes = [
  { path: '/student/login', component: StudentLogin },
  { path: '/student/dashboard', component: StudentDashboard },
  { path: '/student/register', component: StudentRegister },
  { path: '/lecturer/login', component: LecturerLogin },
  { path: '/lecturer/dashboard', component: LecturerDashboard },
  { path: '/lecturer/register', component: LecturerRegister },
  { path: '/admin/dashboard', component: AdminDashboard },
  { path: '/advisor/dashboard', component: AdvisorDashboard },
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router