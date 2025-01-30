<script>
import axios from 'axios';
import DataTable from "@/Components/DataTable.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import SidebarMahasiswa from "@/Components/SidebarMahasiswa.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import dayjs from 'dayjs';
import { router } from '@inertiajs/vue3';

export default {
  components: {
    DataTable,
    Navbar,
    Card,
    SidebarMahasiswa,
    Breadcrumb
  },
  data() {
    return {
      breadcrumbs: [
        { text: "Assessment", href: "/mahasiswa/assessment/self" },
        { text: "Self Assessment", href: null }
      ],
      headers: [
        { key: 'no', label: 'No' },
        { key: 'tahunAjaran', label: 'Tahun Ajaran' },
        { key: 'proyek', label: 'Proyek' },
        { key: 'status', label: 'Status' },
        { key: 'tanggal', label: 'Tanggal Pengisian' },
        { key: 'actions', label: 'Actions' },
      ],
      items: [],
    }
  },
  methods: {
    handleAnswer(item) {
      const tahunAjaran = item.tahunAjaran;
      const namaProyek = item.proyek;

      console.log('Tahun Ajaran:', tahunAjaran);
      console.log('Nama Proyek:', namaProyek);

      router.visit(`/mahasiswa/assessment/self-assessment`, {
        method: 'get',
        data: {
          tahunAjaran: tahunAjaran,
          namaProyek: namaProyek
        },
        preserveState: true
      });
    },
    handleDetail(item) {
      router.visit(`/mahasiswa/peer-assessment/self-detail`, {
        method: 'get',
        preserveState: true
      });
    }
  },
  mounted() {
    axios.get('/api/self-assessment')
      .then(response => {
        this.items = response.data.map((item, index) => ({
          id: item.id,
          no: index + 1,
          tahunAjaran: item.tahun_ajaran,
          proyek: item.nama_proyek,
          status: item.status,
          tanggal: dayjs(item.created_at).format('DD MMMM YYYY HH:mm'),
        }));
      })
      .catch(error => {
        console.error('There something when reach data:', error);
      });
  }
}
</script>

<template>
  <div class="flex min-h-screen">
    <SidebarMahasiswa role="mahasiswa" />
    <div class="flex-1">
      <Navbar userName="Mahasiswa" />
      <main class="p-6">
        <div class="mb-4">
          <Breadcrumb :items="breadcrumbs" />
        </div>

        <Card title="DAFTAR HASIL PENGISIAN SELF ASSESSMENT" description="" class="w-full">
          <DataTable :headers="headers" :items="items" class="mt-10">
            <template #column-actions="{ item }">
              <div class="flex justify-center space-x-2">
                <button v-if="item.status === 'aktif'" @click="handleAnswer(item)"
                  class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                  <font-awesome-icon icon="fa-solid fa-pen" class="mr-2" />
                  Answer
                </button>
                <button @click="handleDetail(item)"
                  class="px-3 py-1 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                  <font-awesome-icon icon="fa-solid fa-eye" class="mr-2" />
                  Detail
                </button>
              </div>
            </template>

            <template #column-status="{ item }">
              <span :class="[
                'px-2 py-1 rounded-full text-xs font-medium',
                item.status === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
              ]">
                {{ item.status }}
              </span>
            </template>
          </DataTable>
        </Card>
      </main>
    </div>
  </div>
</template>



