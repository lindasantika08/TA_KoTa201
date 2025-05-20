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
            { key: "point_1", label: "Point 1" },
            { key: "point_2", label: "Point 2" },
            { key: "point_3", label: "Point 3" },
            { key: "point_4", label: "Point 4" },
            { key: "point_5", label: "Point 5" },
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
                    "/sispa/api/detail-answer-reflective-writing",
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

                <div>
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
