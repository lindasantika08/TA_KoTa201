<script>
import axios from "axios";
import { router } from "@inertiajs/vue3";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import DataTable from "@/Components/DataTable.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import Dropdown from "@/Components/Dropdown.vue";


export default {
  name: "KelolaMahasiswa",
  components: {
    Sidebar,
    Navbar,
    Card,
    Breadcrumb,
  },
  data() {
    return {
      breadcrumbs: [
        { text: "Manage Mahasiswa", href: "/dosen/kelola-mahasiswa" },
      ],
    };
  },
  methods: {
    inputMahasiswa(url) {
      router.visit("/dosen/kelola-mahasiswa/input");
    },
  },
};
</script>

<template>
  <div class="flex min-h-screen">
    <Sidebar role="dosen" />

    <div class="flex-1 ">
      <Navbar userName="Dosen" />
      <main class="p-6">
        <div class="mb-4">
          <Breadcrumb :items="breadcrumbs" />
        </div>
        <Card title="Kelola Mahasiswa">
          <template #actions>

          </template>
        </Card>

        <button @click="inputMahasiswa('/dosen/kelola-mahasiswa/input')"
          class="fixed bottom-10 right-10 bg-blue-500 text-white rounded-full p-6 shadow-lg hover:bg-blue-600 focus:outline-none">
          <font-awesome-icon :icon="['fas', 'plus']" />
        </button>
      </main>
    </div>
  </div>
</template>


<style scoped>
</style>