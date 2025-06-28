<template>
    <div class="register-container">
      <h2>Lecturer Registration</h2>
      <form @submit.prevent="register">
        <input v-model="staff_id" placeholder="Staff ID" required />
        <input v-model="password" type="password" placeholder="Password" required />
        <input v-model="full_name" placeholder="Full Name" required />
        <input v-model="email" type="email" placeholder="Email" required />
        <input v-model="phone_number" placeholder="Phone Number (optional)" />
        <button type="submit" :disabled="loading">Register</button>
        <div v-if="error" class="error">{{ error }}</div>
        <div v-if="success" class="success">{{ success }}</div>
      </form>
      <router-link to="/lecturer/login">Already have an account? Login</router-link>
    </div>
  </template>
  
  <script setup>
  import { ref } from 'vue'
  import api from '../api'
  import { useRouter } from 'vue-router'
  
  const router = useRouter()
  const staff_id = ref('')
  const password = ref('')
  const full_name = ref('')
  const email = ref('')
  const phone_number = ref('')
  const loading = ref(false)
  const error = ref('')
  const success = ref('')
  
  const register = async () => {
    loading.value = true
    error.value = ''
    success.value = ''
    try {
      const res = await api.post('/lecturer/register', {
        staff_id: staff_id.value,
        password: password.value,
        full_name: full_name.value,
        email: email.value,
        phone_number: phone_number.value || null
      })
      if (res.data.success) {
        success.value = 'Registration successful! You can now log in.'
        setTimeout(() => router.push('/lecturer/login'), 1500)
      } else {
        error.value = res.data.message || 'Registration failed'
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Registration failed'
    } finally {
      loading.value = false
    }
  }
  </script>
  
  <style scoped>
  .register-container { max-width: 400px; margin: 2rem auto; }
  .error { color: red; margin-top: 1rem; }
  .success { color: green; margin-top: 1rem; }
  </style>