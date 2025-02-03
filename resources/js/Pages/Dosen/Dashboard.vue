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
      totalGroups: 0,
      completedGroups: 0,
      combinedOptions: [],
      selectedOption: null,
    };
  },
  mounted() {
    this.initializeSelectedProject();
    this.fetchActiveProjects();
    this.fetchDropdownOptions();
  },
  methods: {
    initializeSelectedProject() {
      const savedProject = localStorage.getItem("selectedProject");
      if (savedProject) {
        try {
          this.selectedProject = JSON.parse(savedProject);
          this.fetchStatistics();
          this.fetchPeerStatistics();
        } catch (error) {
          console.error("Error parsing saved project:", error);
          localStorage.removeItem("selectedProject");
        }
      }
    },
    async fetchDropdownOptions() {
      try {
        const response = await axios.get("/api/dropdown-options");
        this.combinedOptions = response.data.options || [];

        const storedOption = localStorage.getItem("selectedOption");
        if (storedOption) {
          try {
            const parsedOption = JSON.parse(storedOption);
            const matchedOption = this.combinedOptions.find(
              (option) =>
                option.batchYear === parsedOption.batchYear &&
                option.projectName === parsedOption.projectName
            );

            if (matchedOption) {
              this.selectedOption = matchedOption;
              this.selectedOption.batch_year = matchedOption.batchYear;
              this.selectedOption.project_name = matchedOption.projectName;
              await this.fetchKelompok();
            }
          } catch (error) {
            console.error("Error parsing localStorage data:", error);
            localStorage.removeItem("selectedOption");
          }
        }
      } catch (error) {
        console.error("Error fetching dropdown options:", error);
      }
    },
    async fetchKelompok() {
      // Implement fetchKelompok method if needed
      console.log("Fetching Kelompok");
    },
    fetchPeerStatistics() {
      if (!this.selectedProject) return;

      axios.get("/api/answers/statistics-peer", {
        params: {
          batch_year: this.selectedProject.batch_year,
          project_name: this.selectedProject.project_name,
        },
      })
      .then((response) => {
        this.totalGroups = response.data.totalKelompok;
        this.completedGroups = response.data.kelompokSudahLengkap;
      })
      .catch((error) => {
        console.error("Error fetching peer statistics:", error);
      });
    },
    fetchStatistics() {
      if (!this.selectedProject) return;

      axios.get("/api/answers/statistics", {
        params: {
          batch_year: this.selectedProject.batch_year,
          project_name: this.selectedProject.project_name,
        },
      })
      .then((response) => {
        this.totalAnswers = response.data.totalSudahMengisi;
        this.totalUsers = response.data.totalKeseluruhan;
      })
      .catch((error) => {
        console.error("Error fetching statistics:", error);
      });
    },
    handleDropdownChange(event) {
      const selectedValue = event.target.value;
      const selectedOption = this.combinedOptions.find(
        (option) => option.value === selectedValue
      );

      if (selectedOption) {
        this.selectedOption = selectedOption;
        localStorage.setItem("selectedOption", JSON.stringify(selectedOption));
        
        // Update selected project
        this.selectedProject = {
          batch_year: selectedOption.batchYear,
          project_name: selectedOption.projectName
        };
        localStorage.setItem("selectedProject", JSON.stringify(this.selectedProject));

        // Fetch updated statistics
        this.fetchStatistics();
        this.fetchPeerStatistics();
      }
    },
    handleListAnswer() {
      if (this.selectedProject) {
        router.get('/dosen/answers-self-assessment', {
          batch_year: this.selectedProject.batch_year,
          project_name: this.selectedProject.project_name
        }, {
          preserveState: true
        });
      }
    },
    fetchActiveProjects() {
      // Implement active projects fetching if needed
      console.log("Fetching Active Projects");
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
          <!-- Self Assessment Card -->
          <Card title="Self Assessment" 
            class="cursor-pointer hover:shadow-lg transition-shadow"
            @click="handleListAnswer(selectedProject)"
          >
            <template #title>
              <h3 class="text-sm font-bold">Self Assessment</h3>
            </template>

            <div v-if="selectedProject" class="mt-2">
              <p class="text-xl font-semibold flex items-center">
                <font-awesome-icon 
                  icon="fa-solid fa-user-check" 
                  class="mr-4 text-2xl" 
                />
                <span class="text-3xl">{{ totalAnswers }} / {{ totalUsers }}</span>
              </p>
            </div>

            <div v-else class="mt-2">
              <p class="text-lg">Pilih proyek untuk melihat statistik.</p>
            </div>
          </Card>

          <!-- Peer Assessment Card -->
          <Card title="Peer Assessment" class="cursor-pointer hover:shadow-lg transition-shadow">
            <template #title>
              <h3 class="text-sm font-bold">Peer Assessment</h3>
            </template>

            <div v-if="selectedProject" class="mt-2">
              <p class="text-xl font-semibold flex items-center">
                <font-awesome-icon 
                  icon="fa-solid fa-user-check" 
                  class="mr-4 text-2xl" 
                />
                <span class="text-3xl">{{ completedGroups }} / {{ totalGroups }}</span>
              </p>
            </div>

            <div v-else class="mt-2">
              <p class="text-lg">Pilih proyek untuk melihat statistik.</p>
            </div>
          </Card>

          <!-- Project Selection Card -->
          <Card title="Project" class="cursor-pointer hover:white transition-shadow">
            <template #title>
              <h3 class="text-sm font-bold">Pilih Proyek</h3>
            </template>

            <div class="mt-2">
 
              <div>
                  <label for="combinedDropdown" class="block mb-2 text-sm font-medium text-gray-700">
                    Pilih Batch Year dan Project Name
                  </label>
                  <select
                    id="combinedDropdown"
                    @change="handleDropdownChange"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                    <option value="" disabled selected>
                      Pilih Batch Year - Project Name
                    </option>
                    <option
                      v-for="option in combinedOptions"
                      :key="option.value"
                      :value="option.value"
                      :selected="selectedOption && option.value === selectedOption.value"
                    >
                      {{ option.label }}
                    </option>
                  </select>
                </div>
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