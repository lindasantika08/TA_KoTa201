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
        { text: "Assessment", href: "/sispa/mahasiswa/assessment/peer" },
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
    handleAnswer(item) {
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

          router.visit(`/sispa/mahasiswa/assessment/peer-assessment`, {
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
      router.visit(`/sispa/mahasiswa/peer-assessment/peer-detail`, {
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
    axios.get('/sispa/api/peer-assessment')
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
                <button v-if="item.status === 'Active' && item.answered_questions < item.total_questions"
                  @click="handleAnswer(item)"
                  class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                  <font-awesome-icon icon="fa-solid fa-pen" class="mr-2" />
                  Answer
                </button>

                <button @click="handleDetail(item)"
                  class="px-3 py-1 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                  <font-awesome-icon icon="fa-solid fa-eye" class="mr-2" />
                  Detail
                </button>
              </div>
            </template>

            <template #column-status="{ item }">
              <span :class="[
                'px-2 py-1 rounded-full text-xs font-medium',
                item.status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
              ]">
                {{ item.status }}
              </span>
            </template>
          </DataTable>
        </Card>
      </main>
    </div>
  </div>
</template>