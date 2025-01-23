<script>
import axios from "axios";
import DataTable from "@/Components/DataTable.vue";
import Navbar from "@/Components/Navbar.vue";
import Sidebar from "@/Components/Sidebar.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import { router } from "@inertiajs/vue3";

export default {
  components: {
    DataTable,
    Navbar,
    Sidebar,
    Breadcrumb,
  },
  data() {
    return {
      tahun_ajaran: "",
      nama_proyek: "",
      answers: [],
      breadcrumbs: [
        { text: "Peer Assessment", href: "/dosen/assessment/projectsPeer" },
        { text: "List Answer", href: null },
      ],
      headers: [
        { key: "kelompok", label: "Kelompok" },
        { key: "jumlah_user", label: "Jumlah User" },
        { key: "actions", label: "Actions" },
      ],
    };
  },
  mounted() {
    const query = new URLSearchParams(window.location.search);
    this.tahun_ajaran = query.get("tahun_ajaran");
    this.nama_proyek = query.get("nama_proyek");

    console.log("tahun_ajaran:", this.tahun_ajaran); // Debug
    console.log("nama_proyek:", this.nama_proyek); // Debug

    if (this.tahun_ajaran && this.nama_proyek) {
      this.fetchAnswers();
    } else {
      console.error("Tahun Ajaran atau Nama Proyek tidak ditemukan!");
    }
  },
  methods: {
    fetchAnswers() {
      axios
        .get("/api/answersKelompokPeer/list", {
          params: {
            tahun_ajaran: this.tahun_ajaran,
            nama_proyek: this.nama_proyek,
          },
        })
        .then((response) => {
          if (response.data.success && Array.isArray(response.data.data)) {
            this.answers = response.data.data.map((item) => ({
              kelompok: item.nama_kelompok, // Nama kelompok
              jumlah_user: `${item.total_filled}/${item.total_mahasiswa}`, // Format jumlah user
              user_ids: item.user_ids, // Menyimpan daftar user_id
              id: item.user_id, // User ID untuk navigasi
            }));
          } else {
            console.error(
              "Data yang diterima tidak sesuai format yang diharapkan."
            );
          }
        })
        .catch((error) => {
          console.error("Error fetching answers:", error);
        });
    },

    handleDetail(item) {
      router.get("/dosen/answers-peer-assessment", {
        tahun_ajaran: this.tahun_ajaran, // Sertakan tahun_ajaran
        nama_proyek: this.nama_proyek, // Sertakan nama_proyek
        kelompok: item.kelompok,
        id: item.id, // Jika ID juga diperlukan
      });
    },
  },
};
</script>

<template>
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <Sidebar role="dosen" />

    <div class="flex-1">
      <!-- Navbar -->
      <Navbar userName="dosen" />

      <main class="p-6">
        <!-- Breadcrumb -->
        <div class="mb-4">
          <Breadcrumb :items="breadcrumbs" />
        </div>

        <!-- Title -->
        <div class="mb-6">
          <h1 class="text-xl font-semibold">Answers Peer Assessment</h1>
        </div>

        <!-- Tampilkan Pesan Jika Kosong -->
        <div v-if="answers.length === 0" class="text-center text-gray-500 py-6">
          Belum ada jawaban
        </div>

        <!-- DataTable -->
        <div v-else>
          <DataTable :headers="headers" :items="answers" class="mt-10">
            <!-- Kolom Actions -->
            <template #column-actions="{ item }">
              <button
                @click="handleDetail(item)"
                class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
              >
                Detail
              </button>
            </template>
          </DataTable>
        </div>
      </main>
    </div>
  </div>
</template>
  