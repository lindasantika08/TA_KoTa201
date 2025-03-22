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
      user_ids: "",
      answers: [],
      breadcrumbs: [
        { text: "Peer Assessment", href: "/sispa/dosen/assessment/projects-peer" },
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

    console.log("tahun_ajaran:", this.tahun_ajaran);
    console.log("nama_proyek:", this.nama_proyek);

    if (this.tahun_ajaran && this.nama_proyek) {
      this.fetchAnswers();
    } else {
      console.error("Tahun Ajaran atau Nama Proyek tidak ditemukan!");
    }
  },
  methods: {
    fetchAnswers() {
    axios
      .get("/sispa/api/answersKelompokPeer/list", {
        params: {
          tahun_ajaran: this.tahun_ajaran,
          nama_proyek: this.nama_proyek,
        },
      })
      .then((response) => {
        if (response.data.success && Array.isArray(response.data.data)) {
          this.answers = response.data.data.map((item) => ({
            kelompok: item.nama_kelompok,
            jumlah_user: `${item.total_filled}/${item.total_mahasiswa}`,
            user_ids: item.user_ids,
            id: item.user_id,
          }));
        } else {
          // More detailed error handling
          console.error(
            "Data yang diterima tidak sesuai format yang diharapkan.",
            response.data
          );
          // Optional: show user-friendly notification
          alert(response.data.message || "Gagal memuat data");
        }
      })
      .catch((error) => {
        // Improved error logging
        console.error("Error fetching answers:", error.response?.data || error);
        // Optional: show user-friendly error message
        alert(error.response?.data?.message || "Terjadi kesalahan");
      });
  },

    handleDetail(item) {
      router.get("/dosen/answers-peer-assessment", {
        tahun_ajaran: this.tahun_ajaran,
        nama_proyek: this.nama_proyek,
        kelompok: item.kelompok,
        user_ids: item.user_ids,
        id: item.id,
      });
    },
  },
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

        <div class="mb-6">
          <h1 class="text-xl font-semibold">Answers Peer Assessment</h1>
        </div>

        <div class="mb-6 text-sm">
          <p><strong>Tahun Ajaran </strong> : {{ tahun_ajaran }}</p>
          <p><strong>Nama Proyek </strong> : {{ nama_proyek }}</p>
        </div>

        <div v-if="answers.length === 0" class="text-center text-gray-500 py-6">
          Belum ada jawaban
        </div>

        <div v-else>
          <DataTable :headers="headers" :items="answers" class="mt-10">
            <template #column-actions="{ item }">
              <button @click="handleDetail(item)"
                class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Detail
              </button>
            </template>
          </DataTable>
        </div>
      </main>
    </div>
  </div>
</template>