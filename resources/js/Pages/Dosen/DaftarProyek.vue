<template>
    <div class="flex min-h-screen">
      <!-- Sidebar -->
      <Sidebar role="dosen" />
    
      <!-- Main Content -->
      <div class="flex-1">
        <!-- Navbar -->
        <Navbar userName="Dosen" />
    
        <!-- Content -->
        <main class="p-6">
          <Card title="Daftar Self Assessment">
            <template #actions>
              <!-- Menambahkan pengecekan apakah ada data -->
              <div v-if="$page.props.projects.length > 0" class="overflow-x-auto">
                <table class="table-auto w-full border-collapse border border-gray-300">
                  <thead>
                    <tr class="bg-gray-100">
                      <th class="px-4 py-2 border">Tahun Ajaran</th>
                      <th class="px-4 py-2 border">Nama Proyek</th>
                      <th class="px-4 py-2 border">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(project, index) in $page.props.projects" :key="index" class="hover:bg-gray-50">
                      <td class="px-4 py-2 border">{{ project.tahun_ajaran }}</td>
                      <td class="px-4 py-2 border">{{ project.nama_proyek }}</td>
                      <td class="px-4 py-2 border">
                        <button
                          class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                          @click="handleView(project)"
                        >
                          View
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              
              <!-- Pesan ketika tidak ada data -->
              <div v-else class="text-center p-4 text-gray-500">
                Tidak ada proyek dengan assessment saat ini.
              </div>
            </template>
          </Card>
        </main>
      </div>
    </div>
  </template>
  
  <script setup>
  import { router } from '@inertiajs/vue3';
  import Sidebar from "@/Components/Sidebar.vue";
  import Navbar from "@/Components/Navbar.vue";
  import Card from "@/Components/Card.vue";
  
  methods: {
    const handleView = (project) => {
    router.visit(window.route('dosen.assessment.data-with-bobot'), {
        method: 'get',
        data: {
            tahun_ajaran: "2024/2025",
            nama_proyek: "Proyek 2 : Aplikasi Website"
        },
        preserveState: true
    });
};
  }
  </script>