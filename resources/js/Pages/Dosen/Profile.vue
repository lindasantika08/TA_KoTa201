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
        <Card title="Profile">
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