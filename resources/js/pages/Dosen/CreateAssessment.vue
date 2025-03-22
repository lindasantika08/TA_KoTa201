<script>
import { ref, onMounted, computed } from "vue";
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
        { text: "Create Assessment", href: "/sispa/dosen/assessment/create" },
      ],
    };
  },
  setup() {
    const projects = ref([]);
    const inputMode = ref("export");
    const selectedActiveProject = ref(null);
    const selectedInactiveProject = ref(null);
    // const endDate = ref("");

    const defaultEndDate = new Date();
    defaultEndDate.setDate(defaultEndDate.getDate() + 7);
    const endDate = ref(defaultEndDate.toISOString().split('T')[0]);

    const activeProjects = computed(() => {
      return projects.value.filter(project => project.status === 'Active');
    });

    const inactiveProjects = computed(() => {
      return projects.value.filter(project => project.status !== 'Active');
    });

    const downloadActiveTemplate = async () => {
      if (selectedActiveProject.value) {
        await downloadTemplate(selectedActiveProject.value, 'Active');
      } else {
        alert("Please select an active project.");
      }
    };

    const downloadInactiveTemplate = async () => {
      if (selectedInactiveProject.value) {
        await downloadTemplate(selectedInactiveProject.value, 'NonActive');
      } else {
        alert("Please select a non-active project.");
      }
    };

    const downloadTemplate = async (project, type) => {
      try {
        const token = localStorage.getItem("auth_token");
        const response = await axios.get("/sispa/api/export-self-assessment", {
          params: {
            batch_year: project.batch_year,
            project_name: project.project_name,
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
        alert("There was an error downloading the Excel file.");
      }
    };

    const handleFileUpload = async (event) => {
      const file = event.target.files[0];
      if (!file) return;

      const selectedEndDate = endDate.value || defaultEndDate.toISOString().split('T')[0];

      const formData = new FormData();
      formData.append("file", file);
      formData.append("end_date", selectedEndDate);

      try {
        const token = localStorage.getItem("auth_token");
        const response = await axios.post("/dosen/assessment/import", formData, {
          headers: {
            "Content-Type": "multipart/form-data",
            "Authorization": `Bearer ${token}`
          }
        });

        alert(response.data.message || "Data imported successfully.");
        event.target.value = '';
      } catch (error) {
        console.error("Import error:", error);
        alert("There was an error importing the data.");
      }
    };

    onMounted(async () => {
      try {
        const response = await axios.get("/sispa/api/projects");
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
      inputMode,
      endDate,
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
        <Card title="Create Assessment">
          <template #actions>
            <!-- Segmented Radio Buttons -->
            <div class="flex w-full mb-4">
              <label class="w-1/2 text-center py-2 border cursor-pointer" :class="{
                'bg-blue-500 text-white': inputMode === 'export',
                'bg-white text-gray-700 border-gray-300':
                  inputMode !== 'export',
              }">
                <input type="radio" v-model="inputMode" value="export" class="hidden" />
                Export
              </label>
              <label class="w-1/2 text-center py-2 border cursor-pointer" :class="{
                'bg-blue-500 text-white': inputMode === 'import',
                'bg-white text-gray-700 border-gray-300':
                  inputMode !== 'import',
              }">
                <input type="radio" v-model="inputMode" value="import" class="hidden" />
                Import
              </label>
            </div>

            <div v-if="inputMode === 'export'">
              <div class="grid grid-cols-2 gap-8">
                <div class="border-r pr-4">
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Proyek Aktif
                  </label>
                  <select v-model="selectedActiveProject" class="mt-2 p-2 border border-gray-300 rounded w-full"
                    required>
                    <option value="" disabled selected>Pilih Proyek Aktif</option>
                    <option v-for="project in activeProjects"
                      :key="`active-${project.batch_year}-${project.project_name}`" :value="project">
                      {{ project.batch_year }} - {{ project.project_name }}
                    </option>
                  </select>
                  <div class="mt-4">
                    <button @click="downloadActiveTemplate"
                      class="w-full px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      :disabled="!selectedActiveProject">
                      <font-awesome-icon :icon="['fas', 'file-excel']" class="mr-2" />
                      Download Template Aktif
                    </button>
                  </div>
                </div>

                <div class="pl-4">
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Proyek Tidak Aktif
                  </label>
                  <select v-model="selectedInactiveProject" class="mt-2 p-2 border border-gray-300 rounded w-full"
                    required>
                    <option value="" disabled selected>Pilih Proyek Tidak Aktif</option>
                    <option v-for="project in inactiveProjects"
                      :key="`inactive-${project.batch_year}-${project.project_name}`" :value="project">
                      {{ project.batch_year }} - {{ project.project_name }}
                    </option>
                  </select>
                  <div class="mt-4">
                    <button @click="downloadInactiveTemplate"
                      class="w-full px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      :disabled="!selectedInactiveProject">
                      <font-awesome-icon :icon="['fas', 'file-excel']" class="mr-2" />
                      Download Template Tidak Aktif
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="inputMode === 'import'" class="mt-8">
              <!-- New End Date Input -->
              <div class="mb-4">
                <label for="end-date" class="block text-sm font-medium text-gray-700 mb-2">
                  Tanggal Akhir Pengisian Assessment
                </label>
                <input type="date" id="end-date" v-model="endDate"
                  class="mt-1 p-2 border border-gray-300 rounded w-full" required />
              </div>

              <label for="file-upload" class="block text-sm font-medium text-gray-700">
                Import Data Excel (File .xlsx/.xls)
              </label>
              <input type="file" id="file-upload" accept=".xlsx, .xls" @change="handleFileUpload"
                class="mt-2 p-2 border border-gray-300 rounded w-full" :disabled="!endDate" />
            </div>
          </template>
        </Card>
      </main>
    </div>
  </div>
</template>