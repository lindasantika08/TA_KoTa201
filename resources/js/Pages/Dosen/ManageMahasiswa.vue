<template>
  <div class="flex min-h-screen">
    <Sidebar role="dosen" />

    <div class="flex-1">
      <Navbar userName="Dosen" />
      <main class="p-6">
        <div class="mb-4">
          <Breadcrumb :items="breadcrumbs" />
        </div>
        <Card title="Kelola Mahasiswa">
          <template #actions>
            <!-- Filter and Search in a row -->
            <div class="grid grid-cols-5 gap-4 mb-6">
              <!-- Dropdown Angkatan -->
              <div>
                <label for="angkatan-select" class="block text-sm font-medium text-gray-700">
                  Filter Angkatan
                </label>
                <select id="angkatan-select" v-model="selectedAngkatan" @change="fetchUsers"
                  class="mt-2 p-2 border border-gray-300 rounded w-full">
                  <option value="" disabled>Pilih Angkatan</option>
                  <option v-for="angkatan in angkatanList" :key="angkatan" :value="angkatan">
                    {{ angkatan }}
                  </option>
                </select>
              </div>

              <!-- Dropdown Kelas -->
              <div>
                <label for="class-select" class="block text-sm font-medium text-gray-700">
                  Filter Kelas
                </label>
                <select id="class-select" v-model="selectedClass" @change="fetchUsers"
                  class="mt-2 p-2 border border-gray-300 rounded w-full">
                  <option value="" disabled selected>Pilih Kelas</option>
                  <option v-for="classItem in classList" :key="classItem" :value="classItem">
                    {{ classItem }}
                  </option>
                </select>
              </div>

              <!-- Search Input -->
              <div class="col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700">
                  Cari (Nama/NIM)
                </label>
                <input
                  type="text"
                  id="search"
                  v-model="searchQuery"
                  @input="handleSearch"
                  placeholder="Cari berdasarkan nama atau NIM..."
                  class="mt-2 p-2 border border-gray-300 rounded w-full"
                />
              </div>
            </div>

            <!-- Data Table -->
            <DataTable :headers="headers" :items="filteredUsers" class="mt-10">
              <template #column-actions="{ item }">
                <button @click="detailUser(item.user_id)"
                  class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                  <font-awesome-icon icon="fa-solid fa-eye" class="mr-2" />
                  Detail
                </button>
              </template>

              <template #column-angkatan="{ item }">
                {{ item.class_room.angkatan }}
              </template>

              <template #column-class="{ item }">
                {{ item.class_room.class_name }}
              </template>

              <template #column-name="{ item }">
                {{ item.user.name }}
              </template>

              <template #column-nim="{ item }">
                {{ item.nim }}
              </template>

              <template #column-email="{ item }">
                {{ item.user.email }}
              </template>
            </DataTable>
          </template>
        </Card>

        <button @click="inputMahasiswa"
          class="fixed bottom-8 right-8 flex items-center justify-center w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105">
          <font-awesome-icon :icon="['fas', 'plus']" />
        </button>
      </main>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import { router } from "@inertiajs/vue3";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import DataTable from "@/Components/DataTable.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import { debounce } from 'lodash';

export default {
  name: "ManageMahasiswa",
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
        { text: "Manage Mahasiswa", href: "/dosen/manage-mahasiswa" },
      ],
      users: [],
      headers: [
        { key: "no", label: "No" },
        { key: "angkatan", label: "Angkatan" },
        { key: "class", label: "Kelas" },
        { key: "name", label: "Nama" },
        { key: "nim", label: "NIM" },
        { key: "email", label: "Email" },
        { key: "actions", label: "Actions" },
      ],
      selectedAngkatan: null,
      selectedClass: null,
      searchQuery: "",
      angkatanList: [],
      classList: [],
    };
  },
  computed: {
    filteredUsers() {
      if (!this.searchQuery) {
        return this.users;
      }

      const query = this.searchQuery.toLowerCase();
      return this.users.filter((user) => {
        const userName = user.user.name.toLowerCase();
        const userNim = user.nim.toLowerCase();
        return userName.includes(query) || userNim.includes(query);
      });
    },
  },
  mounted() {
    this.fetchAngkatan();
    this.fetchClassList();
    this.fetchUsers();
  },
  methods: {
    handleSearch: debounce(function() {
      this.fetchUsers();
    }, 300),

    async fetchUsers() {
      try {
        const response = await axios.get("/api/get-mahasiswa", {
          params: {
            angkatan: this.selectedAngkatan,
            class_name: this.selectedClass,
            search: this.searchQuery,
          },
        });

        console.log("Data Mahasiswa:", response.data);
        this.users = response.data.map((user, index) => ({
          ...user,
          no: index + 1,
        }));
      } catch (error) {
        console.error("Error fetching users:", error);
      }
    },
    async fetchAngkatan() {
      try {
        const response = await axios.get("/api/get-angkatan");
        console.log("Data Angkatan:", response.data);
        this.angkatanList = response.data;
      } catch (error) {
        console.error("Error fetching angkatan:", error);
      }
    },
    async fetchClassList() {
      try {
        const response = await axios.get("/api/get-class");
        console.log("Data Kelas:", response.data);
        this.classList = response.data;
      } catch (error) {
        console.error("Error fetching class list:", error);
      }
    },
    inputMahasiswa() {
      router.visit("/dosen/manage-mahasiswa/input");
    },
    detailUser(user_id) {
      router.visit(`/dosen/manage-mahasiswa/detail?user_id=${user_id}`);
    },
  },
};
</script>