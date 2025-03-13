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
            tableHeaders: [
                { key: "index", label: "No", width: "5%", sortable: false },
                { key: "pertanyaan", label: "Pertanyaan", width: "50%" },
                { key: "jawaban", label: "Jawaban", width: "30%" },
                { key: "skor", label: "Skor", width: "15%", align: "center" },
                {
                    key: "score_SLA",
                    label: "Skor SLA",
                    width: "15%",
                    align: "center",
                },
                {
                    key: "similarity",
                    label: "similarity",
                    width: "15%",
                    align: "center",
                },
            ],
            loading: true,
            error: null,
        };
    },
    computed: {
        totalScore() {
            return this.answers.reduce(
                (sum, answer) => sum + (parseFloat(answer.skor) || 0),
                0
            );
        },
        averageScore() {
            return this.answers.length > 0
                ? (this.totalScore / this.answers.length).toFixed(2)
                : "0.00";
        },
    },
    mounted() {
        this.fetchAnswerDetails();
    },
    methods: {
        fetchAnswerDetails() {
            this.loading = true;
            this.error = null;
            axios
                .get("/api/answers/get-details", {
                    params: {
                        mahasiswaId: this.mahasiswaId, // Make sure this is the actual ID
                        batch_year: this.batch_year,
                        project_name: this.project_name,
                        project_id: this.project_id,
                        assessment_order: this.assessment_order,
                    },
                })
                .then((response) => {
                    this.answers = response.data.answers;
                    this.loading = false;
                })
                .catch((error) => {
                    console.error("Error fetching answer details:", error);
                    this.error = "Gagal memuat data. Silakan coba lagi.";
                    this.loading = false;
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
                <Breadcrumb
                    :items="[
                        { text: 'Self Assessment', href: '' },
                        { text: 'Detail', href: '' },
                    ]"
                />

                <div class="bg-white border rounded-lg p-6 shadow-sm">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800 mb-2">
                                Detail Jawaban: {{ mahasiswaName }}
                            </h1>
                            <div class="text-sm text-gray-600 space-y-1">
                                <p>
                                    <strong>Tahun Ajaran:</strong>
                                    {{ batch_year }}
                                </p>
                                <p>
                                    <strong>Nama Proyek:</strong>
                                    {{ project_name }}
                                </p>
                            </div>
                        </div>
                        <div
                            class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-center"
                        >
                            <p class="text-sm text-gray-600">Rata-rata Skor</p>
                            <p class="text-2xl font-bold text-blue-600">
                                {{ averageScore }}
                            </p>
                        </div>
                    </div>

                    <div v-if="loading" class="text-center py-6 text-gray-500">
                        Memuat data...
                    </div>

                    <div
                        v-else-if="error"
                        class="text-center py-6 text-red-500"
                    >
                        {{ error }}
                    </div>

                    <div
                        v-else-if="answers.length === 0"
                        class="text-center text-gray-500 py-6"
                    >
                        Belum ada jawaban untuk pengguna ini.
                    </div>

                    <div v-else>
                        <DataTable
                            :headers="tableHeaders"
                            :items="answers"
                            class="border rounded-lg overflow-hidden"
                        >
                            <template #column-index="{ index }">
                                {{ index + 1 }}
                            </template>

                            <template #column-pertanyaan="{ item }">
                                <div class="text-gray-800 font-medium">
                                    {{ item.pertanyaan }}
                                </div>
                            </template>

                            <template #column-jawaban="{ item }">
                                <div class="text-gray-600">
                                    {{ item.jawaban }}
                                </div>
                            </template>

                            <template #column-skor="{ item }">
                                <div
                                    class="text-center font-bold"
                                    :class="{
                                        'text-green-600': item.skor >= 7,
                                        'text-yellow-600':
                                            item.skor >= 5 && item.skor < 7,
                                        'text-red-600': item.skor < 5,
                                    }"
                                >
                                    {{ item.skor }}
                                </div>
                            </template>

                            <template #column-score_SLA="{ item }">
                                <div class="text-gray-600">
                                    {{ item.score_SLA }}
                                </div>
                            </template>

                            <template #column-similarity="{ item }">
                                <div class="text-gray-600">
                                    {{ item.similarity }}
                                </div>
                            </template>
                        </DataTable>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>
