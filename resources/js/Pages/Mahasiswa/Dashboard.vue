<script>
import axios from "axios";
import { router } from "@inertiajs/vue3";
import VueApexCharts from "vue3-apexcharts";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import SidebarMahasiswa from "@/Components/SidebarMahasiswa.vue";

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
                project_id: null,
            },
            scoreData: null,
            loadingScoreData: false,
            selfAssessmentStatus: null,
            peerGroupSize: 0,
            peerCompletedCount: 0,
            showChangePasswordToast: false,
            needPasswordChange: false,
            toastTimeout: null,
            feedbackLoading: false,
            feedbackError: null,
            activeTab: "peer", // Tab aktif untuk feedback (peer/dosen)
            feedback: {
                lecturerFeedback: [],
                peerFeedback: [
                    // Data contoh untuk peer feedback
                    {
                        feedback:
                            "Kontribusi yang bagus dalam tim. Aktif memberikan ide dan solusi.",
                        createdAt: new Date(2025, 4, 1),
                    },
                    {
                        feedback:
                            "Sangat membantu dalam menyelesaikan tugas teknis yang rumit.",
                        createdAt: new Date(2025, 4, 2),
                    },
                ],
            },
        };
    },
    computed: {
        analysisScores() {
            if (
                !this.scoreData?.self_assessment ||
                !this.scoreData?.peer_assessment
            ) {
                return [];
            }

            return this.scoreData.self_assessment.map((selfAspect) => {
                const peerEvaluations = this.scoreData.peer_assessment.filter(
                    (peer) => peer.aspek === selfAspect.aspek
                );

                const averagePeerScore =
                    peerEvaluations.length > 0
                        ? peerEvaluations.reduce((sum, peer) => {
                              const score =
                                  peer.total_score_peer != null
                                      ? peer.total_score_peer
                                      : peer.total_score || 0;
                              return sum + score;
                          }, 0) / peerEvaluations.length
                        : 0;

                const selfScore =
                    selfAspect.total_score_self != null
                        ? selfAspect.total_score_self
                        : selfAspect.total_score || 0;

                const scoreDifference = selfScore - averagePeerScore;

                return {
                    aspek: selfAspect.aspek,
                    kriteria: selfAspect.kriteria,
                    selfScore: selfScore.toFixed(2),
                    averagePeerScore: averagePeerScore.toFixed(2),
                    scoreDifference: scoreDifference.toFixed(2),
                    status:
                        scoreDifference > 0
                            ? "Over"
                            : scoreDifference < 0
                            ? "Under"
                            : "Match",
                    questions: selfAspect.questions,
                };
            });
        },
        radarChartOptions() {
            return {
                chart: {
                    type: "radar",
                    height: 600,
                    width: 600,
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
                        data: this.analysisScores.map((s) =>
                            parseFloat(s.selfScore)
                        ),
                    },
                    {
                        name: "Peer Average",
                        data: this.analysisScores.map((s) =>
                            parseFloat(s.averagePeerScore)
                        ),
                    },
                ],
                labels: this.analysisScores.map((s) => s.aspek),
                colors: ["#2563EB", "#F97316"],
                stroke: {
                    width: 2,
                    colors: ["#2563EB", "#F97316"],
                },
                colors: ["#2563EB", "#F97316"],
                fill: {
                    opacity: 0.2,
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
                            fontSize: "14px",
                        },
                    },
                },
                xaxis: {
                    labels: {
                        style: {
                            fontSize: "14px",
                        },
                    },
                },
                legend: {
                    position: "bottom",
                    horizontalAlign: "center",
                    fontSize: "14px",
                    markers: {
                        width: 18,
                        height: 18,
                    },
                    itemMargin: {
                        horizontal: 15,
                    },
                },
            };
        },
    },
    mounted() {
        this.fetchProjectData();
        this.fetchSelfAssessmentStatus();
        this.fetchPeerAssessmentDetails();
        this.fetchFeedbackData();
        // this.checkPasswordChangeStatus();
    },
    beforeUnmount() {
        if (this.toastTimeout) {
            clearTimeout(this.toastTimeout);
        }
    },
    watch: {
        selectedProject(newProject) {
            if (
                newProject &&
                newProject.project_name &&
                newProject.batch_year &&
                newProject.project_id
            ) {
                this.fetchSelfAssessmentStatus(newProject.project_name);
                this.fetchPeerAssessmentDetails(newProject.project_name);
                this.fetchFeedbackData(newProject.project_name);
                this.fetchProjectScoreDetails(
                    newProject.batch_year,
                    newProject.project_id
                );
            }
        },
    },
    methods: {
        async fetchProjectScoreDetails(batchYear, projectId) {
            this.loadingScoreData = true;

            try {
                const response = await axios.get(
                    "/sispa/api/project-score-details",
                    {
                        params: {
                            batch_year: batchYear,
                            project_id: projectId,
                        },
                    }
                );

                console.log("API Response:", response);

                if (response.data.status === "success") {
                    this.scoreData = response.data.data;
                } else {
                    console.warn(
                        "Gagal mengambil data:",
                        response.data.message
                    );
                }
            } catch (err) {
                console.error("Gagal fetch detail skor:", err);
            } finally {
                this.loadingScoreData = false;
            }
        },
        checkPasswordChangeStatus() {
            const needPasswordChange = localStorage.getItem(
                "need_password_change"
            );

            if (needPasswordChange === "true") {
                this.showChangePasswordToast = true;
                this.needPasswordChange = true;

                this.toastTimeout = setTimeout(() => {
                    this.showChangePasswordToast = false;
                }, 10000);
            } else {
                this.checkUserStatus();
            }
        },

        async checkUserStatus() {
            try {
                const response = await axios.get("/sispa/api/user/status");

        if (response.data.hasOwnProperty("change_password")) {
          const needPasswordChange = !response.data.change_password;

                    if (needPasswordChange) {
                        this.showChangePasswordToast = true;
                        this.needPasswordChange = true;

                        localStorage.setItem("need_password_change", "true");

                        this.toastTimeout = setTimeout(() => {
                            this.showChangePasswordToast = false;
                        }, 10000);
                    } else {
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
                .get("/sispa/api/projects-user")
                .then((response) => {
                    this.projects = response.data.projects.map((project) => ({
                        project_name: project.project_name,
                        batch_year: project.batch_year,
                        project_id: project.id || project.project_id,
                    }));

                    if (this.projects.length > 0) {
                        this.selectedProject = this.projects[0];
                    }
                })
                .catch((error) => {
                    console.error("Error fetching project data:", error);
                });
        },
        fetchSelfAssessmentStatus(projectName) {
            axios
                .get(`/sispa/api/assessment-status`, {
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
                    console.error(
                        "Error fetching self assessment status:",
                        error
                    );
                    this.selfAssessmentStatus = "Not Started";
                });
        },
        fetchPeerAssessmentDetails(projectName) {
            axios
                .get("/sispa/api/count-peer", {
                    params: { project: projectName },
                })
                .then((response) => {
                    this.peerGroupSize = response.data.group_size;
                    this.peerCompletedCount = response.data.group_peers.filter(
                        (peer) =>
                            response.data.completed_peer_assessments[peer.id]
                                .is_completed
                    ).length;
                })
                .catch((error) => {
                    console.error(
                        "Error fetching peer assessment details:",
                        error
                    );
                });
        },
        fetchFeedbackData(projectName) {
            this.feedbackLoading = true;
            this.feedbackError = null;

            axios
                .get("/sispa/api/feedback-dashboard-mhs", {
                    params: { project: projectName },
                })
                .then((response) => {
                    if (response.data.success) {
                        // Make sure to properly assign the feedback data
                        this.feedback = {
                            lecturerFeedback:
                                response.data.data.lecturerFeedback || [],
                            peerFeedback: response.data.data.peerFeedback || [],
                        };
                        console.log("Feedback data:", this.feedback); // For debugging
                    } else {
                        this.feedbackError =
                            response.data.message ||
                            "Failed to load feedback data";
                    }
                })
                .catch((error) => {
                    console.error("Error fetching feedback:", error);
                    this.feedbackError =
                        "An error occurred while fetching feedback data";
                })
                .finally(() => {
                    this.feedbackLoading = false;
                });
        },
        handleChangePassword() {
            this.showChangePasswordToast = false;
            this.needPasswordChange = false;
            localStorage.removeItem("need_password_change");
            router.visit("/sispa/mahasiswa/profile");
        },
        goToDashboardSelf(path) {
            router.visit("/sispa/mahasiswa/assessment/self");
        },
        goToDashboardPeer(path) {
            router.visit("/sispa/mahasiswa/assessment/peer");
        },
        goToKelolaProyek(path) {
            router.visit(path);
        },
        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString("id-ID", {
                day: "numeric",
                month: "short",
                year: "numeric",
            });
        },
    },
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Password Change Toast -->
        <div
            v-if="showChangePasswordToast"
            class="fixed top-4 right-4 bg-white shadow-xl rounded-lg p-4 max-w-md z-50 animate-fade-in border-l-4 border-yellow-500"
        >
            <div class="flex items-center space-x-4">
                <div
                    class="bg-yellow-500 flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center"
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
                        class="px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-md hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-colors"
                    >
                        Ganti Password
                    </button>
                    <button
                        @click="showChangePasswordToast = false"
                        class="p-2 text-gray-400 hover:text-gray-500 focus:outline-none transition-colors"
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
    <div class="flex-1 flex flex-col">
      <Navbar userName="Mahasiswa" />
      <main class="flex-1 p-6 md:p-8">
        <div class="mb-6">
          <h1 class="text-2xl font-bold text-gray-800">Dashboard Mahasiswa</h1>
          <p class="text-gray-600 mt-1">
            Pantau progres pembelajaran dan asesmen Anda
          </p>
        </div>

                <!-- Project Selection -->
                <!-- Menghapus section project selection dan memindahkannya ke card Kelola Project -->

        <!-- Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
          <!-- Self Assessment Card -->
          <div
            class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-300 cursor-pointer"
            @click="goToDashboardSelf('/sispa/mahasiswa/self-assessment')"
          >
            <div class="px-6 py-5 border-b border-gray-100">
              <h3 class="text-lg font-medium text-gray-800">Self Assessment</h3>
              <p class="text-sm text-gray-500 mt-1">Evaluasi diri Anda</p>
            </div>
            <div class="p-6">
              <div class="flex items-center space-x-4">
                <div
                  class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center"
                >
                  <svg
                    class="w-6 h-6 text-indigo-600"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                    />
                  </svg>
                </div>
                <div>
                  <span
                    :class="`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${statusClass}`"
                  >
                    <svg
                      v-if="selfAssessmentStatus"
                      class="w-4 h-4 mr-1.5"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        v-if="selfAssessmentStatus === 'Completed'"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 13l4 4L19 7"
                      />
                      <path
                        v-else-if="selfAssessmentStatus === 'Pending'"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                      />
                      <path
                        v-else
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                      />
                    </svg>
                    {{ selfAssessmentStatus }}
                  </span>
                </div>
              </div>
            </div>
          </div>

                    <!-- Peer Assessment Card -->
                    <div
                        class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-300 cursor-pointer"
                        @click="
                            goToDashboardPeer(
                                '/sispa/mahasiswa/peer-assessment'
                            )
                        "
                    >
                        <div class="px-6 py-5 border-b border-gray-100">
                            <h3 class="text-lg font-medium text-gray-800">
                                Peer Assessment
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Evaluasi rekan sejawat
                            </p>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center space-x-4">
                                <div
                                    class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center"
                                >
                                    <svg
                                        class="w-6 h-6 text-green-600"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                        />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div
                                        class="flex justify-between items-center mb-1"
                                    >
                                        <span
                                            class="text-sm font-medium text-gray-700"
                                            >Progress</span
                                        >
                                        <span
                                            class="text-sm font-medium text-gray-700"
                                            >{{ peerCompletedCount }}/{{
                                                peerGroupSize
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        class="w-full bg-green-600 rounded-full h-2.5"
                                    >
                                        <div
                                            :class="`h-2.5 rounded-full ${progressColor}`"
                                            :style="`width: ${
                                                peerGroupSize > 0
                                                    ? (peerCompletedCount /
                                                          peerGroupSize) *
                                                      100
                                                    : 0
                                            }%`"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Project Management Card -->
                    <div
                        class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-300 cursor-pointer"
                    >
                        <div class="px-6 py-5 border-b border-gray-100">
                            <h3 class="text-lg font-medium text-gray-800">
                                Project
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Project saat ini
                            </p>
                        </div>
                        <div class="p-6">
                            <div class="flex flex-col space-y-4">
                                <div class="flex items-center space-x-4">
                                    <!-- Dropdown pemilihan project dipindahkan ke sini -->
                                    <div class="w-full mt-2">
                                        <label
                                            for="project-select"
                                            class="block text-sm font-medium text-gray-700 mb-1"
                                            >Pilih Project:</label
                                        >
                                        <select
                                            id="project-select"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white shadow-sm"
                                            v-model="selectedProject"
                                        >
                                            <option
                                                v-for="project in projects"
                                                :key="
                                                    project.project_id ||
                                                    project.id
                                                "
                                                :value="project"
                                            >
                                                {{ project.project_name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Assessment Activity Chart -->
                    <div class="lg:col-span-2">
                        <div
                            class="bg-white rounded-xl shadow-sm overflow-hidden"
                        >
                            <div class="px-6 py-5 border-b border-gray-100">
                                <h3 class="text-lg font-medium text-gray-800">
                                    Skills Assessment
                                </h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    Visualisasi kompetensi Anda dalam project
                                </p>
                            </div>
                            <div id="chart-container">
                                <apexchart
                                    width="100%"
                                    type="radar"
                                    :options="radarChartOptions"
                                    :series="radarChartOptions.series"
                                />
                            </div>
                        </div>
                    </div>

          <!-- Feedback Section -->
          <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden h-full">
              <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-lg font-medium text-gray-800">Feedback</h3>
                <p class="text-sm text-gray-500 mt-1">
                  Evaluasi dan saran untuk pengembangan Anda
                </p>
              </div>

              <!-- Tabs untuk Feedback -->
              <div class="px-4 pt-4">
                <div class="border-b border-gray-200">
                  <nav class="-mb-px flex" aria-label="Tabs">
                    <button
                      @click="activeTab = 'peer'"
                      :class="[
                        activeTab === 'peer'
                          ? 'border-indigo-500 text-indigo-600'
                          : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                        'w-1/2 py-3 px-1 text-center border-b-2 font-medium text-sm',
                      ]"
                    >
                      Peer Feedback
                    </button>
                    <button
                      @click="activeTab = 'dosen'"
                      :class="[
                        activeTab === 'dosen'
                          ? 'border-indigo-500 text-indigo-600'
                          : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                        'w-1/2 py-3 px-1 text-center border-b-2 font-medium text-sm',
                      ]"
                    >
                      Dosen Feedback
                    </button>
                  </nav>
                </div>
              </div>

              <!-- Konten Tab Peer Feedback -->
              <div
                v-if="activeTab === 'peer'"
                class="p-4 max-h-64 overflow-y-auto"
              >
                <div
                  v-if="feedbackLoading"
                  class="flex items-center justify-center h-32"
                >
                  <div
                    class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500"
                  ></div>
                </div>
                <div
                  v-else-if="feedbackError"
                  class="text-red-500 p-4 bg-red-50 rounded-lg"
                >
                  {{ feedbackError }}
                </div>
                <div
                  v-else-if="feedback?.peerFeedback?.length"
                  class="space-y-3"
                >
                  <div
                    v-for="(item, index) in feedback.peerFeedback"
                    :key="index"
                    class="p-3 bg-blue-50 rounded-lg border border-blue-100"
                  >
                    <p class="text-sm text-gray-700">
                      {{
                        item.feedback ||
                        "Peer memberikan penilaian positif terhadap kontribusi Anda."
                      }}
                    </p>
                    <div
                      class="mt-2 flex items-center justify-between text-xs text-gray-500"
                    >
                      <span class="font-medium">Anggota Tim</span>
                      <span>{{
                        formatDate(item.createdAt || new Date())
                      }}</span>
                    </div>
                  </div>
                </div>
                <div
                  v-else
                  class="flex flex-col items-center justify-center h-32 text-gray-500"
                >
                  <svg
                    class="w-10 h-10 text-gray-300 mb-2"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"
                    />
                  </svg>
                  <p>Belum ada feedback dari rekan tim</p>
                </div>
              </div>

              <div
                v-if="activeTab === 'dosen'"
                class="p-4 max-h-64 overflow-y-auto"
              >
                <div
                  v-if="feedbackLoading"
                  class="flex items-center justify-center h-32"
                >
                  <div
                    class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500"
                  ></div>
                </div>
                <div
                  v-else-if="feedbackError"
                  class="text-red-500 p-4 bg-red-50 rounded-lg"
                >
                  {{ feedbackError }}
                </div>
                <div
                  v-else-if="
                    feedback &&
                    feedback.lecturerFeedback &&
                    feedback.lecturerFeedback.length > 0
                  "
                  class="space-y-3"
                >
                  <div
                    v-for="(item, index) in feedback.lecturerFeedback"
                    :key="index"
                    class="p-3 bg-gray-50 rounded-lg border border-gray-100"
                  >
                    <p class="text-sm text-gray-700">
                      {{ item.feedback }}
                    </p>
                    <div
                      class="mt-2 flex items-center justify-between text-xs text-gray-500"
                    >
                      <span class="font-medium">{{ item.dosenName }}</span>
                      <span>{{ formatDate(item.createdAt) }}</span>
                    </div>
                  </div>
                </div>
                <div
                  v-else
                  class="flex flex-col items-center justify-center h-32 text-gray-500"
                >
                  <svg
                    class="w-10 h-10 text-gray-300 mb-2"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
                    />
                  </svg>
                  <p>Belum ada feedback dari dosen</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<style>
@import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap");

.animate-fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

#chart-container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 700px;
}
</style>
