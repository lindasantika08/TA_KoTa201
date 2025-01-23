<script>
import axios from "axios";
import { toRaw } from "vue";
import DataTable from "@/Components/DataTable.vue";
import Navbar from "@/Components/Navbar.vue";
import Sidebar from "@/Components/Sidebar.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

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
      kelompok: "",
      user_ids: "",
      answers: [],
      breadcrumbs: [
        { text: "Peer Assessment", href: "/dosen/assessment/projectsPeer" },
        { text: "List Answer", href: null },
      ],
      headers: [
        { key: "no", label: "No" },
        { key: "nama_pengguna", label: "Nama Pengguna" },
        { key: "nama_rekan", label: "Nama Rekan" },
        { key: "pertanyaan", label: "Pertanyaan" },
        { key: "skor", label: "Skor" },
        { key: "jawaban", label: "Jawaban" },
        { key: "status", label: "Status" },
      ],
    };
  },
  mounted() {
    const query = new URLSearchParams(window.location.search);
    this.tahun_ajaran = query.get("tahun_ajaran");
    this.nama_proyek = query.get("nama_proyek");
    this.kelompok = query.get("kelompok");

    console.log("Tahun Ajaran:", this.tahun_ajaran);
    console.log("Nama Proyek:", this.nama_proyek);
    console.log("Kelompok:", this.kelompok);

    if (this.tahun_ajaran && this.nama_proyek && this.kelompok) {
      this.fetchAnswers();
    } else {
      console.error("Parameter tidak lengkap!");
    }
  },
  methods: {
    fetchAnswers() {
      axios
        .get("/api/answersPeer/list", {
          params: {
            tahun_ajaran: this.tahun_ajaran,
            nama_proyek: this.nama_proyek,
            kelompok: this.kelompok,
          },
        })
        .then((response) => {
          console.log("Data jawaban diterima:", response.data);

          this.answers = response.data.data;
        })
        .catch((error) => {
          console.error("Error fetching answers:", error);
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
          <p><strong>Kelompok </strong> : {{ kelompok }}</p>
        </div>

        <div v-if="answers.length === 0" class="text-center text-gray-500 py-6">
          Belum ada jawaban
        </div>

        <div v-else>
          <DataTable :headers="headers" :items="answers" class="mt-10">
            <template #column-status="{ item }">
              <span
                :class="['px-2 py-1 rounded-full text-xs font-medium', item.status === 'aktif' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800']">
                {{ item.status }}
              </span>
            </template>
            <template #column-no="{ index }">
              {{ index + 1 }}
            </template>
            <template #column-nama_pengguna="{ item }">
              {{ item.user.name }}
            </template>
            <template #column-nama_rekan="{ item }">
              {{ item.peer ? item.peer.name : "-" }}
            </template>
            <template #column-pertanyaan="{ item }">
              {{ item.pertanyaan }}
            </template>
            <template #column-skor="{ item }">
              {{ item.score }}
            </template>
            <template #column-jawaban="{ item }">
              {{ item.answer }}
            </template>
          </DataTable>
        </div>
      </main>
    </div>
  </div>
</template>
