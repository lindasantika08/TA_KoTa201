<script>
export default {
  name: "Navbar",
  props: {
    userName: {
      type: String,
      default: "User",
    },
  },
};
</script>

<template>
  <nav class="bg-blue-500 text-white py-4 px-6 flex justify-between items-center">
    <div class="text-xl font-bold">Assessment App</div>
    <div class="flex items-center">
      <span class="mr-4">Hi, {{ userName }}</span>
      <button 
        @click="$emit('logout')" 
        class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded"
      >
        Logout
      </button>
    </div>
  </nav>
</template>

<style scoped>
/* Add custom styles here if needed */
</style>
