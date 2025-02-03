<script>
import SidebarMahasiswa from "@/Components/SidebarMahasiswa.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import axios from "axios";
import VueApexCharts from 'vue3-apexcharts';

export default {
  name: "ReportScoreMahasiswa",
  components: {
    SidebarMahasiswa,
    Navbar,
    Card,
    Breadcrumb,
    apexchart: VueApexCharts,
  },

  data() {
    return {
      breadcrumbs: [
        { text: "Report", href: "/mahasiswa/project/report" },
        { text: "Detail Score", href: null },
      ],
      projectDetails: {
        batch_year: "",
        project_id: "",
        nama_kelompok: "",
      },
      scoreData: null,
      loading: false,
      error: null,
    };
  },

  computed: {
    chartOptions() {
      const analysisScores = this.calculateAnalysisScores();
      
      return {
        chart: {
          type: 'radar',
          height: 400,
        },
        series: [
          {
            name: 'Self Score',
            data: analysisScores.map(score => parseFloat(score.selfScore))
          },
          {
            name: 'Peer Average',
            data: analysisScores.map(score => parseFloat(score.averagePeerScore))
          }
        ],
        options: {
          chart: {
            type: 'radar',
          },
          labels: analysisScores.map(score => `${score.aspek} - ${score.kriteria}`),
          colors: ['#FF4560', '#00E396'],
          markers: {
            size: 4,
            colors: ['#FF4560', '#00E396'],
            strokeColor: '#fff',
            strokeWidth: 2,
          },
          tooltip: {
            y: {
              formatter: (val) => val.toFixed(2)
            }
          },
          plotOptions: {
            radar: {
              size: 140,
              polygons: {
                strokeColors: '#e9e9e9',
                fill: {
                  colors: ['#f8f8f8', '#fff']
                }
              }
            }
          },
          title: {
            text: 'Score Comparison',
            align: 'center',
          },
          yaxis: {
            min: 0,
            max: 5,
            tickAmount: 5
          },
          legend: {
            position: 'bottom',
            horizontalAlign: 'center',
          }
        }
      };
    },

    hasRequiredParams() {
      return Boolean(this.projectDetails.batch_year && this.projectDetails.project_id);
    }
  },

  methods: {
    async fetchProjectScoreDetails() {
      if (!this.hasRequiredParams) {
        this.error = 'Missing required parameters (batch_year or project_id)';
        return;
      }

      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('/api/project-score-details', {
          params: {
            batch_year: this.projectDetails.batch_year,
            project_id: this.projectDetails.project_id,
          },
          headers: {
            'Accept': 'application/json',
          }
        });

        if (response.data?.status === 'success' && response.data?.data) {
          this.scoreData = response.data.data;
          console.log('Score Data Set:', this.scoreData);
        } else {
          throw new Error('Invalid response format from API');
        }

      } catch (error) {
        console.error('API Error:', error);
        if (error.response) {
          // Error response from server
          this.error = error.response.data.message || 'Server error occurred';
        } else if (error.request) {
          // Request made but no response
          this.error = 'No response from server. Please check your connection.';
        } else {
          // Error in request setup
          this.error = error.message || 'Failed to fetch project details';
        }
      } finally {
        this.loading = false;
      }
    },

    calculateAnalysisScores() {
      if (!this.scoreData) return [];

      const analysisScores = [];
      const selfAssessments = this.scoreData.self_assessment || [];
      const peerAssessments = this.scoreData.peer_assessment || [];

      // Create map of peer scores
      const peerScoresMap = new Map();
      peerAssessments.forEach(peer => {
        const key = `${peer.aspek}_${peer.kriteria}`;
        peerScoresMap.set(key, peer.total_score);
      });

      // Process self assessments
      selfAssessments.forEach(self => {
        const key = `${self.aspek}_${self.kriteria}`;
        const peerScore = peerScoresMap.get(key) || 0;
        const selfScore = self.total_score || 0;
        const difference = parseFloat((selfScore - peerScore).toFixed(2));

        let status = 'Match';
        if (difference > 0.5) status = 'Over';
        else if (difference < -0.5) status = 'Under';

        analysisScores.push({
          aspek: self.aspek,
          kriteria: self.kriteria,
          selfScore: selfScore.toFixed(2),
          averagePeerScore: peerScore.toFixed(2),
          scoreDifference: difference,
          status: status,
          questions: self.questions
        });
      });

      return analysisScores;
    },

    retryFetch() {
      this.fetchProjectScoreDetails();
    }
  },

  mounted() {
    const urlParams = new URLSearchParams(window.location.search);
    this.projectDetails.batch_year = urlParams.get("batch_year");
    this.projectDetails.project_id = urlParams.get("project_id");
    
    if (this.hasRequiredParams) {
      this.fetchProjectScoreDetails();
    }
  },

  watch: {
    // Watch for changes in URL parameters
    '$route.query': {
      handler(newQuery) {
        this.projectDetails.batch_year = newQuery.batch_year;
        this.projectDetails.project_id = newQuery.project_id;
        
        if (this.hasRequiredParams) {
          this.fetchProjectScoreDetails();
        }
      },
      deep: true
    }
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
          <h1 class="text-2xl font-bold text-gray-800 mt-4">Project Score Details</h1>
        </div>

        <div class="space-y-6">
          <!-- Loading State -->
          <div v-if="loading" class="text-center py-4">
            <div class="animate-pulse">
              <div class="h-4 bg-gray-200 rounded w-3/4 mx-auto mb-4"></div>
              <div class="h-4 bg-gray-200 rounded w-1/2 mx-auto"></div>
            </div>
            <p class="text-gray-600 mt-4">Loading project details...</p>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex items-center">
              <svg class="h-5 w-5 text-red-400 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
              <p class="text-red-600">{{ error }}</p>
            </div>
            <button 
              @click="retryFetch"
              class="mt-3 px-4 py-2 bg-red-100 text-red-600 rounded-md hover:bg-red-200 transition-colors duration-200"
            >
              Retry
            </button>
          </div>

          <!-- Score Content -->
          <div v-else-if="scoreData" class="space-y-6">
            <!-- Student Information Card -->
            <Card title="Student Information" class="bg-white shadow-lg">
              <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <!-- Student Name -->
                  <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 hover:border-blue-200 transition-colors duration-200">
                    <h3 class="text-sm font-medium text-gray-500 mb-2 flex items-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                      Name
                    </h3>
                    <p class="text-lg font-semibold text-gray-900">{{ scoreData.name }}</p>
                  </div>

                  <!-- Batch Year -->
                  <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 hover:border-blue-200 transition-colors duration-200">
                    <h3 class="text-sm font-medium text-gray-500 mb-2 flex items-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      Batch Year
                    </h3>
                    <p class="text-lg font-semibold text-gray-900">{{ projectDetails.batch_year }}</p>
                  </div>

                  <!-- Project ID -->
                  <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 hover:border-blue-200 transition-colors duration-200">
                    <h3 class="text-sm font-medium text-gray-500 mb-2 flex items-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Project ID
                    </h3>
                    <p class="text-lg font-semibold text-gray-900">{{ projectDetails.project_id }}</p>
                  </div>
                </div>
              </div>
            </Card>

            <!-- Spider Web Chart -->
            <Card title="Score Comparison Chart" class="bg-white shadow-lg">
              <div class="p-6">
                <apexchart
                  v-if="scoreData && calculateAnalysisScores().length > 0"
                  type="radar"
                  height="400"
                  :options="chartOptions.options"
                  :series="chartOptions.series"
                />
                <p v-else class="text-center text-gray-500">No data available for chart</p>
              </div>
            </Card>

            <!-- Analysis Score Card -->
            <Card title="Score Analysis" class="bg-white shadow-lg">
              <div class="p-6">
                <div class="overflow-x-auto">
                  <table class="w-full border-collapse">
                    <thead class="bg-gray-100">
                      <tr>
                        <th class="border p-2 text-left">Aspek</th>
                        <th class="border p-2 text-left">Kriteria</th>
                        <th class="border p-2 text-right">Self Score</th>
                        <th class="border p-2 text-right">Peer Average</th>
                        <th class="border p-2 text-right">Difference</th>
                        <th class="border p-2 text-center">Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr 
                        v-for="score in calculateAnalysisScores()" 
                        :key="`${score.aspek}-${score.kriteria}`"
                        :class="{
                          'bg-green-50': score.status === 'Over',
                          'bg-yellow-50': score.status === 'Match',
                          'bg-red-50': score.status === 'Under'
                        }"
                      >
                        <td class="border p-2">{{ score.aspek }}</td>
                        <td class="border p-2">{{ score.kriteria }}</td>
                        <td class="border p-2 text-right">{{ score.selfScore }}</td>
                        <td class="border p-2 text-right">{{ score.averagePeerScore }}</td>
                        <td class="border p-2 text-right">{{ score.scoreDifference }}</td>
                        <td class="border p-2 text-center font-medium"
                            :class="{
                              'text-green-600': score.status === 'Over',
                              'text-yellow-600': score.status === 'Match',
                              'text-red-600': score.status === 'Under'
                            }"
                        >
                          {{ score.status }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </Card>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>