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
        <Card :title="`Data Kelompok - ${namaProyek} (${tahunAjaran})`">
          <template #actions>
            <!-- Container untuk tabel kelompok -->
            <div class="overflow-x-auto mt-6">
              <!-- Daftar Kelompok -->
              <div v-for="(kelompok, index) in group" :key="kelompok.id" class="mt-4">
                <h3 class="text-lg font-semibold mb-2">
                  {{ index + 1 }}.
                  {{ kelompok.nama_mahasiswa }}
                </h3>
                <h4 class="text-sm text-gray-600">
                  Kelompok: {{ kelompok.kelompok }}
                </h4>

                <!-- Tabel Data Kelompok -->
                <div class="overflow-x-auto mt-2">
                  <table class="w-full table-auto border-collapse bg-white">
                    <thead>
                      <tr>
                        <th class="px-4 py-2 border">Tahun Ajaran</th>
                        <th class="px-4 py-2 border">Nama Proyek</th>
                        <th class="px-4 py-2 border">Nama Mahasiswa</th>
                        <th class="px-4 py-2 border">Kelompok</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td class="px-4 py-2 border">{{ kelompok.tahun_ajaran }}</td>
                        <td class="px-4 py-2 border">{{ kelompok.nama_proyek }}</td>
                        <td class="px-4 py-2 border">{{ kelompok.nama_mahasiswa }}</td>
                        <td class="px-4 py-2 border">{{ kelompok.kelompok }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </main>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from "vue";
import axios from "axios";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";

export default {
  components: {
    Sidebar,
    Navbar,
    Card,
  },
  props: {
    tahunAjaran: String,
    namaProyek: String,
  },
  setup() {
    const kelompokData = ref([]); // Menyimpan data kelompok

    // Mengambil data kelompok saat komponen dipasang
    onMounted(async () => {
      try {
        const response = await axios.get("/dosen/kelola-kelompok/data");
        kelompokData.value = response.data; // Menyimpan data kelompok
      } catch (error) {
        console.error("Error fetching kelompok data:", error);
      }
    });

    return {
      kelompokData,
    };
  },
};
</script>
