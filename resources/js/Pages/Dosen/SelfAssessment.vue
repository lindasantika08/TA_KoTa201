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
        <h1 class="text-2xl font-bold">Self Assessment</h1>

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

        <!-- Container untuk assessment yang dikelompokkan berdasarkan aspek -->
        <div class="mt-6 space-y-8">
          <div 
            v-for="(group, aspek) in groupedAssessments" 
            :key="aspek" 
            class="bg-white rounded-lg shadow-lg p-6"
          >
            <!-- Header Aspek -->
            <div class="border-b pb-4 mb-6">
              <h2 class="text-2xl font-bold text-gray-800">{{ aspek }}</h2>
            </div>

            <!-- Daftar Pertanyaan untuk Aspek ini -->
            <div class="space-y-6">
              <div 
                v-for="assessment in group" 
                :key="assessment.id" 
                class="bg-gray-50 p-6 rounded-lg"
              >
                <!-- Pertanyaan -->
                <h3 class="text-lg font-semibold mb-4">{{ assessment.pertanyaan }}</h3>

                <!-- Tabel Bobot -->
                <div class="w-full">
                  <h4 class="font-medium mb-2">Bobot:</h4>
                  <div class="overflow-x-auto">
                    <table class="w-full table-auto border-collapse bg-white">
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
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
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

    onMounted(async () => {
      try {
        const response = await axios.get('/dosen/assessment/data-with-bobot');
        // Filter hanya yang bertipe 'self assessment'
        assessments.value = response.data.filter(assessment => assessment.type === 'selfAssessment');
      } catch (error) {
        console.error('Error fetching assessments or type criteria:', error);
      }
    });

    // Group assessments berdasarkan aspek
    const groupedAssessments = computed(() => {
      const groups = {};
      assessments.value.forEach(assessment => {
        if (!groups[assessment.aspek]) {
          groups[assessment.aspek] = [];
        }
        groups[assessment.aspek].push(assessment);
      });
      return groups;
    });

    return {
      handleLogout,
      downloadTemplate,
      handleFileUpload,
      groupedAssessments,
    };
  },
};
</script>