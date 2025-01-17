<script>
import axios from "axios";
import SidebarMahasiswa from "@/Components/SidebarMahasiswa.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";


export default {
  name: "Profile",
  components: {
    SidebarMahasiswa,
    Navbar,
    Card,

  },
};
</script>

<template>
  <div class="flex min-h-screen">
    <SidebarMahasiswa role="mahasiswa" />

    <div class="flex-1 ">
      <Navbar userName="Mahasiswa" />
      <main class="p-6">
        <Card title="Feedback">
          <template #actions>
          </template>
        </Card>
      </main>
    </div>
  </div>
</template>


<style scoped>
</style>