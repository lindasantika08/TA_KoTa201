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

const activeTab = ref('history');
const feedbacks = ref([]);
const feedbackDosen = ref("");
const loading = ref(true);
const error = ref(null);
const summaryData = ref([]); // Data untuk summary
const summaryLoading = ref(false); // Loading state untuk summary
const summaryError = ref(null); // Error state untuk summary

const tabs = [
  { id: 'history', name: 'Riwayat Feedback', icon: 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z' },
  { id: 'input', name: 'Input Feedback', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
  { id: 'summary', name: 'Summary Feedback', icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
];

// Fetch feedback summary dari API
const fetchFeedbackSummary = async () => {
    summaryLoading.value = true;
    summaryError.value = null;

    try {
        const queryParams = new URLSearchParams({
            batch_year: props.batch_year,
            project_name: props.project_name,
            kelompok: props.kelompok,
        });

        const response = await fetch(`/api/feedback-summary?${queryParams}`);

        if (response.ok) {
            const result = await response.json();
            summaryData.value = result.summaries || []; // Sesuaikan dengan struktur respons API
        } else {
            const errorText = await response.text(); // Ambil teks error
            throw new Error(`Gagal mengambil data summary: `);
        }
    } catch (err) {
        summaryError.value = err.message;
    } finally {
        summaryLoading.value = false;
    }
};


// Panggil fetchFeedbackSummary saat tab summary aktif
const handleTabChange = (tabId) => {
  activeTab.value = tabId;

  if (tabId === 'summary') {
    fetchFeedbackSummary();
  }
};

// Fetch feedback history (seperti sebelumnya)
const fetchFeedbacks = async () => {
  try {
    const queryParams = new URLSearchParams({
      batch_year: props.batch_year,
      project_name: props.project_name,
      kelompok: props.kelompok,
    });

    const response = await fetch(`/api/feedbacks-get-answer?${queryParams}`);

    if (response.ok) {
      const result = await response.json();
      feedbacks.value = result.data || [];
    } else {
      throw new Error('Gagal mengambil feedback');
    }
  } catch (err) {
    error.value = err.message;
  } finally {
    loading.value = false;
  }
};

// Submit feedback dosen (seperti sebelumnya)
const submitFeedbackDosen = async () => {
  if (!feedbackDosen.value.trim()) return;

  try {
    const response = await fetch("/api/feedbacks-dosen", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        batch_year: props.batch_year,
        project_name: props.project_name,
        kelompok: props.kelompok,
        feedback: feedbackDosen.value,
      }),
    });

    if (response.ok) {
      feedbackDosen.value = "";
      alert("Feedback berhasil dikirim!");
      fetchFeedbacks();
      activeTab.value = 'history';
    } else {
      throw new Error("Gagal mengirim feedback");
    }
  } catch (err) {
    alert(err.message);
  }
};

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
              <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Tahun Ajaran</h3>
                <p class="text-lg font-semibold text-gray-900">{{ batch_year }}</p>
              </div>
              <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Nama Proyek</h3>
                <p class="text-lg font-semibold text-gray-900">{{ project_name }}</p>
              </div>
              <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Kelompok</h3>
                <p class="text-lg font-semibold text-gray-900">{{ kelompok }}</p>
              </div>
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
                    'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm'
                  ]"
                >
                  <svg
                    class="mr-2 h-5 w-5"
                    :class="[
                      activeTab === tab.id
                        ? 'text-blue-500'
                        : 'text-gray-400 group-hover:text-gray-500'
                    ]"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    aria-hidden="true"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
                  </svg>
                  {{ tab.name }}
                </button>
              </nav>
            </div>

            <div class="mt-6">
              <!-- Riwayat Feedback Panel -->
              <div v-show="activeTab === 'history'" class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6">
                  <div v-if="loading" class="text-center py-8">
                    <div class="animate-spin h-8 w-8 border-4 border-blue-500 border-t-transparent rounded-full mx-auto"></div>
                    <p class="mt-2 text-gray-500">Memuat feedback...</p>
                  </div>
                  <div v-else-if="error" class="text-center py-8">
                    <p class="text-red-500">{{ error }}</p>
                    <button @click="fetchFeedbacks" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                      Coba Lagi
                    </button>
                  </div>
                  <div v-else-if="feedbacks.length" class="space-y-6">
                    <div v-for="feedback in feedbacks" :key="`${feedback.mahasiswa_nim}-${feedback.created_at}`" class="space-y-4">
                      <div class="text-sm text-gray-500">
                        {{ new Date(feedback.created_at).toLocaleString("id-ID", { year: "numeric", month: "long", day: "numeric", hour: "2-digit", minute: "2-digit" }) }}
                      </div>
                      
                      <!-- Feedback participants info -->
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white p-4 rounded-lg border border-gray-200">
                        <div>
                          <h4 class="text-sm font-medium text-gray-500 mb-1">Penerima Feedback</h4>
                          <div class="text-gray-900">
                            <p class="font-medium">{{ feedback.peer_name }}</p>
                            <p class="text-sm text-gray-500">{{ `NIM: ${feedback.peer_nim}` }}</p>
                          </div>
                        </div>
                        
                        <div>
                          <h4 class="text-sm font-medium text-gray-500 mb-1">Pengirim Feedback</h4>
                          <div class="text-gray-900">
                            <p class="font-medium">{{ feedback.mahasiswa_name }}</p>
                            <p class="text-sm text-gray-500">NIM: {{ feedback.mahasiswa_nim }}</p>
                          </div>
                        </div>
                      </div>

                      <!-- Feedback content -->
                      <div class="bg-white p-4 rounded-lg border border-gray-200">
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Isi Feedback</h4>
                        <div class="prose prose-sm max-w-none">
                          <p class="text-gray-700 whitespace-pre-wrap">{{ feedback.feedback }}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-center py-8">
                    <p class="text-gray-500">Belum ada feedback untuk kelompok ini.</p>
                  </div>
                </div>
              </div>
              <!-- Input Panel -->
              <div v-show="activeTab === 'input'" class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6">
                  <textarea
                    v-model="feedbackDosen"
                    class="w-full p-3 border rounded-lg"
                    rows="4"
                    placeholder="Masukkan feedback untuk kelompok ini..."
                  ></textarea>
                  <button
                    @click="submitFeedbackDosen"
                    class="mt-4 px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600"
                  >
                    Kirim Feedback
                  </button>
                </div>
              </div>

              <!-- Summary Panel -->
              <div v-show="activeTab === 'summary'" class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6">
                  <div v-if="summaryLoading" class="text-center py-8">
                    <div class="animate-spin h-8 w-8 border-4 border-blue-500 border-t-transparent rounded-full mx-auto"></div>
                    <p class="mt-2 text-gray-500">Memuat summary...</p>
                  </div>
                  <div v-else-if="summaryError" class="text-center py-8 text-red-500">
                    {{ summaryError }}
                  </div>
                  <div v-else-if="summaryData.length === 0" class="text-center py-8">
                    <p class="text-gray-500">Belum ada data summary yang tersedia.</p>
                  </div>
                  <div v-else class="space-y-6">
                    <div v-for="summary in summaryData" :key="summary.peer_id" class="border rounded-lg p-4">
                      <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">{{ summary.peer_name }}</h3>
                        <p class="text-sm text-gray-500">NIM: {{ summary.peer_nim }}</p>
                      </div>
                      <div class="prose prose-sm max-w-none">
                        <p class="text-gray-700 whitespace-pre-wrap">{{ summary.summary }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </Card>
        </div>
      </main>
    </div>
  </div>
</template>
