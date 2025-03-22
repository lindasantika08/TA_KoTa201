<script setup>
import { defineProps, ref, computed, onMounted } from "vue";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";

const props = defineProps({
  batch_year: String,
  project_name: String,
  kelompok: String,
  initialData: Object,
});

// Existing state
const activeTab = ref("history");
const feedbacks = ref([]);
const feedbackDosen = ref("");
const loading = ref(true);
const error = ref(null);
const summaryData = ref([]);
const summaryLoading = ref(false);
const summaryError = ref(null);

// New state for student selection
const selectedStudent = ref(null);

const tabs = [
  {
    id: "history",
    name: "Riwayat Feedback",
    icon: "M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z",
  },
  {
    id: "input",
    name: "Input Feedback",
    icon: "M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z",
  },
  {
    id: "summary",
    name: "Summary Feedback",
    icon: "M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z",
  },
];

// Frontend - Add CSRF token handling
const submitFeedbackDosen = async () => {
  // Validate all required fields
  if (!feedbackDosen.value?.trim()) {
    alert("Feedback tidak boleh kosong");
    return;
  }

  if (!selectedStudent.value?.mahasiswa?.id) {
    alert("Silakan pilih mahasiswa");
    return;
  }

  if (!props.batch_year || !props.project_name || !props.kelompok) {
    alert("Data kelompok tidak lengkap");
    return;
  }

  const payload = {
    batch_year: props.batch_year,
    project_name: props.project_name,
    kelompok: props.kelompok,
    feedback: feedbackDosen.value.trim(),
    student_id: selectedStudent.value.mahasiswa.id,
  };

  console.log("Submitting feedback with payload:", payload);
  try {
    // Get CSRF token from meta tag
    const csrfToken = document
      .querySelector('meta[name="csrf-token"]')
      ?.getAttribute("content");

    const response = await fetch("/dosen/feedbacks-store-dosen", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": csrfToken, // Add CSRF token
        Accept: "application/json", // Add Accept header
      },
      credentials: "include", // Include cookies
      body: JSON.stringify(payload),
    });

    if (response.ok) {
      const data = await response.json();
      feedbackDosen.value = "";
      selectedStudent.value = null;
      alert("Feedback berhasil dikirim!");
      fetchFeedbacks();
      activeTab.value = "history";
    } else {
      const errorData = await response.json();
      throw new Error(errorData.message || "Gagal mengirim feedback");
    }
  } catch (err) {
    console.error("Error submitting feedback:", err);
    alert(err.message);
  }
};

const fetchFeedbackSummary = async (forceRegenerate = false) => {
  summaryLoading.value = true;
  summaryError.value = null;

  try {
    const queryParams = new URLSearchParams({
      batch_year: props.batch_year,
      project_name: props.project_name,
      kelompok: props.kelompok,
      force_regenerate: forceRegenerate ? "1" : "0",
    });

    const response = await fetch(`/sispa/api/feedback-summary?${queryParams}`, {
      method: "GET",
      headers: {
        Accept: "application/json",
      },
    });

    const result = await response.json();

    if (response.ok) {
      summaryData.value = result.summaries || [];
    } else {
      throw new Error(result.message || "Failed to fetch summary");
    }
  } catch (err) {
    summaryError.value = err.message;
    console.error("Fetch Error:", err);
    alert(err.message);
  } finally {
    summaryLoading.value = false;
  }
};

const refreshSummary = async () => {
  summaryLoading.value = true;
  summaryError.value = null;
  console.log("Refreshing summary with force regenerate");
  try {
    await fetchFeedbackSummary(true);
    console.log("Summary refresh completed");
  } catch (error) {
    console.error("Summary refresh error:", error);
  }
};

const handleTabChange = (tabId) => {
  activeTab.value = tabId;

  if (tabId === "summary") {
    fetchFeedbackSummary();
  }
};

const fetchFeedbacks = async () => {
  try {
    const queryParams = new URLSearchParams({
      batch_year: props.batch_year,
      project_name: props.project_name,
      kelompok: props.kelompok,
    });

    const response = await fetch(`/sispa/api/feedbacks-get-answer?${queryParams}`);

    if (response.ok) {
      const result = await response.json();
      console.log("Fetched feedbacks:", result.data); // Debug log
      feedbacks.value = result.data || [];
    } else {
      throw new Error("Gagal mengambil feedback");
    }
  } catch (err) {
    console.error("Error fetching feedbacks:", err);
    error.value = err.message;
  } finally {
    loading.value = false;
  }
};

const groupedFeedbacks = computed(() => {
  const groups = {};

  feedbacks.value.forEach((feedback) => {
    if (!groups[feedback.peer_id]) {
      groups[feedback.peer_id] = {
        peer_id: feedback.peer_id,
        peer_name: feedback.peer_name,
        peer_nim: feedback.peer_nim,
        feedbacks: [],
      };
    }
    groups[feedback.peer_id].feedbacks.push(feedback);
  });

  Object.values(groups).forEach((group) => {
    group.feedbacks.sort(
      (a, b) => new Date(b.created_at) - new Date(a.created_at)
    );
  });

  return Object.values(groups);
});

// New computed property for form validation
const canSubmitFeedback = computed(() => {
  return selectedStudent.value && feedbackDosen.value.trim().length > 0;
});

const dosenFeedbacks = computed(() => {
  console.log("Processing dosen feedbacks", feedbacks.value);
  return feedbacks.value
    .filter((feedback) => {
      // Changed the condition to properly identify dosen feedback
      const isDosenFeedback = feedback.dosen_id != null;
      console.log(
        `Feedback ${feedback.id}: isDosenFeedback=${isDosenFeedback}, dosen_id=${feedback.dosen_id}`
      );
      return isDosenFeedback;
    })
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

const peerFeedbacks = computed(() => {
  console.log("Processing peer feedbacks", feedbacks.value); // Debug log
  return feedbacks.value.filter((feedback) => {
    // Only include feedback that has peer_id and NO dosen_id
    const isPeerFeedback = feedback.peer_id && !feedback.dosen_id;
    console.log(`Feedback ${feedback.id}: isPeerFeedback=${isPeerFeedback}`); // Debug log
    return isPeerFeedback;
  });
});

const groupedPeerFeedbacks = computed(() => {
  const groups = {};

  peerFeedbacks.value.forEach((feedback) => {
    const peerId = feedback.peer_id;
    if (!groups[peerId]) {
      groups[peerId] = {
        peer_id: peerId,
        peer_name: feedback.peer_name || "Unnamed Peer",
        peer_nim: feedback.peer_nim || "No NIM",
        feedbacks: [],
      };
    }

    // Add this feedback to the group
    groups[peerId].feedbacks.push({
      ...feedback,
      // Make sure we have the correct mahasiswa (sender) information
      mahasiswa_name: feedback.mahasiswa_name || "Unknown Student",
      mahasiswa_nim: feedback.mahasiswa_nim || "No NIM",
      created_at: feedback.created_at,
      feedback: feedback.feedback,
    });
  });

  // Sort feedbacks within each group by date
  Object.values(groups).forEach((group) => {
    group.feedbacks.sort(
      (a, b) => new Date(b.created_at) - new Date(a.created_at)
    );
  });

  return Object.values(groups);
});
onMounted(fetchFeedbacks);
</script>

<template>
  <div class="flex min-h-screen bg-gray-100">
    <Sidebar role="dosen" />
    <div class="flex-1 flex flex-col">
      <Navbar userName="Dosen" />
      <main class="flex-1 p-8">
        <div class="max-w-7xl mx-auto space-y-8">
          <Card>
            <template #title>
              <h1 class="text-2xl font-bold text-gray-800">Detail Feedback</h1>
            </template>

            <!-- Informasi Proyek -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div
                class="bg-white p-6 rounded-lg shadow-sm border border-gray-200"
              >
                <h3 class="text-sm font-medium text-gray-500 mb-2">
                  Tahun Ajaran
                </h3>
                <p class="text-lg font-semibold text-gray-900">
                  {{ batch_year }}
                </p>
              </div>
              <div
                class="bg-white p-6 rounded-lg shadow-sm border border-gray-200"
              >
                <h3 class="text-sm font-medium text-gray-500 mb-2">
                  Nama Proyek
                </h3>
                <p class="text-lg font-semibold text-gray-900">
                  {{ project_name }}
                </p>
              </div>
              <div
                class="bg-white p-6 rounded-lg shadow-sm border border-gray-200"
              >
                <h3 class="text-sm font-medium text-gray-500 mb-2">Kelompok</h3>
                <p class="text-lg font-semibold text-gray-900">
                  {{ kelompok }}
                </p>
              </div>
            </div>

            <!-- Daftar Anggota Kelompok -->
            <div class="p-6 bg-white shadow-md rounded-lg mt-4">
              <h2 class="text-lg font-semibold text-gray-800 mb-3">
                Anggota Kelompok
              </h2>
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-gray-200">
                    <th class="p-3">No</th>
                    <th class="p-3">Nama</th>
                    <th class="p-3">Email</th>
                    <th class="p-3">NIM</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(member, index) in initialData.groupMembers"
                    :key="member.id"
                    class="border-t"
                  >
                    <td class="p-3">{{ index + 1 }}</td>
                    <td class="p-3">
                      {{ member.mahasiswa?.user?.name || "Tidak Ada" }}
                    </td>
                    <td class="p-3">
                      {{ member.mahasiswa?.user?.email || "Tidak Ada" }}
                    </td>
                    <td class="p-3">
                      {{ member.mahasiswa?.nim || "Tidak Ada" }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Tab Navigation -->
            <div class="border-b border-gray-200 mt-6">
              <nav class="flex space-x-8" aria-label="Tabs">
                <button
                  v-for="tab in tabs"
                  :key="tab.id"
                  @click="handleTabChange(tab.id)"
                  :class="[
                    activeTab === tab.id
                      ? 'border-blue-500 text-blue-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                    'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm',
                  ]"
                >
                  <svg
                    class="mr-2 h-5 w-5"
                    :class="[
                      activeTab === tab.id
                        ? 'text-blue-500'
                        : 'text-gray-400 group-hover:text-gray-500',
                    ]"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    aria-hidden="true"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      :d="tab.icon"
                    />
                  </svg>
                  {{ tab.name }}
                </button>
              </nav>
            </div>

            <div class="mt-6">
              <!-- Riwayat Feedback Panel -->
              <div
                v-show="activeTab === 'history'"
                class="bg-white rounded-lg shadow-sm border border-gray-200"
              >
                <div class="p-6">
                  <div v-if="loading" class="text-center py-8">
                    <div
                      class="animate-spin h-8 w-8 border-4 border-blue-500 border-t-transparent rounded-full mx-auto"
                    ></div>
                    <p class="mt-2 text-gray-500">Memuat feedback...</p>
                  </div>

                  <div v-else-if="error" class="text-center py-8">
                    <p class="text-red-500">{{ error }}</p>
                    <button
                      @click="fetchFeedbacks"
                      class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                    >
                      Coba Lagi
                    </button>
                  </div>

                  <div v-else-if="feedbacks.length" class="space-y-8">
                    <!-- Modified Dosen Feedback Section -->
                    <div v-if="dosenFeedbacks.length" class="mb-8">
                      <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        Feedback Dosen
                      </h2>
                      <div
                        v-for="feedback in dosenFeedbacks"
                        :key="feedback.id"
                        class="border rounded-lg p-6 mb-4"
                      >
                        <div class="flex justify-between items-start mb-3">
                          <div>
                            <p class="text-sm font-medium text-gray-900">
                              Untuk: {{ feedback.peer_name || "Tidak Ada" }}
                            </p>
                            <p class="text-xs text-gray-500">
                              NIM: {{ feedback.peer_nim || "Tidak Ada" }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                              Dari: {{ feedback.dosen_name || "Dosen" }}
                            </p>
                          </div>
                          <div class="text-sm text-gray-500">
                            {{
                              new Date(feedback.created_at).toLocaleString(
                                "id-ID",
                                {
                                  year: "numeric",
                                  month: "long",
                                  day: "numeric",
                                  hour: "2-digit",
                                  minute: "2-digit",
                                }
                              )
                            }}
                          </div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                          <p class="text-gray-700 whitespace-pre-wrap">
                            {{ feedback.feedback }}
                          </p>
                        </div>
                      </div>
                    </div>

                    <!-- Peer Feedback Section -->
                    <div v-if="groupedPeerFeedbacks.length">
                      <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        Feedback Teman Sebaya
                      </h2>
                      <div
                        v-for="peer in groupedPeerFeedbacks"
                        :key="peer.peer_id"
                        class="border rounded-lg p-6 mb-4"
                      >
                        <!-- Peer Information Header -->
                        <div class="mb-4">
                          <h3 class="text-lg font-semibold text-gray-900">
                            Feedback untuk: {{ peer.peer_name }}
                          </h3>
                          <p class="text-sm text-gray-500">
                            NIM: {{ peer.peer_nim }}
                          </p>
                        </div>

                        <!-- Individual Feedback Items -->
                        <div class="space-y-4">
                          <div
                            v-for="feedback in peer.feedbacks"
                            :key="feedback.created_at"
                            class="bg-gray-50 rounded-lg p-4"
                          >
                            <div class="flex justify-between items-start mb-2">
                              <div>
                                <p class="text-sm font-medium text-gray-900">
                                  Dari: {{ feedback.mahasiswa_name }}
                                </p>
                                <p class="text-xs text-gray-500">
                                  NIM: {{ feedback.mahasiswa_nim }}
                                </p>
                              </div>
                              <div class="text-sm text-gray-500">
                                {{
                                  new Date(feedback.created_at).toLocaleString(
                                    "id-ID",
                                    {
                                      year: "numeric",
                                      month: "long",
                                      day: "numeric",
                                      hour: "2-digit",
                                      minute: "2-digit",
                                    }
                                  )
                                }}
                              </div>
                            </div>
                            <div class="mt-2">
                              <p class="text-gray-700 whitespace-pre-wrap">
                                {{ feedback.feedback }}
                              </p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-else class="text-center py-8">
                    <p class="text-gray-500">
                      Belum ada feedback untuk kelompok ini.
                    </p>
                  </div>
                </div>
              </div>
              <!-- Modified Input Panel -->
              <div
                v-show="activeTab === 'input'"
                class="bg-white rounded-lg shadow-sm border border-gray-200"
              >
                <div class="p-6 space-y-6">
                  <!-- Modified Student Selection Dropdown -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      Pilih Mahasiswa
                    </label>
                    <select
                      v-model="selectedStudent"
                      class="w-full p-3 border rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                      <option :value="null">Pilih mahasiswa...</option>
                      <option
                        v-for="student in initialData.groupMembers"
                        :key="student.mahasiswa?.id"
                        :value="student"
                        :disabled="!student.mahasiswa?.id"
                      >
                        {{ student.mahasiswa?.user?.name || "Tidak Ada" }} -
                        {{ student.mahasiswa?.nim || "Tidak Ada" }}
                      </option>
                    </select>
                  </div>

                  <!-- Feedback Input -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      Feedback
                    </label>
                    <textarea
                      v-model="feedbackDosen"
                      class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      rows="4"
                      placeholder="Masukkan feedback untuk mahasiswa ini..."
                      :disabled="!selectedStudent"
                    ></textarea>
                  </div>

                  <!-- Submit Button -->
                  <div class="flex justify-end">
                    <button
                      @click="submitFeedbackDosen"
                      class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                      :disabled="!canSubmitFeedback"
                    >
                      Kirim Feedback
                    </button>
                  </div>
                </div>
              </div>
              <div
                v-show="activeTab === 'summary'"
                class="bg-white rounded-lg shadow-sm border border-gray-200"
              >
                <div class="p-6">
                  <div v-if="summaryLoading" class="text-center py-8">
                    <div
                      class="animate-spin h-8 w-8 border-4 border-blue-500 border-t-transparent rounded-full mx-auto"
                    ></div>
                    <p class="mt-2 text-gray-500">Memuat summary...</p>
                  </div>

                  <div
                    v-else-if="summaryError"
                    class="text-center py-8 text-red-500"
                  >
                    {{ summaryError }}
                    <button
                      @click="refreshSummary"
                      class="mt-4 px-4 py-2 bg-blue-500 text-white rounded"
                    >
                      Coba Lagi
                    </button>
                  </div>

                  <template v-else>
                    <div
                      v-if="summaryData.length > 0"
                      class="flex justify-end mb-4"
                    >
                      <button
                        @click="refreshSummary"
                        :disabled="summaryLoading"
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50"
                      >
                        {{ summaryLoading ? "Memuat..." : "Refresh Summary" }}
                      </button>
                    </div>

                    <div
                      v-if="summaryData.length === 0"
                      class="text-center py-8"
                    >
                      <p class="text-gray-500">
                        Belum ada data summary yang tersedia.
                      </p>
                    </div>

                    <div v-else class="space-y-6">
                      <div
                        v-for="summary in summaryData"
                        :key="summary.peer_id"
                        class="border rounded-lg p-4"
                      >
                        <div class="mb-4">
                          <h3 class="text-lg font-semibold text-gray-900">
                            {{ summary.peer_name }}
                          </h3>
                          <p class="text-sm text-gray-500">
                            NIM: {{ summary.peer_nim }}
                          </p>
                        </div>
                        <div class="prose prose-sm max-w-none">
                          <p class="text-gray-700 whitespace-pre-wrap">
                            {{ summary.summary }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </template>
                </div>
              </div>
            </div>
          </Card>
        </div>
      </main>
    </div>
  </div>
</template>
