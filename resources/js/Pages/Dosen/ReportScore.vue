<script>
import axios from "axios";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import ApexChart from 'apexcharts';
import VueApexCharts from 'vue3-apexcharts';

export default {
  props: {
    tahunAjaran: {
      type: String,
      required: true,
    },
    namaProyek: {
      type: String,
      required: true,
    },
    kelompok: {
      type: String,
      required: true,
    },
  },
  components: {
    Sidebar,
    Navbar,
    Card,
    Breadcrumb,
    ApexChart: VueApexCharts,
  },
  data() {
    return {
      breadcrumbs: [{ text: "Laporan", href: "/dosen/laporan" }],
      userAnalysis: {},
      loading: false,
      error: null,
      questions: {},
      peerQuestions: {},
      selectedUserId: null,
    };
  },
  computed: {
    userIds() {
      return Object.keys(this.userAnalysis);
    },
    selectedUserData() {
      return this.selectedUserId ? this.userAnalysis[this.selectedUserId] : null;
    },
  },
  mounted() {
    if (this.tahunAjaran && this.namaProyek && this.kelompok) {
      this.fetchQuestions();
      this.fetchPeerQuestions();
      this.fetchKelompokAnalysis();
    } else {
      this.error = "Tahun Ajaran, Nama Proyek, atau Kelompok tidak valid";
    }
  },
  methods: {
    async fetchKelompokAnalysis() {
      this.loading = true;
      this.error = null;

      if (this.tahunAjaran && this.namaProyek && this.kelompok) {
        try {
          const response = await axios.get("/api/report/kelompok/answers", {
            params: {
              tahun_ajaran: this.tahunAjaran,
              nama_proyek: this.namaProyek,
              kelompok: this.kelompok,
            },
          });
          this.userAnalysis = response.data;
        } catch (error) {
          console.error("Gagal mengambil analisis:", error);
          this.error = "Gagal memuat data";
        } finally {
          this.loading = false;
        }
      } else {
        this.error = "Tahun Ajaran, Nama Proyek, atau Kelompok tidak valid";
        this.loading = false;
      }
    },
    async fetchQuestions() {
      try {
        const response = await axios.get("/api/questions");
        this.questions = response.data;
      } catch (error) {
        console.error("Gagal memuat pertanyaan:", error);
      }
    },
    async fetchPeerQuestions() {
      try {
        const response = await axios.get("/api/questions-peer", {
          params: {
            tahun_ajaran: this.tahunAjaran,
            nama_proyek: this.namaProyek,
          },
        });
        this.peerQuestions = response.data.reduce((acc, question) => {
          acc[question.id] = question.pertanyaan;
          return acc;
        }, {});
      } catch (error) {
        console.error("Gagal memuat pertanyaan peer:", error);
      }
    },
    getQuestionText(questionId) {
      return this.questions[questionId] || "Pertanyaan tidak ditemukan";
    },
    getPeerQuestionText(questionId) {
      return this.peerQuestions[questionId] || "Pertanyaan tidak ditemukan";
    },
    calculateTotalAverage(userId) {
      const userData = this.userAnalysis[userId];
      if (
        !userData ||
        !userData.evaluated_by_peers ||
        !userData.evaluated_by_peers.length
      )
        return "N/A";

      const totalScores = userData.evaluated_by_peers.map(
        (group) => group.total_score || 0
      );
      const averageTotal =
        totalScores.reduce((sum, score) => sum + score, 0) / totalScores.length;

      return averageTotal.toFixed(2);
    },
    calculateAverageSelfScores() {
      const userIds = Object.keys(this.userAnalysis);
      
      const allAspects = userIds.flatMap(userId => 
        this.userAnalysis[userId].self_assessment || []
      );

      const aspectGroups = allAspects.reduce((acc, aspect) => {
        const key = `${aspect.aspek}-${aspect.kriteria}`;
        if (!acc[key]) {
          acc[key] = { 
            aspek: aspect.aspek, 
            kriteria: aspect.kriteria, 
            scores: [] 
          };
        }
        if (aspect.total_score) {
          acc[key].scores.push(aspect.total_score);
        }
        return acc;
      }, {});

      return Object.values(aspectGroups).map(group => ({
        aspek: group.aspek,
        kriteria: group.kriteria,
        averageScore: group.scores.length > 0 
          ? (group.scores.reduce((sum, score) => sum + score, 0) / group.scores.length).toFixed(2)
          : "0.00"
      }));
    },
    groupPeerEvaluations(evaluatedByPeers) {
      if (!evaluatedByPeers || !Array.isArray(evaluatedByPeers)) {
        return [];
      }

      return evaluatedByPeers.map((group) => {
        const evaluatorDetails = Object.values(group.evaluated_by || {})
          .flatMap(evaluator => {
            if (evaluator.evaluated_by) {
              return Object.values(evaluator.evaluated_by);
            }
            return [evaluator];
          });

        const names = evaluatorDetails.map((evaluator) => evaluator.name);

        const processedAnswers = evaluatorDetails.flatMap((evaluator) => 
          evaluator.answers.map((answer) => ({
            ...answer,
            evaluator_name: evaluator.name,
            pertanyaan: this.getPeerQuestionText(answer.question_id)
          }))
        );

        return {
          aspek: group.aspek,
          kriteria: group.kriteria,
          names: names,
          total_score: group.total_score,
          answers: processedAnswers,
        };
      });
    },
    calculateAnalysisScores(userData) {
      if (!userData.self_assessment || !userData.evaluated_by_peers) return [];

      const analysisScores = userData.self_assessment.map(selfAspect => {
        const peerEvaluations = userData.evaluated_by_peers.filter(
          peer => peer.aspek === selfAspect.aspek
        );
        
        const averagePeerScore = peerEvaluations.length > 0
          ? peerEvaluations.reduce((sum, peer) => sum + (peer.total_score || 0), 0) / peerEvaluations.length
          : 0;

        const selfScore = selfAspect.total_score || 0;
        const scoreDifference = selfScore - averagePeerScore;

        let status;
        if (scoreDifference > 0) status = 'Over';
        else if (scoreDifference < 0) status = 'Under';
        else status = 'Match';

        return {
          aspek: selfAspect.aspek,
          kriteria: selfAspect.kriteria,
          selfScore: selfScore.toFixed(2),
          averagePeerScore: averagePeerScore.toFixed(2),
          scoreDifference: scoreDifference.toFixed(2),
          status: status
        };
      });

      return analysisScores;
    },
    preparePeerComparisonChartData(userData) {
      const analysisScores = this.calculateAnalysisScores(userData);
      
      return {
        series: [
          {
            name: 'Skor Sendiri',
            data: analysisScores.map(score => parseFloat(score.selfScore))
          },
          {
            name: 'Rata-rata Peer',
            data: analysisScores.map(score => parseFloat(score.averagePeerScore))
          }
        ],
        options: {
          chart: {
            type: 'radar',
            height: 350
          },
          labels: analysisScores.map(score => `${score.aspek} - ${score.kriteria}`),
          plotOptions: {
            radar: {
              polygons: {
                strokeColors: '#e8e8e8',
                fill: {
                  colors: ['#f7f7f7', '#fff']
                }
              }
            }
          },
          colors: ['#FF4560', '#00E396'],
          markers: {
            size: 4,
            colors: ['#FF4560', '#00E396'],
            strokeColors: '#fff',
            strokeWidth: 2,
            hover: {
              size: 7
            }
          },
          tooltip: {
            y: {
              formatter: function (val) {
                return val.toFixed(2)
              }
            }
          },
          yaxis: {
            show: false,
            min: 0,
            max: 5
          },
          xaxis: {
            show: true
          },
          fill: {
            opacity: 0.4
          },
          title: {
            text: 'Perbandingan Skor',
            align: 'center'
          },
          legend: {
            position: 'bottom',
            horizontalAlign: 'center'
          },
        }
      };
    },
    prepareSelfComparisonChartData(userData) {
      const selfScores = userData.self_assessment 
        ? userData.self_assessment.map(aspect => parseFloat(aspect.total_score || 0).toFixed(2))
        : [];
      
      const averageSelfScores = this.calculateAverageSelfScores().map(score => parseFloat(score.averageScore));

      const labels = userData.self_assessment 
        ? userData.self_assessment.map(aspect => `${aspect.aspek} - ${aspect.kriteria}`)
        : [];

      return {
        series: [
          {
            name: 'Skor Sendiri',
            data: selfScores
          },
          {
            name: 'Rata-rata Self Kelompok',
            data: averageSelfScores
          }
        ],
        options: {
          chart: {
            type: 'radar',
            height: 350
          },
          labels: labels,
          plotOptions: {
            radar: {
              polygons: {
                strokeColors: '#e8e8e8',
                fill: {
                  colors: ['#f7f7f7', '#fff']
                }
              }
            }
          },
          colors: ['#FF4560', '#3B82F6'],
          markers: {
            size: 4,
            colors: ['#FF4560', '#3B82F6'],
            strokeColors: '#fff',
            strokeWidth: 2,
            hover: {
              size: 7
            }
          },
          tooltip: {
            y: {
              formatter: function (val) {
                return val.toFixed(2)
              }
            }
          },
          yaxis: {
            show: false,
            min: 0,
            max: 5
          },
          xaxis: {
            show: true
          },
          fill: {
            opacity: 0.4
          },
          title: {
            text: 'Perbandingan Skor Self Assessment',
            align: 'center'
          },
          legend: {
            position: 'bottom',
            horizontalAlign: 'center'
          },
        }
      };
    }
  }
}
</script>

<template>
  <div class="flex min-h-screen">
    <Sidebar role="dosen" />
    <div class="flex-1">
      <Navbar userName="Dosen" />
      <main class="p-6">
        <div class="mb-4">
          <Breadcrumb :items="breadcrumbs" />
        </div>
        
        <div class="grid grid-cols-2 gap-4">
          <Card title="Detail Kelompok">
            <div v-if="tahunAjaran && namaProyek && kelompok">
              <p><strong>Tahun Ajaran:</strong> {{ tahunAjaran }}</p>
              <p><strong>Nama Proyek:</strong> {{ namaProyek }}</p>
              <p><strong>Kelompok:</strong> {{ kelompok }}</p>
            </div>
            <div v-else>
              <p>Tidak ada data yang tersedia</p>
            </div>
          </Card>

          <Card title="Pilih Peserta">
            <select 
              v-model="selectedUserId" 
              class="w-full p-2 border rounded"
            >
              <option value="" disabled>Pilih Peserta</option>
              <option 
                v-for="(userData, userId) in userAnalysis" 
                :key="userId" 
                :value="userId"
              >
                {{ userData.name }}
              </option>
            </select>
          </Card>

          <Card v-if="selectedUserData" title="Perbandingan Skor Peer" class="col-span-2">
            <ApexChart 
              type="radar"
              height="350"
              :series="preparePeerComparisonChartData(selectedUserData).series"
              :options="preparePeerComparisonChartData(selectedUserData).options"
            />
          </Card>

          <Card v-if="selectedUserData" title="Perbandingan Skor Self Assessment" class="col-span-2">
            <ApexChart 
              type="radar"
              height="350"
              :series="prepareSelfComparisonChartData(selectedUserData).series"
              :options="prepareSelfComparisonChartData(selectedUserData).options"
            />
          </Card>
        </div>
        
        <div v-if="loading" class="text-center mt-4">Memuat...</div>
        <div v-else-if="error" class="text-red-500 mt-4">{{ error }}</div>
        <div v-else-if="selectedUserData">
          <Card 
            :title="`Analisis Jawaban - ${selectedUserData.name}`" 
            class="mt-4"
          >
            <!-- Self Assessment Section -->
            <div v-if="selectedUserData.self_assessment && selectedUserData.self_assessment.length">
              <h3 class="font-bold mb-2">Self Assessment</h3>
              <table class="w-full border-collapse">
                <thead>
                  <tr class="bg-gray-200">
                    <th class="border p-2">Aspek</th>
                    <th class="border p-2">Kriteria</th>
                    <th class="border p-2">Skor Total</th>
                    <th class="border p-2">Detail Pertanyaan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(aspek, index) in selectedUserData.self_assessment" :key="index">
                    <td class="border p-2">{{ aspek.aspek }}</td>
                    <td class="border p-2">{{ aspek.kriteria }}</td>
                    <td class="border p-2">
                      {{ aspek.total_score ? aspek.total_score.toFixed(2) : "N/A" }}
                    </td>
                    <td class="border p-2">
                      <table class="w-full">
                        <thead>
                          <tr>
                            <th>Pertanyaan</th>
                            <th>Skor</th>
                            <th>Jawaban</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="pertanyaan in aspek.questions" :key="pertanyaan.question_id">
                            <td>{{ pertanyaan.pertanyaan }}</td>
                            <td>{{ pertanyaan.score || "N/A" }}</td>
                            <td>{{ pertanyaan.answer || "-" }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="text-center">Tidak ada data analisis</p>

            <!-- Peer Evaluation Section -->
            <div class="mt-4">
              <div class="flex items-center mb-2">
                <h3 class="font-bold mr-2">Evaluasi dari Peer</h3>
                <span class="text-gray-600">
                  (Total Average: {{ calculateTotalAverage(selectedUserId) }})
                </span>
              </div>
              
              <div v-if="selectedUserData.evaluated_by_peers && selectedUserData.evaluated_by_peers.length">
                <template 
                  v-for="(peerGroup, index) in groupPeerEvaluations(selectedUserData.evaluated_by_peers)" 
                  :key="index"
                >
                  <div class="bg-white shadow rounded-lg overflow-hidden mb-4">
                    <div class="bg-gray-100 p-3 flex justify-between items-center">
                      <div>
                        <p class="font-semibold text-gray-700">
                          <span class="font-bold">Aspek:</span> {{ peerGroup.aspek }}
                        </p>
                        <p class="text-gray-600">
                          <span class="font-bold">Kriteria:</span> {{ peerGroup.kriteria }}
                        </p>
                      </div>
                      <div class="text-right">
                        <p class="text-sm text-gray-600">
                          <span class="font-bold">Total Skor:</span> 
                          {{ peerGroup.total_score ? peerGroup.total_score.toFixed(2) : 'N/A' }}
                        </p>
                        <p class="text-sm text-gray-500">
                          Dievaluasi Oleh: {{ peerGroup.names.join(", ") }}
                        </p>
                      </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                      <table class="w-full">
                        <thead class="bg-gray-200">
                          <tr>
                            <th class="p-3 text-left">Penilai</th>
                            <th class="p-3 text-left">Pertanyaan</th>
                            <th class="p-3 text-center">Skor</th>
                            <th class="p-3 text-left">Jawaban</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr
                            v-for="(answer, idx) in peerGroup.answers"
                            :key="idx"
                            class="border-b hover:bg-gray-50 transition-colors"
                          >
                            <td class="p-3 text-sm">
                              <span class="font-medium text-gray-700">
                                {{ answer.evaluator_name }}
                              </span>
                            </td>
                            <td class="p-3 text-sm text-gray-600">
                              {{ answer.pertanyaan }}
                            </td>
                            <td class="p-3 text-center">
                              <span 
                                :class="[
                                  'px-2 py-1 rounded text-xs font-semibold',
                                  answer.score >= 4 ? 'bg-green-100 text-green-800' : 
                                  answer.score >= 2 ? 'bg-yellow-100 text-yellow-800' : 
                                  'bg-red-100 text-red-800'
                                ]"
                              >
                                {{ answer.score || 'N/A' }}
                              </span>
                            </td>
                            <td class="p-3 text-sm text-gray-700">
                              {{ answer.answer || '-' }}
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </template>
              </div>
              <p v-else class="text-center text-gray-500">Tidak ada data evaluasi peer</p>
            </div>

            <!-- Analysis Score Section -->
            <div class="mt-4">
              <h3 class="font-bold mb-2">Analysis Score</h3>
              <table class="w-full border-collapse">
                <thead class="bg-gray-200">
                  <tr>
                    <th class="border p-2">Aspek</th>
                    <th class="border p-2">Kriteria</th>
                    <th class="border p-2">Skor Total (Self)</th>
                    <th class="border p-2">Average Skor (Peer)</th>
                    <th class="border p-2">Selisih Skor</th>
                    <th class="border p-2">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr 
                    v-for="(score, index) in calculateAnalysisScores(selectedUserData)" 
                    :key="index"
                    :class="{
                      'bg-green-100': score.status === 'Over',
                      'bg-yellow-100': score.status === 'Match',
                      'bg-red-100': score.status === 'Under'
                    }"
                  >
                    <td class="border p-2">{{ score.aspek }}</td>
                    <td class="border p-2">{{ score.kriteria }}</td>
                    <td class="border p-2">{{ score.selfScore }}</td>
                    <td class="border p-2">{{ score.averagePeerScore }}</td>
                    <td class="border p-2">{{ score.scoreDifference }}</td>
                    <td class="border p-2 font-bold">{{ score.status }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </Card>
        </div>
        <div v-else class="text-center text-gray-500 mt-4">
          Pilih peserta untuk melihat detail
        </div>
      </main>
    </div>
  </div>
</template>


<style scoped>
.text-center {
  text-align: center;
}
.text-red-500 {
  color: #f56565;
}
.bg-green-100 {
  background-color: #f0fff4;
}
.bg-yellow-100 {
  background-color: #fffff0;
}
.bg-red-100 {
  background-color: #fff5f5;
}
.border {
  border: 1px solid #e2e8f0;
}
.hover\:bg-gray-50:hover {
  background-color: #f9fafb;
}
</style>