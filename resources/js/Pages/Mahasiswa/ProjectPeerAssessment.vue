<script>
import axios from 'axios';
import DataTable from "@/Components/DataTable.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import SidebarMahasiswa from "@/Components/SidebarMahasiswa.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import dayjs from 'dayjs';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

export default {
  components: {
    DataTable,
    Navbar,
    Card,
    SidebarMahasiswa,
    Breadcrumb
  },
  data() {
    return {
      breadcrumbs: [
        { text: "Assessment", href: "/mahasiswa/assessment/peer" },
        { text: "Peer Assessment", href: null }
      ],
      headers: [
        { key: 'no', label: 'No' },
        { key: 'batch_year', label: 'Tahun Ajaran' },
        { key: 'project_name', label: 'Nama Proyek' },
        { key: 'assessment_order', label: 'Order' },
        { key: 'status', label: 'Status' },
        { key: 'date', label: 'Tanggal Pengisian' },
        { key: 'actions', label: 'Actions' },
      ],
      items: [],
    }
  },
  methods: {
    // Method untuk mengecek apakah semua assessment sudah lengkap
    isAllAssessmentComplete(item) {
      // Jika ada total_members dan answered_members, gunakan itu
      if (item.total_members && item.answered_members !== undefined) {
        return item.answered_members >= item.total_members && 
               item.answered_questions >= item.total_questions;
      }
      
      // Fallback: jika struktur data berbeda, bisa disesuaikan
      // Misalnya jika ada field completion_percentage
      if (item.completion_percentage !== undefined) {
        return item.completion_percentage >= 100;
      }
      
      // Fallback: gunakan logika lama jika tidak ada data member
      return item.answered_questions >= item.total_questions;
    },

    handleAnswer(item) {
      // Cek apakah masih ada assessment yang belum lengkap
      if (this.isAllAssessmentComplete(item)) {
        Swal.fire({
          title: 'Informasi',
          text: 'Anda sudah menyelesaikan semua assessment untuk proyek ini.',
          icon: 'info',
          confirmButtonText: 'OK',
          confirmButtonColor: '#3085d6',
        });
        return;
      }

      Swal.fire({
        title: 'Peringatan!',
        html: 'Anda wajib mengisi alasan dengan <b>minimal 1 kalimat</b> pada setiap penilaian yang diberikan.',
        icon: 'warning',
        confirmButtonText: 'Mengerti',
        confirmButtonColor: '#3085d6',
        backdrop: `
      rgba(128,128,128,0.4)
      left top
      no-repeat
    `
      }).then((result) => {
        if (result.isConfirmed) {
          const batch_year = item.batch_year;
          const project_name = item.project_name;
          const assessment_order = item.assessment_order || '1';

          router.visit(`/mahasiswa/assessment/peer-assessment`, {
            method: 'get',
            data: {
              batch_year: batch_year,
              project_name: project_name,
              assessment_order: assessment_order
            },
            preserveState: true,
            onError: (error) => {
              console.error('Navigation error:', error);
            }
          });
        }
      });
    },
    
    handleDetail(item) {
      router.visit(`/mahasiswa/peer-assessment/peer-detail`, {
        method: 'get',
        data: {
          batch_year: item.batch_year,
          project_name: item.project_name,
          assessment_order: item.assessment_order || '1'
        },
        preserveState: true
      });
    },
  },
  mounted() {
    axios.get('/api/peer-assessment')
      .then(response => {
        this.items = response.data.assessments.map((item, index) => ({
          id: item.id,
          no: index + 1,
          batch_year: item.batch_year,
          project_name: item.project_name,
          assessment_order: item.assessment_order || '1',
          status: item.status,
          date: dayjs(item.created_at).format('DD MMMM YYYY HH:mm'),
          total_questions: item.total_questions,
          answered_questions: item.answered_questions,
          // Tambahan field untuk tracking member assessment
          total_members: item.total_members || 0,
          answered_members: item.answered_members || 0,
          completion_percentage: item.completion_percentage || 0,
        }));
      })
      .catch(error => {
        console.error('There was an error fetching data:', error);
        if (error.response) {
          console.error('Error response:', error.response.data);
          console.error('Error status:', error.response.status);
        }
      });
  }
}
</script>

<template>
  <div class="flex min-h-screen">
    <SidebarMahasiswa role="mahasiswa" />
    <div class="flex-1">
      <Navbar userName="Mahasiswa" />
      <main class="p-6">
        <div class="mb-4">
          <Breadcrumb :items="breadcrumbs" />
        </div>

        <Card title="DAFTAR HASIL PENGISIAN PEER ASSESSMENT" description="" class="w-full">
          <DataTable :headers="headers" :items="items" class="mt-10">
            <template #column-actions="{ item }">
              <div class="flex justify-center space-x-2">
                <!-- Tombol Answer: muncul jika status Active dan belum semua assessment lengkap -->
                <button v-if="item.status === 'Active' && !isAllAssessmentComplete(item)"
                  @click="handleAnswer(item)"
                  class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                  <font-awesome-icon icon="fa-solid fa-pen" class="mr-2" />
                  Answer
                </button>

                <!-- Tombol Complete: muncul jika semua assessment sudah lengkap -->
                <button v-if="item.status === 'Active' && isAllAssessmentComplete(item)"
                  disabled
                  class="px-3 py-1 bg-green-500 text-white rounded-md opacity-75 cursor-not-allowed">
                  <font-awesome-icon icon="fa-solid fa-check" class="mr-2" />
                  Complete
                </button>

                <button @click="handleDetail(item)"
                  class="px-3 py-1 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                  <font-awesome-icon icon="fa-solid fa-eye" class="mr-2" />
                  Detail
                </button>
              </div>
            </template>

            <template #column-status="{ item }">
              <div class="flex items-center space-x-2">
                <span :class="[
                  'px-2 py-1 rounded-full text-xs font-medium',
                  item.status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                ]">
                  {{ item.status }}
                </span>
                
                <!-- Progress indicator untuk peer assessment -->
                <span v-if="item.status === 'Active'" class="text-xs text-gray-500">
                  <template v-if="item.total_members > 0">
                    ({{ item.answered_members }}/{{ item.total_members }} members)
                  </template>
                  <template v-else>
                    <!-- ({{ item.answered_questions }}/{{ item.total_questions }} questions) -->
                  </template>
                </span>
              </div>
            </template>
          </DataTable>
        </Card>
      </main>
    </div>
  </div>
</template>