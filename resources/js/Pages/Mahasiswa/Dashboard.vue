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
      selectedProject: null,
      selfAssessmentStatus: null,
      peerGroupSize: 0,
      peerCompletedCount: 0,
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
          toolbar: { show: false }
        },
        labels: ['Communication', 'Teamwork', 'Technical Skills', 'Problem Solving', 'Time Management'],
        plotOptions: {
          radar: {
            polygons: {
              strokeColor: '#e9e9e9',
              fill: {
                colors: ['#f8f8f8', '#fff']
              }
            }
          }
        },
        title: {
          text: '',
          align: 'left'
        },
        xaxis: {
          categories: ['Communication', 'Teamwork', 'Technical Skills', 'Problem Solving', 'Time Management']
        },
        yaxis: {
          show: false
        }
      }
    }
  },
  mounted() {
    this.fetchProjectData();
    this.fetchSelfAssessmentStatus();
    this.fetchPeerAssessmentDetails();
  },
  watch: {
    selectedProject(newProject) {
      if (newProject) {
        this.fetchSelfAssessmentStatus(newProject);
        this.fetchPeerAssessmentDetails(newProject);
      }
    }
  },
  methods: {
    fetchProjectData() {
      axios.get('/api/projects-user')
        .then(response => {
          this.projects = response.data.projects;
          if (this.projects.length > 0) {
            this.selectedProject = this.projects[0].project_name;
          }
        })
        .catch(error => {
          console.error('Error fetching project data:', error);
        });
    },
    fetchSelfAssessmentStatus(projectName) {
      axios.get(`/api/assessment-status`, {
        params: { project: projectName }
      })
        .then(response => {
          const projectStatuses = response.data.projects || [];
          const currentProjectStatus = projectStatuses.find(
            project => project.project_name === projectName
          );

          this.selfAssessmentStatus = currentProjectStatus
            ? currentProjectStatus.selfAssessmentStatus
            : 'Not Started';
        })
        .catch(error => {
          console.error('Error fetching self assessment status:', error);
          this.selfAssessmentStatus = 'Not Started';
        });
    },
    fetchPeerAssessmentDetails(projectName) {
      axios.get('/api/count-peer', {
        params: { project: projectName }
      })
        .then(response => {
          this.peerGroupSize = response.data.group_size;
          this.peerCompletedCount = response.data.group_peers.filter(peer =>
            response.data.completed_peer_assessments[peer.id].is_completed
          ).length;
        })
        .catch(error => {
          console.error('Error fetching peer assessment details:', error);
        });
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
                <option v-for="project in projects" :value="project.project_name">{{ project.project_name }}</option>
              </select>
            </div>
          </Card>
        </div>
        <Card title="Assessment Activity Chart" class="w-full mt-8">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <apexchart type="radar" height="350" :options="chartOptions" :series="series"></apexchart>
            </div>
            <div>
              <div class="text-xs font-semibold mb-3">
                <Card title="Feedback Peer" class="text-xs h-full mb-4">
                  
                </Card>
                <Card title="Feedback Dosen" class="text-xs h-full">

                </Card>
              </div>
            </div>
          </div>
        </Card>
      </main>
    </div>
  </div>
</template>
