<template>
    <div class="login-container">
      <h2>Student Login</h2>
      <form @submit.prevent="login">
        <input v-model="matric_number" placeholder="Matric Number" required />
        <input v-model="pin" type="password" placeholder="PIN" required />
        <button type="submit" :disabled="loading">Login</button>
        <div v-if="error" class="error">{{ error }}</div>
      </form>
    </div>
  </template>
  
  <script>
  import api from '../api';
  
  export default {
    data() {
      return {
        matric_number: '',
        pin: '',
        loading: false,
        error: ''
      };
    },
    methods: {
      async login() {
        this.loading = true;
        this.error = '';
        try {
          const res = await api.post('/student/login', {
            matric_number: this.matric_number,
            pin: this.pin
          });
          if (res.data.success) {
            // Save student info (for demo, use localStorage)
            localStorage.setItem('student', JSON.stringify(res.data.student));
            this.$router.push('/student/dashboard');
          } else {
            this.error = res.data.message || 'Login failed';
          }
        } catch (err) {
          this.error = err.response?.data?.message || 'Login failed';
        } finally {
          this.loading = false;
        }
      }
    }
  };
  </script>
  
  <style scoped>
  .login-container { max-width: 400px; margin: 2rem auto; }
  .error { color: red; margin-top: 1rem; }
  </style>