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
                {
                    key: "pertanyaan",
                    label: "Pertanyaan",
                },
                {
                    key: "kriteria",
                    label: "Aspek yang Dinilai",
                },
                {
                    key: "jawaban",
                    label: "Jawaban",
                },
            ],
        };
    },

    mounted() {
        console.log("Component mounted, fetching answer details...");
        this.fetchAnswerDetails();
    },

    methods: {
        fetchAnswerDetails() {
            this.loading = true;
            this.error = null;
            console.log("Fetching data with params:", {
                mahasiswaId: this.mahasiswaId,
                batch_year: this.batch_year,
                project_name: this.project_name,
                assessment_order: this.assessment_order,
            });

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
                    console.log("API response:", response.data);
                    if (response.data && response.data.answers) {
                        this.answers = response.data.answers;
                        console.log("Answers loaded:", this.answers);
                    } else {
                        console.error(
                            "Unexpected response format:",
                            response.data
                        );
                        this.error = "Format respons tidak sesuai";
                    }
                    this.loading = false;
                })
                .catch((error) => {
                    console.error("Error fetching answer details:", error);
                    this.loading = false;

                    if (error.response && error.response.status === 404) {
                        this.error =
                            error.response.data.message ||
                            "Data tidak ditemukan";
                    } else {
                        this.error = "Gagal memuat data. Silakan coba lagi.";
                    }

                    if (
                        error.response &&
                        error.response.data &&
                        error.response.data.message
                    ) {
                        this.error = error.response.data.message;
                    }
                });
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
                    <p>
                        <strong>Tahun Ajaran:</strong>
                        {{ batch_year }}
                    </p>
                    <p>
                        <strong>Nama Proyek:</strong>
                        {{ project_name }}
                    </p>
                    <p>
                        <strong>Assessment Order:</strong>
                        {{ assessment_order }}
                    </p>
                </div>

                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">
                        Detail Jawaban Reflektif
                    </h2>

                    <!-- Loading state -->
                    <div v-if="loading" class="text-center py-4">
                        <p class="text-gray-600">Memuat data...</p>
                    </div>

                    <!-- Error state -->
                    <div
                        v-else-if="error"
                        class="bg-red-50 text-red-600 p-4 rounded-md"
                    >
                        {{ error }}
                    </div>

                    <!-- Empty state -->
                    <div
                        v-else-if="!answers || answers.length === 0"
                        class="text-center py-4"
                    >
                        <p class="text-gray-600">
                            Tidak ada jawaban yang ditemukan
                        </p>
                    </div>

                    <!-- Data table -->
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
            </main>
        </div>
    </div>
</template>
