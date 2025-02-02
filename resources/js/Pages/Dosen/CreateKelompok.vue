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
            <!-- Dropdown untuk memilih Tahun Ajaran dan Nama Proyek (Untuk Export) -->
            <div class="mt-4">
              <label for="project-select" class="block text-sm font-medium text-gray-700">
                Pilih Tahun Ajaran dan Nama Proyek
              </label>
              <select id="project-select" v-model="selectedProject"
                class="mt-2 p-2 border border-gray-300 rounded w-full" required>
                <option value="" disabled selected>Pilih Proyek</option>
                <option v-for="project in projects" :key="`${project.tahun_ajaran}-${project.nama_proyek}`"
                  :value="project">
                  {{ project.tahun_ajaran }} - {{ project.nama_proyek }}
                </option>
              </select>
            </div>

            <!-- Tombol untuk mendownload template -->
            <div class="mt-4">
              <button @click="downloadTemplate" class="px-4 py-2 mt-2 bg-blue-500 text-white rounded"
                :disabled="!selectedProject.tahun_ajaran || !selectedProject.nama_proyek">
                Download Template Kelompok
              </button>
            </div>

            <!-- Import Data Excel -->
            <div class="mt-4">
              <label for="file-upload" class="block text-sm font-medium text-gray-700">
                Import Data Excel (File .xlsx/.xls)
              </label>
              <input type="file" id="file-upload" accept=".xlsx, .xls" @change="handleFileUpload"
                class="mt-2 p-2 border border-gray-300 rounded" />
            </div>
          </template>
        </Card>
      </main>
    </div>
  </div>
</template>

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
    const projects = ref([]); // Menyimpan daftar proyek
    const selectedProject = ref({ tahun_ajaran: "", nama_proyek: "" }); // Menyimpan pilihan proyek

    // Fungsi untuk mendownload template kelompok
    const downloadTemplate = async () => {
      if (!selectedProject.value.tahun_ajaran || !selectedProject.value.nama_proyek) {
        alert("Pilih Tahun Ajaran dan Nama Proyek terlebih dahulu.");
        return;
      }

      try {
        const token = localStorage.getItem("auth_token");

        const response = await axios.get("/dosen/kelola-kelompok/export", {
          params: {
            tahun_ajaran: selectedProject.value.tahun_ajaran,
            nama_proyek: selectedProject.value.nama_proyek,
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

    // Fungsi untuk menghandle upload file Excel
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
        const errorMessage = error.response ? error.response.data.error : "Terjadi kesalahan saat mengimpor data";
        alert(`Error: ${errorMessage}`);
      }
    };

    // Mengambil data proyek saat komponen dipasang
    onMounted(async () => {
      try {
        const response = await axios.get("/api/projects");
        projects.value = response.data; // Menyimpan daftar proyek
      } catch (error) {
        console.error("Error fetching projects:", error);
      }
    });

    return {
      downloadTemplate,
      handleFileUpload,
      projects,
      selectedProject,
    };
  },
};
</script>
