<template>
    <div class="register-container">
      <h2>Student Registration</h2>
      <form @submit.prevent="register">
        <input v-model="matric_number" placeholder="Matric Number" required />
        <input v-model="pin" type="password" placeholder="PIN" required />
        <input v-model="full_name" placeholder="Full Name" required />
        <input v-model="email" type="email" placeholder="Email" required />
        <input v-model="advisor_id" placeholder="Advisor ID (optional)" />
        <input v-model="phone_number" placeholder="Phone Number (optional)" />
        <button type="submit" :disabled="loading">Register</button>
        <div v-if="error" class="error">{{ error }}</div>
        <div v-if="success" class="success">{{ success }}</div>
      </form>
      <router-link to="/student/login">Already have an account? Login</router-link>
    </div>
  </template>
  
  <script setup>
  import { ref } from 'vue'
  import api from '../api'
  import { useRouter } from 'vue-router'
  
  const router = useRouter()
  const matric_number = ref('')
  const pin = ref('')
  const full_name = ref('')
  const email = ref('')
  const advisor_id = ref('')
  const phone_number = ref('')
  const loading = ref(false)
  const error = ref('')
  const success = ref('')
  
  const register = async () => {
    loading.value = true
    error.value = ''
    success.value = ''
    try {
      const res = await api.post('/student/register', {
        matric_number: matric_number.value,
        pin: pin.value,
        full_name: full_name.value,
        email: email.value,
        advisor_id: advisor_id.value || null,
        phone_number: phone_number.value || null
      })
      if (res.data.success) {
        success.value = 'Registration successful! You can now log in.'
        setTimeout(() => router.push('/student/login'), 1500)
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