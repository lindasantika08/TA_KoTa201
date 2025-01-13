<template>
  <div class="flex min-h-screen">
    <Sidebar role="dosen" />

    <div class="flex-1">
      <Navbar userName="Dosen" @logout="handleLogout" />

      <main class="p-6">
        <h1 class="text-2xl font-bold">Dashboard Dosen</h1>
        <p>Selamat datang di dashboard dosen.</p>

        <button 
          @click="downloadTemplate" 
          class="px-4 py-2 mt-4 bg-blue-500 text-white rounded"
        >
          Download Template Excel
        </button>

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

        <div class="mt-6">
          <h2 class="text-xl font-bold">Data Assessment</h2>
          <table class="min-w-full table-auto mt-4 border-collapse border border-gray-300">
            <thead>
              <tr>
                <th class="px-4 py-2 border">ID</th>
                <th class="px-4 py-2 border">Type</th>
                <th class="px-4 py-2 border">Created At</th>
                <th class="px-4 py-2 border">Updated At</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="assessment in assessments" :key="assessment.id">
                <td class="px-4 py-2 border">{{ assessment.id }}</td>
                <td class="px-4 py-2 border">{{ assessment.type }}</td>
                <td class="px-4 py-2 border">{{ assessment.created_at }}</td>
                <td class="px-4 py-2 border">{{ assessment.updated_at }}</td>
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
    const assessments = ref([]);

    const handleLogout = async () => {
      if (isLoggingOut.value) return;
      isLoggingOut.value = true;

      try {
        const token = localStorage.getItem('auth_token');
        if (token) {
          await axios.put(
            '/api/logout',
            {},
            {
              headers: {
                Authorization: `Bearer ${token}`
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

    const handleFileUpload = async (event) => {
      const formData = new FormData();
      formData.append('file', event.target.files[0]);

      try {
        const token = localStorage.getItem('auth_token');
        
        await axios.post('/api/import-self-assessment', formData, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'multipart/form-data',
          },
        });
        
        alert('Data berhasil diimpor');
        fetchAssessments();  
      } catch (error) {
        console.error('Import error:', error);
        alert(error.response?.data?.message || 'Terjadi kesalahan saat mengimpor data');
      }
    };

    const fetchAssessments = async () => {
      try {
        const token = localStorage.getItem('auth_token');
        const response = await axios.get('/api/dosen/assessment/data', {
          headers: {
            'Authorization': `Bearer ${token}`,
          }
        });
        assessments.value = response.data;
      } catch (error) {
        console.error('Error fetching assessments:', error);
      }
    };

    onMounted(() => {
      fetchAssessments();
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
</style>