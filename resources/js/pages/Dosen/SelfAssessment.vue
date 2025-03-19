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
            required: true
        },
        totalOrders: {
            type: Number,
            required: true
        },
        assessments: {
            type: Array,
            required: true,
        },
    },
    setup(props) {
        const showDropdown = ref(false);
        const page = usePage();

        const breadcrumbs = [
            { text: "Self Assessment", href: "/sispa/dosen/assessment/projects-self" },
            { text: "Detail", href: null },
        ];

        const toggleDropdown = () => {
            showDropdown.value = !showDropdown.value;
        };

        const groupedAssessments = computed(() => {
            const groups = {};
            props.assessments.forEach((assessment) => {
                if (assessment.type_criteria) {
                    const aspect = assessment.type_criteria.aspect || 'Uncategorized';
                    if (!groups[aspect]) {
                        groups[aspect] = [];
                    }
                    groups[aspect].push(assessment);
                }
            });
            return groups;
        });

        const changeOrder = (newOrder) => {
            router.get('/sispa/dosen/assessment/data-with-bobot-self', {
                batch_year: props.batchYear,
                project_name: props.projectName,
                assessment_order: newOrder
            }, {
                preserveState: true
            });
        };

        const fetchQuestionId = async () => {
            try {
                const response = await axios.get("/api/get-question-id", {
                    params: {
                        batch_year: props.batchYear,
                        project_name: props.projectName,
                        assessment_order: props.currentOrder
                    },
                });

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
            const questionId = await fetchQuestionId();
            if (!questionId) return;

            router.visit("/sispa/dosen/AnswerSelf", {
                method: "get",
                data: {
                    batch_year: props.batchYear,
                    project_name: props.projectName,
                    assessment_order: props.currentOrder
                },
            });
        };

        return {
            breadcrumbs,
            toggleDropdown,
            showDropdown,
            groupedAssessments,
            handleAnswers,
            changeOrder
        };
    },
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <Sidebar role="dosen" />
        <div class="flex-1">
            <Navbar userName="Dosen" />
            <main class="p-6">
                <div class="mb-6">
                    <Breadcrumb :items="breadcrumbs" />
                    <div class="mt-4 flex justify-between items-center">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Self Assessment</h1>
                            <div class="mt-2 text-sm text-gray-600">
                                <span class="font-medium">Project:</span> {{ projectName }} | 
                                <span class="font-medium">Batch Year:</span> {{ batchYear }}
                            </div>
                            <div class="mt-2 flex items-center space-x-4">
                            </div>
                        </div>
                        <button @click="handleAnswers()"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                            <font-awesome-icon icon="fa-solid fa-pencil" class="mr-2" />
                            Start Assessment
                        </button>
                    </div>
                </div>

                <!-- Assessment Groups -->
                <div class="space-y-6">
                    <div v-for="(group, aspect) in groupedAssessments" :key="aspect" 
                        class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h2 class="text-lg font-semibold text-gray-900">{{ aspect || 'Uncategorized' }}</h2>
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                    {{ group.length }} Questions
                                </span>
                            </div>
                        </div>

                        <div class="divide-y divide-gray-200">
                            <div v-for="(assessment, index) in group" :key="assessment.id" 
                                class="p-6 hover:bg-gray-50 transition-colors">
                                <div class="flex items-start space-x-4">
                                    <div class="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-semibold">
                                        {{ index + 1 }}
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                                            {{ assessment.question }}
                                        </h3>
                                        <div class="flex items-center text-sm text-gray-600 mb-4">
                                            <font-awesome-icon icon="fa-solid fa-tag" class="mr-2" />
                                            {{ assessment.type_criteria?.criteria }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 bg-gray-50 rounded-lg p-4">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3">Scoring Rubric</h4>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead>
                                                <tr class="bg-gray-100">
                                                    <th v-for="i in 5" :key="i" 
                                                        class="px-4 py-3 text-sm font-medium text-gray-700 text-center">
                                                        Score {{ i }}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <tr>
                                                    <td v-for="i in 5" :key="i" 
                                                        class="px-4 py-3 text-sm text-gray-600 text-center">
                                                        {{ assessment.type_criteria?.[`bobot_${i}`] || '-' }}
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
                <div v-if="Object.keys(groupedAssessments).length === 0" 
                    class="text-center py-12 bg-white rounded-lg shadow-sm">
                    <div class="text-gray-500">
                        <font-awesome-icon icon="fa-solid fa-clipboard-list" class="text-4xl mb-4" />
                        <p class="text-lg">No assessment criteria found</p>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>