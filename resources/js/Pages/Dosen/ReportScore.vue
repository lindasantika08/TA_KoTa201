<script>
import axios from 'axios';
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

export default {
  props: {
    tahunAjaran: {
      type: String,
      required: true,
    },
    namaProyek: {
      type: String,
      required: true,
    },
    kelompok: {
      type: String,
      required: true,
    },
  },
  components: {
    Sidebar,
    Navbar,
    Card,
    Breadcrumb,
  },
  data() {
    return {
      breadcrumbs: [{ text: "Laporan", href: "/dosen/laporan" }],
      aspekAnalysis: [], // Untuk menyimpan analisis aspek dari backend
      loading: false,
      error: null
    };
  },
  methods: {
    fetchKelompokAnalysis() {
      this.loading = true;
      this.error = null;
      
      axios
        .get('/api/report/kelompok/answers', {
          params: {
            tahun_ajaran: this.tahunAjaran,
            nama_proyek: this.namaProyek,
            kelompok: this.kelompok,
          },
        })
        .then((response) => {
          this.aspekAnalysis = response.data;
          this.loading = false;
        })
        .catch((error) => {
          console.error('Gagal mengambil analisis:', error);
          this.error = 'Gagal memuat data';
          this.loading = false;
        });
    },
  },
  mounted() {
    this.fetchKelompokAnalysis();
  },
};
</script>

<template>
  <div class="flex min-h-screen">
    <Sidebar role="dosen" />
    <div class="flex-1">
      <Navbar userName="Dosen" />
      <main class="p-6">
        <div class="mb-4">
          <Breadcrumb :items="breadcrumbs" />
        </div>
        <Card title="Detail Kelompok">
          <div v-if="tahunAjaran && namaProyek && kelompok">
            <p><strong>Tahun Ajaran:</strong> {{ tahunAjaran }}</p>
            <p><strong>Nama Proyek:</strong> {{ namaProyek }}</p>
            <p><strong>Kelompok:</strong> {{ kelompok }}</p>
          </div>
          <div v-else>
            <p>Tidak ada data yang tersedia</p>
          </div>
        </Card>

        <Card title="Analisis Jawaban Kelompok" class="mt-4">
    <div v-if="loading" class="text-center">Memuat...</div>
    <div v-else-if="error" class="text-red-500">{{ error }}</div>
    <div v-else>
      <table v-if="aspekAnalysis.length" class="w-full border-collapse">
        <thead>
          <tr class="bg-gray-200">
            <th class="border p-2">Aspek</th>
            <th class="border p-2">Kriteria</th>
            <th class="border p-2">Skor Total</th>
            <th class="border p-2">Total Jawaban</th>
            <th class="border p-2">Anggota Kelompok</th>
            <th class="border p-2">Detail Pertanyaan</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(aspek, index) in aspekAnalysis" :key="index" class="border">
            <td class="border p-2">{{ aspek.aspek }}</td>
            <td class="border p-2">{{ aspek.kriteria }}</td>
            <td class="border p-2">{{ aspek.total_score ? aspek.total_score.toFixed(2) : 'N/A' }}</td>
            <td class="border p-2">{{ aspek.total_answers }}</td>
            <td class="border p-2">
              <ul>
                <li v-for="user in aspek.users" :key="user.user_id">
                  {{ user.name }}
                </li>
              </ul>
            </td>
            <td class="border p-2">
              <table class="w-full">
                <thead>
                  <tr>
                    <th>Pertanyaan</th>
                    <th>Jumlah Jawaban</th>
                    <th>Skor Rata-rata</th>
                    <th>Detail Jawaban</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="pertanyaan in aspek.questions" :key="pertanyaan.question_id">
                    <td>{{ pertanyaan.pertanyaan }}</td>
                    <td>{{ pertanyaan.answers_count }}</td>
                    <td>{{ pertanyaan.average_score ? pertanyaan.average_score.toFixed(2) : 'N/A' }}</td>
                    <td>
                      <ul>
                        <li v-for="jawaban in pertanyaan.user_answers" :key="jawaban.user_id">
                          {{ jawaban.user_name }}: {{ jawaban.score }}
                        </li>
                      </ul>
                    </td>
                  </tr>
                </tbody>
              </table>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else class="text-center">Tidak ada data analisis</p>
    </div>
  </Card>
      </main>
    </div>
  </div>
</template>