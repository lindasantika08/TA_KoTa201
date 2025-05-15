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
            isLoading: true,
            selectedAspect: "all",
        };
    },
    computed: {
        filteredAspects() {
            return Object.keys(this.groupedAnswers);
        },
        totalAnswers() {
            let count = 0;
            for (const aspect in this.groupedAnswers) {
                count += this.groupedAnswers[aspect].answers.length;
            }
            return count;
        },
        completionPercentage() {
            if (this.totalAnswers === 0) return 0;

            let completedCount = 0;
            for (const aspect in this.groupedAnswers) {
                completedCount += this.groupedAnswers[aspect].answers.filter(
                    (answer) => answer.reason && answer.reason.trim() !== ""
                ).length;
            }

            return Math.round((completedCount / this.totalAnswers) * 100);
        },
        filteredGroupedAnswers() {
            if (this.selectedAspect === "all") {
                return this.groupedAnswers;
            }

            const filtered = {};
            if (this.groupedAnswers[this.selectedAspect]) {
                filtered[this.selectedAspect] =
                    this.groupedAnswers[this.selectedAspect];
            }
            return filtered;
        },
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
            }
        },
        async fetchAnswerSelf() {
            try {
                this.isLoading = true;
                const response = await axios.get(
                    "/sispa/api/detail-answer-self",
                    {
                        params: {
                            batch_year: this.batchYear,
                            project_name: this.projectName,
                        },
                    }
                );
                this.groupedAnswers = response.data.answers.reduce(
                    (acc, aspect) => {
                        acc[aspect.aspect] = aspect;
                        return acc;
                    },
                    {}
                );
                this.isLoading = false;
            } catch (error) {
                console.error("Error fetching answers:", error);
                this.isLoading = false;
            }
        },
        getScaleColor(scale) {
            const scaleNum = parseInt(scale);
            if (scaleNum >= 4) return "bg-green-100 text-green-800";
            if (scaleNum >= 3) return "bg-blue-100 text-blue-800";
            if (scaleNum >= 2) return "bg-yellow-100 text-yellow-800";
            return "bg-red-100 text-red-800";
        },
        getCompletionStatus() {
            if (this.completionPercentage === 100) return "Selesai";
            if (this.completionPercentage >= 75) return "Hampir Selesai";
            if (this.completionPercentage >= 50) return "Setengah Jalan";
            if (this.completionPercentage > 0) return "Baru Mulai";
            return "Belum Mulai";
        },
        getCompletionClass() {
            if (this.completionPercentage === 100) return "bg-green-500";
            if (this.completionPercentage >= 75) return "bg-blue-500";
            if (this.completionPercentage >= 50) return "bg-yellow-500";
            if (this.completionPercentage > 0) return "bg-orange-500";
            return "bg-red-500";
        },
    },
    created() {
        this.fetchUserInfo();
        this.fetchAnswerSelf();
    },
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <SidebarMahasiswa role="mahasiswa" />

        <div class="flex-1">
            <Navbar userName="Mahasiswa" />
            <main class="p-6">
                <div class="mb-4">
                    <Breadcrumb :items="breadcrumbs" />
                </div>

                <div
                    v-if="isLoading"
                    class="flex justify-center items-center h-64"
                >
                    <div class="flex flex-col items-center">
                        <div
                            class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-blue-600"
                        ></div>
                        <p class="mt-4 text-lg text-blue-600 font-medium">
                            Memuat Data Self Assessment...
                        </p>
                    </div>
                </div>

                <div v-else>
                    <Card class="shadow-lg mb-6">
                        <template #title>
                            <div
                                class="flex flex-col sm:flex-row sm:justify-between sm:items-center p-2"
                            >
                                <div
                                    class="flex items-center space-x-3 mb-4 sm:mb-0"
                                >
                                    <div class="bg-blue-100 p-2 rounded-full">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-8 w-8 text-blue-600"
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
                                    <h1
                                        class="text-2xl font-bold text-blue-800"
                                    >
                                        HASIL PENGISIAN SELF ASSESSMENT
                                    </h1>
                                </div>
                                <div class="flex flex-col">
                                    <div class="flex items-center mb-1">
                                        <span
                                            class="text-sm font-medium text-gray-600 mr-2"
                                            >Status:</span
                                        >
                                        <span
                                            class="px-2 py-1 text-xs font-semibold rounded-full"
                                            :class="
                                                completionPercentage === 100
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-yellow-100 text-yellow-800'
                                            "
                                        >
                                            {{ getCompletionStatus() }}
                                        </span>
                                    </div>
                                    <div
                                        class="w-full bg-gray-200 rounded-full h-2.5"
                                    >
                                        <div
                                            class="h-2.5 rounded-full"
                                            :class="getCompletionClass()"
                                            :style="`width: ${completionPercentage}%`"
                                        ></div>
                                    </div>
                                    <div
                                        class="text-xs text-right mt-1 text-gray-600"
                                    >
                                        {{ completionPercentage }}% selesai
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Student Info Section -->
                        <div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
                            <h2
                                class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2 flex items-center"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 mr-2 text-blue-600"
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
                                Informasi Mahasiswa
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div
                                    class="bg-blue-50 p-4 rounded-lg shadow-sm"
                                >
                                    <div class="space-y-3">
                                        <div class="flex items-center">
                                            <span
                                                class="text-gray-600 font-medium w-32"
                                                >NIM</span
                                            >
                                            <span
                                                class="text-gray-800 font-semibold"
                                                >: {{ studentInfo.nim }}</span
                                            >
                                        </div>
                                        <div class="flex items-center">
                                            <span
                                                class="text-gray-600 font-medium w-32"
                                                >Nama Lengkap</span
                                            >
                                            <span
                                                class="text-gray-800 font-semibold"
                                                >: {{ studentInfo.name }}</span
                                            >
                                        </div>
                                        <div class="flex items-center">
                                            <span
                                                class="text-gray-600 font-medium w-32"
                                                >Kelas</span
                                            >
                                            <span
                                                class="text-gray-800 font-semibold"
                                                >: {{ studentInfo.class }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="bg-blue-50 p-4 rounded-lg shadow-sm"
                                >
                                    <div class="space-y-3">
                                        <div class="flex items-center">
                                            <span
                                                class="text-gray-600 font-medium w-32"
                                                >Kelompok</span
                                            >
                                            <span
                                                class="text-gray-800 font-semibold"
                                                >: {{ studentInfo.group }}</span
                                            >
                                        </div>
                                        <div class="flex items-center">
                                            <span
                                                class="text-gray-600 font-medium w-32"
                                                >Proyek</span
                                            >
                                            <span
                                                class="text-gray-800 font-semibold"
                                                >:
                                                {{ studentInfo.project }}</span
                                            >
                                        </div>
                                        <div class="flex items-center">
                                            <span
                                                class="text-gray-600 font-medium w-32"
                                                >Tanggal</span
                                            >
                                            <span
                                                class="text-gray-800 font-semibold"
                                                >: {{ studentInfo.date }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Assessment Results Section -->
                        <div class="bg-white rounded-lg p-6 shadow-sm">
                            <h2
                                class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2 flex items-center"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 mr-2 text-blue-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                    />
                                </svg>
                                Hasil Penilaian
                                <span
                                    class="ml-2 text-sm font-normal text-gray-500"
                                >
                                    ({{ totalAnswers }} Penilaian)
                                </span>
                            </h2>

                            <div
                                v-if="
                                    Object.keys(filteredGroupedAnswers).length >
                                    0
                                "
                            >
                                <div
                                    v-for="(
                                        aspectData, aspectName
                                    ) in filteredGroupedAnswers"
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
                                            <table
                                                class="min-w-full border border-gray-200 mb-4 rounded-lg overflow-hidden"
                                            >
                                                <thead>
                                                    <tr class="bg-gray-100">
                                                        <th
                                                            class="border px-4 py-3 text-center w-16"
                                                        >
                                                            No
                                                        </th>
                                                        <th
                                                            class="border px-4 py-3 text-left"
                                                        >
                                                            Pertanyaan
                                                        </th>
                                                        <th
                                                            class="border px-4 py-3 text-center w-24"
                                                        >
                                                            Skala
                                                        </th>
                                                        <th
                                                            class="border px-4 py-3 text-left"
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
                                                        class="hover:bg-blue-50 transition-colors duration-150"
                                                    >
                                                        <td
                                                            class="border px-4 py-3 text-center"
                                                        >
                                                            <span
                                                                class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-blue-100 text-blue-800 font-semibold"
                                                            >
                                                                {{ index + 1 }}
                                                            </span>
                                                        </td>
                                                        <td
                                                            class="border px-4 py-3"
                                                        >
                                                            <div
                                                                class="font-medium text-gray-700"
                                                            >
                                                                {{
                                                                    answer.question
                                                                }}
                                                            </div>
                                                        </td>
                                                        <td
                                                            class="border px-4 py-3 text-center"
                                                        >
                                                            <span
                                                                class="inline-block px-3 py-1 rounded-full text-sm font-semibold"
                                                                :class="
                                                                    getScaleColor(
                                                                        answer.scale
                                                                    )
                                                                "
                                                            >
                                                                {{
                                                                    answer.scale
                                                                }}
                                                            </span>
                                                        </td>
                                                        <td
                                                            class="border px-4 py-3"
                                                        >
                                                            <div
                                                                v-if="
                                                                    answer.reason &&
                                                                    answer.reason.trim() !==
                                                                        ''
                                                                "
                                                                class="bg-gray-50 p-2 rounded text-gray-800"
                                                            >
                                                                {{
                                                                    answer.reason
                                                                }}
                                                            </div>
                                                            <div
                                                                v-else
                                                                class="italic text-gray-500"
                                                            >
                                                                (Belum ada
                                                                alasan yang
                                                                diberikan)
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </Card>
                                </div>
                            </div>

                            <div
                                v-else
                                class="bg-blue-50 p-6 rounded-lg text-center"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-16 w-16 mx-auto text-blue-400 mb-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <p
                                    class="text-blue-600 font-medium text-lg mb-2"
                                >
                                    Tidak ada data yang ditemukan
                                </p>
                                <p class="text-gray-600">
                                    Silakan gunakan filter lain atau kembali ke
                                    tampilan semua data
                                </p>

                                <button
                                    @click="selectedAspect = 'all'"
                                    class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center mx-auto"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5 mr-1"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                        />
                                    </svg>
                                    Reset Filter
                                </button>
                            </div>
                        </div>

                        <!-- No data message -->
                        <div
                            v-if="Object.keys(groupedAnswers).length === 0"
                            class="bg-white rounded-lg p-8 shadow-sm flex flex-col items-center justify-center mt-6"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-16 w-16 text-gray-400 mb-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            <p class="text-center text-gray-500 text-lg mb-2">
                                Belum ada data self assessment yang tersedia.
                            </p>
                            <p class="text-center text-gray-400 text-sm">
                                Silahkan isi self assessment terlebih dahulu
                                untuk melihat hasilnya.
                            </p>
                        </div>
                    </Card>
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
.animation-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}
</style>
