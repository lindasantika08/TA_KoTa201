<template>
  <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto outline-none focus:outline-none">
    <div class="fixed inset-0 bg-black opacity-50" @click="closeModal"></div>
    <div class="relative w-full max-w-md mx-auto my-6">
      <div class="relative flex flex-col w-full bg-white border-0 rounded-lg shadow-lg outline-none focus:outline-none">
        <div class="flex items-start justify-between p-5 border-b border-solid rounded-t border-blueGray-200">
          <h2 class="text-2xl font-bold">Reset Password</h2>
          <button 
            @click="closeModal" 
            class="float-right p-1 ml-auto text-3xl font-semibold leading-none text-black bg-transparent border-0 outline-none opacity-5 focus:outline-none"
          >
            ×
          </button>
        </div>
        <div class="relative flex-auto p-6">
          <form @submit.prevent="handleForgetPassword" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Email <span class="text-red-500">*</span>
              </label>
              <input 
                type="email" 
                v-model="forgetEmail" 
                required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
            
            <div v-if="error" class="bg-red-50 text-red-500 p-3 rounded-md">
              {{ error }}
            </div>
            
            <div v-if="success" class="bg-green-50 text-green-500 p-3 rounded-md">
              {{ success }}
            </div>
            
            <div class="flex space-x-4">
              <button 
                type="submit" 
                class="w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-400 hover:bg-blue-500"
              >
                Send Reset Link
              </button>
              <button 
                type="button" 
                @click="closeModal"
                class="w-full py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import axios from 'axios'

export default {
  props: {
    showModal: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close'],
  setup(props, { emit }) {
    const forgetEmail = ref('')
    const error = ref(null)
    const success = ref(null)

    const handleForgetPassword = async () => {
      error.value = null
      success.value = null

      try {
        const response = await axios.post('/forgot-password', {
          email: forgetEmail.value
        })

        success.value = 'Reset password link has been sent to your email.'
        forgetEmail.value = ''
      } catch (err) {
        error.value = err.response?.data?.message || 'Failed to send reset link'
      }
    }

    const closeModal = () => {
      error.value = null
      success.value = null
      emit('close')
    }

    return {
      forgetEmail,
      error,
      success,
      handleForgetPassword,
      closeModal
    }
  }
}
</script>