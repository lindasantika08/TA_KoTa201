<script>
import { ref, onMounted } from "vue";
import axios from "axios";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

export default {
  components: {
    Sidebar,
    Navbar,
    Card,
    Breadcrumb,
  },
  data() {
    return {
      breadcrumbs: [
        { text: "Manage Group", href: "/dosen/kelola-kelompok" },
        { text: "Create Group", href: "/dosen/kelola-kelompok/create" },
      ],
    };
  },
  setup() {
    const inputMode = ref("export");
    const projects = ref([]);
    const angkatanList = ref([]);
    const selectedProject = ref({
      batch_year: "",
      semester: "",
      project_name: "",
    });
    const selectedAngkatan = ref("");

    const downloadTemplate = async () => {
      if (
        !selectedProject.value.batch_year ||
        !selectedProject.value.semester ||
        !selectedProject.value.project_name ||
        !selectedAngkatan.value
      ) {
        alert("Pilih Project dan angkatan terlebih dahulu.");
        return;
      }

      try {
        const token = localStorage.getItem("auth_token");

        const response = await axios.get("/dosen/kelola-kelompok/export", {
          params: {
            batch_year: selectedProject.value.batch_year,
            semester: selectedProject.value.semester,
            project_name: selectedProject.value.project_name,
            angkatan: selectedAngkatan.value,
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
        link.setAttribute("download", "Data_Kelompok.xlsx");
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
        await axios.post("/dosen/kelola-kelompok/import", formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        });

        alert("Data kelompok berhasil diimpor");
      } catch (error) {
        console.error("Import error:", error);
        const errorMessage = error.response
          ? error.response.data.error
          : "Terjadi kesalahan saat mengimpor data";
        alert(`Error: ${errorMessage}`);
      }
    };

    onMounted(async () => {
      try {
        const [projectsResponse, angkatanResponse] = await Promise.all([
          axios.get("/api/project-dropdown"),
          axios.get("/api/get-angkatan"),
        ]);

        projects.value = projectsResponse.data;
        angkatanList.value = angkatanResponse.data;
      } catch (error) {
        console.error("Error fetching data:", error);
      }
    });

    return {
      downloadTemplate,
      handleFileUpload,
      projects,
      selectedProject,
      angkatanList,
      selectedAngkatan,
      inputMode,
    };
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
        <Card title="Buat Kelompok Mahasiswa">
          <template #actions>
            <div class="flex w-full mb-4">
              <label
                class="w-1/2 text-center py-2 border cursor-pointer"
                :class="{
                  'bg-blue-500 text-white': inputMode === 'export',
                  'bg-white text-gray-700 border-gray-300':
                    inputMode !== 'export',
                }"
              >
                <input
                  type="radio"
                  v-model="inputMode"
                  value="export"
                  class="hidden"
                />
                Export
              </label>
              <label
                class="w-1/2 text-center py-2 border cursor-pointer"
                :class="{
                  'bg-blue-500 text-white': inputMode === 'import',
                  'bg-white text-gray-700 border-gray-300':
                    inputMode !== 'import',
                }"
              >
                <input
                  type="radio"
                  v-model="inputMode"
                  value="import"
                  class="hidden"
                />
                Import
              </label>
            </div>

            <div v-if="inputMode === 'export'">
              <div class="mt-4">
                <label
                  for="project-select"
                  class="block text-sm font-medium text-gray-700"
                >
                  Choose Project
                </label>
                <select
                  id="project-select"
                  v-model="selectedProject"
                  class="mt-2 p-2 border border-gray-300 rounded w-full"
                  required
                >
                  <option value="" disabled selected>Pilih Project</option>
                  <option
                    v-for="project in projects"
                    :key="`${project.batch_year}-${project.semester}-${project.project_name}`"
                    :value="project"
                  >
                    {{ project.batch_year }} - {{ project.semester }} -
                    {{ project.project_name }}
                  </option>
                </select>
              </div>

              <!-- Tambahan dropdown untuk angkatan -->
              <div class="mt-4">
                <label
                  for="angkatan-select"
                  class="block text-sm font-medium text-gray-700"
                >
                Choose Angkatan
                </label>
                <select
                  id="angkatan-select"
                  v-model="selectedAngkatan"
                  class="mt-2 p-2 border border-gray-300 rounded w-full"
                  required
                >
                  <option value="" disabled selected>Pilih Angkatan</option>
                  <option
                    v-for="angkatan in angkatanList"
                    :key="angkatan"
                    :value="angkatan"
                  >
                    {{ angkatan }}
                  </option>
                </select>
              </div>

              <div class="mt-4">
                <button
                  @click="downloadTemplate"
                  class="px-4 py-2 mt-2 bg-blue-500 text-white rounded"
                  :disabled="
                    !selectedProject.batch_year ||
                    !selectedProject.semester ||
                    !selectedProject.project_name || 
                    !selectedAngkatan
                  "
                >
                  Download Template Kelompok
                </button>
              </div>
            </div>

            <div v-if="inputMode === 'import'" class="mt-4">
              <label
                for="file-upload"
                class="block text-sm font-medium text-gray-700"
              >
                Import Data Excel (File .xlsx/.xls)
              </label>
              <input
                type="file"
                id="file-upload"
                accept=".xlsx, .xls"
                @change="handleFileUpload"
                class="mt-2 p-2 border border-gray-300 rounded"
              />
            </div>
          </template>
        </Card>
      </main>
    </div>
  </div>
</template>