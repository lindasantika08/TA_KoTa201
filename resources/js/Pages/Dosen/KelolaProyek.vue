<template>
  <div class="flex min-h-screen">
    <Sidebar role="dosen" />
    <div class="flex-1">
      <Navbar title="Kelola Proyek" />
      <main class="p-6">
        <h1 class="font-bold text-gray-800 mb-6">Kelola Proyek</h1>
        <button
          @click="openModal"
          class="px-4 py-2 mt-4 bg-blue-500 text-white rounded"
        >
          Tambah Proyek
        </button>

        <!-- Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
          <div class="bg-white p-6 rounded-lg w-1/2">
            <h2 class="text-lg font-semibold mb-4">Tambah Proyek</h2>
            <form @submit.prevent="addProject">
              <div class="mb-4">
                <label class="block text-sm font-medium">Semester</label>
                <input
                  type="text"
                  v-model="newProject.semester"
                  class="w-full border border-gray-300 rounded p-2"
                  required
                />
              </div>
              <div class="mb-4">
                <label class="block text-sm font-medium">Tahun Ajaran</label>
                <input
                  type="text"
                  v-model="newProject.tahun_ajaran"
                  class="w-full border border-gray-300 rounded p-2"
                  required
                />
              </div>
              <div class="mb-4">
                <label class="block text-sm font-medium">Nama Proyek</label>
                <input
                  type="text"
                  v-model="newProject.nama_proyek"
                  class="w-full border border-gray-300 rounded p-2"
                  required
                />
              </div>
              <div class="mb-4">
                <label class="block text-sm font-medium">Jurusan</label>
                <input
                  type="text"
                  v-model="newProject.jurusan"
                  class="w-full border border-gray-300 rounded p-2"
                  required
                />
              </div>
              <div class="mb-4">
                <label class="block text-sm font-medium">Tanggal Mulai</label>
                <input
                  type="date"
                  v-model="newProject.start_date"
                  class="w-full border border-gray-300 rounded p-2"
                  required
                />
              </div>
              <div class="mb-4">
                <label class="block text-sm font-medium">Tanggal Selesai</label>
                <input
                  type="date"
                  v-model="newProject.end_date"
                  class="w-full border border-gray-300 rounded p-2"
                  required
                />
              </div>
              <div class="mb-4">
                <label class="block text-sm font-medium">Status</label>
                <select
                  v-model="newProject.status"
                  class="w-full border border-gray-300 rounded p-2"
                  required
                >
                  <option value="aktif">Aktif</option>
                  <option value="nonaktif">Nonaktif</option>
                </select>
              </div>
              <div class="flex justify-end">
                <button
                  type="button"
                  @click="closeModal"
                  class="px-4 py-2 bg-gray-300 text-black rounded mr-2"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  class="px-4 py-2 bg-blue-500 text-white rounded"
                >
                  Simpan
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Tabel Daftar Proyek -->
        <div class="mt-8">
          <h2 class="text-lg font-semibold mb-4">Daftar Proyek</h2>
          <table class="min-w-full border-collapse table-auto">
            <thead>
              <tr class="bg-gray-100">
                <th class="px-4 py-2 border">Nama Proyek</th>
                <th class="px-4 py-2 border">Semester</th>
                <th class="px-4 py-2 border">Tahun Ajaran</th>
                <th class="px-4 py-2 border">Jurusan</th>
                <th class="px-4 py-2 border">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(project, index) in projects" :key="index">
                <td class="px-4 py-2 border">{{ project.nama_proyek }}</td>
                <td class="px-4 py-2 border">{{ project.semester }}</td>
                <td class="px-4 py-2 border">{{ project.tahun_ajaran }}</td>
                <td class="px-4 py-2 border">{{ project.jurusan }}</td>
                <td class="px-4 py-2 border">{{ project.status }}</td>
              </tr>
            </tbody>
          </table>
        </div>

      </main>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";

export default {
  name: "KelolaProyek",
  components: {
    Sidebar,
    Navbar,
  },
  data() {
    return {
      isModalOpen: false,
      newProject: {
        semester: "",
        tahun_ajaran: "",
        nama_proyek: "",
        jurusan: "",
        start_date: "",
        end_date: "",
        status: "aktif",
      },
      projects: [], // Menyimpan data proyek
    };
  },
  mounted() {
    this.getProjects(); // Panggil untuk mengambil data proyek saat halaman dimuat
  },
  methods: {
    openModal() {
      this.isModalOpen = true;
    },
    closeModal() {
      this.isModalOpen = false;
    },
    async addProject() {
      try {
        const response = await axios.post('/api/project', this.newProject, {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
          },
        });
        alert("Proyek berhasil ditambahkan!");
        console.log(response.data);
        this.closeModal();
        this.getProjects(); // Reload data proyek setelah menambahkan proyek baru
      } catch (error) {
        console.error("Error adding project:", error);
        alert("Terjadi kesalahan saat menambahkan proyek.");
      }
    },
    async getProjects() {
      try {
        const response = await axios.get('/api/projects', {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
          },
        });
        this.projects = response.data; // Menyimpan data proyek yang diterima
      } catch (error) {
        console.error("Error fetching projects:", error);
        alert("Terjadi kesalahan saat mengambil data proyek.");
      }
    },
  },
};
</script>
