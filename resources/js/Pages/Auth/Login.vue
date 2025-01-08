<script>
import axios from 'axios';

export default {
  name: 'LoginPage',
  data() {
    return {
      email: '',
      password: '',
      error: ''
    };
  },
  methods: {
    async handleSubmit() {
      try {
        const response = await axios.post('/api/login', {
          email: this.email,
          password: this.password
        });
        
        if (response.data.token) {
          localStorage.setItem('auth_token', response.data.token);
          this.$inertia.visit('/home');
        }
      } catch (error) {
        this.error = 'Login Gagal. Periksa kembali email dan password.';
      }
    }
  }
};
</script>

<template>
    <div class="min-h-screen flex">
      <!-- Left Panel -->
      <div class="w-1/2 bg-sky-100 p-12">
        <h1 class="text-3xl font-bold mb-6">Self and Peer Assessment Project</h1>
        <p class="text-gray-600 leading-relaxed">
          Lorem ipsum dolor sit amet, consectetur adipiscing elit,
          sed do eiusmod tempor incididunt ut labore et dolore
          magna aliqua. Ut enim ad minim veniam, quis nostrud
          exercitation ullamco laboris nisi ut aliquip ex ea
          commodo consequat. Duis aute irure dolor in
          reprehenderit in voluptate velit esse cillum dolore eu
          fugiat nulla pariatur. Excepteur sint occaecat cupidatat
          non proident, sunt in culpa qui officia deserunt mollit
          anim id est laborum.
        </p>
      </div>
  
      <!-- Right Panel - Login Form -->
      <div class="w-1/2 p-12 flex flex-col justify-center">
        <div class="max-w-md w-full mx-auto">
          <h2 class="text-3xl font-bold mb-2">Log In Account</h2>
          <p class="text-gray-600 mb-8">Hi, Welcome!</p>
  
          <form @submit.prevent="handleSubmit" class="space-y-6">
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Email <span class="text-red-500">*</span>
              </label>
              <input
                type="email"
                v-model="email"
                required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
  
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Password <span class="text-red-500">*</span>
              </label>
              <input
                type="password"
                v-model="password"
                required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
  
            <div class="flex items-center justify-end">
              <a href="#" class="text-sm text-gray-600 hover:text-gray-900">
                Lupa Kata Sandi?
              </a>
            </div>
  
            <button
              type="submit"
              class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-400 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              MASUK
            </button>
          </form>
        </div>
      </div>
    </div>
  </template>
  

