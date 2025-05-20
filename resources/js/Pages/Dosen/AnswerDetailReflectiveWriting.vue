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
        };
    },

    mounted() {
        this.fetchAnswerDetails();
    },

    methods: {
        fetchAnswerDetails() {
            this.loading = true;
            this.error = null;

            axios
                .get(
                    "/sispa/api/answers/get-details-answer-reflective-writing",
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
