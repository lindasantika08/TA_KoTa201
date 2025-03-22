<script>
import axios from "axios";
import { router } from "@inertiajs/vue3";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import {
  faChevronUp,
  faChevronDown,
  faUser,
  faUsers,
  faProjectDiagram,
  faCheckCircle,
  faHourglassHalf
} from "@fortawesome/free-solid-svg-icons";
import { library } from "@fortawesome/fontawesome-svg-core";

// Tambahkan semua ikon yang digunakan
library.add(
  faChevronUp,
  faChevronDown,
  faUser,
  faUsers,
  faProjectDiagram,
  faCheckCircle,
  faHourglassHalf
);

export default {
  name: "Dashboard",
  components: {
    Sidebar,
    Navbar,
    Card,
    FontAwesomeIcon,
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
      showChangePasswordToast: false, // Tambahkan state untuk notifikasi
      needPasswordChange: false,
      toastTimeout: null,
    };
  },
  mounted() {
    this.initializeSelectedProject();
    this.fetchDropdownOptions();
    this.checkPasswordChangeStatus();
  },
  beforeUnmount() {
    if (this.toastTimeout) {
      clearTimeout(this.toastTimeout);
    }
  },
  methods: {
    checkPasswordChangeStatus() {
      console.log('Checking password change status...'); // Debugging

      // Cek apakah need_password_change ada di localStorage
      const needPasswordChange = localStorage.getItem("need_password_change");
      console.log('Need password change from localStorage:', needPasswordChange); // Debug

      if (needPasswordChange === "true") {
        console.log('Setting up password change notification from localStorage'); // Debug
        this.showChangePasswordToast = true;
        this.needPasswordChange = true;

        // Auto-hide toast after 10 seconds
        this.toastTimeout = setTimeout(() => {
          console.log('Hiding toast after timeout');
          this.showChangePasswordToast = false;
        }, 10000);
      } else {
        // Jika tidak ada di localStorage atau false, cek status user dari API
        this.checkUserStatus();
      }
    },

    async checkUserStatus() {
      console.log('Checking user status from API...'); // Debugging
      try {
        const response = await axios.get("/sispa/api/user/status");
        console.log('User status response:', response.data);

        // Pastikan properti change_password ada
        if (response.data.hasOwnProperty('change_password')) {
          console.log("Raw change_password value from API:", response.data.change_password);

          // Perbaiki logika penentuan need_password_change
          const needPasswordChange = !response.data.change_password;

          if (needPasswordChange) {
            console.log('User needs to change password');
            this.showChangePasswordToast = true;
            this.needPasswordChange = true;

            // Simpan ke localStorage agar tetap konsisten
            localStorage.setItem("need_password_change", "true");

            // Auto-hide toast after 10 seconds
            this.toastTimeout = setTimeout(() => {
              console.log('Hiding toast after timeout');
              this.showChangePasswordToast = false;
            }, 10000);
          } else {
            console.log('User does not need to change password');
            localStorage.removeItem("need_password_change");
          }
        } else {
          console.error("API response missing change_password field");
        }
      } catch (error) {
        console.error("Error checking user status:", error);
      }
    },



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
        const response = await axios.get("/sispa/api/dropdown-options");
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

      axios
        .get("/sispa/api/answers/statistics-peer", {
          params: {
            batch_year: this.selectedProject.batch_year,
            project_name: this.selectedProject.project_name,
          },
        })
        .then((response) => {
          console.log("=== Peer Statistics Response ===");
          console.log("Total Groups:", response.data.totalGroup);
          console.log("Completed Groups:", response.data.groupSudahLengkap);
          console.log("Incomplete Groups:", response.data.groupBelumLengkap);
          console.log("Group Statistics:", response.data.groupStatistics);
          console.log("Full Response:", response.data);

          // Detailed logging for each group
          response.data.groupStatistics.forEach((group, index) => {
            console.log(`\nGroup ${index + 1} Details:`);
            console.log("Group ID:", group.group_id);
            console.log("Group Name:", group.group_name);
            console.log("Is Completed:", group.is_completed);
            console.log("Total Members:", group.total_members);
          });

          this.totalGroups = response.data.totalGroup;
          this.completedGroups = response.data.groupSudahLengkap;
          this.groupStatistics = response.data.groupStatistics;
        })
        .catch((error) => {
          console.error("Error fetching peer statistics:", error);
          if (error.response) {
            console.error("Error Response Data:", error.response.data);
            console.error("Error Response Status:", error.response.status);
          }
        });
    },
    fetchStatistics() {
      if (!this.selectedProject) return;

      axios
        .get("/sispa/api/answers/statistics-dashboard", {
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
          project_name: selectedOption.projectName,
        };
        localStorage.setItem(
          "selectedProject",
          JSON.stringify(this.selectedProject)
        );

        // Fetch updated statistics
        this.fetchStatistics();
        this.fetchPeerStatistics();
      }
    },
    handleListAnswer() {
      if (this.selectedProject) {
        router.get(
          "/dosen/answers-self-assessment",
          {
            batch_year: this.selectedProject.batch_year,
            project_name: this.selectedProject.project_name,
          },
          {
            preserveState: true,
          }
        );
      }
    },
    handleListAnswerPeer() {
      if (this.selectedProject) {
        router.get(
          "/dosen/answers-peer-assessment",
          {
            batch_year: this.selectedProject.batch_year,
            project_name: this.selectedProject.project_name,
          },
          {
            preserveState: true,
          }
        );
      }
    },
    handleChangePassword() {
      console.log('Handling password change...'); // Debug
      this.showChangePasswordToast = false;
      this.needPasswordChange = false;
      localStorage.removeItem("need_password_change");
      router.visit("/sispa/dosen/profile");
    },
  },
};
</script>

<template>
  <div class="flex min-h-screen bg-gray-50">
    <div v-if="showChangePasswordToast"
      class="fixed top-4 right-4 bg-white shadow-lg rounded-lg p-4 max-w-md animate-fade-in-up z-50">
      <div class="flex items-center space-x-4">
        <div class="bg-yellow-500 flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center">
          <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
          </svg>
        </div>
        <div class="flex-1">
          <p class="text-sm font-medium text-gray-900">
            Untuk alasan keamanan, silakan ganti password Anda
          </p>
          <p class="text-sm text-gray-500 mt-1">
            Direkomendasikan untuk mengganti password secara berkala
          </p>
        </div>
        <div class="flex space-x-2">
          <button @click="handleChangePassword"
            class="px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-md hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
            Ganti Password
          </button>
          <button @click="showChangePasswordToast = false"
            class="p-2 text-gray-400 hover:text-gray-500 focus:outline-none">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <Sidebar role="dosen" />
    <div class="flex-1">
      <Navbar userName="Dosen" />
      <main class="p-6">


        <!-- Project Selection Header -->
        <div class="mb-8">
          <h1 class="text-2xl font-bold text-gray-800 mb-4">
            Assessment Dashboard
          </h1>
          <div class="bg-white rounded-lg shadow p-4">
            <label for="combinedDropdown" class="block text-sm font-medium text-gray-700 mb-2">
              Select Project
            </label>
            <select id="combinedDropdown" @change="handleDropdownChange"
              class="w-full md:w-1/2 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
              <option value="" disabled selected>
                Select Batch Year - Project Name
              </option>
              <option v-for="option in combinedOptions" :key="option.value" :value="option.value" :selected="selectedOption && option.value === selectedOption.value
                ">
                {{ option.label }}
              </option>
            </select>
          </div>
        </div>

        <!-- Statistics Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <!-- Self Assessment Card -->
          <div @click="handleListAnswer()"
            class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer overflow-hidden">
            <div class="p-6">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">
                  Self Assessment
                </h3>
                <font-awesome-icon icon="fa-solid fa-user" class="text-blue-500 text-xl" />
              </div>

              <div v-if="selectedProject">
                <div class="flex items-end gap-2">
                  <span class="text-3xl font-bold text-gray-900">{{ totalAnswers }}/{{ totalUsers }}</span>
                  <span class="text-sm text-gray-600 mb-1">completed</span>
                </div>

                <div class="mt-4">
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-500 h-2 rounded-full transition-all duration-500" :style="{
                      width: `${(totalAnswers / totalUsers) * 100}%`,
                    }"></div>
                  </div>
                  <p class="text-sm text-gray-600 mt-2">
                    {{ Math.round((totalAnswers / totalUsers) * 100) }}%
                    Completion Rate
                  </p>
                </div>
              </div>
              <div v-else>
                <p class="text-gray-500">
                  Please select a project to view statistics
                </p>
              </div>
            </div>
          </div>

          <!-- Peer Assessment Card -->
          <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
            <div class="p-6">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">
                  Peer Assessment
                </h3>
                <font-awesome-icon icon="fa-solid fa-users" class="text-green-500 text-xl" />
              </div>

              <div v-if="selectedProject">
                <div @click="handleListAnswerPeer()" class="cursor-pointer">
                  <div class="flex items-end gap-2">
                    <span class="text-3xl font-bold text-gray-900">{{ completedGroups }}/{{ totalGroups }}</span>
                    <span class="text-sm text-gray-600 mb-1">groups completed</span>
                  </div>

                  <div class="mt-4">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                      <div class="bg-green-500 h-2 rounded-full transition-all duration-500" :style="{
                        width: `${(completedGroups / totalGroups) * 100}%`,
                      }"></div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">
                      {{ Math.round((completedGroups / totalGroups) * 100) }}%
                      Group Completion Rate
                    </p>
                  </div>
                </div>

                <!-- Group Details Expansion -->
                <div class="mt-4 border-t pt-4">
                  <button @click="showGroupDetails = !showGroupDetails"
                    class="flex items-center justify-between w-full text-sm font-medium text-gray-700 hover:bg-gray-50 p-2 rounded-lg">
                    <span>Group Details</span>
                    <font-awesome-icon :icon="showGroupDetails
                        ? 'fa-solid fa-chevron-up'
                        : 'fa-solid fa-chevron-down'
                      " class="text-gray-500 transition-transform duration-200" />
                  </button>

                  <transition enter-active-class="transition duration-200 ease-out"
                    enter-from-class="transform scale-y-0 opacity-0" enter-to-class="transform scale-y-100 opacity-100"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="transform scale-y-100 opacity-100" leave-to-class="transform scale-y-0 opacity-0">
                    <div v-if="showGroupDetails" class="space-y-2 mt-2 origin-top">
                      <template v-if="groupStatistics.length > 0">
                        <div v-for="group in groupStatistics" :key="group.group_id"
                          class="bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition-colors duration-200">
                          <div class="flex items-center justify-between">
                            <div>
                              <h4 class="font-medium text-gray-800">
                                {{ group.group_name }}
                              </h4>
                              <p class="text-xs text-gray-600">
                                {{ group.total_members }} members
                              </p>
                            </div>
                            <span :class="[
                              'px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1',
                              group.is_completed
                                ? 'bg-green-100 text-green-800'
                                : 'bg-yellow-100 text-yellow-800',
                            ]">
                              <font-awesome-icon :icon="group.is_completed
                                  ? 'fa-check-circle'
                                  : 'fa-hourglass-half'
                                " class="text-xs" />
                              {{
                                group.is_completed ? "Completed" : "In Progress"
                              }}
                            </span>
                          </div>
                        </div>
                      </template>
                      <p v-else class="text-sm text-gray-500 italic text-center py-4">
                        No group data available
                      </p>
                    </div>
                  </transition>
                </div>
              </div>
              <div v-else>
                <p class="text-gray-500">
                  Please select a project to view statistics
                </p>
              </div>
            </div>
          </div>

          <!-- Project Summary Card -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-800">
                Project Summary
              </h3>
              <font-awesome-icon icon="fa-solid fa-project-diagram" class="text-purple-500 text-xl" />
            </div>

            <div v-if="selectedProject" class="space-y-4">
              <div>
                <p class="text-sm text-gray-600">Selected Project</p>
                <p class="font-medium text-gray-800">
                  {{ selectedOption?.projectName }}
                </p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Batch Year</p>
                <p class="font-medium text-gray-800">
                  {{ selectedOption?.batchYear }}
                </p>
              </div>
              <div class="pt-4 border-t">
                <div class="grid grid-cols-2 gap-4">
                  <div class="bg-purple-50 rounded-lg p-3">
                    <p class="text-sm text-purple-600 mb-1">Total Users</p>
                    <p class="text-xl font-semibold text-purple-900">
                      {{ totalUsers }}
                    </p>
                  </div>
                  <div class="bg-purple-50 rounded-lg p-3">
                    <p class="text-sm text-purple-600 mb-1">Total Groups</p>
                    <p class="text-xl font-semibold text-purple-900">
                      {{ totalGroups }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
            <div v-else>
              <p class="text-gray-500">
                Please select a project to view summary
              </p>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.animate-fade-in-up {
  animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>