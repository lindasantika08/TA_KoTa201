<script>
import axios from "axios";
import { router } from "@inertiajs/vue3";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import DataTable from "@/Components/DataTable.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

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
        // { key: "jurusan", label: "Jurusan" },
        // { key: "prodi", label: "Prodi" },
        { key: "actions", label: "Actions" },
      ],
      selectedAngkatan: null,
      selectedJurusan: null,
      selectedProdi: null,
      selectedClass: null,
      angkatanList: [], // akan diisi dari database/axios
      jurusanList: [], // gunakan list yang sudah Anda miliki
      prodiList: [],
      classList: [],
    };
  },
  mounted() {
    this.fetchAngkatan();
    this.fetchJurusanList();
    this.fetchClassList();
    this.fetchUsers();
  },
  methods: {
    async fetchUsers() {
      try {
        const response = await axios.get("/api/get-mahasiswa", {
          params: {
            angkatan: this.selectedAngkatan,
            jurusan: this.selectedJurusan,
            prodi: this.selectedProdi,
            class: this.selectedClass,
          },
        });
        // Add numbering to each user object
        this.users = response.data.map((user, index) => ({
          ...user,
          no: index + 1, // Add number starting from 1
        }));
      } catch (error) {
        console.error("Error fetching users:", error);
      }
    },
    async fetchAngkatan() {
      try {
        const response = await axios.get("/api/get-angkatan");
        this.angkatanList = response.data;
      } catch (error) {
        console.error("Error fetching angkatan:", error);
      }
    },
    async fetchJurusanList() {
      try {
        const response = await axios.get("/api/get-jurusan");
        this.jurusanList = response.data;
      } catch (error) {
        console.error("Error fetching jurusan list:", error);
      }
    },
    async fetchClassList() {
      try {
        const response = await axios.get("/api/get-class");
        this.classList = response.data;
      } catch (error) {
        console.error("Error fetching class list:", error);
      }
    },
    filterJurusan() {
      // Reset jurusan dan prodi
      this.selectedJurusan = null;
      this.selectedProdi = null;
      this.fetchUsers();
    },
    filterProdi() {
      // Reset prodi
      this.selectedProdi = null;
      this.fetchUsers();
    },
    onJurusanChange() {
      // Filter prodi berdasarkan jurusan yang dipilih
      const selectedJurusan = this.jurusanList.find(
        (j) => j.jurusan === this.selectedJurusan
      );
      this.prodiList = selectedJurusan ? selectedJurusan.prodi : [];
      this.selectedProdi = null;
      this.fetchUsers();
    },
    inputMahasiswa() {
      router.visit("/dosen/manage-mahasiswa/input");
    },
    detailUser(user) {
      router.visit(`/dosen/manage-mahasiswa/detail`);
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
        <Card title="Kelola Mahasiswa">
          <template #actions>
            <!-- Dropdown filters in a row -->
            <div class="grid grid-cols-4 gap-4 mb-6">
              <!-- Dropdown Angkatan -->
              <div>
                <label
                  for="angkatan-select"
                  class="block text-sm font-medium text-gray-700"
                >
                  Filter Angkatan
                </label>
                <select
                  id="angkatan-select"
                  v-model="selectedAngkatan"
                  @change="filterJurusan"
                  class="mt-2 p-2 border border-gray-300 rounded w-full"
                >
                  <option value="" disabled>Pilih Angkatan</option>
                  <option
                    v-for="angkatan in angkatanList"
                    :key="angkatan"
                    :value="angkatan"
                  >
                    {{ angkatan }}
                  </option>
                </select>
              </div>

              <!-- Dropdown Jurusan -->
              <!-- <div>
                <label
                  for="jurusan-select"
                  class="block text-sm font-medium text-gray-700"
                >
                  Filter Jurusan
                </label>
                <select
                  id="jurusan-select"
                  v-model="selectedJurusan"
                  @change="onJurusanChange"
                  class="mt-2 p-2 border border-gray-300 rounded w-full"
                >
                  <option value="" disabled selected>Pilih Jurusan</option>
                  <option
                    v-for="item in jurusanList"
                    :key="item.jurusan"
                    :value="item.jurusan"
                  >
                    {{ item.jurusan }}
                  </option>
                </select>
              </div> -->

              <!-- Dropdown Prodi -->
              <!-- <div>
                <label
                  for="prodi-select"
                  class="block text-sm font-medium text-gray-700"
                >
                  Filter Prodi
                </label>
                <select
                  id="prodi-select"
                  v-model="selectedProdi"
                  @change="fetchUsers"
                  class="mt-2 p-2 border border-gray-300 rounded w-full"
                >
                  <option value="" disabled selected>Pilih Prodi</option>
                  <option
                    v-for="prodi in prodiList"
                    :key="prodi"
                    :value="prodi"
                  >
                    {{ prodi }}
                  </option>
                </select>
              </div> -->

              <!-- New Class Dropdown -->
              <div>
                <label
                  for="class-select"
                  class="block text-sm font-medium text-gray-700"
                >
                  Filter Kelas
                </label>
                <select
                  id="class-select"
                  v-model="selectedClass"
                  @change="fetchUsers"
                  class="mt-2 p-2 border border-gray-300 rounded w-full"
                >
                  <option value="" disabled selected>Pilih Kelas</option>
                  <option
                    v-for="classItem in classList"
                    :key="classItem"
                    :value="classItem"
                  >
                    {{ classItem }}
                  </option>
                </select>
              </div>
            </div>

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

        <button
          @click="inputMahasiswa"
          class="fixed bottom-10 right-10 bg-blue-500 text-white rounded-full p-6 shadow-lg hover:bg-blue-600 focus:outline-none"
        >
          <font-awesome-icon :icon="['fas', 'plus']" />
        </button>
      </main>
    </div>
  </div>
</template>

<style scoped></style>