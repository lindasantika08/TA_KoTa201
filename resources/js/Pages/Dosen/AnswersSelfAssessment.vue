<script>
import axios from 'axios';
import DataTable from "@/Components/DataTable.vue";
import Navbar from "@/Components/Navbar.vue";
import Sidebar from "@/Components/Sidebar.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";

export default {
  components: {
    DataTable,
    Navbar,
    Sidebar,
    Breadcrumb,
  },
  data() {
    return {
      batch_year: '',
      project_id: '',
      project_name: '', // Add project_name to data
      answers: [],
      totalKeseluruhan: 0,
      totalSudahMengisi: 0,
      breadcrumbs: [
        { text: "Self Assessment", href: "/dosen/assessment/projectsSelf" }
      ],
      headers: [
        { key: 'no', label: 'No' },
        { key: 'nama_mahasiswa', label: 'Nama Mahasiswa' },
        { key: 'status', label: 'Status' },
        { key: 'detail', label: 'Actions' }
      ],
    };
  },
  mounted() {
    const query = new URLSearchParams(window.location.search);
    this.batch_year = query.get('batch_year');
    this.project_id = query.get('project_id');

    if (this.batch_year && this.project_id) {
      this.fetchProjectDetails(); // New method to fetch project details
      this.fetchAnswers();
      this.fetchStatistics();
    } else {
      console.error('Batch Year atau Project ID tidak ditemukan!');
    }
  },
  methods: {
    fetchProjectDetails() {
      axios.get(`/api/projects/${this.project_id}`)
        .then(response => {
          this.project_name = response.data.name; // Adjust based on your API response structure
        })
        .catch(error => {
          console.error('Error fetching project details:', error);
        });
    },
    fetchAnswers() {
      axios.get('/api/answers/list', {
        params: {
          batch_year: this.batch_year,
          project_id: this.project_id
        }
      })
        .then(response => {
          if (Array.isArray(response.data)) {
            this.answers = response.data;
          } else {
            console.error("Data yang diterima tidak sesuai format yang diharapkan.");
          }
        })
        .catch(error => {
          console.error('Error fetching answers:', error);
        });
    },
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
        })
        .catch(error => {
          console.error('Error fetching statistics:', error);
        });
    },

    showDetails(mahasiswaName, batchYear, projectId) {
      console.log(`Menampilkan detail untuk mahasiswa: ${mahasiswaName}`);
      console.log(`Batch Year: ${batchYear}, Project ID: ${projectId}`);

      router.visit(`/dosen/answers/details?mahasiswaName=${mahasiswaName}&batch_year=${batchYear}&project_id=${projectId}`);

      console.log("Data yang dikirim:", {
        mahasiswaName,
        batchYear,
        projectId
      });
    },
  },
  computed: {
    groupedAnswers() {
      const userGroups = {};
      let globalIndex = 1;

      this.answers.forEach(answer => {
        const mahasiswaName = answer.mahasiswa.name;
        if (!userGroups[mahasiswaName]) {
          userGroups[mahasiswaName] = {
            index: globalIndex++,
            mahasiswaName,
            status: answer.status || 'unsubmitted'
          };
        }
      });

      const sortedAnswers = Object.values(userGroups).sort((a, b) => {
        const statusOrder = ['submitted', 'on progress', 'unsubmitted'];
        return statusOrder.indexOf(a.status) - statusOrder.indexOf(b.status);
      });

      return sortedAnswers;
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

        <div class="mb-6 text-sm font-semibold">
          <h1 class="text-xl font-semibold mb-6">Answers Self Assessment</h1>

          <p><strong>Batch Year : </strong> {{ batch_year }}</p>
          <p><strong>Project Name : </strong> {{ project_name }}</p>

          <p class="mt-4 text-2xl font-semibold">
            <font-awesome-icon icon="fa-solid fa-user-check" class="mr-4 text-3xl" />
            Total Mengisi: {{ totalSudahMengisi }} / {{ totalKeseluruhan }}
          </p>
        </div>

        <div v-if="groupedAnswers.length === 0" class="text-center text-gray-500 py-6">
          Belum ada jawaban
        </div>

        <div v-else>
          <DataTable :headers="headers" :items="groupedAnswers" class="mt-10">
            <template #column-no="{ item }">
              {{ item.index }}
            </template>

            <template #column-nama_mahasiswa="{ item }">
              {{ item.mahasiswaName }}
            </template>

            <template #column-status="{ item }">
              <div class="flex justify-center">
                <span :class="[
                  'px-2 py-1 rounded-full text-xs font-medium',
                  item.status === 'unsubmitted'
                    ? 'bg-red-100 text-red-800'
                    : item.status === 'on progress'
                      ? 'bg-yellow-100 text-yellow-800'
                      : item.status === 'submitted'
                        ? 'bg-green-100 text-green-800'
                        : 'bg-green-100 text-green-800'
                ]">
                  {{ item.status }}
                </span>
              </div>
            </template>

            <template #column-detail="{ item }">
              <div class="flex justify-center">
                <button v-if="item.status === 'on progress' || item.status === 'submitted'"
                  @click="showDetails(item.mahasiswaName, batch_year, project_id)"
                  class="px-4 py-2 text-white bg-blue-500 rounded-lg hover:bg-blue-700">
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