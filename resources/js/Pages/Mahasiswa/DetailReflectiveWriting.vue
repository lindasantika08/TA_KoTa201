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
                href: "/mahasiswa/assessment/self",
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
        const collapsedCards = ref({});

        const toggleCard = (groupIndex, writingIndex) => {
            const key = `${groupIndex}-${writingIndex}`;
            collapsedCards.value[key] = !collapsedCards.value[key];
        };

        const isCardCollapsed = (groupIndex, writingIndex) => {
            const key = `${groupIndex}-${writingIndex}`;
            return collapsedCards.value[key] === true;
        };

        const toggleGroup = (groupIndex) => {
            collapsedCards.value[`group-${groupIndex}`] =
                !collapsedCards.value[`group-${groupIndex}`];
        };

        const isGroupCollapsed = (groupIndex) => {
            return collapsedCards.value[`group-${groupIndex}`] === true;
        };

        const fetchUserInfo = async () => {
            try {
                const response = await axios.get(
                    "/api/user-detail-answer",
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
                    "/api/detail-answer-reflective-writing",
                    {
                        params: {
                            batch_year: props.batchYear,
                            project_name: props.projectName,
                        },
                    }
                );
                console.log("Fetched answers:", response.data.answers);
                groupedAnswers.value = response.data.answers;

                // Initialize collapsed state for all cards
                groupedAnswers.value.forEach((group, groupIndex) => {
                    collapsedCards.value[`group-${groupIndex}`] = false;
                    group.answers.forEach((writing, writingIndex) => {
                        const key = `${groupIndex}-${writingIndex}`;
                        collapsedCards.value[key] = false;
                    });
                });

                // Calculate completion percentage
                if (groupedAnswers.value.length > 0) {
                    let totalAnswers = 0;
                    let completedAnswers = 0;

                    groupedAnswers.value.forEach((aspect) => {
                        aspect.answers.forEach((writing) => {
                            totalAnswers++;
                            if (
                                writing.answer &&
                                writing.answer.trim() !== ""
                            ) {
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
            completionPercentage,
            toggleCard,
            isCardCollapsed,
            toggleGroup,
            isGroupCollapsed,
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

                                <!-- Progress Bar -->
                                <div class="flex flex-col items-center">
                                    <div class="text-gray-700 font-medium mb-1">
                                        Progres Pengisian
                                    </div>
                                    <div
                                        class="w-full h-4 bg-gray-200 rounded-full"
                                    >
                                        <div
                                            class="h-full bg-green-500 rounded-full transition-all duration-500"
                                            :style="{
                                                width:
                                                    completionPercentage + '%',
                                            }"
                                        ></div>
                                    </div>
                                    <div class="text-sm text-gray-600 mt-1">
                                        {{ completionPercentage }}% Selesai
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

                        <!-- Reflective Writings Section -->
                        <div
                            v-for="(group, groupIndex) in groupedAnswers"
                            :key="groupIndex"
                            class="mb-8"
                        >
                            <div class="bg-white rounded-lg p-4 shadow-sm">
                                <!-- Group Header - Clickable -->
                                <div
                                    @click="toggleGroup(groupIndex)"
                                    class="flex justify-between items-center cursor-pointer border-b pb-3 mb-4 hover:bg-blue-50 rounded-t-lg p-2"
                                >
                                    <h2 class="text-xl font-bold text-blue-700">
                                        {{ group.type }}
                                    </h2>
                                    <div class="flex items-center">
                                        <button
                                            class="p-1 rounded-full hover:bg-blue-100"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-6 w-6 text-blue-600 transition-transform duration-300"
                                                :class="{
                                                    'transform rotate-180':
                                                        !isGroupCollapsed(
                                                            groupIndex
                                                        ),
                                                }"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M19 9l-7 7-7-7"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Group Content -->
                                <div v-show="!isGroupCollapsed(groupIndex)">
                                    <div
                                        v-for="(
                                            writing, writingIndex
                                        ) in group.answers"
                                        :key="writingIndex"
                                        class="mb-6"
                                    >
                                        <div class="bg-gray-50 rounded-lg">
                                            <!-- Reflective Writing Header - Clickable -->
                                            <div
                                                @click="
                                                    toggleCard(
                                                        groupIndex,
                                                        writingIndex
                                                    )
                                                "
                                                class="p-4 cursor-pointer flex justify-between items-center hover:bg-gray-100 rounded-t-lg"
                                            >
                                                <h3
                                                    class="text-lg font-semibold text-gray-800"
                                                >
                                                    Reflective Writing
                                                </h3>
                                                <button
                                                    class="p-1 rounded-full hover:bg-gray-200"
                                                >
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        class="h-5 w-5 text-gray-600 transition-transform duration-300"
                                                        :class="{
                                                            'transform rotate-180':
                                                                !isCardCollapsed(
                                                                    groupIndex,
                                                                    writingIndex
                                                                ),
                                                        }"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 9l-7 7-7-7"
                                                        />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Reflective Writing Content - Collapsible -->
                                            <div
                                                v-show="
                                                    !isCardCollapsed(
                                                        groupIndex,
                                                        writingIndex
                                                    )
                                                "
                                                class="p-4 pt-0"
                                            >
                                                <!-- Points Section - Horizontal Table -->
                                                <div class="mb-4">
                                                    <h4
                                                        class="font-medium text-blue-600 mb-2"
                                                    >
                                                        Points yang harus
                                                        direfleksikan:
                                                    </h4>

                                                    <div
                                                        class="overflow-x-auto"
                                                    >
                                                        <table
                                                            class="min-w-full bg-white rounded-lg border border-gray-200"
                                                        >
                                                            <thead>
                                                                <tr>
                                                                    <th
                                                                        v-for="(
                                                                            _,
                                                                            pointIndex
                                                                        ) in writing.points"
                                                                        :key="
                                                                            pointIndex
                                                                        "
                                                                        class="py-2 px-3 border-b border-r text-left text-sm font-medium text-gray-700 uppercase tracking-wider"
                                                                    >
                                                                        Point
                                                                        {{
                                                                            pointIndex +
                                                                            1
                                                                        }}
                                                                    </th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr>
                                                                    <td
                                                                        v-for="(
                                                                            point,
                                                                            pointIndex
                                                                        ) in writing.points"
                                                                        :key="
                                                                            pointIndex
                                                                        "
                                                                        class="py-2 px-3 border-r text-sm text-gray-800 align-top"
                                                                    >
                                                                        {{
                                                                            point
                                                                        }}
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>

                                                <!-- Answer Section -->
                                                <div class="mt-4">
                                                    <h4
                                                        class="font-medium text-green-600 mb-2"
                                                    >
                                                        Jawaban Refleksi:
                                                    </h4>
                                                    <div
                                                        class="bg-white p-4 rounded border border-gray-200"
                                                    >
                                                        <p
                                                            v-if="
                                                                writing.answer &&
                                                                writing.answer.trim() !==
                                                                    ''
                                                            "
                                                            class="text-gray-800"
                                                        >
                                                            {{ writing.answer }}
                                                        </p>
                                                        <p
                                                            v-else
                                                            class="text-gray-500 italic"
                                                        >
                                                            Belum ada jawaban
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div
                            v-if="groupedAnswers.length === 0"
                            class="bg-white rounded-lg p-8 text-center"
                        >
                            <div class="flex flex-col items-center">
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
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                                <h3
                                    class="text-xl font-medium text-gray-700 mb-2"
                                >
                                    Belum Ada Data
                                </h3>
                                <p class="text-gray-500">
                                    Saat ini belum ada reflective writing yang
                                    tersedia untuk proyek ini.
                                </p>
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
