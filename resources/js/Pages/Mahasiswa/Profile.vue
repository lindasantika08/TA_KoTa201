<script>
import axios from "axios";
import SidebarMahasiswa from "@/Components/SidebarMahasiswa.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";


export default {
  name: "Profile",
  components: {
    SidebarMahasiswa,
    Navbar,
    Card,
    Breadcrumb,
  },
  data() {
    return {
      breadcrumbs: [
        { text: "Profile", href: "/Mahasiswa/profile" },
      ],
    };
  },
};
</script>

<template>
  <!-- Wrapper with Flexbox Layout -->
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <SidebarMahasiswa role="mahasiswa" />

    <!-- Main Content Area -->
    <div class="flex-1 ">
      <!-- Navbar -->
      <Navbar userName="Mahasiswa" />
      <main class="p-6">
        <div class="mb-4">
          <Breadcrumb :items="breadcrumbs" />
        </div>
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