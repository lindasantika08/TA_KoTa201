<script setup>
import { ref, computed, onMounted } from "vue";
import SidebarMahasiswa from "@/Components/SidebarMahasiswa.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import VueApexCharts from "vue3-apexcharts";
import axios from "axios";

const props = defineProps({
  batchYear: {
    type: String,
    required: true,
  },
  projectId: {
    type: Number,
    required: true,
  },
  projectName: {
    // Add this prop
    type: String,
    required: true,
  },
  kelompok: {
    type: String,
    required: true,
  },
  userName: {
    type: String,
    required: true,
  },
});

// Reactive state
const breadcrumbs = ref([
  { text: "Report", href: "/sispa/mahasiswa/project/report" },
  { text: "Detail Score", href: null },
]);

const scoreData = ref(null);
const loading = ref(false);
const error = ref(null);
const activeTab = ref("overview");
const feedbackLoading = ref(false);
const feedbackError = ref(null);

// Computed properties
const projectScoreDetails = computed(() => scoreData.value);

const analysisScores = computed(() => {
  if (!scoreData.value?.self_assessment || !scoreData.value?.peer_assessment)
    return [];

  return scoreData.value.self_assessment.map((selfAspect) => {
    const peerEvaluations = scoreData.value.peer_assessment.filter(
      (peer) => peer.aspek === selfAspect.aspek
    );

    const averagePeerScore =
      peerEvaluations.length > 0
        ? peerEvaluations.reduce(
            (sum, peer) => sum + (peer.total_score || 0),
            0
          ) / peerEvaluations.length
        : 0;

    const selfScore = selfAspect.total_score || 0;
    const scoreDifference = selfScore - averagePeerScore;

    return {
      aspek: selfAspect.aspek,
      kriteria: selfAspect.kriteria,
      selfScore: selfScore.toFixed(2),
      averagePeerScore: averagePeerScore.toFixed(2),
      scoreDifference: scoreDifference.toFixed(2),
      status:
        scoreDifference > 0 ? "Over" : scoreDifference < 0 ? "Under" : "Match",
      questions: selfAspect.questions,
    };
  });
});

const radarChartOptions = computed(() => ({
  chart: {
    type: "radar",
    height: "100%",  // Reduced from 100% to make it smaller
    width: "100%",   // Reduced from 100% to make it smaller
    dropShadow: {
      enabled: true,
      blur: 1,
      left: 1,
      top: 1,
    },
    toolbar: {
      show: true,
    },
  },
  series: [
    {
      name: "Self Assessment",
      data: analysisScores.value.map((score) => parseFloat(score.selfScore)),
    },
    {
      name: "Peer Average",
      data: analysisScores.value.map((score) =>
        parseFloat(score.averagePeerScore)
      ),
    },
  ],
  labels: analysisScores.value.map((score) => score.aspek),
  colors: ["#2563EB", "#F97316"], // Changed to blue and orange
  stroke: {
    width: 2,  // Reduced from 3 to match smaller size
  },
  fill: {
    opacity: 0.4,
  },
  markers: {
    size: 6,
    hover: {
      size: 8,
    },
  },
  tooltip: {
    y: {
      formatter: (val) => val.toFixed(2),
    },
  },
  yaxis: {
    show: true,
    min: 0,
    max: 5,
    tickAmount: 5,
    labels: {
      formatter: (val) => val.toFixed(1),
      style: {
        fontSize: "14px",  // Reduced from 16px
      },
    },
  },
  xaxis: {
    labels: {
      style: {
        fontSize: "14px",  // Reduced from 16px
      },
    },
  },
  legend: {
    position: "bottom",
    horizontalAlign: "center",
    fontSize: "14px",  // Reduced from 16px
    markers: {
      width: 16,  // Reduced from 20
      height: 16,  // Reduced from 20
    },
    itemMargin: {
      horizontal: 15,  // Reduced from 20
    },
  },
}));

// Methods
const fetchProjectScoreDetails = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await axios.get("/sispa/api/project-score-details", {
      params: {
        batch_year: props.batchYear,
        project_id: props.projectId,
        kelompok: props.kelompok,
      },
    });

    if (response.data.status === "success") {
      scoreData.value = response.data.data;
    } else {
      error.value = response.data.message || "Failed to fetch project details";
    }
  } catch (err) {
    error.value =
      err.response?.data?.message || "Failed to fetch project details";
    console.error("Error fetching project details:", err);
  } finally {
    loading.value = false;
  }
};

// Calculate summary statistics
const calculateSummaryStats = computed(() => {
  if (!analysisScores.value.length) return null;

  const selfScores = analysisScores.value.map((score) =>
    parseFloat(score.selfScore)
  );
  const peerScores = analysisScores.value.map((score) =>
    parseFloat(score.averagePeerScore)
  );

  return {
    averageSelfScore: (
      selfScores.reduce((a, b) => a + b, 0) / selfScores.length
    ).toFixed(2),
    averagePeerScore: (
      peerScores.reduce((a, b) => a + b, 0) / peerScores.length
    ).toFixed(2),
    highestSelfScore: Math.max(...selfScores).toFixed(2),
    lowestSelfScore: Math.min(...selfScores).toFixed(2),
    highestPeerScore: Math.max(...peerScores).toFixed(2),
    lowestPeerScore: Math.min(...peerScores).toFixed(2),
  };
});


const feedback = ref({
  aiFeedback: null,
  lecturerFeedback: []
});

const fetchFeedback = async () => {
  feedbackLoading.value = true;
  feedbackError.value = null;

  try {
    const response = await axios.get('/sispa/api/project-feedback', {
      params: {
        batch_year: props.batchYear,
        project_name: props.projectName
      }
    });

    if (response.data.status === 'success') {
      feedback.value = response.data.data;
    } else {
      feedbackError.value = response.data.message || 'Failed to fetch feedback';
    }
  } catch (err) {
    console.error('Error details:', err.response?.data);
    feedbackError.value = err.response?.data?.message || 'Failed to fetch feedback';
  } finally {
    feedbackLoading.value = false;
  }
};

// Lifecycle hooks
onMounted(() => {
  fetchProjectScoreDetails();
  fetchFeedback();
});
</script>

<template>
  <div class="flex min-h-screen bg-gray-50">
    <SidebarMahasiswa role="mahasiswa" />

    <div class="flex-1">
      <Navbar :userName="userName" />

      <div class="p-6 space-y-6">
        <Breadcrumb :items="breadcrumbs" />

        <!-- Header Section -->
        <div class="flex justify-between items-center">
          <h1 class="text-3xl font-bold text-gray-800">
            Project Score Dashboard
          </h1>
          <div class="text-sm text-gray-500">
            Last updated: {{ new Date().toLocaleDateString() }}
          </div>
        </div>

        <!-- Loading and Error States -->
        <div v-if="loading" class="flex justify-center p-8">
          <div
            class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"
          ></div>
        </div>

        <div v-if="error" class="bg-red-50 border-l-4 border-red-500 p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg
                class="h-5 w-5 text-red-400"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-red-700">{{ error }}</p>
            </div>
          </div>
        </div>

        <div v-if="projectScoreDetails && !loading" class="space-y-6">
          <!-- Project Overview Card -->
          <Card class="bg-white shadow-lg">
            <template #title>
              <div class="flex items-center space-x-2">
                <svg
                  class="w-5 h-5 text-gray-500"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                  />
                </svg>
                <span>Project Overview</span>
              </div>
            </template>

            <div
              class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 p-4"
            >
              <div class="p-4 bg-gray-50 rounded-lg">
                <div class="text-sm font-medium text-gray-500">
                  Student Name
                </div>
                <div class="mt-1 text-lg font-semibold">{{ userName }}</div>
              </div>
              <div class="p-4 bg-gray-50 rounded-lg">
                <div class="text-sm font-medium text-gray-500">
                  Academic Year
                </div>
                <div class="mt-1 text-lg font-semibold">{{ batchYear }}</div>
              </div>
              <div class="p-4 bg-gray-50 rounded-lg">
                <div class="text-sm font-medium text-gray-500">
                  Project Name
                </div>
                <div class="mt-1 text-lg font-semibold">
                  {{ projectName }}
                </div>
              </div>
              <div class="p-4 bg-gray-50 rounded-lg">
                <div class="text-sm font-medium text-gray-500">Group</div>
                <div class="mt-1 text-lg font-semibold">
                  {{ projectScoreDetails.kelompok }}
                </div>
              </div>
            </div>
          </Card>

          <!-- Navigation Tabs -->
          <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
<!-- Add to navigation tabs -->
<button
  v-for="tab in ['overview', 'details', 'analysis', 'feedback']"
  :key="tab"
  @click="activeTab = tab"
  :class="[
    activeTab === tab
      ? 'border-indigo-500 text-indigo-600'
      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
    'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm',
  ]"
>
  {{ tab.charAt(0).toUpperCase() + tab.slice(1) }}
</button>
            </nav>
          </div>

     <!-- Tab Contents -->
  <div v-if="activeTab === 'overview'" class="space-y-6">
    <!-- Radar Chart -->
    <Card class="bg-white">
      <template #title>Score Comparison</template>
      <div class="p-4 w-full aspect-square max-w-[1000px] mx-auto">
        <VueApexCharts
          type="radar"
          :height="'100%'"
          :options="radarChartOptions"
          :series="radarChartOptions.series"
        />
      </div>
    </Card>
  </div>

          <div v-if="activeTab === 'details'" class="space-y-6">
            <!-- Detailed Scores -->
            <Card
              v-for="score in analysisScores"
              :key="score.aspek"
              class="bg-white"
            >
              <template #title
                >{{ score.aspek }} - {{ score.kriteria }}</template
              >
              <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                  <div class="p-3 bg-indigo-50 rounded-lg">
                    <div class="text-sm text-indigo-600 font-medium">
                      Self Score
                    </div>
                    <div class="text-2xl font-bold text-indigo-700">
                      {{ score.selfScore }}
                    </div>
                  </div>
                  <div class="p-3 bg-emerald-50 rounded-lg">
                    <div class="text-sm text-emerald-600 font-medium">
                      Peer Average
                    </div>
                    <div class="text-2xl font-bold text-emerald-700">
                      {{ score.averagePeerScore }}
                    </div>
                  </div>
                  <div
                    class="p-3 rounded-lg"
                    :class="{
                      'bg-green-50': score.status === 'Over',
                      'bg-yellow-50': score.status === 'Match',
                      'bg-red-50': score.status === 'Under',
                    }"
                  >
                    <div
                      class="text-sm font-medium"
                      :class="{
                        'text-green-600': score.status === 'Over',
                        'text-yellow-600': score.status === 'Match',
                        'text-red-600': score.status === 'Under',
                      }"
                    >
                      Score Difference
                    </div>
                    <div
                      class="text-2xl font-bold"
                      :class="{
                        'text-green-700': score.status === 'Over',
                        'text-yellow-700': score.status === 'Match',
                        'text-red-700': score.status === 'Under',
                      }"
                    >
                      {{ score.scoreDifference }}
                    </div>
                  </div>
                </div>

                <!-- Questions and Answers -->
                <div class="space-y-4">
                  <div
                    v-for="question in score.questions"
                    :key="question.question_id"
                    class="p-4 bg-gray-50 rounded-lg"
                  >
                    <div class="font-medium text-gray-700">
                      {{ question.pertanyaan }}
                    </div>
                    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <div class="text-sm text-gray-500">Answer</div>
                        <div class="text-gray-700">{{ question.answer }}</div>
                      </div>
                      <div>
                        <div class="text-sm text-gray-500">Score</div>
                        <div class="text-gray-700">{{ question.score }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </Card>
          </div>

          <div v-if="activeTab === 'analysis'" class="space-y-6">
            <!-- Analysis Table -->
            <Card class="bg-white">
              <template #title>Score Analysis Summary</template>
              <div class="p-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead>
                    <tr>
                      <th
                        class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                      >
                        Aspect
                      </th>
                      <th
                        class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                      >
                        Criteria
                      </th>
                      <th
                        class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                      >
                        Self Score
                      </th>
                      <th
                        class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                      >
                        Peer Average
                      </th>
                      <th
                        class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                      >
                        Difference
                      </th>
                      <th
                        class="px-6 py-3 bg-gray-50 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
                      >
                        Status
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                      v-for="score in analysisScores"
                      :key="`${score.aspek}-${score.kriteria}`"
                      :class="{
                        'bg-green-50': score.status === 'Over',
                        'bg-yellow-50': score.status === 'Match',
                        'bg-red-50': score.status === 'Under',
                      }"
                    >
                      <td
                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                      >
                        {{ score.aspek }}
                      </td>
                      <td
                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                      >
                        {{ score.kriteria }}
                      </td>
                      <td
                        class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900"
                      >
                        {{ score.selfScore }}
                      </td>
                      <td
                        class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900"
                      >
                        {{ score.averagePeerScore }}
                      </td>
                      <td
                        class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium"
                        :class="{
                          'text-green-700': score.status === 'Over',
                          'text-yellow-700': score.status === 'Match',
                          'text-red-700': score.status === 'Under',
                        }"
                      >
                        {{ score.scoreDifference }}
                      </td>
                      <td
                        class="px-6 py-4 whitespace-nowrap text-sm text-center font-medium"
                        :class="{
                          'text-green-700': score.status === 'Over',
                          'text-yellow-700': score.status === 'Match',
                          'text-red-700': score.status === 'Under',
                        }"
                      >
                        {{ score.status }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </Card>
          </div>
          <!-- Add new tab content -->
          <div v-if="activeTab === 'feedback'" class="space-y-6">
  <!-- AI Feedback Card -->
  <Card class="bg-white shadow-lg">
    <template #title>
      <div class="flex items-center space-x-3">
        <svg 
          class="w-6 h-6 text-indigo-600" 
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path 
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"
          />
        </svg>
        <span class="text-xl font-bold text-gray-900">Feedback Rekan Kelompok</span>
      </div>
    </template>
    
    <div class="p-6">
      <!-- Loading State -->
      <div v-if="feedbackLoading" class="flex justify-center p-8">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="feedbackError" class="bg-red-50 border-l-4 border-red-500 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-red-700">{{ feedbackError }}</p>
          </div>
        </div>
      </div>

      <!-- AI Feedback Content -->
      <div v-else-if="feedback.aiFeedback" class="space-y-6">
        <div class="border-b border-gray-200 pb-4">
          <h3 class="text-lg font-medium text-gray-900">Feedback dari Rekan</h3>
          <p class="mt-1 text-sm text-gray-500">
            Berikut adalah rangkuman feedback dari rekan-rekan kelompok Anda
          </p>
        </div>

        <div class="prose max-w-none text-gray-700">
          <div class="bg-gray-50 rounded-lg p-6">
            <div class="flex items-start space-x-3">
              <svg class="w-6 h-6 text-indigo-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <div class="flex-1">
                <p class="whitespace-pre-line">{{ feedback.aiFeedback }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Additional Info -->
        <div class="mt-6 bg-yellow-50 rounded-lg p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg 
                class="h-5 w-5 text-yellow-400" 
                viewBox="0 0 20 20" 
                fill="currentColor"
              >
                <path 
                  fill-rule="evenodd" 
                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" 
                  clip-rule="evenodd"
                />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-yellow-800">
                Catatan
              </h3>
              <div class="mt-2 text-sm text-yellow-700">
                <p>
                  Feedback ini dihasilkan berdasarkan penilaian dari rekan-rekan kelompok Anda. 
                  Gunakan feedback ini sebagai bahan evaluasi untuk meningkatkan kinerja Anda dalam tim.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="feedback.lecturerFeedback && feedback.lecturerFeedback.length > 0" class="mt-8 space-y-6">
  <div class="border-b border-gray-200 pb-4">
    <h3 class="text-lg font-medium text-gray-900">Feedback dari Dosen</h3>
    <p class="mt-1 text-sm text-gray-500">
      Feedback dan evaluasi dari dosen pembimbing
    </p>
  </div>

  <div class="space-y-4">
    <div 
      v-for="(item, index) in feedback.lecturerFeedback" 
      :key="index"
      class="bg-white shadow-sm rounded-lg border border-gray-200 p-4"
    >
      <div class="flex items-start justify-between">
        <div class="flex items-center space-x-3">
          <div class="flex-shrink-0">
            <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
              <span class="text-indigo-700 font-medium">
                {{ item.dosenName.charAt(0) }}
              </span>
            </div>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-900">{{ item.dosenName }}</p>
            <p class="text-sm text-gray-500">
              {{ new Date(item.createdAt).toLocaleDateString() }}
            </p>
          </div>
        </div>
      </div>
      <div class="mt-4 text-sm text-gray-700">
        <p class="whitespace-pre-line">{{ item.feedback }}</p>
      </div>
    </div>
  </div>
</div>

      <!-- No Feedback Available -->
      <div v-else-if="!feedback.aiFeedback && (!feedback.lecturerFeedback || feedback.lecturerFeedback.length === 0)" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada feedback</h3>
        <p class="mt-1 text-sm text-gray-500">
          Feedback akan tersedia setelah rekan kelompok dan dosen memberikan penilaian.
        </p>
      </div>
    </div>
  </Card>
</div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.text-center {
  text-align: center;
}
.aspect-square {
  aspect-ratio: 1 / 1;
}
.hover\:bg-gray-50:hover {
  background-color: #f9fafb;
}
</style>
