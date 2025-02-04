<script>
import axios from "axios";
import { router } from '@inertiajs/vue3';
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faChevronUp, faChevronDown } from '@fortawesome/free-solid-svg-icons';
import { library } from '@fortawesome/fontawesome-svg-core';

library.add(faChevronUp, faChevronDown);

export default {
  name: "Dashboard",
  components: {
    Sidebar,
    Navbar,
    Card,
    FontAwesomeIcon
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
      groupStatistics: [],
      showGroupDetails: false,
    };
  },
  mounted() {
    this.initializeSelectedProject();
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
              
              // Fetch statistics when an option is selected
              this.fetchStatistics();
              this.fetchPeerStatistics();
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
    fetchPeerStatistics() {
  if (!this.selectedProject) return;

  axios.get("/api/answers/statistics-peer", {
    params: {
      batch_year: this.selectedProject.batch_year,
      project_name: this.selectedProject.project_name,
    },
  })
  .then((response) => {
    console.log('=== Peer Statistics Response ===');
    console.log('Total Groups:', response.data.totalGroup);
    console.log('Completed Groups:', response.data.groupSudahLengkap);
    console.log('Incomplete Groups:', response.data.groupBelumLengkap);
    console.log('Group Statistics:', response.data.groupStatistics);
    console.log('Full Response:', response.data);
    
    // Detailed logging for each group
    response.data.groupStatistics.forEach((group, index) => {
      console.log(`\nGroup ${index + 1} Details:`);
      console.log('Group ID:', group.group_id);
      console.log('Group Name:', group.group_name);
      console.log('Is Completed:', group.is_completed);
      console.log('Total Members:', group.total_members);
    });

    this.totalGroups = response.data.totalGroup;
    this.completedGroups = response.data.groupSudahLengkap;
    this.groupStatistics = response.data.groupStatistics;
  })
  .catch((error) => {
    console.error("Error fetching peer statistics:", error);
    if (error.response) {
      console.error('Error Response Data:', error.response.data);
      console.error('Error Response Status:', error.response.status);
    }
  });
},
    fetchStatistics() {
      if (!this.selectedProject) return;

      axios.get("/api/answers/statistics-dashboard", {
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
    handleListAnswerPeer() {
      if (this.selectedProject) {
        router.get('/dosen/answers-peer-assessment', {
          batch_year: this.selectedProject.batch_year,
          project_name: this.selectedProject.project_name
        }, {
          preserveState: true
        });
      }
    },
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
          <Card 
            title="Self Assessment" 
            class="cursor-pointer hover:shadow-lg transition-shadow"
            @click="handleListAnswer()"
          >
            <template #title>
              <h3 class="text-sm font-bold">Self Assessment</h3>
            </template>

            <div v-if="selectedProject" class="mt-2">
              <p class="text-xl font-semibold flex items-center">
                <font-awesome-icon 
                  icon="fa-solid fa-user" 
                  class="mr-4 text-2xl" 
                />
                <span class="text-3xl">{{ totalAnswers }} / {{ totalUsers }}</span>
              </p>
              <p class="text-sm text-gray-600 mt-1">
                Users Completed Self Assessment
              </p>
            </div>

            <div v-else class="mt-2">
              <p class="text-lg">Pilih proyek untuk melihat statistik.</p>
            </div>
          </Card>

          <!-- Peer Assessment Card section -->
<Card 
  title="Peer Assessment" 
  class="cursor-pointer hover:shadow-lg transition-shadow"
>
  <template #title>
    <h3 class="text-sm font-bold">Peer Assessment</h3>
  </template>

  <div v-if="selectedProject" class="mt-2">
    <!-- Main card content - clickable area -->
    <div @click="handleListAnswerPeer()">
      <p class="text-xl font-semibold flex items-center">
        <font-awesome-icon 
          icon="fa-solid fa-users" 
          class="mr-4 text-2xl" 
        />
        <span class="text-3xl">{{ completedGroups }} / {{ totalGroups }}</span>
      </p>
      <p class="text-sm text-gray-600 mt-1">
        Groups Completed Peer Assessment
      </p>
    </div>
    
    <!-- Group Details Section with Toggle - separate click handler -->
    <div class="mt-4" @click.stop>
      <button 
        @click="showGroupDetails = !showGroupDetails"
        class="flex items-center justify-between w-full text-sm font-semibold mb-2 hover:bg-gray-50 p-2 rounded-lg transition-colors duration-200"
      >
        <span>Group Details</span>
        <font-awesome-icon 
          :icon="showGroupDetails ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down'"
          class="text-gray-600 transition-transform duration-200"
        />
      </button>
      
      <!-- Expandable Content -->
      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="transform scale-y-0 opacity-0"
        enter-to-class="transform scale-y-100 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="transform scale-y-100 opacity-100"
        leave-to-class="transform scale-y-0 opacity-0"
      >
        <div v-if="showGroupDetails" class="space-y-2 origin-top">
          <template v-if="groupStatistics.length > 0">
            <div 
              v-for="group in groupStatistics" 
              :key="group.group_id" 
              class="flex justify-between items-center bg-gray-100 p-2 rounded-lg hover:bg-gray-200 transition-colors duration-200"
            >
              <div class="flex flex-col">
                <span class="text-sm font-medium">{{ group.group_name }}</span>
                <span class="text-xs text-gray-600">{{ group.total_members }} members</span>
              </div>
              <span 
                :class="[
                  'px-2 py-1 rounded-full text-xs font-medium',
                  group.is_completed 
                    ? 'bg-green-200 text-green-800' 
                    : 'bg-red-200 text-red-800'
                ]"
              >
                {{ group.is_completed ? 'Completed' : 'Pending' }}
              </span>
            </div>
          </template>
          <p v-else class="text-sm text-gray-500 italic">
            No group details available
          </p>
        </div>
      </transition>
    </div>
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
                <label 
                  for="combinedDropdown" 
                  class="block mb-2 text-sm font-medium text-gray-700"
                >
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