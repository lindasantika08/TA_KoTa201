<script>
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import axios from "axios";

export default {
    components: {
        Sidebar,
        Navbar,
        Card,
        Breadcrumb,
    },
    props: {
        batchYear: String,
        projectName: String,
        currentOrder: {
            type: Number,
            required: true,
        },
        totalOrders: {
            type: Number,
            required: true,
        },
        assessments: {
            type: Array,
            required: true,
        },
    },
    setup(props) {
        const showDropdown = ref(false);
        const page = usePage();
        const loading = ref(false);
        const infoModalOpen = ref(false);

        const breadcrumbs = [
            {
                text: "Reflective Assessment",
                href: "/sispa/dosen/reflectiveAssessment",
            },
            { text: "Detail", href: null },
        ];

        const toggleDropdown = () => {
            showDropdown.value = !showDropdown.value;
        };

        const toggleInfoModal = () => {
            infoModalOpen.value = !infoModalOpen.value;
        };

        const groupedAssessments = computed(() => {
            const groups = {};
            props.assessments.forEach((assessment) => {
                if (assessment.reflective_rubric) {
                    const aspect =
                        assessment.reflective_rubric.aspect || "Uncategorized";
                    if (!groups[aspect]) {
                        groups[aspect] = [];
                    }
                    groups[aspect].push(assessment);
                }
            });
            return groups;
        });

        const totalQuestions = computed(() => {
            return props.assessments.length;
        });

        const aspectCount = computed(() => {
            return Object.keys(groupedAssessments.value).length;
        });

        const changeOrder = (newOrder) => {
            loading.value = true;
            router.get(
                "/sispa/dosen/reflectiveAssessment/detail",
                {
                    batch_year: props.batchYear,
                    project_name: props.projectName,
                    reflective_assessment_order: newOrder,
                },
                {
                    preserveState: true,
                    onSuccess: () => {
                        loading.value = false;
                    },
                }
            );
        };

        const fetchQuestionId = async () => {
            try {
                const response = await axios.get(
                    "/sispa/api/get-reflective-question-id",
                    {
                        params: {
                            batch_year: props.batchYear,
                            project_name: props.projectName,
                            reflective_assessment_order: props.currentOrder,
                        },
                    }
                );

                if (response.data) {
                    return response.data.questionId;
                }
            } catch (error) {
                console.error("Error fetching QuestionId:", error);
                alert("Failed to get QuestionId. Please check your data.");
                return null;
            }
        };

        const handleAnswers = async () => {
            loading.value = true;
            const questionId = await fetchQuestionId();
            if (!questionId) {
                loading.value = false;
                return;
            }

            router.visit("/sispa/dosen/ReflectiveAnswer", {
                method: "get",
                data: {
                    batch_year: props.batchYear,
                    project_name: props.projectName,
                    reflective_assessment_order: props.currentOrder,
                },
            });
        };

        return {
            breadcrumbs,
            toggleDropdown,
            showDropdown,
            groupedAssessments,
            handleAnswers,
            changeOrder,
            loading,
            totalQuestions,
            aspectCount,
            toggleInfoModal,
            infoModalOpen,
        };
    },
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <Sidebar role="dosen" />
        <div class="flex-1 flex flex-col">
            <Navbar userName="Dosen" />
            <main class="p-6 flex-1">
                <!-- Header Section -->
                <div class="mb-6">
                    <Breadcrumb :items="breadcrumbs" />
                    <div
                        class="mt-4 flex flex-col md:flex-row md:justify-between md:items-center gap-4"
                    >
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                Reflective Assessment
                            </h1>
                            <div class="mt-2 text-sm text-gray-600">
                                <div class="flex flex-wrap gap-2 items-center">
                                    <div
                                        class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full flex items-center gap-2"
                                    >
                                        <font-awesome-icon
                                            icon="fa-solid fa-folder-open"
                                        />
                                        <span class="font-medium">{{
                                            projectName
                                        }}</span>
                                    </div>
                                    <div
                                        class="px-3 py-1 bg-green-50 text-green-700 rounded-full flex items-center gap-2"
                                    >
                                        <font-awesome-icon
                                            icon="fa-solid fa-calendar"
                                        />
                                        <span class="font-medium">{{
                                            batchYear
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assessment Overview Card -->
                <div
                    class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden"
                >
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2
                                class="text-lg font-semibold text-gray-900 flex items-center"
                            >
                                <font-awesome-icon
                                    icon="fa-solid fa-clipboard-check"
                                    class="mr-2 text-blue-600"
                                />
                                Assessment Overview
                            </h2>
                            <button
                                @click="toggleInfoModal"
                                class="text-blue-600 hover:text-blue-800 focus:outline-none"
                                title="Assessment Information"
                            >
                                <font-awesome-icon
                                    icon="fa-solid fa-circle-info"
                                    class="text-lg"
                                />
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div
                                class="bg-blue-50 p-4 rounded-lg flex items-center"
                            >
                                <div class="rounded-full bg-blue-100 p-3 mr-3">
                                    <font-awesome-icon
                                        icon="fa-solid fa-list-ol"
                                        class="text-blue-600"
                                    />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Assessment Order
                                    </p>
                                    <div class="flex items-center">
                                        <div class="relative">
                                            <button
                                                @click="toggleDropdown"
                                                type="button"
                                                class="inline-flex justify-center items-center rounded-md border border-gray-300 shadow-sm px-3 py-1 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                            >
                                                <span class="font-semibold"
                                                    >Order
                                                    {{ currentOrder }}</span
                                                >
                                                <font-awesome-icon
                                                    icon="fa-solid fa-chevron-down"
                                                    class="ml-2 text-xs"
                                                />
                                            </button>
                                            <div
                                                v-if="showDropdown"
                                                class="origin-top-left absolute left-0 mt-2 w-32 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none z-10"
                                            >
                                                <div class="py-1">
                                                    <button
                                                        v-for="i in totalOrders"
                                                        :key="i"
                                                        @click="
                                                            changeOrder(i);
                                                            toggleDropdown();
                                                        "
                                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                        :class="{
                                                            'bg-blue-50 text-blue-700 font-medium':
                                                                i ===
                                                                currentOrder,
                                                        }"
                                                    >
                                                        Order {{ i }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="text-xs text-gray-500 ml-2"
                                            >of {{ totalOrders }}</span
                                        >
                                    </div>
                                </div>
                            </div>

                            <div
                                class="bg-green-50 p-4 rounded-lg flex items-center"
                            >
                                <div class="rounded-full bg-green-100 p-3 mr-3">
                                    <font-awesome-icon
                                        icon="fa-solid fa-question"
                                        class="text-green-600"
                                    />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Total Questions
                                    </p>
                                    <p class="text-lg font-semibold">
                                        {{ totalQuestions }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="bg-purple-50 p-4 rounded-lg flex items-center"
                            >
                                <div
                                    class="rounded-full bg-purple-100 p-3 mr-3"
                                >
                                    <font-awesome-icon
                                        icon="fa-solid fa-layer-group"
                                        class="text-purple-600"
                                    />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Assessment Aspects
                                    </p>
                                    <p class="text-lg font-semibold">
                                        {{ aspectCount }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assessment Groups -->
                <div class="space-y-6">
                    <div
                        v-for="(group, aspect) in groupedAssessments"
                        :key="aspect"
                        class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden transition-all duration-300 hover:shadow-md"
                    >
                        <div
                            class="px-6 py-4 bg-gradient-to-r from-blue-50 to-white border-b border-gray-200"
                        >
                            <div class="flex justify-between items-center">
                                <h2
                                    class="text-lg font-semibold text-gray-900 flex items-center"
                                >
                                    <font-awesome-icon
                                        icon="fa-solid fa-bookmark"
                                        class="mr-2 text-blue-600"
                                    />
                                    {{ aspect || "Uncategorized" }}
                                </h2>
                                <span
                                    class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm flex items-center"
                                >
                                    <font-awesome-icon
                                        icon="fa-solid fa-list"
                                        class="mr-1"
                                    />
                                    {{ group.length }} Questions
                                </span>
                            </div>
                        </div>

                        <div class="divide-y divide-gray-200">
                            <div
                                v-for="(assessment, index) in group"
                                :key="assessment.id"
                                class="p-6 hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex items-start space-x-4">
                                    <div
                                        class="flex-shrink-0 w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-semibold shadow-sm"
                                    >
                                        {{ index + 1 }}
                                    </div>
                                    <div class="flex-1">
                                        <h3
                                            class="text-lg font-medium text-gray-900 mb-2"
                                        >
                                            {{ assessment.question }}
                                        </h3>
                                        <div
                                            class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm"
                                        >
                                            <font-awesome-icon
                                                icon="fa-solid fa-tag"
                                                class="mr-2 text-blue-600"
                                            />
                                            {{
                                                assessment.reflective_rubric
                                                    ?.criteria
                                            }}
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="mt-4 bg-gray-50 rounded-lg p-4 border border-gray-100"
                                >
                                    <h4
                                        class="text-sm font-medium text-gray-700 mb-3 flex items-center"
                                    >
                                        <font-awesome-icon
                                            icon="fa-solid fa-chart-bar"
                                            class="mr-2 text-blue-600"
                                        />
                                        Scoring Rubric
                                    </h4>
                                    <div class="overflow-x-auto">
                                        <table
                                            class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-lg overflow-hidden"
                                        >
                                            <thead>
                                                <tr
                                                    class="bg-gradient-to-r from-blue-50 to-white"
                                                >
                                                    <th
                                                        v-for="i in 5"
                                                        :key="i"
                                                        class="px-4 py-3 text-sm font-medium text-gray-700 text-center"
                                                    >
                                                        <div
                                                            class="flex flex-col items-center"
                                                        >
                                                            <span
                                                                class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center mb-1 text-xs"
                                                            >
                                                                {{ i }}
                                                            </span>
                                                            <span
                                                                >Score
                                                                {{ i }}</span
                                                            >
                                                        </div>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody
                                                class="bg-white divide-y divide-gray-200"
                                            >
                                                <tr>
                                                    <td
                                                        v-for="i in 5"
                                                        :key="i"
                                                        class="px-4 py-4 text-sm text-gray-600 text-center border-r border-gray-100 last:border-r-0"
                                                    >
                                                        {{
                                                            assessment
                                                                .reflective_rubric?.[
                                                                `bobot_${i}`
                                                            ] || "-"
                                                        }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No Data State -->
                <div
                    v-if="Object.keys(groupedAssessments).length === 0"
                    class="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200"
                >
                    <div class="text-gray-500">
                        <font-awesome-icon
                            icon="fa-solid fa-clipboard-list"
                            class="text-5xl mb-4 text-gray-400"
                        />
                        <p class="text-lg mb-2">No assessment criteria found</p>
                        <p class="text-sm text-gray-400">
                            There are no assessment criteria available for this
                            order.
                        </p>
                    </div>
                </div>

                <!-- Information Modal -->
                <div
                    v-if="infoModalOpen"
                    class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
                >
                    <div
                        class="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto"
                    >
                        <div
                            class="flex justify-between items-center p-6 border-b border-gray-200"
                        >
                            <h2 class="text-xl font-semibold text-gray-900">
                                About Reflective Assessment
                            </h2>
                            <button
                                @click="toggleInfoModal"
                                class="text-gray-400 hover:text-gray-600"
                            >
                                <font-awesome-icon
                                    icon="fa-solid fa-times"
                                    class="text-xl"
                                />
                            </button>
                        </div>
                        <div class="p-6">
                            <div class="mb-4">
                                <h3
                                    class="text-md font-medium text-gray-800 mb-2"
                                >
                                    What is a Reflective Assessment?
                                </h3>
                                <p class="text-gray-600 mb-3">
                                    Reflective assessments encourage students to
                                    analyze their learning experiences, insights
                                    gained, and areas for improvement.
                                </p>
                            </div>
                            <div class="mb-4">
                                <h3
                                    class="text-md font-medium text-gray-800 mb-2"
                                >
                                    Scoring System
                                </h3>
                                <p class="text-gray-600 mb-3">
                                    Each question has a scoring rubric with
                                    criteria for scores 1-5, where 5 represents
                                    the highest level of reflection and insight.
                                </p>
                            </div>
                            <div class="mb-4">
                                <h3
                                    class="text-md font-medium text-gray-800 mb-2"
                                >
                                    Assessment Process
                                </h3>
                                <ul
                                    class="list-disc list-inside text-gray-600 space-y-1"
                                >
                                    <li>Review each question carefully</li>
                                    <li>
                                        Refer to the scoring rubric for guidance
                                    </li>
                                    <li>Provide constructive feedback</li>
                                    <li>
                                        Ensure assessment is completed for all
                                        questions
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div
                            class="p-4 bg-gray-50 border-t border-gray-200 flex justify-end"
                        >
                            <button
                                @click="toggleInfoModal"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <footer
                class="bg-white border-t border-gray-200 p-4 text-center text-gray-500 text-sm"
            >
                <p>
                    © {{ new Date().getFullYear() }} SISPA - Reflective
                    Assessment System
                </p>
            </footer>
        </div>
    </div>
</template>
