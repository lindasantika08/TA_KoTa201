<script>
import axios from "axios";
import { router } from "@inertiajs/vue3";
import VueApexCharts from 'vue3-apexcharts'
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import SidebarMahasiswa from "../../Components/SidebarMahasiswa.vue";

export default {
  name: "Dashboard",
  components: {
    SidebarMahasiswa,
    Navbar,
    Card,
    apexchart: VueApexCharts
  },
  data() {
    return {
      projects: [],
      selectedProject: {
        project_name: null,
        batch_year: null
      },
      selfAssessmentStatus: null,
      peerGroupSize: 0,
      peerCompletedCount: 0,
      feedback: {
        aiFeedback: null,
        lecturerFeedback: []
      },
      feedbackLoading: false,
      feedbackError: null,
    };
  },
  computed: {
    series() {
      return [{
        name: 'Project Skills',
        data: [80, 70, 65, 75, 60]
      }]
    },
    chartOptions() {
      return {
        chart: {
          type: 'radar',
          toolbar: { show: false },
          background: '#fff',
        },
        labels: ['Communication', 'Teamwork', 'Technical Skills', 'Problem Solving', 'Time Management'],
        plotOptions: {
          radar: {
            polygons: {
              strokeColor: '#e8e8e8',
              fill: {
                colors: ['#f8f8f8', '#fff']
              }
            }
          }
        },
        stroke: {
          width: 2,
          colors: ['#3B82F6']
        },
        fill: {
          opacity: 0.2,
          colors: ['#93C5FD']
        },
        markers: {
          size: 4,
          colors: ['#3B82F6'],
          strokeWidth: 2,
        },
        tooltip: {
          y: {
            formatter: function(val) {
              return val + '%'
            }
          }
        },
        yaxis: {
          show: false,
          max: 100
        }
      }
    },
    statusColor() {
      const colors = {
        'Not Started': 'bg-red-100 text-red-800',
        'Pending': 'bg-yellow-100 text-yellow-800',
        'Completed': 'bg-green-100 text-green-800'
      }
      return colors[this.selfAssessmentStatus] || 'bg-gray-100 text-gray-800'
    }
  },
  mounted() {
    this.fetchProjectData();
  },
  watch: {
    selectedProject: {
      handler(newProject) {
        if (newProject && newProject.project_name && newProject.batch_year) {
          this.fetchSelfAssessmentStatus(newProject.project_name);
          this.fetchPeerAssessmentDetails(newProject.project_name);
          this.fetchFeedback();
        }
      },
      deep: true
    }
  },
  methods: {
    async fetchProjectData() {
      try {
        const response = await axios.get('/api/projects-user');
        this.projects = response.data.projects;
        if (this.projects.length > 0) {
          this.selectedProject = {
            project_name: this.projects[0].project_name,
            batch_year: this.projects[0].batch_year
          };
        }
      } catch (error) {
        console.error('Error fetching project data:', error);
      }
    },
    async fetchSelfAssessmentStatus(projectName) {
      try {
        const response = await axios.get(`/api/assessment-status`, {
          params: { project: projectName }
        });
        
        const projectStatuses = response.data.projects || [];
        const currentProjectStatus = projectStatuses.find(
          project => project.project_name === projectName
        );

        this.selfAssessmentStatus = currentProjectStatus
          ? currentProjectStatus.selfAssessmentStatus
          : 'Not Started';
      } catch (error) {
        console.error('Error fetching self assessment status:', error);
        this.selfAssessmentStatus = 'Not Started';
      }
    },
    async fetchFeedback() {
      if (!this.selectedProject?.project_name || !this.selectedProject?.batch_year) {
        console.log('Missing required project data');
        return;
      }

      this.feedbackLoading = true;
      this.feedbackError = null;

      try {
        console.log('Fetching feedback with params:', {
          project_name: this.selectedProject.project_name,
          batch_year: this.selectedProject.batch_year
        });

        const response = await axios.get('/api/project-feedback', {
          params: {
            project_name: this.selectedProject.project_name,
            batch_year: this.selectedProject.batch_year
          }
        });

        console.log('Feedback response:', response.data);

        if (response.data.status === 'success') {
          this.feedback = response.data.data;
        } else {
          this.feedbackError = response.data.message || 'Failed to fetch feedback';
        }
      } catch (error) {
        console.error('Error fetching feedback:', error);
        this.feedbackError = error.response?.data?.message || 'An error occurred while fetching feedback';
      } finally {
        this.feedbackLoading = false;
      }
    },
    async fetchPeerAssessmentDetails(projectName) {
      try {
        const response = await axios.get('/api/count-peer', {
          params: { project: projectName }
        });
        
        this.peerGroupSize = response.data.group_size;
        this.peerCompletedCount = response.data.group_peers.filter(peer =>
          response.data.completed_peer_assessments[peer.id].is_completed
        ).length;
      } catch (error) {
        console.error('Error fetching peer assessment details:', error);
      }
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
    <SidebarMahasiswa role="mahasiswa" />
    <div class="flex-1">
      <Navbar userName="Mahasiswa" />
      <main class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
          <Card title="Self Assessment" class="cursor-pointer hover:shadow-lg transition-shadow">
            <div class="flex items-center space-x-5">
              <font-awesome-icon :icon="['fas', 'user']" class="text-3xl" />
              <span :class="{
                'text-red-500 text-lg uppercase': selfAssessmentStatus === 'Not Started',
                'text-yellow-500 text-lg uppercase': selfAssessmentStatus === 'Pending',
                'text-green-500 text-lg uppercase': selfAssessmentStatus === 'Completed'
              }">
                {{ selfAssessmentStatus }}
              </span>
            </div>
          </Card>
          <Card title="Peer Assessment" class="cursor-pointer hover:shadow-lg transition-shadow">
            <div class="flex items-center space-x-3">
              <font-awesome-icon :icon="['fas', 'user-group']" class="text-3xl" />
              <span class="text-xl">{{ peerCompletedCount }} / {{ peerGroupSize }}</span>
            </div>
          </Card>
          <Card title="Project" class="cursor-pointer hover:shadow-lg transition-shadow">
            <div>
              <select
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                v-model="selectedProject">
                <option v-for="project in projects" 
                        :key="project.project_name" 
                        :value="project">
                  {{ project.project_name }} ({{ project.batch_year }})
                </option>
              </select>
            </div>
          </Card>
        </div>

        <card> 
        <!-- Skills and Feedback Section -->
         <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Skills Chart -->
          <Card title="Assessment Chart" class="bg-white">
            <div class="p-4">
              <apexchart type="radar" height="350" :options="chartOptions" :series="series"></apexchart>
            </div>
          </Card>

          <!-- Feedback Section -->
          <div class="space-y-6">
            <!-- AI Feedback -->
            <Card title="Peer Feedback Summary" class="bg-white">
              <div class="p-4 h-40 overflow-y-auto">
                <div v-if="feedbackLoading" class="flex items-center justify-center h-full">
                  <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                </div>
                <div v-else-if="feedbackError" class="text-red-500 p-4 bg-red-50 rounded">
                  {{ feedbackError }}
                </div>
                <div v-else-if="feedback?.aiFeedback" class="prose prose-sm max-w-none">
                  {{ feedback.aiFeedback }}
                </div>
                <div v-else class="flex items-center justify-center h-full text-gray-500">
                  No feedback available
                </div>
              </div>
            </Card>

            <!-- Lecturer Feedback -->
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
          </div>
        </div>
      </card>
      </main>
    </div>
  </div>
</template>