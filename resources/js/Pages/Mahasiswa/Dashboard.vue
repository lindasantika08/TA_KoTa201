<script>
import axios from "axios";
import { router } from "@inertiajs/vue3";
import VueApexCharts from "vue3-apexcharts";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import SidebarMahasiswa from "../../Components/SidebarMahasiswa.vue";

export default {
  name: "Dashboard",
  components: {
    SidebarMahasiswa,
    Navbar,
    Card,
    apexchart: VueApexCharts,
  },
  data() {
    return {
      projects: [],
      selectedProject: {
        project_name: null,
        batch_year: null,
      },
      selfAssessmentStatus: null,
      peerGroupSize: 0,
      peerCompletedCount: 0,
      showChangePasswordToast: false, // Tambahkan state untuk notifikasi
      needPasswordChange: false,
      toastTimeout: null,
    };
  },
  computed: {
    series() {
      return [
        {
          name: "Project Skills",
          data: [80, 70, 65, 75, 60],
        },
      ];
    },
    chartOptions() {
      return {
        chart: {
          type: "radar",
          toolbar: { show: false },
        },
        labels: [
          "Communication",
          "Teamwork",
          "Technical Skills",
          "Problem Solving",
          "Time Management",
        ],
        plotOptions: {
          radar: {
            polygons: {
              strokeColor: "#e9e9e9",
              fill: {
                colors: ["#f8f8f8", "#fff"],
              },
            },
          },
        },
        title: {
          text: "",
          align: "left",
        },
        xaxis: {
          categories: [
            "Communication",
            "Teamwork",
            "Technical Skills",
            "Problem Solving",
            "Time Management",
          ],
        },
        yaxis: {
          show: false,
        },
      };
    },
  },
  mounted() {
    this.fetchProjectData();
    this.fetchSelfAssessmentStatus();
    this.fetchPeerAssessmentDetails();
    this.checkPasswordChangeStatus();
  },
  beforeUnmount() {
    if (this.toastTimeout) {
      clearTimeout(this.toastTimeout);
    }
  },
  watch: {
    selectedProject(newProject) {
      if (newProject) {
        this.fetchSelfAssessmentStatus(newProject);
        this.fetchPeerAssessmentDetails(newProject);
      }
    },
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
        const response = await axios.get("/api/user/status");
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
    fetchProjectData() {
      axios
        .get("/api/projects-user")
        .then((response) => {
          this.projects = response.data.projects;
          if (this.projects.length > 0) {
            this.selectedProject = this.projects[0].project_name;
          }
        })
        .catch((error) => {
          console.error("Error fetching project data:", error);
        });
    },
    fetchSelfAssessmentStatus(projectName) {
      axios
        .get(`/api/assessment-status`, {
          params: { project: projectName },
        })
        .then((response) => {
          const projectStatuses = response.data.projects || [];
          const currentProjectStatus = projectStatuses.find(
            (project) => project.project_name === projectName
          );

          this.selfAssessmentStatus = currentProjectStatus
            ? currentProjectStatus.selfAssessmentStatus
            : "Not Started";
        })
        .catch((error) => {
          console.error("Error fetching self assessment status:", error);
          this.selfAssessmentStatus = "Not Started";
        });
    },
    fetchPeerAssessmentDetails(projectName) {
      axios
        .get("/api/count-peer", {
          params: { project: projectName },
        })
        .then((response) => {
          this.peerGroupSize = response.data.group_size;
          this.peerCompletedCount = response.data.group_peers.filter(
            (peer) =>
              response.data.completed_peer_assessments[peer.id].is_completed
          ).length;
        })
        .catch((error) => {
          console.error("Error fetching peer assessment details:", error);
        });
    },
    handleChangePassword() {
      console.log("Handling password change..."); // Debug
      this.showChangePasswordToast = false;
      this.needPasswordChange = false;
      localStorage.removeItem("need_password_change");
      router.visit("/mahasiswa/profile");
    },
    goToDashboardSelf(path) {
      router.visit(path);
    },
    goToDashboardPeer(path) {
      router.visit(path);
    },
    goToKelolaProyek(path) {
      router.visit(path);
    },
  },
};
</script>

<template>
  <div class="flex min-h-screen">
    <!-- Password Change Toast -->
    <div
      v-if="showChangePasswordToast"
      class="fixed top-4 right-4 bg-white shadow-lg rounded-lg p-4 max-w-md animate-fade-in-up z-50"
    >
      <div class="flex items-center space-x-4">
        <div
          class="bg-yellow-500 flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
        >
          <svg
            class="w-6 h-6 text-white"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
            />
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
          <button
            @click="handleChangePassword"
            class="px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-md hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
          >
            Ganti Password
          </button>
          <button
            @click="showChangePasswordToast = false"
            class="p-2 text-gray-400 hover:text-gray-500 focus:outline-none"
          >
            <svg
              class="w-5 h-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <SidebarMahasiswa role="mahasiswa" />
    <div class="flex-1">
      <Navbar userName="Mahasiswa" />
      <main class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
          <Card
            title="Self Assessment"
            class="cursor-pointer hover:shadow-lg transition-shadow"
          >
            <div class="flex items-center space-x-5">
              <font-awesome-icon :icon="['fas', 'user']" class="text-3xl" />
              <span
                :class="{
                  'text-red-500 text-lg uppercase':
                    selfAssessmentStatus === 'Not Started',
                  'text-yellow-500 text-lg uppercase':
                    selfAssessmentStatus === 'Pending',
                  'text-green-500 text-lg uppercase':
                    selfAssessmentStatus === 'Completed',
                }"
              >
                {{ selfAssessmentStatus }}
              </span>
            </div>
          </Card>
          <Card
            title="Peer Assessment"
            class="cursor-pointer hover:shadow-lg transition-shadow"
          >
            <div class="flex items-center space-x-3">
              <font-awesome-icon
                :icon="['fas', 'user-group']"
                class="text-3xl"
              />
              <span class="text-xl"
                >{{ peerCompletedCount }} / {{ peerGroupSize }}</span
              >
            </div>
          </Card>
          <Card
            title="Project"
            class="cursor-pointer hover:shadow-lg transition-shadow"
          >
            <div>
              <select
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                v-model="selectedProject"
              >
                <option
                  v-for="project in projects"
                  :key="project.id"
                  :value="project.project_name"
                >
                  {{ project.project_name }}
                </option>
              </select>
            </div>
          </Card>
        </div>

        <Card title="Assessment Activity Chart" class="w-full mt-8">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <apexchart
                type="radar"
                height="350"
                :options="chartOptions"
                :series="series"
              ></apexchart>
            </div>
            <div>
              <Card title="Feedback Peer" class="text-xs h-full mb-4"></Card>
              <Card title="Feedback Dosen" class="text-xs h-full"></Card>
            </div>
          </div>
        </Card>

        <Card title="Lecturer Feedback" class="bg-white">
          <div class="p-4 h-40 overflow-y-auto">
            <div v-if="feedbackLoading" class="flex items-center justify-center h-full">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            </div>
            <div v-else-if="feedbackError" class="text-red-500 p-4 bg-red-50 rounded">
              {{ feedbackError }}
            </div>
            <div v-else-if="feedback?.lecturerFeedback?.length" class="space-y-4">
              <div v-for="(item, index) in feedback.lecturerFeedback" 
                   :key="index" 
                   class="p-3 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-700">{{ item.feedback }}</p>
                <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                  <span class="font-medium">{{ item.dosenName }}</span>
                  <span>{{ new Date(item.createdAt).toLocaleDateString() }}</span>
                </div>
              </div>
            </div>
            <div v-else class="flex items-center justify-center h-full text-gray-500">
              No lecturer feedback available
            </div>
          </div>
        </Card>
      </main>
    </div>
  </div>
</template>