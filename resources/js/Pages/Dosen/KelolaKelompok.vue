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
  name: "KelolaKelompok",
  components: {
    Sidebar,
    Navbar,
    Card,
    DataTable,
    Breadcrumb,
    Dropdown,
  },
  props: {
    kelompok: Array, // Data kelompok yang sudah diolah di controller
  },
  data() {
    return {
      breadcrumbs: [
        { text: "Manage Group", href: "/dosen/kelola-kelompok" },
      ],
      headers: [
        { label: "Tahun Ajaran", key: "tahun_ajaran" },
        { label: "Nama Proyek", key: "nama_proyek" },
        { label: "Kelompok", key: "kelompok" },
        { label: "Manager Dosen", key: "dosen" },
        { label: "Anggota Kelompok", key: "anggota" },
        { label: "Aksi", key: "aksi" },
      ],
      projects: [], // Data project dari /project-dropdown
      selectedProject: "", // Pilihan dropdown
      filteredKelompok: [], // Data kelompok yang sudah difilter
    };
  },
  mounted() {
    console.log("Data Kelompok:", this.kelompok);
    this.fetchProjects();
    this.filteredKelompok = this.kelompok; // Default tampilkan semua data kelompok
  },
  methods: {
    async fetchProjects() {
      try {
        const response = await axios.get("/api/project-dropdown");
        this.projects = response.data;
      } catch (error) {
        console.error("Error fetching projects:", error);
      }
    },
    applyFilter() {
      if (!this.selectedProject) {
        this.filteredKelompok = this.kelompok; // Reset ke data awal
        return;
      }

      const [tahun_ajaran, nama_proyek] = this.selectedProject.split(" - ");
      this.filteredKelompok = this.kelompok.filter(
        (item) =>
          item.tahun_ajaran === tahun_ajaran &&
          item.nama_proyek === nama_proyek
      );
    },
    showDetail(kelompokId) {
      console.log(`Show detail for kelompok with ID: ${kelompokId}`);
      this.$inertia.get(route("DetailKelompok", { id: kelompokId }));
    },
    createKelompok(url) {
      router.visit("/dosen/kelola-kelompok/create"); // Menggunakan Inertia.js untuk navigasi
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
        <Card title="Kelola Kelompok">
          <!-- Container untuk Dropdown dan Button Create -->
          <div class="flex justify-between mb-4 items-center">
            <!-- Label "Daftar Kelompok" (Posisi Kanan) -->
            <div class="ml-4">
              <span class="text-lg font-semibold text-black">Daftar Kelompok</span>
            </div> 
            <!-- Dropdown Filter (Posisi Kiri) -->
            <div class="flex-1 max-w-xs">
              <select id="projectDropdown" v-model="selectedProject"
                class="py-2 px-2 border border-gray-300 rounded w-full" @change="applyFilter">
                <option value="" disabled>Pilih Tahun Ajaran - Proyek</option>
                <option v-for="project in projects" :key="`${project.tahun_ajaran}-${project.nama_proyek}`"
                  :value="`${project.tahun_ajaran} - ${project.nama_proyek}`">
                  {{ project.tahun_ajaran }} - {{ project.nama_proyek }}
                </option>
              </select>
            </div>

            
          </div>

          <!-- Data Table -->
          <DataTable :headers="headers" :items="filteredKelompok">
            <!-- Slot untuk Anggota Kelompok -->
            <template v-slot:column-anggota="{ item }">
              <ul>
                <li v-for="(anggota, index) in item.anggota" :key="index">
                  - {{ anggota }}
                </li>
              </ul>
            </template>

            <!-- Slot untuk Kolom Aksi -->
            <template v-slot:column-aksi="{ item }">
              <button @click="showDetail(item.id)" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Detail
              </button>
            </template>
          </DataTable>
        </Card>

        <button @click="createKelompok('/dosen/kelola-kelompok/create')"
          class="fixed bottom-10 right-10 bg-blue-500 text-white rounded-full p-6 shadow-lg hover:bg-blue-600 focus:outline-none">
          <font-awesome-icon :icon="['fas', 'plus']" />
        </button>
      </main>
    </div>
  </div>
</template>
