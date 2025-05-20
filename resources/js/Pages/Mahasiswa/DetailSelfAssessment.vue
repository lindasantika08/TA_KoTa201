<script>
import axios from "axios";
import SidebarMahasiswa from "@/Components/SidebarMahasiswa.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import DataTable from "@/Components/DataTable.vue";

export default {
    props: {
        batchYear: {
            type: String,
            required: true,
        },
        projectName: {
            type: String,
            required: true,
        },
        assessmentOrder: {
            type: String,
            required: true,
        },
    },
    components: {
        SidebarMahasiswa,
        Navbar,
        Card,
        Breadcrumb,
        DataTable,
    },
    data() {
        return {
            breadcrumbs: [
                {
                    text: "Self Assessment",
                    href: "/sispa/mahasiswa/assessment/self",
                },
                { text: "Detail", href: null },
            ],
            headers: [
                { key: "no", label: "No" },
                { key: "pertanyaan", label: "Pertanyaan" },
                { key: "skala", label: "Skala" },
                { key: "alasan", label: "Alasan" },
            ],
            studentInfo: {
                nim: "",
                name: "",
                class: "",
                group: "",
                project: "",
                date: "",
            },
            groupedAnswers: {},
            loading: true,
            error: null,
        };
    },
    methods: {
        async fetchUserInfo() {
            try {
                const response = await axios.get(
                    "/sispa/api/user-detail-answer",
                    {
                        params: {
                            batch_year: this.batchYear,
                            project_name: this.projectName,
                        },
                    }
                );
                this.studentInfo = response.data;
            } catch (error) {
                console.error("Error fetching user info:", error);
                this.error = "Gagal memuat informasi mahasiswa";
            }
        },
        async fetchAnswerSelf() {
            this.loading = true;
            try {
                console.log("Fetching answers with params:", {
                    batch_year: this.batchYear,
                    project_name: this.projectName,
                    assessment_order: this.assessmentOrder,
                });

                const response = await axios.get(
                    "/sispa/api/detail-answer-self",
                    {
                        params: {
                            batch_year: this.batchYear,
                            project_name: this.projectName,
                            assessment_order: this.assessmentOrder,
                        },
                    }
                );

                console.log("Received answer data:", response.data);

                if (response.data.answers && response.data.answers.length > 0) {
                    this.groupedAnswers = response.data.answers.reduce(
                        (acc, aspect) => {
                            acc[aspect.aspect] = aspect;
                            return acc;
                        },
                        {}
                    );
                } else {
                    console.warn("No answers found in response");
                    this.groupedAnswers = {};
                }
            } catch (error) {
                console.error("Error fetching answers:", error);
                this.error = "Gagal memuat data penilaian";
            } finally {
                this.loading = false;
            }
        },
        getScaleColor(scale) {
            const scaleNum = parseInt(scale);
            if (scaleNum >= 4) return "bg-green-100 text-green-800";
            if (scaleNum >= 3) return "bg-blue-100 text-blue-800";
            if (scaleNum >= 2) return "bg-yellow-100 text-yellow-800";
            return "bg-red-100 text-red-800";
        },
    },
    created() {
        if (this.batchYear && this.projectName && this.assessmentOrder) {
            console.log("Component created with props:", {
                batchYear: this.batchYear,
                projectName: this.projectName,
                assessmentOrder: this.assessmentOrder,
            });
            this.fetchUserInfo();
            this.fetchAnswerSelf();
        } else {
            console.error("Missing required props:", {
                batchYear: this.batchYear,
                projectName: this.projectName,
                assessmentOrder: this.assessmentOrder,
            });
            this.error = "Parameter yang dibutuhkan tidak lengkap";
            this.loading = false;
        }
    },
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <SidebarMahasiswa role="mahasiswa" />

        <div class="flex-1">
            <Navbar userName="Mahasiswa" />
            <main class="p-6">
                <div class="mb-4">
                    <Breadcrumb :items="breadcrumbs" />
                </div>

                <!-- Main Card -->
                <Card class="shadow-lg">
                    <template #title>
                        <div class="flex items-center space-x-2 text-blue-700">
                            <h1 class="text-2xl font-bold">
                                HASIL PENGISIAN SELF ASSESSMENT
                            </h1>
                        </div>
                    </template>

                    <!-- Loading State -->
                    <div
                        v-if="loading"
                        class="py-8 flex justify-center items-center"
                    >
                        <div class="flex flex-col items-center">
                            <div
                                class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-700"
                            ></div>
                            <p class="mt-4 text-gray-600">Memuat data...</p>
                        </div>
                    </div>

                    <!-- Error State -->
                    <div
                        v-else-if="error"
                        class="bg-red-50 text-red-700 p-4 rounded-lg mb-6"
                    >
                        <div class="flex items-center">
                            <svg
                                class="w-5 h-5 mr-2"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                ></path>
                            </svg>
                            <p>{{ error }}</p>
                        </div>
                    </div>

                    <div v-else>
                        <!-- Student Info Section -->
                        <div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
                            <h2
                                class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2"
                            >
                                Informasi Mahasiswa
                            </h2>
                            <div class="grid grid-cols-2 gap-8">
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <span
                                            class="text-gray-600 font-medium w-32"
                                            >NIM</span
                                        >
                                        <span class="text-gray-800"
                                            >: {{ studentInfo.nim }}</span
                                        >
                                    </div>
                                    <div class="flex items-center">
                                        <span
                                            class="text-gray-600 font-medium w-32"
                                            >Nama Lengkap</span
                                        >
                                        <span class="text-gray-800"
                                            >: {{ studentInfo.name }}</span
                                        >
                                    </div>
                                    <div class="flex items-center">
                                        <span
                                            class="text-gray-600 font-medium w-32"
                                            >Kelas</span
                                        >
                                        <span class="text-gray-800"
                                            >: {{ studentInfo.class }}</span
                                        >
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <span
                                            class="text-gray-600 font-medium w-32"
                                            >Kelompok</span
                                        >
                                        <span class="text-gray-800"
                                            >: {{ studentInfo.group }}</span
                                        >
                                    </div>
                                    <div class="flex items-center">
                                        <span
                                            class="text-gray-600 font-medium w-32"
                                            >Proyek</span
                                        >
                                        <span class="text-gray-800"
                                            >: {{ studentInfo.project }}</span
                                        >
                                    </div>
                                    <div class="flex items-center">
                                        <span
                                            class="text-gray-600 font-medium w-32"
                                            >Tanggal</span
                                        >
                                        <span class="text-gray-800"
                                            >: {{ studentInfo.date }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- No Data State -->
                        <div
                            v-if="Object.keys(groupedAnswers).length === 0"
                            class="text-center py-8"
                        >
                            <svg
                                class="w-16 h-16 mx-auto text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                ></path>
                            </svg>
                            <p class="mt-4 text-gray-600">
                                Tidak ada data penilaian yang ditemukan.
                            </p>
                        </div>

                        <!-- Assessment Results Section -->
                        <div
                            v-else
                            v-for="(aspectData, aspectName) in groupedAnswers"
                            :key="aspectName"
                            class="mb-6 bg-white rounded-lg shadow-sm"
                        >
                            <Card class="border-none shadow-none">
                                <template #title>
                                    <div
                                        class="flex items-center space-x-2 bg-blue-50 p-3 rounded-t-lg border-b"
                                    >
                                        <h2
                                            class="text-lg font-semibold text-blue-700"
                                        >
                                            Aspek: {{ aspectName }}
                                        </h2>
                                    </div>
                                </template>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full">
                                        <thead>
                                            <tr class="bg-gray-50">
                                                <th
                                                    class="px-4 py-3 text-left text-sm font-semibold text-gray-600 w-16"
                                                >
                                                    No
                                                </th>
                                                <th
                                                    class="px-4 py-3 text-left text-sm font-semibold text-gray-600"
                                                >
                                                    Pertanyaan
                                                </th>
                                                <th
                                                    class="px-4 py-3 text-center text-sm font-semibold text-gray-600 w-24"
                                                >
                                                    Skala
                                                </th>
                                                <th
                                                    class="px-4 py-3 text-left text-sm font-semibold text-gray-600"
                                                >
                                                    Alasan
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(
                                                    answer, index
                                                ) in aspectData.answers"
                                                :key="index"
                                                class="border-t hover:bg-gray-50"
                                            >
                                                <td
                                                    class="px-4 py-3 text-gray-600"
                                                >
                                                    {{ index + 1 }}
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-gray-800"
                                                >
                                                    {{ answer.question }}
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-center"
                                                >
                                                    <span
                                                        class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-sm font-medium"
                                                        :class="
                                                            getScaleColor(
                                                                answer.scale
                                                            )
                                                        "
                                                    >
                                                        {{ answer.scale }}
                                                    </span>
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-gray-700"
                                                >
                                                    {{ answer.reason }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </Card>
                        </div>
                    </div>
                </Card>
            </main>
        </div>
    </div>
</template>
