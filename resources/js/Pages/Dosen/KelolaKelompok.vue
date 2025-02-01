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
    kelompok: Array,
  },
  data() {
    return {
      breadcrumbs: [
        { text: "Manage Group", href: "/dosen/kelola-kelompok" },
      ],
      headers: [
        { label: "Tahun Ajaran", key: "batch_year" },
        { label: "Nama Proyek", key: "project_name" },
        { label: "Kelompok", key: "group" },
        { label: "Manager Dosen", key: "dosen" },
        { label: "Anggota Kelompok", key: "anggota" },
        { label: "Aksi", key: "aksi" },
      ],
      projects: [],
      selectedProject: "",
      filteredKelompok: [],
    };
  },
  mounted() {
    console.log("Data Kelompok:", this.kelompok);
    this.fetchProjects();

    // Flatten the nested structure
    this.filteredKelompok = this.kelompok.flatMap(dosenGroup =>
      dosenGroup.projects.map(project => ({
        ...project,
        dosen: dosenGroup.dosen_name
      }))
    );
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
        this.filteredKelompok = this.kelompok.flatMap(dosenGroup =>
          dosenGroup.projects.map(project => ({
            ...project,
            dosen: dosenGroup.dosen_name
          }))
        );
        return;
      }

      const [batch_year, project_name] = this.selectedProject.split(" - ");
      this.filteredKelompok = this.kelompok.flatMap(dosenGroup =>
        dosenGroup.projects
          .filter(project =>
            project.batch_year === batch_year &&
            project.project_name === project_name
          )
          .map(project => ({
            ...project,
            dosen: dosenGroup.dosen_name
          }))
      );
    },
    showDetail(kelompokId) {
      console.log(`Show detail for kelompok with ID: ${kelompokId}`);
      this.$inertia.get(route("DetailKelompok", { id: kelompokId }));
    },
    createKelompok(url) {
      router.visit("/dosen/kelola-kelompok/create");
    },
    goToProfile(user_id) {
      router.visit(`/dosen/kelola-kelompok/profile-mhs?user_id=${user_id}`);
    }
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
          <div class="flex justify-between mb-4 items-center">
            <div class="ml-4">
              <span class="text-lg font-semibold text-black">Daftar Kelompok</span>
            </div>
            <div class="flex-1 max-w-xs">
              <select id="projectDropdown" v-model="selectedProject"
                class="py-2 px-2 border border-gray-300 rounded w-full" @change="applyFilter">
                <option value="" disabled>Pilih Tahun Ajaran - Proyek</option>
                <option v-for="project in projects" :key="`${project.batch_year}-${project.project_name}`"
                  :value="`${project.batch_year} - ${project.project_name}`">
                  {{ project.batch_year }} - {{ project.project_name }}
                </option>
              </select>
            </div>


          </div>

          <DataTable :headers="headers" :items="filteredKelompok">
            <template v-slot:column-anggota="{ item }">
              <ul>
                <li v-for="(anggota, index) in item.anggota" :key="index">
                  <a href="#" @click.prevent="goToProfile(anggota.user_id)" class="text-blue-900 hover:underline">
                    - {{ anggota.name }}
                  </a>
                </li>
              </ul>
            </template>

            <template v-slot:column-aksi="{ item }">
              <button @click="showDetail(item.id)"
                class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <font-awesome-icon icon="fa-solid fa-eye" class="mr-2" />
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
