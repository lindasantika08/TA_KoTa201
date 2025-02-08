<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-r from-blue-100 to-blue-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
      <div class="text-center">
        <h2 class="text-3xl font-extrabold text-gray-900 mb-2">
          Reset Password
        </h2>
        <p class="text-gray-600 text-sm mb-8">
          Create a strong password to secure your account
        </p>
      </div>

      <form @submit.prevent="submitResetPassword" class="space-y-6">
        <input type="hidden" :value="token" name="token">
        
        <div class="space-y-4">
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
              Email address
            </label>
            <input 
              id="email" 
              v-model="email" 
              type="email" 
              required 
              :disabled="!!props.email"
              class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm disabled:bg-gray-100"
              placeholder="Enter your email"
            >
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
              New Password
            </label>
            <input 
              id="password" 
              v-model="password" 
              type="password" 
              required 
              class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              placeholder="Enter new password"
              minlength="8"
            >
          </div>

          <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
              Confirm Password
            </label>
            <input 
              id="password_confirmation" 
              v-model="password_confirmation" 
              type="password" 
              required 
              class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              placeholder="Confirm your password"
              minlength="8"
            >
          </div>
        </div>

        <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-md text-sm">
          {{ error }}
        </div>

        <div>
          <button 
            type="submit" 
            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200 disabled:opacity-50"
            :disabled="loading || !isValidForm"
          >
            <span v-if="loading">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Processing...
            </span>
            <span v-else>Reset Password</span>
          </button>
        </div>

        <div class="text-center">
          <a href="/login" class="text-sm text-blue-600 hover:text-blue-500">
            Back to login
          </a>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

export default {
  props: {
    token: {
      type: String,
      required: true
    },
    email: {
      type: String,
      default: ''
    }
  },
  setup(props) {
    const email = ref(props.email || '')
    const password = ref('')
    const password_confirmation = ref('')
    const error = ref(null)
    const loading = ref(false)

    const isValidForm = computed(() => {
      return email.value && 
             password.value && 
             password_confirmation.value && 
             password.value === password_confirmation.value &&
             password.value.length >= 8
    })

    const submitResetPassword = async () => {
      if (!isValidForm.value) return

      loading.value = true
      error.value = null

      try {
        await router.post('/reset-password', {
          token: props.token,
          email: email.value,
          password: password.value,
          password_confirmation: password_confirmation.value
        }, {
          preserveState: true,
          onSuccess: () => {
            // Redirect to login page with success message
            const successMessage = encodeURIComponent("Your password has been successfully reset! You can now log in with your new password to access your account.")
            window.location.href = `/login?reset=success&message=${successMessage}`
          },
          onError: (errors) => {
            error.value = "We couldn't reset your password. Please make sure your passwords match and try again."
            loading.value = false
          }
        })
      } catch (err) {
        error.value = 'Something went wrong. Please try again in a few moments.'
        loading.value = false
      }
    }

    return {
      email,
      password,
      password_confirmation,
      error,
      loading,
      isValidForm,
      submitResetPassword,
      props
    }
  }
}
</script>