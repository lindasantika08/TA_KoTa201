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
        { text: "Manage Group", href: "/sispa/dosen/kelola-kelompok" },
      ],
      headers: [
        { label: "Tahun Ajaran", key: "batch_year" },
        { label: "Nama Proyek", key: "project_name" },
        { label: "Angkatan", key: "angkatan" },
        { label: "Kelompok", key: "group" },
        { label: "Manager Dosen", key: "dosen" },
        { label: "Anggota Kelompok", key: "anggota" },
      ],
      projects: [],
      selectedProject: "",
      filteredKelompok: [],
    };
  },
  mounted() {
    console.log("Data Kelompok:", this.kelompok);
    this.fetchProjects();
    this.processGroupData();
  },
  methods: {
    async fetchProjects() {
      try {
        const response = await axios.get("/sispa/api/project-dropdown");
        this.projects = response.data;
      } catch (error) {
        console.error("Error fetching projects:", error);
      }
    },
    processGroupData() {
      // First, flatten the nested structure
      const flatGroups = this.kelompok.flatMap((dosenGroup) =>
        dosenGroup.projects.map((project) => ({
          ...project,
          dosen: dosenGroup.dosen_name,
          angkatan: project.angkatan || "-",
          dosen_id: dosenGroup.dosen_id,
        }))
      );

      // Create a map to consolidate groups with the same project and group number
      const groupMap = {};

      flatGroups.forEach((group) => {
        // Create a unique key for each group based on project and group number
        const key = `${group.project_id}_${group.batch_year}_${group.project_name}_${group.group}`;

        if (!groupMap[key]) {
          groupMap[key] = {
            id: group.id,
            batch_year: group.batch_year,
            project_name: group.project_name,
            project_id: group.project_id,
            angkatan: group.angkatan,
            group: group.group,
            dosen: group.dosen,
            dosen_id: group.dosen_id,
            classes: group.class ? [group.class] : [],
            anggota: [...(group.anggota || [])],
          };
        } else {
          // Merge the anggota from the same group
          groupMap[key].anggota = [
            ...groupMap[key].anggota,
            ...(group.anggota || []),
          ];

          // Add the class if it's not already included
          if (
            group.class &&
            !groupMap[key].classes.includes(group.class)
          ) {
            groupMap[key].classes.push(group.class);
          }
        }
      });

      // Convert the map back to an array and store in filteredKelompok
      this.filteredKelompok = Object.values(groupMap);
      console.log("Consolidated Kelompok:", this.filteredKelompok);
    },
    applyFilter() {
      // Reset the data first
      this.processGroupData();

      if (!this.selectedProject) {
        return; // Already reset with processGroupData
      }

      const [batch_year, project_name] =
        this.selectedProject.split(" - ");
      this.filteredKelompok = this.filteredKelompok.filter(
        (group) =>
          group.batch_year === batch_year &&
          group.project_name === project_name
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
      router.visit(
        `/dosen/kelola-kelompok/profile-mhs?user_id=${user_id}`
      );
    },
    // Format classes for display, e.g., "A, B"
    formatClasses(classes) {
      return classes && classes.length > 0 ? classes.join(", ") : "";
    },
  },
  showDetail(kelompokId) {
    // console.log(`Show detail for kelompok with ID: ${kelompokId}`);
    this.$inertia.get(route("DetailKelompok", { id: kelompokId }));
  },
  createKelompok(url) {
    router.visit("/sispa/dosen/kelola-kelompok/create");
  },
  goToProfile(user_id) {
    router.visit(`/dosen/kelola-kelompok/profile-mhs?user_id=${user_id}`);
  }

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
                          <span class="text-lg font-semibold text-black"
                              >Daftar Kelompok</span
                          >
                      </div>
                      <div class="flex-1 max-w-xs">
                          <select
                              id="projectDropdown"
                              v-model="selectedProject"
                              class="py-2 px-2 border border-gray-300 rounded w-full"
                              @change="applyFilter"
                          >
                              <option value="" disabled>
                                  Pilih Tahun Ajaran - Proyek
                              </option>
                              <option
                                  v-for="project in projects"
                                  :key="`${project.batch_year}-${project.project_name}`"
                                  :value="`${project.batch_year} - ${project.project_name}`"
                              >
                                  {{ project.batch_year }} -
                                  {{ project.project_name }}
                              </option>
                          </select>
                      </div>
                  </div>

                  <DataTable :headers="headers" :items="filteredKelompok">
                      <template v-slot:column-angkatan="{ item }">
                          <span>{{ item.angkatan }}</span>
                      </template>

                      <template v-slot:column-anggota="{ item }">
                          <div>
                              <!-- Show class information if available -->
                              <div
                                  v-if="
                                      item.classes && item.classes.length > 0
                                  "
                                  class="text-xs font-medium text-gray-500 mb-1"
                              >
                                  Kelas: {{ formatClasses(item.classes) }}
                              </div>
                              <div
                                  v-else
                                  class="text-xs font-medium text-gray-500 mb-1"
                              >
                                  Tidak Ada Kelas
                              </div>

                              <!-- List all students in the group -->
                              <ul>
                                  <li
                                      v-for="(anggota, index) in item.anggota"
                                      :key="index"
                                      class="flex items-center space-x-2"
                                  >
                                      <span class="text-gray-400">•</span>
                                      <a
                                          href="#"
                                          @click.prevent="
                                              goToProfile(anggota.user_id)
                                          "
                                          class="text-blue-600 hover:text-blue-800 hover:underline transition-colors duration-200"
                                      >
                                          {{ anggota.name }}
                                      </a>
                                  </li>
                              </ul>
                          </div>
                      </template>
                  </DataTable>
              </Card>

              <button
                  @click="createKelompok('/dosen/kelola-kelompok/create')"
                  class="fixed bottom-8 right-8 flex items-center justify-center w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105"
              >
                  <font-awesome-icon :icon="['fas', 'plus']" />
              </button>
          </main>
      </div>
  </div>
</template>
