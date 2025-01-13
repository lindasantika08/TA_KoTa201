<script>
import axios from 'axios';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';

export default {
  name: 'Profile',
  components: {
    Sidebar,
    Navbar,
  },
  
};
</script>

<template>
  <!-- Wrapper with Flexbox Layout -->
  <div class="flex h-screen">
    <!-- Sidebar -->
    <Sidebar role="dosen" />


    <!-- Main Content Area -->
    <div class="flex-1 bg-gray-100">
      <!-- Navbar -->
      <Navbar />
      <main class="p-6">
      <h1 class="text-2xl font-bold text-gray-800 mb-6">Profile</h1>
      
      </main>
    </div>
  </div>
</template>

<style scoped>
/* Optional: Add custom styles here */
</style>