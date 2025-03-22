<script>
import axios from "axios";
import SidebarMahasiswa from "@/Components/SidebarMahasiswa.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import dayjs from 'dayjs';
import { router } from '@inertiajs/vue3';

export default {
  name: "MahasiswaReport",
  components: {
    SidebarMahasiswa,
    Navbar,
    Card,
    Breadcrumb,
  },

  data() {
    return {
      breadcrumbs: [
        { text: "Dashboard", href: "/sispa/mahasiswa/dashboard" },
        { text: "Report", href: null },
      ],
      projectList: [],
      error: null,
      loading: false,
      stats: {
        totalProjects: 0,
        activeProjects: 0,
        completedProjects: 0
      }
    };
  },

  methods: {
    async fetchStudentProjects() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get("/sispa/api/mahasiswa/projects");
        console.log('Projects API response:', response.data);
        
        if (response.data.success) {
          this.projectList = response.data.projects || [];
          await this.fetchAssessmentStatuses();
          this.updateStats();
        } else {
          this.error = response.data.message || 'Failed to fetch projects';
          console.error(this.error);
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'An error occurred while fetching projects';
        console.error("Error fetching project data:", error);
      } finally {
        this.loading = false;
      }
    },

    async fetchAssessmentStatuses() {
      try {
        const response = await axios.get("/sispa/api/mahasiswa/feedback");
        console.log('Feedback API response:', response.data);
        
        if (response.data.status === 'success' && response.data.projects) {
          const assessmentStatuses = response.data.projects;
          
          console.log('Assessment Statuses:', assessmentStatuses);
          
          this.projectList = this.projectList.map(project => {
            // Find matching assessment using project_name and group_name
            const assessment = assessmentStatuses.find(
              assess => assess.project_name === project.nama_proyek && 
                       assess.group_name === project.nama_kelompok
            );
            
            console.log(`Looking for project: ${project.nama_proyek}, group: ${project.nama_kelompok}`);
            console.log('Found assessment:', assessment);
            
            // If assessment is found, use its status, otherwise default to pending
            const isAssessmentCompleted = assessment ? 
              (assessment.selfAssessmentStatus === 'COMPLETED' && 
               assessment.peerAssessmentStatus === 'COMPLETED') : false;
            
            return {
              ...project,
              selfAssessmentStatus: assessment?.selfAssessmentStatus || 'PENDING',
              peerAssessmentStatus: assessment?.peerAssessmentStatus || 'PENDING',
              isAssessmentCompleted,
              assessmentDetails: assessment || null
            };
          });

          console.log('Updated Project List:', this.projectList);
        } else {
          console.error("Failed to fetch assessment statuses:", response.data.message);
        }
      } catch (error) {
        console.error("Error fetching assessment statuses:", error);
      }
    },

    updateStats() {
      this.stats.totalProjects = this.projectList.length;
      this.stats.activeProjects = this.projectList.filter(p => 
        p.status?.toLowerCase() === 'active').length;
      this.stats.completedProjects = this.projectList.filter(p => 
        p.status?.toLowerCase() === 'nonactive').length;
    },

    handleProjectDetail(project) {
      console.log('Handling project:', {
        nama_proyek: project.nama_proyek,
        nama_kelompok: project.nama_kelompok,
        assessmentDetails: project.assessmentDetails,
        isAssessmentCompleted: project.isAssessmentCompleted
      });

      if (!project.isAssessmentCompleted) {
        alert("Assessments are not completed yet.");
        return;
      }

      router.get(
        "/mahasiswa/feedback-details",
        {
          tahun_ajaran: project.tahun_ajaran,
          nama_proyek: project.nama_proyek,
          kelompok: project.nama_kelompok,
        },
        {
          preserveState: true,
          preserveScroll: true,
        }
      );
    },

    getStatusColor(status) {
      const normalizedStatus = status?.toLowerCase() || '';
      return normalizedStatus === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
    },

    getAssessmentStatusColor(status) {
      return status === 'COMPLETED' ? 'text-green-600' : 'text-yellow-600';
    }
  },

  mounted() {
    this.fetchStudentProjects();
  }
};
</script>

<template>
  <div class="flex min-h-screen bg-gray-50">
    <SidebarMahasiswa role="mahasiswa" />

    <div class="flex-1">
      <Navbar userName="Mahasiswa" />
      <main class="p-6">
        <div class="mb-6">
          <Breadcrumb :items="breadcrumbs" />
          <h1 class="text-2xl font-bold text-gray-800 mt-4">Feedback Report Dashboard</h1>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
              <div class="ml-4">
                <p class="text-sm text-gray-600">Total Projects</p>
                <p class="text-2xl font-bold text-gray-800">{{ stats.totalProjects }}</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div class="ml-4">
                <p class="text-sm text-gray-600">Active Projects</p>
                <p class="text-2xl font-bold text-gray-800">{{ stats.activeProjects }}</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <div class="ml-4">
                <p class="text-sm text-gray-600">Completed Projects</p>
                <p class="text-2xl font-bold text-gray-800">{{ stats.completedProjects }}</p>
              </div>
            </div>
          </div>
        </div>
        
        <Card title="My Projects" class="bg-white shadow-lg">
          <!-- Loading State -->
          <div v-if="loading" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="bg-red-50 text-red-800 p-4 rounded-lg text-center">
            {{ error }}
          </div>

          <!-- Empty State -->
          <div v-else-if="projectList.length === 0" class="text-center py-12">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <p class="text-gray-500 text-lg">Anda belum terdaftar dalam proyek apapun.</p>
          </div>

          <!-- Project List -->
          <div v-else class="grid gap-6">
            <div
              v-for="project in projectList"
              :key="project.id"
              @click="handleProjectDetail(project)"
              :class="[
                'bg-white border rounded-lg p-6 transition-all duration-200',
                project.isAssessmentCompleted ? 'hover:shadow-lg cursor-pointer' : 'opacity-75 cursor-not-allowed'
              ]"
            >
              <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="space-y-2">
                  <h3 class="text-xl font-semibold text-gray-800">{{ project.nama_proyek }}</h3>
                  <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                    <div class="flex items-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      <span>{{ project.tahun_ajaran }}</span>
                    </div>
                    <div class="flex items-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                      </svg>
                      <span>{{ project.nama_kelompok }}</span>
                    </div>
                  </div>
                  <!-- Assessment Status -->
                  <div class="flex flex-wrap gap-4 text-sm mt-2">
                    <span :class="[
                      'px-2 py-1 rounded-full font-medium',
                      getAssessmentStatusColor(project.selfAssessmentStatus)
                    ]">
                      Self Assessment: {{ project.selfAssessmentStatus }}
                    </span>
                    <span :class="[
                      'px-2 py-1 rounded-full font-medium',
                      getAssessmentStatusColor(project.peerAssessmentStatus)
                    ]">
                      Peer Assessment: {{ project.peerAssessmentStatus }}
                    </span>
                  </div>
                </div>
                <div class="mt-4 md:mt-0">
                  <span :class="[
                    'px-3 py-1 rounded-full text-sm font-medium',
                    getStatusColor(project.status)
                  ]">
                    {{ project.status }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </Card>
      </main>
    </div>
  </div>
</template>

<style scoped>
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>