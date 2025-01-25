<script>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

export default {
  setup() {
    const email = ref('')
    const password = ref('')
    const error = ref(null)
    const isRedirecting = ref(false)

    const handleSubmit = async () => {
      try {
        error.value = null
        const response = await axios.post('/api/login', {
          email: email.value,
          password: password.value
        })

        if (response.data.token) {
          localStorage.setItem('auth_token', response.data.token)
          localStorage.setItem('user_data', JSON.stringify(response.data.user))

          axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`

          const role = response.data.user.role
          isRedirecting.value = true
          if (role === 'dosen') {
            router.visit('/dosen/dashboard')
          } else if (role === 'mahasiswa') {
            router.visit('/mahasiswa/dashboard')
          }
        }
      } catch (err) {
        error.value = err.response?.data?.message || 'Login gagal'
        localStorage.removeItem('auth_token')
        localStorage.removeItem('user_data')
      } finally {
        isRedirecting.value = false
      }
    }

    const checkAuthAndRedirect = async () => {
      if (isRedirecting.value) return
      const token = localStorage.getItem('auth_token')
      if (!token) return

      try {
        isRedirecting.value = true

        const response = await axios.get('/api/validate-token')

        if (!response.data.valid) {
          localStorage.removeItem('auth_token')
          localStorage.removeItem('user_data')
          router.visit('/login')
          return
        }

        const userData = JSON.parse(localStorage.getItem('user_data') || 'null')
        if (!userData) {
          router.visit('/login')
          return
        }

        const role = userData.role
        const routeMap = {
          'dosen': '/dosen/dashboard',
          'mahasiswa': '/mahasiswa/dashboard'
        }

        if (routeMap[role]) {
          router.visit(routeMap[role])
        } else {
          router.visit('/login')
        }

      } catch (err) {
        localStorage.removeItem('auth_token')
        localStorage.removeItem('user_data')
        router.visit('/login')
      } finally {
        isRedirecting.value = false
      }
    }

    onMounted(() => {
      if (window.location.pathname === '/login' && localStorage.getItem('auth_token')) {
        checkAuthAndRedirect()
      }
    })

    console.log('Function checkAuthAndRedirect executed');


    return {
      email,
      password,
      error,
      handleSubmit
    }
  }
}
</script>

<template>
  <div class="min-h-screen flex">
    <div class="w-1/2 bg-sky-100 p-12">
      <h1 class="text-3xl font-bold mb-6">Self and Peer Assessment Project</h1>
      <p class="text-gray-600 leading-relaxed">
        Lorem ipsum dolor sit amet, consectetur adipiscing elit,
        sed do eiusmod tempor incididunt ut labore et dolore
        magna aliqua.
      </p>
    </div>

    <div class="w-1/2 p-12 flex flex-col justify-center">
      <div class="max-w-md w-full mx-auto">
        <h2 class="text-3xl font-bold mb-2">Log In Account</h2>
        <p class="text-gray-600 mb-8">Hi, Welcome!</p>

        <form @submit.prevent="handleSubmit" class="space-y-6">
          <div v-if="error" class="bg-red-50 text-red-500 p-3 rounded-md mb-4">
            {{ error }}
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">
              Email <span class="text-red-500">*</span>
            </label>
            <input type="email" v-model="email" required
              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">
              Password <span class="text-red-500">*</span>
            </label>
            <input type="password" v-model="password" required
              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" />
          </div>

          <div class="flex items-center justify-end">
            <a href="#" class="text-sm text-gray-600 hover:text-gray-900">
              Forget Password?
            </a>
          </div>

          <button type="submit"
            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-400 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Login
          </button>
        </form>
      </div>
    </div>
  </div>
</template>