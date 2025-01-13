<template>
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <Sidebar role="dosen" />

    <!-- Main Content -->
    <div class="flex-1">
      <!-- Navbar -->
      <Navbar userName="Dosen" @logout="handleLogout" />

      <!-- Content -->
      <main class="p-6">
        <h1 class="text-2xl font-bold">Dashboard Dosen</h1>
        <p>Selamat datang di dashboard dosen.</p>

        <!-- Tombol untuk download template Excel -->
        <button 
          @click="downloadTemplate" 
          class="px-4 py-2 mt-4 bg-blue-500 text-white rounded"
        >
          Download Template Excel
        </button>

        <!-- Form untuk mengunggah file Excel -->
        <div class="mt-4">
          <label for="file-upload" class="block text-sm font-medium text-gray-700">
            Import Data Excel
          </label>
          <input 
            type="file" 
            id="file-upload" 
            accept=".xlsx, .xls" 
            @change="handleFileUpload"
            class="mt-2 p-2 border border-gray-300 rounded"
          />
        </div>

        <!-- Tabel untuk menampilkan data dari database -->
        <div class="mt-6">
          <h2 class="text-xl font-bold">Data Assessment</h2>
          <table class="min-w-full table-auto mt-4 border-collapse border border-gray-300">
            <thead>
              <tr>
                <th class="px-4 py-2 border">ID</th>
                <th class="px-4 py-2 border">Type</th>
                <th class="px-4 py-2 border">Pertanyaan</th>
                <th class="px-4 py-2 border">aspek</th>
                <th class="px-4 py-2 border">kriteria</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="assessment in assessments" :key="assessment.id">
                <td class="px-4 py-2 border">{{ assessment.id }}</td>
                <td class="px-4 py-2 border">{{ assessment.type }}</td>
                <td class="px-4 py-2 border">{{ assessment.pertanyaan }}</td>
                <td class="px-4 py-2 border">{{ assessment.aspek }}</td>
                <td class="px-4 py-2 border">{{ assessment.kriteria }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </main>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';

export default {
  components: {
    Sidebar,
    Navbar,
  },
  setup() {
    const isLoggingOut = ref(false);
    const assessments = ref([]); // Menyimpan data assessment

    const handleLogout = async () => {
      if (isLoggingOut.value) return;
      isLoggingOut.value = true;

      try {
        const token = localStorage.getItem('auth_token');
        if (token) {
          await axios.post(
            '/api/logout',
            {},
            {
              headers: {
                Authorization: `Bearer ${token}`,
              },
            }
          );
        }
      } catch (error) {
        console.error('Logout error:', error);
      } finally {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
        router.visit('/login');
        isLoggingOut.value = false;
      }
    };

    // Fungsi untuk mengunduh template Excel
    const downloadTemplate = () => {
      const templateUrl = '/templates/template.xlsx';
      const link = document.createElement('a');
      link.href = templateUrl;
      link.download = 'template.xlsx';
      link.click();
    };

    // Fungsi untuk menangani unggahan file
    const handleFileUpload = async (event) => {
      const formData = new FormData();
      formData.append('file', event.target.files[0]);

      try {
        await axios.post('/dosen/assessment/import', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        });
        alert('Data berhasil diimpor');
      } catch (error) {
        console.error('Import error:', error);
        alert('Terjadi kesalahan saat mengimpor data');
      }
    };

    // Ambil data assessment dari API saat komponen dimuat
    onMounted(async () => {
      try {
        const response = await axios.get('/dosen/assessment/data');
        assessments.value = response.data;
      } catch (error) {
        console.error('Error fetching assessments:', error);
      }
    });

    return {
      handleLogout,
      downloadTemplate,
      handleFileUpload,
      assessments,
    };
  },
};
</script>

<style scoped>
/* Tambahkan styling khusus jika diperlukan */
</style>
