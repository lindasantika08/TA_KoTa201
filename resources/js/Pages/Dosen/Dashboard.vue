<script>
import axios from "axios";
import { router } from '@inertiajs/vue3';
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";

export default {
  name: "Dashboard",
  components: {
    Sidebar,
    Navbar,
    Card,
  },
  data() {
    return {
      activeProjects: [],
      selectedProject: null,
      totalAnswers: 0,
      totalUsers: 0,
    };
  },
  mounted() {
    const savedProject = localStorage.getItem("selectedProject");
    if (savedProject) {
      this.selectedProject = JSON.parse(savedProject);
      this.fetchStatistics();
    }
    this.fetchActiveProjects();
  },
  watch: {
    selectedProject: {
      handler(newProject) {
        if (newProject) {
          localStorage.setItem("selectedProject", JSON.stringify(newProject));
        }
        this.fetchStatistics();
      },
      immediate: true,
    },
  },
  methods: {
    fetchActiveProjects() {
      axios
        .get("/api/projects/active")
        .then((response) => {
          this.activeProjects = response.data;
        })
        .catch((error) => {
          console.error("Error fetching active projects:", error);
        });
    },
    fetchStatistics() {
  if (this.selectedProject) {
    // Log data yang akan dikirim dalam permintaan
    console.log("Fetching statistics with parameters:");
    console.log("Tahun Ajaran:", this.selectedProject.tahun_ajaran);
    console.log("Nama Proyek:", this.selectedProject.nama_proyek);

    axios
      .get("/api/answers/statistics", {
        params: {
          tahun_ajaran: this.selectedProject.tahun_ajaran,
          nama_proyek: this.selectedProject.nama_proyek,
        },
      })
      .then((response) => {
        // Log respons data untuk memastikan data diterima
        console.log("Statistics response:", response.data);

        this.totalAnswers = response.data.totalSudahMengisi;
        this.totalUsers = response.data.totalKeseluruhan;
      })
      .catch((error) => {
        // Log error jika terjadi masalah saat fetch
        console.error("Error fetching statistics:", error);
      });
  } else {
    console.log("No project selected. Skipping fetch.");
  }
},

    handleListAnswer(item) {
      router.get('/dosen/answers-self-assessment', {
        tahun_ajaran: item.tahun_ajaran,
        nama_proyek: item.nama_proyek
      }, {
        preserveState: true
      });
    }
  },
};
</script>

<template>
  <div class="flex min-h-screen">
    <Sidebar role="dosen" />

    <div class="flex-1">
      <Navbar userName="Dosen" />
      <main class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
          <Card title="Self Assessment" class="cursor-pointer hover:shadow-lg transition-shadow"
            @click="handleListAnswer(selectedProject)">
            <template #title>
              <h3 class="text-sm font-bold">Self Assessment</h3>
            </template>

            <div v-if="selectedProject" class="mt-2">
              <p class="text-xl font-semibold flex items-center">
                <font-awesome-icon icon="fa-solid fa-user-check" class="mr-4 text-2xl" />
                <span class="text-3xl">{{ totalAnswers }} / {{ totalUsers }}</span>
              </p>
            </div>

            <div v-else class="mt-2">
              <p class="text-lg">Pilih proyek untuk melihat statistik.</p>
            </div>
          </Card>

          <Card title="Peer Assessment" class="cursor-pointer hover:shadow-lg transition-shadow">
            <template #title>
              <h3 class="text-sm font-bold">Peer Assessment</h3>
            </template>
            <p>Content for Card 2</p>
          </Card>

          <Card title="Project" class="cursor-pointer hover:white transition-shadow">
            <template #title>
              <h3 class="text-sm font-bold">Pilih Proyek</h3>
            </template>

            <div class="mt-2">
              <label for="project-dropdown" class="block text-sm font-medium text-gray-700">Pilih Proyek</label>
              <select id="project-dropdown" v-model="selectedProject" @change="fetchStatistics"
                class="block w-full mt-1 rounded-md border-2 border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-lg transition-all"
                :class="{ 'border-transparent': selectedProject }">
                <option value="" disabled selected>Pilih Proyek</option>
                <option v-for="project in activeProjects" :key="project.nama_proyek" :value="project">
                  {{ project.tahun_ajaran }} - {{ project.nama_proyek }}
                </option>
              </select>
            </div>
          </Card>
        </div>

        <Card title="Dashboard" class="mt-8">
          <template #actions></template>
        </Card>
      </main>
    </div>
  </div>
</template>



<style scoped></style>
