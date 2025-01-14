<script>
import axios from "axios";
import { router } from "@inertiajs/vue3";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";


export default {
  name: "Dashboard",
  components: {
    Sidebar,
    Navbar,
    Card,
  },
  methods: {
    goToDashboardSelf(url) {
      router.visit("/dosen/dashboard/self"); // Menggunakan Inertia.js untuk navigasi
    },
    goToDashboardPeer(url) {
      router.visit("/dosen/dashboard/peer"); // Menggunakan Inertia.js untuk navigasi
    },
    goToKelolaProyek(url) {
      router.visit("/dosen/kelola-proyek"); // Menggunakan Inertia.js untuk navigasi
    },
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
        <!-- Three Cards Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
          <Card title="Self Assessment" class="cursor-pointer hover:shadow-lg transition-shadow"
            @click="goToDashboardSelf('/dosen/dashboard/self')">
            <template #title>
              <h3 class="text-sm font-bold">{{ title }}</h3> 
            </template>
            <p>Content for Card 1</p>
          </Card>
          <Card title="Peer Assessment" class="cursor-pointer hover:shadow-lg transition-shadow"
            @click="goToDashboardPeer('/dosen/dashboard/peer')">
            <template #title>
              <h3 class="text-sm font-bold">{{ title }}</h3> 
            </template>
            <p>Content for Card 2</p>
          </Card>
          <Card title="Proyek" class="cursor-pointer hover:shadow-lg transition-shadow"
            @click="goToKelolaProyek('/dosen/kelola-proyek')">
            <template #title>
              <h3 class="text-sm font-bold">{{ title }}</h3> 
            </template>
            <p>Content for Card 3</p>
          </Card>

        </div>
        <Card title="Dashboard" class="mt-8">
          <!-- You can leave the actions section here if you want, or remove it as well -->
          <template #actions>

          </template>
        </Card>
      </main>
    </div>
  </div>
</template>


<style scoped>
/* Optional: Add custom styles here */
</style>