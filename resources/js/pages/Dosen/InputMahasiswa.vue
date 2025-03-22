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
        { text: "Manage Mahasiswa", href: "/dosen/manage-mahasiswa" },
        { text: "Input", href: "/dosen/manage-mahasiswa/input" },
      ],
    };
  },
  setup() {
    const angkatanOptions = ref([]);
    const selectedAngkatan = ref("");
    const inputMode = ref("export");

    const isLoading = ref(false);
    const errorMessage = ref("");
    const isDragging = ref(false);
    const selectedFile = ref(null);
    const uploadProgress = ref(0);
    const isUploading = ref(false);
    const fileInput = ref(null);

    const jurusanList = ref([]);
    const selectedJurusan = ref("");
    const selectedProdi = ref("");
    const filteredProdi = ref([]);

    const onJurusanChange = async () => {
      if (!selectedJurusan.value) return;

      try {
        const response = await axios.get(
          `/sispa/api/get-prodi/${selectedJurusan.value}`
        );
        if (response.data.status === "success") {
          filteredProdi.value = response.data.data;
        } else {
          filteredProdi.value = [];
        }
        selectedProdi.value = ""; // Reset prodi when jurusan changes
      } catch (error) {
        console.error("Error fetching prodi:", error);
        filteredProdi.value = [];
      }
    };

    const generateAngkatanOptions = () => {
      const currentYear = new Date().getFullYear();
      angkatanOptions.value = Array.from(
        { length: 5 },
        (_, i) => currentYear - i
      );
    };

    const downloadTemplate = async () => {
      if (
        !selectedJurusan.value ||
        !selectedProdi.value ||
        !selectedAngkatan.value
      ) {
        alert("Harap lengkapi semua pilihan: Jurusan, Prodi, dan Angkatan.");
        return;
      }

      try {
        const token = localStorage.getItem("auth_token");

        // First try to get the blob
        const response = await axios.get("/dosen/manage-mahasiswa/export", {
          params: {
            jurusan: selectedJurusan.value,
            prodi: selectedProdi.value,
            angkatan: selectedAngkatan.value,
          },
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
          responseType: "blob",
        });

        // Check if the response is an error message
        if (
          response.data instanceof Blob &&
          response.data.type.includes("json")
        ) {
          const text = await response.data.text();
          const error = JSON.parse(text);
          throw new Error(error.message || "Error downloading file");
        }

        // If we get here, we have a valid Excel file
        const blob = new Blob([response.data], {
          type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        });

        const url = window.URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.setAttribute("download", "Data_Mahasiswa.xlsx");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
      } catch (error) {
        console.error("Download error:", error);
        alert(
          error.response?.data?.message ||
          "Terjadi kesalahan saat mengunduh file excel"
        );
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

    const handleFileUpload = async (event) => {
      if (!selectedFile.value) return;

      const formData = new FormData();
      formData.append("file", selectedFile.value);

      try {
        isUploading.value = true;
        const token = localStorage.getItem("auth_token");
        await axios.post("/dosen/manage-mahasiswa/importDosen", formData, {
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
        alert("Data mahasiswa berhasil diimpor.");
        selectedFile.value = null;
        uploadProgress.value = 0;
      } catch (error) {
        console.error("Error import:", error.response?.data);
        alert("Gagal mengimpor data. Silakan periksa format file Anda.");
      } finally {
        isUploading.value = false;
      }
    };

    const cancelUpload = () => {
      selectedFile.value = null;
      uploadProgress.value = 0;
    };

    onMounted(async () => {
      generateAngkatanOptions();

      try {
        const response = await axios.get("/sispa/api/get-jurusan");
        if (response.data.status === "success") {
          jurusanList.value = response.data.data;
        } else {
          console.error("Error fetching jurusan:", response.data.message);
        }
      } catch (error) {
        console.error("Error fetching jurusan:", error);
      }
    });

    return {
      handleDragOver,
      handleDragLeave,
      handleDrop,
      handleFileSelect,
      cancelUpload,
      downloadTemplate,
      handleFileUpload,
      angkatanOptions,
      selectedAngkatan,
      jurusanList,
      selectedJurusan,
      selectedProdi,
      filteredProdi,
      onJurusanChange,
      inputMode,
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
        <Card title="Input Data Mahasiswa">
          <template #actions>
            <!-- Segmented Radio Buttons -->
            <div class="flex w-full mb-4">
              <label class="w-1/2 text-center py-2 border cursor-pointer transition-all duration-200" :class="{
                'bg-blue-500 text-white': inputMode === 'export',
                'bg-white text-gray-700 border-gray-300':
                  inputMode !== 'export',
              }">
                <input type="radio" v-model="inputMode" value="export" class="hidden" />
                Export
              </label>
              <label class="w-1/2 text-center py-2 border cursor-pointer transition-all duration-200" :class="{
                'bg-blue-500 text-white': inputMode === 'import',
                'bg-white text-gray-700 border-gray-300':
                  inputMode !== 'import',
              }">
                <input type="radio" v-model="inputMode" value="import" class="hidden" />
                Import
              </label>
            </div>

            <!-- Export Section -->
            <div v-if="inputMode === 'export'">
              <div>
                <label for="angkatan-select" class="block text-sm font-medium text-gray-700 mb-2">
                  Pilih Angkatan
                </label>
                <select id="angkatan-select" v-model="selectedAngkatan"
                  class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  required>
                  <option value="" disabled selected>Pilih Angkatan</option>
                  <option v-for="angkatan in angkatanOptions" :key="angkatan" :value="angkatan">
                    {{ angkatan }}
                  </option>
                </select>
              </div>

              <div>
                <label for="jurusan-select" class="block text-sm font-medium text-gray-700 mb-2">
                  Pilih Jurusan
                </label>
                <select id="jurusan-select" v-model="selectedJurusan" @change="onJurusanChange"
                  class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                  <option value="" disabled selected>Pilih Jurusan</option>
                  <option v-for="jurusan in jurusanList" :key="jurusan.id" :value="jurusan.id">
                    {{ jurusan.major_name }}
                  </option>
                </select>
              </div>

              <div>
                <label for="prodi-select" class="block text-sm font-medium text-gray-700 mb-2">
                  Pilih Prodi
                </label>
                <select id="prodi-select" v-model="selectedProdi"
                  class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  :disabled="!filteredProdi.length">
                  <option value="" disabled selected>Pilih Prodi</option>
                  <option v-for="prodi in filteredProdi" :key="prodi.id" :value="prodi.id">
                    {{ prodi.prodi_name }}
                  </option>
                </select>
              </div>

              <div class="mt-4">
                <button @click="downloadTemplate"
                  class="w-full px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
                  :disabled="!selectedJurusan || !selectedProdi || !selectedAngkatan ||
                    isLoading
                    ">
                  <font-awesome-icon :icon="['fas', 'download']" class="mr-2" />
                  Download Template
                </button>
              </div>
            </div>

            <!-- Import Section -->
            <div v-if="inputMode === 'import'" class="space-y-4">
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