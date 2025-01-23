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
      tahun_ajaran: '',
      nama_proyek: '',
      answers: [],
      totalKeseluruhan: 0,
      totalSudahMengisi: 0,
      breadcrumbs: [
        { text: "Self Assessment", href: "/dosen/assessment/projectsSelf" }
      ],
      headers: [
        { key: 'no', label: 'No' },
        { key: 'nama_pengguna', label: 'Nama Pengguna' },
        { key: 'status', label: 'Status' },
        { key: 'detail', label: 'Actions' }
      ],
    };
  },
  mounted() {
    const query = new URLSearchParams(window.location.search);
    this.tahun_ajaran = query.get('tahun_ajaran');
    this.nama_proyek = query.get('nama_proyek');

    if (this.tahun_ajaran && this.nama_proyek) {
      this.fetchAnswers();
      this.fetchStatistics();
    } else {
      console.error('Tahun Ajaran atau Nama Proyek tidak ditemukan!');
    }
  },
  methods: {
    fetchAnswers() {
      axios.get('/api/answers/list', {
        params: {
          tahun_ajaran: this.tahun_ajaran,
          nama_proyek: this.nama_proyek
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
          tahun_ajaran: this.tahun_ajaran,
          nama_proyek: this.nama_proyek
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

  showDetails(userName, tahunAjaran, namaProyek) {
  console.log(`Menampilkan detail untuk pengguna: ${userName}`);
  console.log(`Tahun Ajaran: ${tahunAjaran}, Nama Proyek: ${namaProyek}`);

  router.visit(`/dosen/answers/details?userName=${userName}&tahun_ajaran=${tahunAjaran}&nama_proyek=${namaProyek}`);

  // Log the data being sent to Inertia
  console.log("Data yang dikirim:", {
    userName,
    tahunAjaran,
    namaProyek
  });
},
  },
  computed: {
    groupedAnswers() {
    const userGroups = {};
    let globalIndex = 1;

    // Group answers by user name and keep only unique entries
    this.answers.forEach(answer => {
      const userName = answer.user.name;
      if (!userGroups[userName]) {
        userGroups[userName] = {
          index: globalIndex++,
          userName,
          status: answer.status || 'unsubmitted'  // Default to 'unsubmitted' if no status
        };
      }
    });

    return Object.values(userGroups);
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
          <h1 class="text-xl font-semibold mb-6 ">Answers Self Assessment</h1>

          <!-- Menampilkan Tahun Ajaran dan Nama Proyek dengan label bold -->
          <p><strong>Tahun Ajaran : </strong> {{ tahun_ajaran }}</p>
          <p><strong>Nama Proyek : </strong> {{ nama_proyek }}</p>

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

            <template #column-nama_pengguna="{ item }">
              {{ item.userName }}
            </template>

            <!-- Kolom Status dengan class 'text-center' untuk menempatkan di tengah -->
            <template #column-status="{ item }">
              <div class="flex justify-center">
                <span :class="[ 
                  'px-2 py-1 rounded-full text-xs font-medium', 
                  item.status === 'unsubmitted' 
                    ? 'bg-red-100 text-red-800' 
                    : item.status === 'submitted' 
                    ? 'bg-green-100 text-green-800'
                    : 'bg-green-100 text-green-800'
                ]">
                  {{ item.status }}
                </span>
              </div>
            </template>

            <!-- Kolom Detail dengan class 'text-center' untuk menempatkan tombol di tengah -->
            <template #column-detail="{ item }">
              <div class="flex justify-center">
                <button 
                  v-if="item.status === 'submitted'"  
                  @click="showDetails(item.userName, tahun_ajaran, nama_proyek)" 
                  class="px-4 py-2 text-white bg-blue-500 rounded-lg hover:bg-blue-700"
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


