<script>
import axios from 'axios';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';

export default {
  name: 'Notifications',
  components: {
    Sidebar,
    Navbar,
  },
  data() {
    return {
      notifications: [
        { id: 1, message: 'Assignment 1 has been graded', time: '2 hours ago' },
        { id: 2, message: 'New discussion in your class', time: '1 day ago' },
        { id: 3, message: 'Your profile has been updated', time: '3 days ago' },
      ],
    };
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
      <h1 class="text-2xl font-bold text-gray-800 mb-6">Notifications</h1>
      <ul class="space-y-4">
        <li
          v-for="notification in notifications"
          :key="notification.id"
          class="bg-white shadow rounded-lg p-4"
        >
          <p class="text-gray-800 font-semibold">{{ notification.message }}</p>
          <p class="text-sm text-gray-500">{{ notification.time }}</p>
        </li>
      </ul>
      </main>
    </div>
  </div>
</template>

<style scoped>
/* Optional: Add custom styles here */
</style>