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

        <!-- Menampilkan data assessment dalam bentuk card -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="assessment in assessments" 
            :key="assessment.id" 
            class="bg-white p-4 rounded-lg shadow-lg"
          >
            <!-- Card Header: Pertanyaan -->
            <h3 class="text-xl font-semibold mb-4">{{ assessment.pertanyaan }}</h3>

            <!-- Card Body: Bobot -->
            <div>
              <h4 class="font-medium mb-2">Bobot:</h4>
              <table class="min-w-full table-auto border-collapse">
                <thead>
                  <tr>
                    <th class="px-4 py-2 border">Bobot 1</th>
                    <th class="px-4 py-2 border">Bobot 2</th>
                    <th class="px-4 py-2 border">Bobot 3</th>
                    <th class="px-4 py-2 border">Bobot 4</th>
                    <th class="px-4 py-2 border">Bobot 5</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="px-4 py-2 border">{{ assessment.bobot_1 }}</td>
                    <td class="px-4 py-2 border">{{ assessment.bobot_2 }}</td>
                    <td class="px-4 py-2 border">{{ assessment.bobot_3 }}</td>
                    <td class="px-4 py-2 border">{{ assessment.bobot_4 }}</td>
                    <td class="px-4 py-2 border">{{ assessment.bobot_5 }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
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
    const downloadTemplate = async () => {
    try {
      const token = localStorage.getItem('auth_token');
      
      const response = await axios.get('/api/export-self-assessment', {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
        responseType: 'blob'
      });

      const blob = new Blob([response.data], { 
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      });
      
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'self-assessment.xlsx');
      document.body.appendChild(link);
      
      link.click();
      
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      
    } catch (error) {
      console.error('Download error:', error);
      alert('Terjadi kesalahan saat mengunduh file excel');
    }
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
