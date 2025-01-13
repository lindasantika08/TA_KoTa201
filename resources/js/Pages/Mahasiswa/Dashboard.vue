<script>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

export default {
  setup() {
    const isLoggingOut = ref(false)

    const handleLogout = async () => {
      if (isLoggingOut.value) return
      isLoggingOut.value = true

      try {
        const token = localStorage.getItem('auth_token')
        if (token) {
          await axios.put('/api/logout', {}, {
            headers: {
              'Authorization': `Bearer ${token}`
            }
          })
        }
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        localStorage.removeItem('auth_token')
        localStorage.removeItem('user_data')
        router.visit('/login')
        isLoggingOut.value = false
      }
    }

    return {
      handleLogout
    }
  }
}
</script>

<template>
  <button @click="handleLogout" class="px-4 py-2 bg-red-500 text-white rounded">
    Logout
  </button>
</template>