<script>
import axios from "axios";
import { router } from "@inertiajs/vue3";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import DataTable from "@/Components/DataTable.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

export default {
  name: "ManageDosen",
  components: {
    Sidebar,
    Navbar,
    Card,
    DataTable,
    Breadcrumb,
  },
  data() {
    return {
      breadcrumbs: [
        { text: "Manage Dosen", href: "/dosen/manage-dosen" },
      ],
      users: [],
      headers: [
        { key: "name", label: "Nama" },
        { key: "kode_dosen", label: "Kode Dosen" },
        { key: "email", label: "Email" },
        { key: "nip", label: "NIP" },
        { key: "jurusan", label: "Jurusan" },
        { key: "actions", label: "Actions" },
      ],
    };
  },
  mounted() {
    this.fetchUsers();
  },
  methods: {
    async fetchUsers() {
      try {
        const response = await axios.get("/api/get-dosen");
        this.users = response.data;
      } catch (error) {
        console.error("Error fetching users:", error);
      }
    },
    inputDosen() {
      router.visit("/dosen/manage-dosen/input");
    },
    detailUser(user) {
      router.visit(`/dosen/manage-dosen/detail`);
    },
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
        <Card title="Manage Dosen">
          <template #actions>
            <!-- Data Table -->
            <DataTable :headers="headers" :items="users" class="mt-10">
              <template #column-actions="{ item }">
                  <button 
                    @click="detailUser(item)" 
                    class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                  >
                  <font-awesome-icon icon="fa-solid fa-eye" class="mr-2" />
                    Detail
                  </button>
              </template>
            </DataTable>
          </template>
        </Card>

        <button @click="inputDosen"
          class="fixed bottom-10 right-10 bg-blue-500 text-white rounded-full p-6 shadow-lg hover:bg-blue-600 focus:outline-none">
          <font-awesome-icon :icon="['fas', 'plus']" />
        </button>
      </main>
    </div>
  </div>
</template>

<style scoped></style>