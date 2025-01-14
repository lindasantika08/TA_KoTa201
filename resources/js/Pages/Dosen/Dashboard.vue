<script>
import axios from "axios";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";

export default {
  name: "Profile",
  components: {
    Sidebar,
    Navbar,
    Card,
  },
};
</script>

<template>
  <!-- Wrapper with Flexbox Layout -->
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <Sidebar role="dosen" />

    <!-- Main Content Area -->
    <div class="flex-1 ">
      <!-- Navbar -->
      <Navbar userName="Dosen" />
      <main class="p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Dashboard Dosen</h1>
        <Card
    title="Judul Card Besar"
    description="Ini adalah deskripsi card yang menyesuaikan ukuran halaman. Anda bisa menambahkan konten lebih panjang di sini."
    image="https://via.placeholder.com/800x400"
  >
    <template #actions>
      <button class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
        Action Button
      </button>
    </template>
  </Card>
      </main>
    </div>
  </div>
</template>

<style scoped>
/* Optional: Add custom styles here */
</style>