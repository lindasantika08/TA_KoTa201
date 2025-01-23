<script>
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

export default {
  name: "DetailProfilMhs",
  components: {
    Sidebar,
    Navbar,
    Card,
    Breadcrumb,
  },
  props: {
    user_id: Number,  // Menerima user_id dari controller
    user_name: String, // Menerima user_name dari controller
  },
  data() {
    return {
      breadcrumbs: [
        { text: "Manage Group", href: "/dosen/kelola-kelompok" },
        { text: "Profile Mhs", href: "/dosen/kelola-kelompok/profile-mhs" },
      ],
    };
  },
};
</script>

<template>
  <div class="flex min-h-screen">
    <Sidebar role="dosen" />

    <div class="flex-1">
      <Navbar userName="Dosen" />
      <main class="p-6">
        <div class="mb-4">
          <Breadcrumb :items="breadcrumbs" />
        </div>
        <Card :title="'Profile Mhs - ' + user_name"> <!-- Menampilkan nama mahasiswa di title -->
          <template #actions></template>
        </Card>
      </main>
    </div>
  </div>
</template>

<style scoped>
/* Optional: Add custom styles here */
</style>
