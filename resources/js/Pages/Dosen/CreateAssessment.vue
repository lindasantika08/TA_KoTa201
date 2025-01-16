<template>
  <div class="flex min-h-screen">
    <Sidebar role="dosen" />

    <div class="flex-1">
      <Navbar userName="Dosen" />
      <main class="p-6">
        <Card title="Create Assessment">
          <template #actions>
            <div class="grid grid-cols-2 gap-8">
              <!-- Active Project Section -->
              <div class="border-r pr-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Proyek Aktif
                </label>
                <select
                  v-model="selectedActiveProject"
                  class="mt-2 p-2 border border-gray-300 rounded w-full"
                  required
                >
                  <option value="" disabled selected>Pilih Proyek Aktif</option>
                  <option
                    v-for="project in activeProjects"
                    :key="`active-${project.tahun_ajaran}-${project.nama_proyek}`"
                    :value="project"
                  >
                    {{ project.tahun_ajaran }} - {{ project.nama_proyek }}
                  </option>
                </select>
                <div class="mt-4">
                  <button
                    @click="downloadActiveTemplate"
                    class="w-full px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!selectedActiveProject.tahun_ajaran"
                  >
                    <font-awesome-icon :icon="['fas', 'file-excel']" class="mr-2" />
                    Download Template Aktif
                  </button>
                </div>
              </div>

              <!-- Inactive Project Section -->
              <div class="pl-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Proyek Tidak Aktif
                </label>
                <select
  v-model="selectedInactiveProject"
  class="mt-2 p-2 border border-gray-300 rounded w-full"
  required
>
  <option value="" disabled selected>Pilih Proyek Tidak Aktif</option>
  <option
    v-for="project in inactiveProjects"
    :key="`inactive-${project.tahun_ajaran}-${project.nama_proyek}`"
    :value="project"
  >
    {{ project.tahun_ajaran }} - {{ project.nama_proyek }}
  </option>
</select>
                <div class="mt-4">
                  <button
                    @click="downloadInactiveTemplate"
                    class="w-full px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!selectedInactiveProject.tahun_ajaran"
                  >
                    <font-awesome-icon :icon="['fas', 'file-excel']" class="mr-2" />
                    Download Template Tidak Aktif
                  </button>
                </div>
              </div>
            </div>

            <!-- Import Data Excel -->
            <div class="mt-8">
              <label for="file-upload" class="block text-sm font-medium text-gray-700">
                Import Data Excel (File .xlsx/.xls)
              </label>
              <input
                type="file"
                id="file-upload"
                accept=".xlsx, .xls"
                @change="handleFileUpload"
                class="mt-2 p-2 border border-gray-300 rounded w-full"
              />
            </div>
          </template>
        </Card>
      </main>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";

export default {
  components: {
    Sidebar,
    Navbar,
    Card,
  },
  setup() {
    const projects = ref([]);
    const selectedActiveProject = ref({ tahun_ajaran: "", nama_proyek: "" });
    const selectedInactiveProject = ref({ tahun_ajaran: "", nama_proyek: "" });

    const activeProjects = computed(() => {
      return projects.value.filter(project => project.status === 'aktif');
    });

    const inactiveProjects = computed(() => {
      return projects.value.filter(project => project.status !== 'aktif');
    });

    const downloadActiveTemplate = async () => {
      await downloadTemplate(selectedActiveProject.value, 'active');
    };

    const downloadInactiveTemplate = async () => {
      await downloadTemplate(selectedInactiveProject.value, 'inactive');
    };

    const downloadTemplate = async (project, type) => {
      if (!project.tahun_ajaran || !project.nama_proyek) {
        alert(`Pilih Proyek ${type === 'active' ? 'Aktif' : 'Tidak Aktif'} terlebih dahulu.`);
        return;
      }

      try {
        const token = localStorage.getItem("auth_token");
        const response = await axios.get("/api/export-self-assessment", {
          params: {
            tahun_ajaran: project.tahun_ajaran,
            nama_proyek: project.nama_proyek,
            type: type
          },
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
          responseType: "blob",
        });

        const blob = new Blob([response.data], {
          type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        });

        const url = window.URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.setAttribute("download", `self-assessment-${type}.xlsx`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
      } catch (error) {
        console.error("Download error:", error);
        alert("Terjadi kesalahan saat mengunduh file excel");
      }
    };

    const handleFileUpload = async (event) => {
      const formData = new FormData();
      formData.append("file", event.target.files[0]);

      try {
        await axios.post("/dosen/assessment/import", formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        });
        alert("Data berhasil diimpor");
      } catch (error) {
        console.error("Import error:", error);
        alert("Terjadi kesalahan saat mengimpor data");
      }
    };

    onMounted(async () => {
      try {
        const response = await axios.get("/api/projects");
        projects.value = response.data;
      } catch (error) {
        console.error("Error fetching projects:", error);
      }
    });

    return {
      projects,
      activeProjects,
      inactiveProjects,
      selectedActiveProject,
      selectedInactiveProject,
      downloadActiveTemplate,
      downloadInactiveTemplate,
      handleFileUpload,
    };
  },
};
</script>