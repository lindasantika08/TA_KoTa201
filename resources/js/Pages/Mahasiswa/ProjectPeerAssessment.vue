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
        { text: "Assessment", href: "/assessment/self" },
        { text: "Peer Assessment", href: null }
      ],
      headers: [
        { key: 'no', label: 'No' },
        { key: 'tahun_ajaran', label: 'Tahun Ajaran' },
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
      console.log('Tahun Ajaran:', item.tahun_ajaran);
      console.log('Proyek:', item.proyek);

      // Menggunakan router.get untuk mengirim data sebagai query parameters
      router.get('/mahasiswa/assessment/peer-assessment', {
        tahun_ajaran: item.tahun_ajaran,
        proyek: item.proyek
      }, {
        preserveState: true,
        onSuccess: (page) => {
          console.log('Navigation successful', page);
        },
        onError: (errors) => {
          console.error('Navigation failed:', errors);
        }
      });
    },
    handleDetail(item) {
      router.visit(`/mahasiswa/peer-assessment/peer-detail`, {
        method: 'get',
        preserveState: true
      });
    }
  },
  mounted() {
    axios.get('/api/peer-assessment')
    .then(response => {
        this.items = response.data.map((item, index) => ({
            id: item.id,
            no: index + 1,
            tahun_ajaran: item.tahun_ajaran,
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
        
        <Card 
          title="DAFTAR HASIL PENGISIAN PEER ASSESSMENT"
          description=""
          class="w-full"
        >
          <DataTable 
            :headers="headers"
            :items="items"
            class="mt-10"
          >
            <template #column-actions="{ item }">
              <button 
                @click="handleAnswer(item)"
                class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
              >
                <font-awesome-icon icon="fa-solid fa-pencil" class="mr-2" />
                Answer
              </button>
              <button 
                @click="handleDetail(item)"
                class="px-3 py-1 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 ml-2"
              >
                <font-awesome-icon icon="fa-solid fa-eye" class="mr-2" />
                Detail
              </button>
            </template>

            <template #column-status="{ item }">
              <span 
                :class="[
                  'px-2 py-1 rounded-full text-xs font-medium',
                  item.status === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                ]"
              >
                {{ item.status }}
              </span>
            </template>
          </DataTable>
        </Card>
      </main>
    </div>
  </div>
</template>