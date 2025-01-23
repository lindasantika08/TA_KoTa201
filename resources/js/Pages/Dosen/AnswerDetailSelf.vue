<template>
  <div class="flex min-h-screen">
    <Sidebar role="dosen" />
    <div class="flex-1">
      <Navbar userName="dosen" />
      <main class="p-6">
        <div class="mb-4">
          <Breadcrumb :items="[{ text: 'Self Assessment', href: '' },
        { text: 'Detail', href: '' }]" />
        </div>

        <div class="mb-6">
          <h1 class="text-xl font-semibold mb-6">Detail Jawaban: {{ userName }}</h1>
          <!-- Menampilkan Tahun Ajaran dan Nama Proyek -->
          <p ><strong>Tahun Ajaran : </strong> {{ tahunAjaran }}</p>
          <p ><strong>Nama Proyek : </strong> {{ namaProyek }}</p>
        </div>

        <!-- Jika tidak ada jawaban, tampilkan pesan -->
        <div v-if="answers.length === 0" class="text-center text-gray-500 py-6">
          Belum ada jawaban untuk pengguna ini.
        </div>

        <!-- Tampilkan jawaban jika ada -->
        <div v-else>
          <DataTable :headers="tableHeaders" :items="answers" class="mt-4">
            <template #column-index="{ index }">
              {{ index + 1 }}
            </template>

            <template #column-pertanyaan="{ item }">
              {{ item.pertanyaan }}
            </template>

            <template #column-jawaban="{ item }">
              {{ item.jawaban }}
            </template>

            <template #column-skor="{ item }">
              {{ item.skor }}
            </template>
          </DataTable>
        </div>
      </main>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import DataTable from "@/Components/DataTable.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";

export default {
  components: {
    DataTable,
    Breadcrumb,
    Sidebar,
    Navbar
  },
  props: {
    userName: String,
    tahunAjaran: String,
    namaProyek: String,
  },
  data() {
    return {
      answers: [],
      tableHeaders: [
        { key: 'index', label: 'No' },          // Kolom nomor
        { key: 'pertanyaan', label: 'Pertanyaan' },
        { key: 'jawaban', label: 'Jawaban' },
        { key: 'skor', label: 'Skor' },
      ]
    };
  },
  mounted() {
    // Ambil jawaban berdasarkan parameter yang diterima
    this.fetchAnswerDetails();
  },
  methods: {
    fetchAnswerDetails() {
      axios.get('/api/answers/get-details', {
        params: {
          userName: this.userName,
          tahun_ajaran: this.tahunAjaran,
          nama_proyek: this.namaProyek,
        }
      })
      .then(response => {
        this.answers = response.data.answers;
      })
      .catch(error => {
        console.error('Error fetching answer details:', error);
      });
    }
  }
};
</script>
