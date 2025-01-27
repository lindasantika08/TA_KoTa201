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
        tahun_ajaran: "",
        nama_proyek: "",
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
    }
  },

  methods: {
    async fetchProjectScoreDetails() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('/api/project-score-details', {
          params: {
            tahun_ajaran: this.projectDetails.tahun_ajaran,
            nama_proyek: this.projectDetails.nama_proyek,
          }
        });
        
        if (response.data.status === 'success') {
          this.scoreData = response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch project details';
        console.error('Error fetching project details:', error);
      } finally {
        this.loading = false;
      }
    },

    calculateAnalysisScores() {
      if (!this.scoreData) return [];

      const analysisScores = [];
      const selfAssessments = this.scoreData.self_assessment || [];
      const peerAssessments = this.scoreData.peer_assessment || [];

      const peerScoresMap = new Map();
      peerAssessments.forEach(peer => {
        peerScoresMap.set(`${peer.aspek}_${peer.kriteria}`, peer.total_score);
      });

      selfAssessments.forEach(self => {
        const peerScore = peerScoresMap.get(`${self.aspek}_${self.kriteria}`) || 0;
        const selfScore = self.total_score || 0;
        const difference = parseFloat((selfScore - peerScore).toFixed(2));

        let status = 'Match';
        if (difference > 0) status = 'Over';
        else if (difference < 0) status = 'Under';

        analysisScores.push({
          aspek: self.aspek,
          kriteria: self.kriteria,
          selfScore: selfScore.toFixed(2),
          averagePeerScore: peerScore.toFixed(2),
          scoreDifference: difference,
          status: status
        });
      });

      return analysisScores;
    },
  },

  mounted() {
    const urlParams = new URLSearchParams(window.location.search);
    this.projectDetails.tahun_ajaran = urlParams.get("tahun_ajaran");
    this.projectDetails.nama_proyek = urlParams.get("nama_proyek");
    
    if (this.projectDetails.tahun_ajaran && this.projectDetails.nama_proyek) {
      this.fetchProjectScoreDetails();
    }
  },
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
              <p class="text-gray-600">Loading project details...</p>
            </div>
  
            <!-- Error State -->
            <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-4">
              <p class="text-red-600">{{ error }}</p>
            </div>
  
            <!-- Score Content -->
            <div v-else-if="scoreData" class="space-y-6">
              <!-- Enhanced User Info Card -->
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
  
                    <!-- Academic Year -->
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 hover:border-blue-200 transition-colors duration-200">
                      <h3 class="text-sm font-medium text-gray-500 mb-2 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Academic Year
                      </h3>
                      <p class="text-lg font-semibold text-gray-900">{{ projectDetails.tahun_ajaran }}</p>
                    </div>
  
                    <!-- Project Name -->
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 hover:border-blue-200 transition-colors duration-200">
                      <h3 class="text-sm font-medium text-gray-500 mb-2 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Project Name
                      </h3>
                      <p class="text-lg font-semibold text-gray-900">{{ projectDetails.nama_proyek }}</p>
                    </div>
  
                    <!-- Group -->
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 hover:border-blue-200 transition-colors duration-200">
                      <h3 class="text-sm font-medium text-gray-500 mb-2 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Group
                      </h3>
                      <p class="text-lg font-semibold text-gray-900">{{ scoreData.kelompok }}</p>
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

            <!-- Score Details Section -->
            <div class="mt-6 space-y-8">
              <div v-for="assessment in scoreData.self_assessment" 
                   :key="assessment.aspek + assessment.kriteria"
                   class="bg-gray-50 rounded-lg p-4">
                <h3 class="font-medium text-gray-900 mb-3">
                  {{ assessment.aspek }} - {{ assessment.kriteria }}
                </h3>
                <div class="space-y-4">
                  <div v-for="question in assessment.questions" 
                       :key="question.question_id"
                       class="border-b border-gray-200 pb-4">
                    <p class="text-sm text-gray-600 mb-2">{{ question.pertanyaan }}</p>
                    <div class="flex justify-between">
                      <span class="text-sm font-medium">Score: {{ question.score || 'N/A' }}</span>
                      <span class="text-sm text-gray-500">
                        Answer: {{ question.answer || 'No answer provided' }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Analysis Score Card -->
            <Card title="Score Analysis" class="bg-white shadow-lg">
              <div class="p-6">
                <div class="overflow-x-auto">
                  <table class="w-full border-collapse">
                    <thead class="bg-gray-100">
                      <tr>
                        <th class="border p-2 text-left">Aspek</th>
                        <th class="border p-2 text-left">Kriteria</th>
                        <th class="border p-2 text-right">Skor Total (Self)</th>
                        <th class="border p-2 text-right">Average Skor (Peer)</th>
                        <th class="border p-2 text-right">Selisih Skor</th>
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