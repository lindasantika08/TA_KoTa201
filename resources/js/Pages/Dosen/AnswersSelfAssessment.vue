<script>
import axios from 'axios';
import DataTable from "@/Components/DataTable.vue";  // Pastikan DataTable sudah terimport
import Navbar from "@/Components/Navbar.vue";  // Pastikan Navbar sudah terimport
import Sidebar from "@/Components/Sidebar.vue";  // Pastikan Sidebar sudah terimport
import Breadcrumb from "@/Components/Breadcrumb.vue";  // Pastikan Breadcrumb sudah terimport

export default {
  components: {
    DataTable,  // Komponen DataTable digunakan
    Navbar,     // Komponen Navbar digunakan
    Sidebar,    // Komponen Sidebar digunakan
    Breadcrumb  // Komponen Breadcrumb digunakan
  },
  data() {
    return {
      tahun_ajaran: '',   // Menyimpan tahun_ajaran
      nama_proyek: '',    // Menyimpan nama_proyek
      answers: [],        // Menyimpan data jawaban
      breadcrumbs: [
        { text: "Self Assessment", href: "/dosen/assessment/projectsSelf" }
      ],  // Breadcrumbs untuk navigasi
      headers: [
        { key: 'no', label: 'No' },
        { key: 'nama_pengguna', label: 'Nama Pengguna' },
        { key: 'pertanyaan', label: 'Pertanyaan' },
        { key: 'skor', label: 'Skor' },
        { key: 'jawaban', label: 'Jawaban' },

        { key: 'status', label: 'Status' }
      ],  // Header untuk DataTable
    };
  },
  mounted() {
    // Mengambil tahun_ajaran dan nama_proyek dari query params
    const query = new URLSearchParams(window.location.search);
    this.tahun_ajaran = query.get('tahun_ajaran');
    this.nama_proyek = query.get('nama_proyek');
    
    // Debugging: Menampilkan tahun_ajaran dan nama_proyek
    console.log('Tahun Ajaran:', this.tahun_ajaran);
    console.log('Nama Proyek:', this.nama_proyek);

    if (this.tahun_ajaran && this.nama_proyek) {
      this.fetchAnswers();  // Ambil data jawaban berdasarkan tahun ajaran dan nama proyek
    } else {
      console.error('Tahun Ajaran atau Nama Proyek tidak ditemukan!');
    }
  },
  methods: {
    fetchAnswers() {
      // Debugging: Menampilkan query params sebelum membuat request
      console.log('Tahun Ajaran:', this.tahun_ajaran);
      console.log('Nama Proyek:', this.nama_proyek);

      // Mengambil data jawaban dari API berdasarkan tahun_ajaran dan nama_proyek
      axios.get('/api/answers/list', {
        params: {
          tahun_ajaran: this.tahun_ajaran,
          nama_proyek: this.nama_proyek
        }
      })
      .then(response => {
        console.log('Data jawaban diterima:', response.data);
        // Periksa apakah data sudah sesuai struktur yang diharapkan
        if (Array.isArray(response.data)) {
          this.answers = response.data;
        } else {
          console.error("Data yang diterima tidak sesuai format yang diharapkan.");
        }
      })
      .catch(error => {
        console.error('Error fetching answers:', error);
      });
    }
  }
};
</script>


<template>
    <div class="flex min-h-screen">
      <!-- Sidebar -->
      <Sidebar role="dosen" />
      
      <div class="flex-1">
        <!-- Navbar -->
        <Navbar Username="dosen" />
        
        <main class="p-6">
          <div class="mb-4">
            <!-- Breadcrumb -->
            <Breadcrumb :items="breadcrumbs" />
          </div>
  
          <div class="mb-6">
            <h1 class="text-xl font-semibold">Answers Self Assessment</h1>
          </div>
  
          <!-- Tabel untuk menampilkan data jawaban -->
          <div v-if="answers.length === 0" class="text-center text-gray-500 py-6">
            Belum ada jawaban
          </div>
          
          <div v-else>
            <DataTable :headers="headers" :items="answers" class="mt-10">
              <template #column-status="{ item }">
                <span :class="[ 
                  'px-2 py-1 rounded-full text-xs font-medium', 
                  item.status === 'aktif' 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-red-100 text-red-800'
                ]">
                  {{ item.status }}
                </span>
              </template>
              <template #column-no="{index }">
                {{ index + 1 }} <!-- Menampilkan nomor urut -->
              </template>
              <template #column-nama_pengguna="{ item }">
                {{ item.user.name }} <!-- Menampilkan nama pengguna, jika ada key 'user.name' -->
              </template>
              <template #column-pertanyaan="{ item }">
                {{ item.pertanyaan }} <!-- Menampilkan pertanyaan -->
              </template>
              <template #column-skor="{ item }">
                {{ item.score }} <!-- Menampilkan skor -->
              </template>
              <template #column-jawaban="{ item }">
                {{ item.answer }} <!-- Menampilkan jawaban -->
              </template>

            </DataTable>
          </div>
        </main>
      </div>
    </div>
  </template>
  