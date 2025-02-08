<script>
import Navbar from "@/Components/Navbar.vue";
import Sidebar from "@/Components/Sidebar.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import DataTable from "@/Components/DataTable.vue";
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import axios from 'axios';

export default {
  components: {
    DataTable,
    Navbar,
    Sidebar,
    Breadcrumb,
  },
  data() {
    return {
      totalKeseluruhan: 0,
      totalSudahMengisi: 0,
      submissionStatus: [],
      breadcrumbs: [
        { text: "Self Assessment", href: "/dosen/assessment/projectsSelf" }
      ],
      headers: [
        { key: 'no', label: 'No', class: 'text-center' },
        { key: 'nama_mahasiswa', label: 'Nama Mahasiswa', class: 'w-1/3' },
        { key: 'status', label: 'Status', class: 'text-center' },
        { key: 'detail', label: 'Actions', class: 'text-center' }
      ],
      isLoading: true,
      error: null
    };
  },
  setup() {
    const page = usePage();
    return { 
      batch_year: computed(() => page.props.batch_year),
      project_id: computed(() => page.props.projectId),
      project_name: computed(() => page.props.project_name)
    };
  },
  mounted() {
    if (this.batch_year && this.project_id) {
      this.fetchStatistics();
    } else {
      this.error = 'Batch Year atau Project ID tidak ditemukan!';
      this.isLoading = false;
    }
  },
  methods: {
    fetchStatistics() {
      axios.get('/api/answers/statistics', {
        params: {
          batch_year: this.batch_year,
          project_id: this.project_id
        }
      })
      .then(response => {
        this.totalKeseluruhan = response.data.totalKeseluruhan;
        this.totalSudahMengisi = response.data.totalSudahMengisi;
        this.submissionStatus = response.data.submissionStatus;
        this.isLoading = false;
      })
      .catch(error => {
        console.error('Error fetching statistics:', error);
        this.error = 'Gagal memuat statistik';
        this.isLoading = false;
      });
    },
    showDetails(mahasiswaName) {
      router.visit(`/dosen/answers/details?mahasiswaName=${mahasiswaName}&batch_year=${this.batch_year}&project_name=${this.project_name}&project_id=${this.project_id}`);
    },
    getStatusClass(status) {
      const statusClasses = {
        'submitted': 'bg-green-100 text-green-800',
        'unsubmitted': 'bg-red-100 text-red-800',
        'on progress': 'bg-yellow-100 text-yellow-800'
      };
      return statusClasses[status] || 'bg-gray-100 text-gray-800';
    }
  },
  computed: {
    groupedAnswers() {
      return this.submissionStatus.map((item, index) => ({
        index: index + 1,
        mahasiswaName: item.mahasiswaName,
        status: item.status
      })).sort((a, b) => {
        const statusOrder = ['submitted', 'on progress', 'unsubmitted'];
        return statusOrder.indexOf(a.status) - statusOrder.indexOf(b.status);
      });
    },
    submissionPercentage() {
      return this.totalKeseluruhan > 0 
        ? Math.round((this.totalSudahMengisi / this.totalKeseluruhan) * 100) 
        : 0;
    }
  }
};
</script>

<template>
  <div class="flex min-h-screen">
    <Sidebar role="dosen" />
    <div class="flex-1">
      <Navbar userName="dosen" />
      <main class="p-6">
        <div class="mb-4">
          <Breadcrumb :items="breadcrumbs" />
        </div>

        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
          <h1 class="text-2xl font-bold text-gray-800 mb-4">Answers Self Assessment</h1>

          <div class="grid md:grid-cols-3 gap-4">
            <div>
              <p class="text-sm text-gray-600">Batch Year</p>
              <p class="font-semibold text-lg">{{ batch_year }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-600">Project Name</p>
              <p class="font-semibold text-lg">{{ project_name }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-600">Submission Progress</p>
              <div class="flex items-center">
                <div class="w-full bg-gray-200 rounded-full h-2.5 mr-2">
                  <div 
                    class="bg-blue-600 h-2.5 rounded-full" 
                    :style="`width: ${submissionPercentage}%`"
                  ></div>
                </div>
                <span class="text-sm font-medium text-gray-500">
                  {{ submissionPercentage }}%
                </span>
              </div>
              <p class="text-lg font-semibold mt-2">
                Total Mengisi: {{ totalSudahMengisi }} / {{ totalKeseluruhan }}
              </p>
            </div>
          </div>
        </div>

        <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
          {{ error }}
        </div>

        <div v-else-if="groupedAnswers.length === 0" class="bg-gray-100 text-center text-gray-500 py-6 rounded-lg">
          Belum ada jawaban
        </div>

        <div v-else>
          <DataTable 
            :headers="headers" 
            :items="groupedAnswers" 
            class="mt-6 shadow-md rounded-lg overflow-hidden"
          >
            <template #column-no="{ item }">
              <div class="text-center">{{ item.index }}</div>
            </template>

            <template #column-nama_mahasiswa="{ item }">
              {{ item.mahasiswaName }}
            </template>

            <template #column-status="{ item }">
              <div class="flex justify-center">
                <span 
                  :class="[
                    'px-2 py-1 rounded-full text-xs font-medium',
                    getStatusClass(item.status)
                  ]"
                >
                  {{ item.status }}
                </span>
              </div>
            </template>

            <template #column-detail="{ item }">
              <div class="flex justify-center">
                <button 
                  v-if="item.status === 'submitted'"
                  @click="showDetails(item.mahasiswaName)"
                  class="px-4 py-2 text-white bg-blue-500 rounded-lg hover:bg-blue-700 transition-colors"
                >
                  Detail
                </button>
              </div>
            </template>
          </DataTable>
        </div>
      </main>
    </div>
  </div>
</template>