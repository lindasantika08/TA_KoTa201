<script>
import axios from "axios";
import SidebarMahasiswa from "@/Components/SidebarMahasiswa.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import DataTable from "@/Components/DataTable.vue";
import { ref, onMounted } from "vue";

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
    setup(props) {
        const Breadcrumbs = [
            {
                text: "Reflective Assessment",
                href: "/sispa/mahasiswa/assessment/self",
            },
            { text: "Detail", href: null },
        ];

        const Headers = [
            { key: "no", label: "No" },
            { key: "pertanyaan", label: "Pertanyaan" },
            { key: "alasan", label: "Alasan" },
        ];

        const studentInfo = ref({
            nim: "",
            name: "",
            class: "",
            group: "",
            project: "",
            date: "",
        });

        const groupedAnswers = ref([]);
        const isLoading = ref(true);
        const activeAspect = ref(0);
        const completionPercentage = ref(0);

        const fetchUserInfo = async () => {
            try {
                const response = await axios.get(
                    "/sispa/api/user-detail-answer",
                    {
                        params: {
                            batch_year: props.batchYear,
                            project_name: props.projectName,
                        },
                    }
                );
                studentInfo.value = response.data;
            } catch (error) {
                console.error("Error fetching user info:", error);
            }
        };

        const fetchAnswerReflective = async () => {
            try {
                isLoading.value = true;
                const response = await axios.get(
                    "/sispa/api/detail-answer-reflective",
                    {
                        params: {
                            batch_year: props.batchYear,
                            project_name: props.projectName,
                        },
                    }
                );
                console.log("Fetched answers:", response.data.answers);
                groupedAnswers.value = response.data.answers;

                // Calculate completion percentage
                if (groupedAnswers.value.length > 0) {
                    let totalAnswers = 0;
                    let completedAnswers = 0;

                    groupedAnswers.value.forEach((aspect) => {
                        aspect.answers.forEach((answer) => {
                            totalAnswers++;
                            if (answer.reason && answer.reason.trim() !== "") {
                                completedAnswers++;
                            }
                        });
                    });

                    completionPercentage.value = Math.round(
                        (completedAnswers / totalAnswers) * 100
                    );
                }

                isLoading.value = false;
            } catch (error) {
                console.error("Error fetching answers:", error);
                isLoading.value = false;
            }
        };

        const setActiveAspect = (index) => {
            activeAspect.value = index;
        };

        const getCompletionStatus = () => {
            if (completionPercentage.value === 100) return "Selesai";
            if (completionPercentage.value >= 75) return "Hampir Selesai";
            if (completionPercentage.value >= 50) return "Setengah Jalan";
            if (completionPercentage.value > 0) return "Baru Mulai";
            return "Belum Mulai";
        };

        const getCompletionClass = () => {
            if (completionPercentage.value === 100) return "bg-green-500";
            if (completionPercentage.value >= 75) return "bg-blue-500";
            if (completionPercentage.value >= 50) return "bg-yellow-500";
            if (completionPercentage.value > 0) return "bg-orange-500";
            return "bg-red-500";
        };

        onMounted(() => {
            fetchUserInfo();
            fetchAnswerReflective();
        });

        return {
            Breadcrumbs,
            Headers,
            studentInfo,
            groupedAnswers,
            isLoading,
            activeAspect,
            completionPercentage,
            setActiveAspect,
            getCompletionStatus,
            getCompletionClass,
        };
    },
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <SidebarMahasiswa role="mahasiswa" />

        <div class="flex-1">
            <Navbar userName="Mahasiswa"></Navbar>
            <main class="p-6">
                <div class="mb-4">
                    <Breadcrumb :items="Breadcrumbs" />
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
                            Memuat Data Reflective Assessment...
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
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                    </div>
                                    <h1
                                        class="text-2xl font-bold text-blue-800"
                                    >
                                        Reflective Assessment
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

                        <!-- Tabs for Aspects -->
                        <div class="mb-6">
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
                                Kriteria Penilaian
                            </h2>

                            <div class="overflow-x-auto">
                                <div
                                    class="flex space-x-2 mb-4 overflow-x-auto pb-2"
                                >
                                    <button
                                        v-for="(
                                            aspect, index
                                        ) in groupedAnswers"
                                        :key="index"
                                        @click="setActiveAspect(index)"
                                        class="px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-all duration-200"
                                        :class="
                                            activeAspect === index
                                                ? 'bg-blue-600 text-white shadow-md'
                                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                        "
                                    >
                                        Kriteria {{ index + 1 }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Answers Section -->
                        <div v-if="groupedAnswers && groupedAnswers.length">
                            <div
                                v-for="(aspect, aspectIndex) in groupedAnswers"
                                :key="aspectIndex"
                                v-show="activeAspect === aspectIndex"
                                class="bg-white rounded-lg p-6 shadow-sm"
                            >
                                <div class="bg-blue-50 p-4 rounded-lg mb-4">
                                    <h3
                                        class="text-lg font-semibold text-blue-800 mb-2 flex items-center"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5 mr-2"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                        {{ aspect.kriteria_reflective }}
                                    </h3>
                                    <p class="text-sm text-gray-600 italic">
                                        Berikut adalah jawaban anda untuk
                                        kriteria ini:
                                    </p>
                                </div>

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
                                                    class="border px-4 py-3 text-left"
                                                >
                                                    Alasan
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(
                                                    answer, answerIndex
                                                ) in aspect.answers"
                                                :key="answerIndex"
                                                class="hover:bg-blue-50 transition-colors duration-150"
                                            >
                                                <td
                                                    class="border px-4 py-3 text-center"
                                                >
                                                    <span
                                                        class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-blue-100 text-blue-800 font-semibold"
                                                    >
                                                        {{ answerIndex + 1 }}
                                                    </span>
                                                </td>
                                                <td class="border px-4 py-3">
                                                    <div
                                                        class="font-medium text-gray-700"
                                                    >
                                                        {{ answer.question }}
                                                    </div>
                                                </td>
                                                <td class="border px-4 py-3">
                                                    <div
                                                        v-if="
                                                            answer.reason &&
                                                            answer.reason.trim() !==
                                                                ''
                                                        "
                                                        class="bg-gray-50 p-2 rounded text-gray-800"
                                                    >
                                                        {{ answer.reason }}
                                                    </div>
                                                    <div
                                                        v-else
                                                        class="italic text-gray-500"
                                                    >
                                                        (Belum ada alasan yang
                                                        diberikan)
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="flex justify-between mt-4">
                                    <button
                                        v-if="aspectIndex > 0"
                                        @click="
                                            setActiveAspect(aspectIndex - 1)
                                        "
                                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg flex items-center transition-colors duration-150"
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
                                                d="M15 19l-7-7 7-7"
                                            />
                                        </svg>
                                        Kriteria Sebelumnya
                                    </button>
                                    <button
                                        v-if="
                                            aspectIndex <
                                            groupedAnswers.length - 1
                                        "
                                        @click="
                                            setActiveAspect(aspectIndex + 1)
                                        "
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center transition-colors duration-150"
                                    >
                                        Kriteria Selanjutnya
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5 ml-1"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 5l7 7-7 7"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- No data message -->
                        <div
                            v-if="
                                !groupedAnswers || groupedAnswers.length === 0
                            "
                            class="bg-white rounded-lg p-8 shadow-sm flex flex-col items-center justify-center"
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
                                Belum ada data reflective assessment yang
                                tersedia.
                            </p>
                            <p class="text-center text-gray-400 text-sm">
                                Silahkan isi reflective assessment terlebih
                                dahulu untuk melihat hasilnya.
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
