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
        { text: "Manage Mahasiswa", href: "/dosen/kelola-mahasiswa" },
        { text: "Input", href: "/dosen/kelola-mahasiswa/input" },
      ],
    };
  },
  setup() {
    const projects = ref([]);
    const selectedProject = ref({ tahun_ajaran: "", nama_proyek: "" });
    const angkatanOptions = ref([]);
    const selectedAngkatan = ref("");

    const jurusanList = ref([
      {
        jurusan: "Teknik Sipil",
        prodi: [
          "D-3 Teknik Konstruksi Sipil",
          "D-3 Teknik Konstruksi Gedung",
          "D-4 Teknik Perancangan Jalan dan Jembatan",
          "D-4 Teknik Perawatan dan Perbaikan Gedung",
          "S-2 Rekayasa Infrastruktur",
        ],
      },
      {
        jurusan: "Teknik Mesin",
        prodi: [
          "D-3 Teknik Mesin",
          "D-3 Teknik Aeronautika",
          "D-4 Teknik Perancangan dan Konstruksi Mesin",
          "D-4 Proses Manufaktur",
        ],
      },
      {
        jurusan: "Teknik Refrigasi dan Tata Udara",
        prodi: [
          "D-3 Teknik Pendingin dan Tata Udara",
          "D-4 Teknik Pendingin dan Tata Udara",
        ],
      },
      {
        jurusan: "Teknik Konversi Energi",
        prodi: [
          "D-3 Teknik Konversi Energi",
          "D-4 Teknologi Pembangkit Tenaga Listrik",
          "D-4 Teknik Konservasi Energi",
        ],
      },
      {
        jurusan: "Teknik Elektro",
        prodi: [
          "D-3 Teknik Elektronika",
          "D-3 Teknik Listrik",
          "D-3 Teknik Telekomunikasi",
          "D-4 Teknik Elektronika",
          "D-4 Teknik Telekomunikasi",
          "D-4 Teknik Otomasi Industri",
        ],
      },
      {
        jurusan: "Teknik Kimia",
        prodi: [
          "D-3 Teknik Kimia",
          "D-3 Analis Kimia",
          "D-4 Teknik Kimia Produksi Bersih",
        ],
      },
      {
        jurusan: "Teknik Komputer dan Informatika",
        prodi: ["D-3 Teknik Informatika", "D-4 Teknik Informatika"],
      },
      {
        jurusan: "Akuntansi",
        prodi: [
          "D-3 Akuntansi",
          "D-3 Keuangan dan Perbankan",
          "D-4 Akuntansi Manajemen Pemerintahan",
          "D-4 Akuntansi",
          "D-4 Keuangan Syariah",
          "S-2 Keuangan & Perbankan Syariah",
        ],
      },
      {
        jurusan: "Administrasi Niaga",
        prodi: [
          "D-3 Administrasi Bisnis",
          "D-3 Manajemen Pemasaran",
          "D-3 Usaha Perjalanan Wisata",
          "D-3 Manajemen Aset",
          "D-4 Manajemen Aset",
          "D-4 Administrasi Bisnis",
          "D-4 Manajemen Pemasaran",
          "D-4 Destinasi Pariwisata",
        ],
      },
      {
        jurusan: "Bahasa Inggris",
        prodi: ["D-3 Bahasa Inggris"],
      },
    ]);

    const selectedJurusan = ref("");
    const selectedProdi = ref("");
    const filteredProdi = ref([]);

    const onJurusanChange = () => {
      const jurusan = jurusanList.value.find(
        (item) => item.jurusan === selectedJurusan.value
      );
      filteredProdi.value = jurusan ? jurusan.prodi : [];
      selectedProdi.value = ""; // Reset prodi jika jurusan berubah
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
        !selectedProject.value.tahun_ajaran ||
        !selectedProject.value.nama_proyek
      ) {
        alert("Pilih Tahun Ajaran dan Nama Proyek terlebih dahulu.");
        return;
      }

      try {
        const token = localStorage.getItem("auth_token");

        const response = await axios.get("/dosen/kelola-mahasiswa/export", {
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
        link.setAttribute("download", "Data_Mahasiswa.xlsx");
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
        await axios.post("/dosen/kelola-mahasiswa/import", formData, {
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

    onMounted(() => {
      generateAngkatanOptions();
      axios
        .get("/api/projects")
        .then((response) => {
          projects.value = response.data;
        })
        .catch((error) => {
          console.error("Error fetching projects:", error);
        });
    });

    return {
      downloadTemplate,
      handleFileUpload,
      projects,
      selectedProject,
      angkatanOptions,
      selectedAngkatan,
      jurusanList,
      selectedJurusan,
      selectedProdi,
      filteredProdi,
      onJurusanChange,
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
            <div class="mt-4">
              <label
                for="project-select"
                class="block text-sm font-medium text-gray-700"
              >
                Pilih Tahun Ajaran dan Nama Proyek
              </label>
              <select
                id="project-select"
                v-model="selectedProject"
                class="mt-2 p-2 border border-gray-300 rounded w-full"
                required
              >
                <option value="" disabled selected>Pilih Proyek</option>
                <option
                  v-for="project in projects"
                  :key="`${project.tahun_ajaran}-${project.nama_proyek}`"
                  :value="project"
                >
                  {{ project.tahun_ajaran }} - {{ project.nama_proyek }}
                </option>
              </select>
            </div>
            <div class="mt-4">
              <label
                for="angkatan-select"
                class="block text-sm font-medium text-gray-700"
              >
                Pilih Angkatan
              </label>
              <select
                id="angkatan-select"
                v-model="selectedAngkatan"
                class="mt-2 p-2 border border-gray-300 rounded w-full"
                required
              >
                <option value="" disabled selected>Pilih Angkatan</option>
                <option
                  v-for="angkatan in angkatanOptions"
                  :key="angkatan"
                  :value="angkatan"
                >
                  {{ angkatan }}
                </option>
              </select>
            </div>

            <div class="mt-4">
              <label
                for="jurusan-select"
                class="block text-sm font-medium text-gray-700"
              >
                Pilih Jurusan
              </label>
              <select
                id="jurusan-select"
                v-model="selectedJurusan"
                @change="onJurusanChange"
                class="mt-2 p-2 border border-gray-300 rounded w-full"
              >
                <option value="" disabled selected>Pilih Jurusan</option>
                <option
                  v-for="jurusan in jurusanList"
                  :key="jurusan.jurusan"
                  :value="jurusan.jurusan"
                >
                  {{ jurusan.jurusan }}
                </option>
              </select>
            </div>

            <!-- Dropdown Prodi -->
            <div class="mt-4">
              <label
                for="prodi-select"
                class="block text-sm font-medium text-gray-700"
              >
                Pilih Prodi
              </label>
              <select
                id="prodi-select"
                v-model="selectedProdi"
                class="mt-2 p-2 border border-gray-300 rounded w-full"
                :disabled="!filteredProdi.length"
              >
                <option value="" disabled selected>Pilih Prodi</option>
                <option
                  v-for="prodi in filteredProdi"
                  :key="prodi"
                  :value="prodi"
                >
                  {{ prodi }}
                </option>
              </select>
            </div>

            <div class="mt-4">
              <button
                @click="downloadTemplate"
                class="px-4 py-2 mt-2 bg-blue-500 text-white rounded"
                :disabled="
                  !selectedProject.tahun_ajaran || !selectedProject.nama_proyek
                "
              >
                Download Template Kelompok
              </button>
            </div>

            <div class="mt-4">
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