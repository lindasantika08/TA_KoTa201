<script>
import axios from 'axios';
import DataTable from "@/Components/DataTable.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Sidebar from "@/Components/Sidebar.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import dayjs from 'dayjs';
import { router } from '@inertiajs/vue3';

export default {
  components: {
    DataTable,
    Navbar,
    Card,
    Sidebar,
    Breadcrumb
  },
  data() {
    return {
      breadcrumbs: [
        { text: "Peer Assessment", href: "/dosen/assessment/projectsPeer" }
      ],
      headers: [
        { key: 'no', label: 'No' },
        { key: 'batch_year', label: 'Batch Year' },
        { key: 'project_name', label: 'Project Name' },
        { key: 'status', label: 'Status' },
        { key: 'date', label: 'Created Date' },
        { key: 'actions', label: 'Actions' },
      ],
      items: [],
    }
  },
  methods: {
    handleDetail(item) {
      router.get('/dosen/assessment/data-with-bobot-peer', {
        batch_year: item.batch_year,
        project_name: item.project_name
      }, {
        preserveState: true
      })
        .then(response => {
          console.log('Response received:', response);
        })
        .catch(error => {
          console.error('Error occurred while fetching data:', error);
        });
    },

    handleListAnswer(item) {
      router.get('/dosen/answers-peer-assessment', {
        batch_year: item.batch_year,
        project_name: item.project_name
      }, {
        preserveState: true
      });
    }
  },
  mounted() {
    axios.get('/api/proyek-Peer-assessment')
      .then(response => {
        this.items = response.data.map((item, index) => ({
          no: index + 1,
          batch_year: item.batch_year,
          project_name: item.project_name,
          status: item.status,
          date: dayjs(item.created_at).format('DD MMMM YYYY HH:mm'),
        }));
      })
      .catch(error => {
        console.error('Error fetching data:', error);
      });
  }
}
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

        <Card title="PEER ASSESSMENT PROJECT LIST" description="" class="w-full">
          <div v-if="items.length === 0" class="text-center text-gray-500 py-6">
            No assessment available
          </div>
          <div v-else>
            <DataTable :headers="headers" :items="items" class="mt-10">
              <template #column-actions="{ item }">
                <button @click="handleDetail(item)"
                  class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                  <font-awesome-icon icon="fa-solid fa-eye" class="mr-2" />
                  Detail
                </button>
                <button @click="handleListAnswer(item)"
                  class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 ml-2">
                  <font-awesome-icon icon="fa-solid fa-file" class="mr-2" />
                  List Answer
                </button>
              </template>

              <template #column-status="{ item }">
                <span :class="[
                  'px-2 py-1 rounded-full text-xs font-medium',
                  item.status === 'Active'
                    ? 'bg-green-100 text-green-800'
                    : 'bg-red-100 text-red-800'
                ]">
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