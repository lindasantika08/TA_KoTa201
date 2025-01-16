<template>
  <div class="flex min-h-screen">
    <Sidebar role="dosen" />
    <div class="flex-1">
      <Navbar Username="dosen" />
      <main class="p-6">
        <div class="mb-4">
          <Breadcrumb :items="breadcrumbs" />
        </div>

        <Card
          title="DAFTAR PROYEK PEER ASSESSMENT"
          description=""
          class="w-full"
        >
          <!-- Cek apakah items kosong -->
          <div v-if="items.length === 0" class="text-center text-gray-500 py-6">
            Belum ada assessment
          </div>
          <div v-else>
            <DataTable :headers="headers" :items="items" class="mt-10">
              <template #column-actions="{ item }">
                <button
                  @click="handleDetail(item)"
                  class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                  <font-awesome-icon icon="fa-solid fa-eye" class="mr-2" />
                  Detail
                </button>
              </template>

              <template #column-status="{ item }">
                <span
                  :class="[ 
                    'px-2 py-1 rounded-full text-xs font-medium', 
                    item.status === 'aktif' 
                      ? 'bg-green-100 text-green-800' 
                      : 'bg-red-100 text-red-800'
                  ]"
                >
                  {{ item.status }}
                </span>
              </template>
            </DataTable>
          </div>
        </Card>
      </main>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import DataTable from "@/Components/DataTable.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Sidebar from "@/Components/Sidebar.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import dayjs from "dayjs";
import { router } from "@inertiajs/vue3";

export default {
  components: {
    DataTable,
    Navbar,
    Card,
    Sidebar,
    Breadcrumb,
  },
  data() {
    return {
      breadcrumbs: [
        { text: "Peer Assessment", href: null },
      ],
      headers: [
        { key: "no", label: "No" },
        { key: "tahun_ajaran", label: "Tahun Ajaran" },
        { key: "nama_proyek", label: "Proyek" },
        { key: "status", label: "Status" },
        { key: "tanggal", label: "Tanggal Pembuatan" },
        { key: "actions", label: "Actions" },
      ],
      items: [],
    };
  },
  methods: {
    handleDetail(item) {
      router.get(
        "/dosen/assessment/data-with-bobot",
        {
          tahun_ajaran: item.tahun_ajaran,
          nama_proyek: item.nama_proyek,
        },
        {
          preserveState: true,
        }
      );
    },
  },
  mounted() {
    axios
      .get("/api/proyek-Peer-assessment")
      .then((response) => {
        this.items = response.data.map((item, index) => ({
          no: index + 1,
          tahun_ajaran: item.tahun_ajaran,
          nama_proyek: item.nama_proyek,
          status: item.status,
          tanggal: dayjs(item.created_at).format("DD MMMM YYYY HH:mm"),
        }));
      })
      .catch((error) => {
        console.error("There something when reach data:", error);
      });
  },
};
</script>
