<script>
import axios from "axios";
import DataTable from "@/Components/DataTable.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";

export default {
    components: {
        DataTable,
        Breadcrumb,
        Sidebar,
        Navbar,
    },
    props: {
        mahasiswaName: String,
        mahasiswaId: String,
        batch_year: String,
        project_name: String,
        project_id: String,
        assessment_order: Number,
    },

    data() {
        return {
            answers: [],
            loading: true,
            error: null,
            columns: [
                { key: "pertanyaan", label: "Pertanyaan" },
                { key: "kriteria", label: "Aspek yang Dinilai" },
                { key: "jawaban", label: "Jawaban" },
            ],
            reflectiveSummary: null,
            summaryLoading: true,
            summaryError: null,
            regeneratingSummary: false,
        };
    },

    mounted() {
        this.fetchAnswerDetails();
        this.fetchReflectiveSummary();
    },

    methods: {
        fetchAnswerDetails() {
            this.loading = true;
            this.error = null;

            axios
                .get("/sispa/api/answers/get-details-answer-reflective", {
                    params: {
                        mahasiswaId: this.mahasiswaId,
                        batch_year: this.batch_year,
                        project_name: this.project_name,
                        assessment_order: this.assessment_order,
                    },
                })
                .then((response) => {
                    if (response.data && response.data.answers) {
                        this.answers = response.data.answers;
                    } else {
                        this.error = "Format respons tidak sesuai";
                    }
                    this.loading = false;
                })
                .catch((error) => {
                    this.loading = false;
                    this.error =
                        error.response?.data?.message ||
                        "Gagal memuat data. Silakan coba lagi.";
                });
        },

        fetchReflectiveSummary(forceRegenerate = false) {
            this.summaryLoading = true;
            this.summaryError = null;

            if (forceRegenerate) {
                this.regeneratingSummary = true;
            }

            axios
                .get("/sispa/api/reflective/summary", {
                    params: {
                        mahasiswa_id: this.mahasiswaId,
                        batch_year: this.batch_year,
                        project_name: this.project_name,
                        force_regenerate: forceRegenerate ? true : undefined,
                    },
                })
                .then((response) => {
                    if (
                        response.data &&
                        response.data.success &&
                        response.data.data
                    ) {
                        this.reflectiveSummary = response.data.data;
                    } else {
                        this.summaryError =
                            "Format respons ringkasan tidak sesuai";
                    }
                    this.summaryLoading = false;
                    this.regeneratingSummary = false;
                })
                .catch((error) => {
                    this.summaryLoading = false;
                    this.regeneratingSummary = false;

                    if (error.response?.status === 404) {
                        this.summaryError =
                            error.response.data.message ||
                            "Ringkasan tidak ditemukan";
                    } else if (error.response?.status === 422) {
                        this.summaryError =
                            "Format parameter tidak valid. Gunakan format UUID yang benar.";
                    } else {
                        this.summaryError =
                            error.response?.data?.message ||
                            "Gagal memuat ringkasan. Silakan coba lagi.";
                    }
                });
        },

        isValidUUID(str) {
            const uuidPattern =
                /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
            return uuidPattern.test(str);
        },

        // Method untuk memformat summary menjadi paragraf-paragraf yang lebih rapi
        formatSummary(text) {
            if (!text) return "";

            // Pisahkan paragraf dengan double line break
            return text
                .split(/\n\s*\n/)
                .map((paragraph) => {
                    // Hapus whitespace berlebih
                    return paragraph.trim();
                })
                .join("\n\n");
        },

        // Method untuk ekstraksi poin-poin penting
        extractKeyPoints(summary) {
            if (!summary) return [];

            const paragraphs = summary.split(/\n\s*\n/);
            const keyPoints = [];

            paragraphs.forEach((paragraph) => {
                // Cari kalimat yang mengandung indikasi poin penting
                if (
                    paragraph.match(
                        /penting|utama|kunci|pokok|inti|signifikan/i
                    )
                ) {
                    keyPoints.push(paragraph.trim());
                }
            });

            return keyPoints.length > 0 ? keyPoints : [];
        },
    },

    computed: {
        isValidMahasiswaId() {
            return this.isValidUUID(this.mahasiswaId);
        },
        isValidProjectId() {
            return this.isValidUUID(this.project_id);
        },
        summaryRequestError() {
            if (!this.isValidMahasiswaId) {
                return "ID Mahasiswa tidak valid. Format UUID diperlukan.";
            }
            if (!this.isValidProjectId) {
                return "ID Proyek tidak valid. Format UUID diperlukan.";
            }
            return null;
        },

        // Computed property untuk memformat summary
        formattedSummary() {
            if (!this.reflectiveSummary || !this.reflectiveSummary.summary)
                return "";
            return this.formatSummary(this.reflectiveSummary.summary);
        },

        // Computed property untuk mendapatkan poin-poin kunci
        keyPoints() {
            if (!this.reflectiveSummary || !this.reflectiveSummary.summary)
                return [];
            return this.extractKeyPoints(this.reflectiveSummary.summary);
        },
    },
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <Sidebar role="dosen" />
        <div class="flex-1 bg-white shadow-sm">
            <Navbar userName="dosen" />
            <main class="p-6 space-y-6">
                <Breadcrumb />

                <div class="text-sm text-gray-600 space-y-1">
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">
                        Detail Jawaban: {{ mahasiswaName }}
                    </h1>
                    <p><strong>Tahun Ajaran:</strong> {{ batch_year }}</p>
                    <p><strong>Nama Proyek:</strong> {{ project_name }}</p>
                    <p>
                        <strong>Assessment Order:</strong>
                        {{ assessment_order }}
                    </p>
                </div>

                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">
                        Detail Jawaban Reflektif
                    </h2>

                    <div v-if="loading" class="text-center py-4">
                        <p class="text-gray-600">Memuat data...</p>
                    </div>

                    <div
                        v-else-if="error"
                        class="bg-red-50 text-red-600 p-4 rounded-md"
                    >
                        {{ error }}
                    </div>

                    <div
                        v-else-if="!answers || answers.length === 0"
                        class="text-center py-4"
                    >
                        <p class="text-gray-600">
                            Tidak ada jawaban yang ditemukan
                        </p>
                    </div>

                    <div v-else>
                        <table class="min-w-full border-collapse table-auto">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Pertanyaan
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Aspek yang Dinilai
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Jawaban
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="(answer, index) in answers"
                                    :key="index"
                                >
                                    <td
                                        class="px-6 py-4 whitespace-normal text-sm text-gray-900"
                                    >
                                        {{ answer.pertanyaan }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-normal text-sm text-gray-900"
                                    >
                                        <span v-if="answer.kriteria">{{
                                            answer.kriteria
                                        }}</span>
                                        <span
                                            v-else
                                            class="text-gray-400 italic"
                                            >Tidak ada kriteria</span
                                        >
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-normal text-sm text-gray-900"
                                    >
                                        {{ answer.jawaban }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Ringkasan Reflektif (Diperbarui) -->
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold">
                            Ringkasan Reflektif
                        </h2>
                    </div>

                    <!-- Loading Summary -->
                    <div v-if="summaryLoading" class="text-center py-8">
                        <svg
                            class="animate-spin h-8 w-8 text-blue-500 mx-auto"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        <p class="mt-3 text-gray-600">Memuat ringkasan...</p>
                    </div>

                    <!-- Error Summary -->
                    <div
                        v-else-if="summaryError"
                        class="bg-red-50 text-red-600 p-4 rounded-md"
                    >
                        {{ summaryError }}
                    </div>

                    <!-- Display Summary (Enhanced) -->
                    <div v-else-if="reflectiveSummary" class="space-y-6">
                        <!-- Tabs for different views -->
                        <div class="border-b border-gray-200">
                            <div class="flex space-x-8" role="tablist">
                                <button
                                    class="py-2 border-b-2 border-blue-500 text-blue-600 font-medium"
                                    role="tab"
                                    aria-selected="true"
                                >
                                    Ringkasan Lengkap
                                </button>
                            </div>
                        </div>

                        <!-- Full Summary View -->
                        <div role="tabpanel">
                            <div class="prose max-w-none">
                                <div
                                    v-for="(
                                        paragraph, idx
                                    ) in formattedSummary.split('\n\n')"
                                    :key="idx"
                                    class="mb-4"
                                >
                                    <p class="text-gray-800 leading-relaxed">
                                        {{ paragraph }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Summary Fallback -->
                    <div v-else class="text-center py-8">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-12 w-12 text-gray-400 mx-auto"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                        <p class="mt-3 text-gray-500 italic">
                            Ringkasan belum tersedia.
                        </p>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>
