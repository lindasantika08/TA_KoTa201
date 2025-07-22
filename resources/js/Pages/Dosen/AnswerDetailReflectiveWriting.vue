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
            rawAnswers: [],
            loading: true,
            error: null,
            regeneratingSummary: false,
            // AI Summary related
            aiSummary: null,
            loadingAISummary: false,
            showAISummary: false,
            hasAISummary: false,
        };
    },

    mounted() {
        this.fetchAnswerDetails();
        this.fetchAISummary();
    },

    methods: {
        fetchAnswerDetails() {
            this.loading = true;
            this.error = null;

            axios
                .get(
                    "/api/answers/get-details-answer-reflective-writing",
                    {
                        params: {
                            mahasiswaId: this.mahasiswaId,
                            batch_year: this.batch_year,
                            project_name: this.project_name,
                            assessment_order: this.assessment_order,
                        },
                    }
                )
                .then((response) => {
                    if (response.data && response.data.answers) {
                        this.rawAnswers = response.data.answers;
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

        isValidUUID(str) {
            const uuidPattern =
                /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
            return uuidPattern.test(str);
        },

        formatDateTime(dateTimeStr) {
            if (!dateTimeStr) return "";
            const date = new Date(dateTimeStr);
            return new Intl.DateTimeFormat("id-ID", {
                day: "2-digit",
                month: "2-digit",
                year: "numeric",
                hour: "2-digit",
                minute: "2-digit",
                hour12: false,
            }).format(date);
        },

        async fetchAISummary() {
            if (!this.isValidMahasiswaId) {
                return;
            }

            this.loadingAISummary = true;
            try {
                const response = await axios.get(
                    "/api/reflective-writing/ai-summary",
                    {
                        params: {
                            mahasiswaId: this.mahasiswaId,
                            batch_year: this.batch_year,
                            project_name: this.project_name,
                        },
                    }
                );

                if (response.data.success && response.data.has_summary) {
                    this.aiSummary = response.data.data;
                    this.hasAISummary = true;
                } else {
                    this.hasAISummary = false;
                }
            } catch (error) {
                console.error("Error fetching AI summary:", error);
                this.hasAISummary = false;
            } finally {
                this.loadingAISummary = false;
            }
        },

        toggleAISummary() {
            this.showAISummary = !this.showAISummary;
        },

        formatAISummary(summary) {
            if (!summary) return [];

            const sections = [];
            const lines = summary.split("\n");
            let currentSection = null;
            let currentContent = [];

            for (let line of lines) {
                // Check if line is a section header
                if (line.startsWith("===") && line.endsWith("===")) {
                    // Save previous section
                    if (currentSection) {
                        sections.push({
                            title: this.formatBoldText(currentSection),
                            content: this.formatBoldText(
                                currentContent.join("\n").trim()
                            ),
                        });
                    }

                    // Start new section
                    currentSection = line.replace(/===/g, "").trim();
                    currentContent = [];
                } else if (line.startsWith("---") && line.endsWith("---")) {
                    // Save previous section
                    if (currentSection) {
                        sections.push({
                            title: this.formatBoldText(currentSection),
                            content: this.formatBoldText(
                                currentContent.join("\n").trim()
                            ),
                        });
                    }

                    // Start new section
                    currentSection = line.replace(/---/g, "").trim();
                    currentContent = [];
                } else {
                    // Add line to current content
                    currentContent.push(line);
                }
            }

            // Don't forget the last section
            if (currentSection) {
                sections.push({
                    title: this.formatBoldText(currentSection),
                    content: this.formatBoldText(
                        currentContent.join("\n").trim()
                    ),
                });
            }

            // If no sections found, return the whole summary as one section
            if (sections.length === 0) {
                sections.push({
                    title: "Analisis AI",
                    content: this.formatBoldText(summary),
                });
            }

            return sections;
        },

        // Format teks yang diapit ** menjadi bold dan menangani format lain
        formatBoldText(text) {
            if (!text) return text;

            // Replace teks yang diapit ** dengan tag <b>
            let formattedText = text.replace(/\*\*(.*?)\*\*/g, "<b>$1</b>");

            // Replace line breaks dengan <br> untuk HTML rendering yang lebih baik
            formattedText = formattedText.replace(/\n/g, "<br>");

            return formattedText;
        },
    },

    computed: {
        hasAnswers() {
            return this.rawAnswers && this.rawAnswers.length > 0;
        },

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

                <!-- AI Summary Section -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2
                            class="text-xl font-semibold flex items-center gap-2"
                        >
                            <svg
                                class="w-6 h-6 text-blue-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                                />
                            </svg>
                            Analisis Reflective Writing
                        </h2>

                        <button
                            v-if="hasAISummary"
                            @click="toggleAISummary"
                            class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors flex items-center gap-2"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="
                                        showAISummary
                                            ? 'M19 9l-7 7-7-7'
                                            : 'M9 5l7 7-7 7'
                                    "
                                />
                            </svg>
                            {{ showAISummary ? "Sembunyikan" : "Tampilkan" }}
                            Analisis
                        </button>
                    </div>

                    <div v-if="loadingAISummary" class="text-center py-8">
                        <div
                            class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"
                        ></div>
                        <p class="mt-2 text-gray-600">Memuat analisis AI...</p>
                    </div>

                    <div
                        v-else-if="!hasAISummary"
                        class="bg-yellow-50 border border-yellow-200 rounded-lg p-4"
                    >
                        <div class="flex items-center gap-2">
                            <svg
                                class="w-5 h-5 text-yellow-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.728-.833-2.498 0L3.316 16.5c-.77.833.192 2.5 1.732 2.5z"
                                />
                            </svg>
                            <p class="text-yellow-800">
                                Belum ada analisis AI untuk mahasiswa ini.
                                Analisis akan otomatis dibuat ketika mahasiswa
                                submit jawaban reflective writing.
                            </p>
                        </div>
                    </div>

                    <div
                        v-else-if="showAISummary && aiSummary"
                        class="space-y-4"
                    >
                        <!-- AI Summary Info -->
                        <div
                            class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-400"
                        >
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-blue-700"
                                        >Mahasiswa:</span
                                    >
                                    <span class="text-blue-600 ml-2"
                                        >{{ aiSummary.mahasiswa_name }} ({{
                                            aiSummary.mahasiswa_nim
                                        }})</span
                                    >
                                </div>
                                <div>
                                    <span class="font-medium text-blue-700"
                                        >Dianalisis pada:</span
                                    >
                                    <span class="text-blue-600 ml-2">{{
                                        formatDateTime(aiSummary.generated_at)
                                    }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- AI Summary Content -->
                        <div class="bg-white border rounded-lg">
                            <div class="bg-gray-50 px-4 py-3 border-b">
                                <h4 class="font-medium text-gray-800">
                                    Hasil Analisis
                                </h4>
                            </div>
                            <div class="p-4">
                                <div class="space-y-4">
                                    <div
                                        v-for="(
                                            section, index
                                        ) in formatAISummary(aiSummary.summary)"
                                        :key="index"
                                        class="border rounded-lg"
                                    >
                                        <div
                                            class="bg-gray-50 px-3 py-2 border-b"
                                        >
                                            <h5
                                                class="font-medium text-gray-700 text-sm"
                                                v-html="section.title"
                                            ></h5>
                                        </div>
                                        <div class="p-3">
                                            <div
                                                class="whitespace-pre-wrap font-sans text-sm text-gray-600 leading-relaxed ai-summary-content"
                                                v-html="section.content"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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

                    <div v-else-if="!hasAnswers" class="text-center py-4">
                        <p class="text-gray-600">
                            Tidak ada jawaban yang ditemukan
                        </p>
                    </div>

                    <div v-else>
                        <!-- Loop through all answers -->
                        <div
                            v-for="(answer, index) in rawAnswers"
                            :key="answer.id"
                            class="mb-8"
                        >
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="text-lg font-medium">
                                    Jawaban #{{ index + 1 }}
                                </h3>
                                <span class="text-sm text-gray-500">
                                    {{ formatDateTime(answer.created_at) }}
                                </span>
                            </div>

                            <!-- Points Table -->
                            <div class="mb-6">
                                <h4 class="text-md font-medium mb-3">Points</h4>
                                <table
                                    class="min-w-full border-collapse table-auto border border-gray-200"
                                >
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border border-gray-200"
                                            >
                                                Point 1
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border border-gray-200"
                                            >
                                                Point 2
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border border-gray-200"
                                            >
                                                Point 3
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border border-gray-200"
                                            >
                                                Point 4
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border border-gray-200"
                                            >
                                                Point 5
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white">
                                        <tr>
                                            <td
                                                class="px-6 py-4 text-sm text-gray-900 border border-gray-200 whitespace-normal"
                                            >
                                                {{ answer.point_1 }}
                                            </td>
                                            <td
                                                class="px-6 py-4 text-sm text-gray-900 border border-gray-200 whitespace-normal"
                                            >
                                                {{ answer.point_2 }}
                                            </td>
                                            <td
                                                class="px-6 py-4 text-sm text-gray-900 border border-gray-200 whitespace-normal"
                                            >
                                                {{ answer.point_3 }}
                                            </td>
                                            <td
                                                class="px-6 py-4 text-sm text-gray-900 border border-gray-200 whitespace-normal"
                                            >
                                                {{ answer.point_4 }}
                                            </td>
                                            <td
                                                class="px-6 py-4 text-sm text-gray-900 border border-gray-200 whitespace-normal"
                                            >
                                                {{ answer.point_5 }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Answer Section -->
                            <div>
                                <div
                                    class="flex justify-between items-center mb-2"
                                >
                                    <h4 class="text-md font-medium">Jawaban</h4>
                                    <span
                                        :class="{
                                            'bg-green-100 text-green-800':
                                                answer.status === 'submitted',
                                            'bg-yellow-100 text-yellow-800':
                                                answer.status === 'draft',
                                        }"
                                        class="px-2 py-1 text-xs font-medium rounded-full"
                                    >
                                        {{
                                            answer.status === "submitted"
                                                ? "Terkirim"
                                                : "Draft"
                                        }}
                                    </span>
                                </div>
                                <div
                                    class="bg-gray-50 p-4 rounded border border-gray-200"
                                >
                                    <p
                                        class="text-sm text-gray-800 whitespace-pre-line"
                                    >
                                        {{ answer.answer }}
                                    </p>
                                </div>
                            </div>

                            <!-- Separator except for the last item -->
                            <hr
                                v-if="index < rawAnswers.length - 1"
                                class="my-6 border-gray-200"
                            />
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
/* Styling khusus untuk AI Summary content */
.ai-summary-content b {
    font-weight: 600;
    color: #374151;
}

.ai-summary-content {
    line-height: 1.6;
}

/* Styling untuk section header yang sudah di-format bold */
h5 b {
    font-weight: 700;
    color: #111827;
}
</style>
