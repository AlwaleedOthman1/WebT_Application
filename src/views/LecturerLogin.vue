<template>
    <div class="login-container">
      <h2>Lecturer Login</h2>
      <form @submit.prevent="login">
        <input v-model="staff_id" placeholder="Staff ID" required />
        <input v-model="password" type="password" placeholder="Password" required />
        <button type="submit" :disabled="loading">Login</button>
        <div v-if="error" class="error">{{ error }}</div>
      </form>
    </div>
  </template>
  
  <script setup>
  import { ref } from 'vue'
  import api from '../api'
  import { useRouter } from 'vue-router'
  
  const router = useRouter()
  const staff_id = ref('')
  const password = ref('')
  const loading = ref(false)
  const error = ref('')
  
  const login = async () => {
    loading.value = true
    error.value = ''
    try {
      const res = await api.post('/lecturer/login', {
        staff_id: staff_id.value,
        password: password.value
      })
      if (res.data.success) {
        localStorage.setItem('lecturer', JSON.stringify(res.data.lecturer))
        router.push('/lecturer/dashboard')
      } else {
        error.value = res.data.message || 'Login failed'
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Login failed'
    } finally {
      loading.value = false
    }
  }
  </script>
  
  <style scoped>
  .login-container { max-width: 400px; margin: 2rem auto; }
  .error { color: red; margin-top: 1rem; }
  </style>