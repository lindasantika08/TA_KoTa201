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
        { text: "Create Assessment", href: "/dosen/assessment/create" },
      ],
    };
  },
  setup() {
    const projects = ref([]);
    const inputMode = ref("export");
    const selectedActiveProject = ref(null);
    const selectedInactiveProject = ref(null);
    // const endDate = ref("");

    const isLoading = ref(false);
    const errorMessage = ref("");
    const isDragging = ref(false);
    const selectedFile = ref(null);
    const uploadProgress = ref(0);
    const isUploading = ref(false);
    const fileInput = ref(null);

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
        const response = await axios.get("/api/export-self-assessment", {
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

    const handleDragOver = (event) => {
      event.preventDefault();
      isDragging.value = true;
    };

    const handleDragLeave = (event) => {
      event.preventDefault();
      isDragging.value = false;
    };

    const handleDrop = (event) => {
      event.preventDefault();
      isDragging.value = false;
      const files = event.dataTransfer.files;
      if (files.length > 0) {
        handleFiles(files[0]);
      }
    };

    const handleFileSelect = (event) => {
      const files = event.target.files;
      if (files.length > 0) {
        handleFiles(files[0]);
      }
    };

    const handleFiles = (file) => {
      if (!file.name.match(/\.(xlsx|xls)$/)) {
        alert('Hanya file Excel (.xlsx atau .xls) yang diperbolehkan');
        return;
      }
      selectedFile.value = file;
      uploadProgress.value = 0;
    };

    const cancelUpload = () => {
      selectedFile.value = null;
      uploadProgress.value = 0;
    };


    const handleFileUpload = async (event) => {
      if (!selectedFile.value) return;
      const selectedEndDate = endDate.value || defaultEndDate.toISOString().split('T')[0];

      const formData = new FormData();
      formData.append("file", selectedFile.value);
      formData.append("end_date", selectedEndDate);

      try {
        isUploading.value = true;
        const token = localStorage.getItem("auth_token");
        await axios.post("/dosen/assessment/import", formData, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "multipart/form-data",
          },
          onUploadProgress: (progressEvent) => {
            uploadProgress.value = Math.round(
              (progressEvent.loaded * 100) / progressEvent.total
            );
          },
        });
        alert("Data assessment berhasil diimpor.");
        selectedFile.value = null;
        uploadProgress.value = 0;
      } catch (error) {
        console.error("Error import:", error.response?.data);
        alert("Gagal mengimpor data. Silakan periksa format file Anda.");
      } finally {
        isUploading.value = false;
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
      handleDragOver,
      handleDragLeave,
      handleDrop,
      handleFileSelect,
      cancelUpload,
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
      fileInput,
      isLoading,
      errorMessage,
      isDragging,
      selectedFile,
      uploadProgress,
      isUploading,
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

            <!-- Import Section -->
            <div v-if="inputMode === 'import'" class="space-y-4">
              <div class="mb-4">
                <label for="end-date" class="block text-sm font-medium text-gray-700 mb-2">
                  Tanggal Akhir Pengisian Assessment
                </label>
                <input type="date" id="end-date" v-model="endDate"
                  class="mt-1 p-2 border border-gray-300 rounded w-full" required />
              </div>

              <!-- Drag & Drop Area -->
              <div @dragover="handleDragOver" @dragleave="handleDragLeave" @drop="handleDrop"
                class="relative border-2 border-dashed rounded-lg p-8 text-center transition-all duration-200" :class="{
                  'border-blue-500 bg-blue-50': isDragging,
                  'border-gray-300 hover:border-gray-400': !isDragging
                }">
                <!-- File input is only visible when no file is selected -->
                <input v-if="!selectedFile" ref="fileInput" type="file" accept=".xlsx,.xls" @change="handleFileSelect"
                  class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />

                <div v-if="!selectedFile" class="space-y-2">
                  <font-awesome-icon :icon="['fas', 'file-excel']" class="text-4xl text-gray-400" />
                  <div class="text-gray-600">
                    <p class="font-medium">
                      Drag & drop file Excel di sini
                    </p>
                    <p class="text-sm">
                      atau klik untuk memilih file
                    </p>
                  </div>
                  <p class="text-xs text-gray-500">
                    Format yang didukung: .xlsx, .xls
                  </p>
                </div>

                <div v-else class="space-y-4">
                  <div class="flex items-center justify-center space-x-2">
                    <font-awesome-icon :icon="['fas', 'file-excel']" class="text-2xl text-green-500" />
                    <span class="font-medium text-gray-700">
                      {{ selectedFile.name }}
                    </span>
                  </div>

                  <!-- Progress Bar -->
                  <div v-if="uploadProgress > 0" class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-blue-500 h-2.5 rounded-full transition-all duration-300"
                      :style="{ width: `${uploadProgress}%` }"></div>
                  </div>

                  <!-- Action Buttons -->
                  <div class="flex justify-center space-x-2">
                    <button @click="handleFileUpload" :disabled="isUploading"
                      class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors">
                      <font-awesome-icon :icon="['fas', 'upload']" class="mr-2" />
                      {{ isUploading ? 'Mengunggah...' : 'Upload' }}
                    </button>
                    <button @click="cancelUpload" :disabled="isUploading"
                      class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors">
                      <font-awesome-icon :icon="['fas', 'times']" class="mr-2" />
                      Batal
                    </button>
                  </div>
                </div>
              </div>
            </div>



          </template>
        </Card>
      </main>
    </div>
  </div>
</template>