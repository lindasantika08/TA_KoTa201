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
        { text: "Manage Dosen", href: "/sispa/dosen/manage-dosen" },
      ],
      users: [],
      headers: [
        { key: "no", label: "No" },
        { key: "name", label: "Nama" },
        { key: "kode_dosen", label: "Kode Dosen" },
        { key: "nip", label: "NIP" },
        { key: "email", label: "Email" },
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
        const response = await axios.get("/sispa/api/get-dosen");
        // Menambahkan nomor urut ke setiap item
        this.users = response.data.map((user, index) => ({
          ...user,
          no: index + 1
        }));
      } catch (error) {
        console.error("Error fetching users:", error);
      }
    },
    inputDosen() {
      router.visit("/sispa/dosen/manage-dosen/input");
    },
    detailUser(user_id) {
      router.visit(`/dosen/manage-dosen/detail?user_id=${user_id}`);
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
                    @click="detailUser(item.user_id)" 
                    class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                  >
                  <font-awesome-icon icon="fa-solid fa-eye" class="mr-2" />
                    Detail
                  </button>
              </template>

              <!-- Menampilkan Nama (name) -->
              <template #column-name="{ item }">
                {{ item.user.name }}
              </template>

              <!-- Menampilkan Kode Dosen (kode_dosen) -->
              <template #column-kode_dosen="{ item }">
                {{ item.kode_dosen }}
              </template>

              <!-- Menampilkan NIP (nip) -->
              <template #column-nip="{ item }">
                {{ item.nip }}
              </template>

              <!-- Menampilkan Email (email) -->
              <template #column-email="{ item }">
                {{ item.user.email }}
              </template>
            </DataTable>
          </template>
        </Card>

        <button @click="inputDosen"
        class="fixed bottom-8 right-8 flex items-center justify-center w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105">
          <font-awesome-icon :icon="['fas', 'plus']" />
        </button>
      </main>
    </div>
  </div>
</template>

<style scoped></style>