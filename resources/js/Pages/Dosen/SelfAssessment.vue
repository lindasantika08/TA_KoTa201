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
    data() {
        return {
            breadcrumbs: [
                { text: "Self Assessment", href: "/dosen/assessment/projectsSelf" },
                { text: "Detail", href: null },
            ],
        };
    },
    props: {
        batchYear: String,
        projectName: String,
        assessments: {
            type: Array,
            required: true,
        },
    },
    setup(props) {
        const showDropdown = ref(false);
        const page = usePage();

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

        const fetchQuestionId = async () => {
            try {
                const response = await axios.get("/api/get-question-id", {
                    params: {
                        batch_year: props.batchYear,
                        project_name: props.projectName,
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

            const data = {
                batch_year: props.batchYear,
                project_name: props.projectName
            };

            console.log("Data to be sent:", data);

            router.visit("/dosen/AnswerSelf", {
                method: "get",
                data: data,
            });
        };

        return {
            toggleDropdown,
            showDropdown,
            groupedAssessments,
            handleAnswers,
            batchYear: props.batchYear,
            projectName: props.projectName,
        };
    },
};
</script>

<template>
    <div class="flex min-h-screen">
        <Sidebar role="dosen" />

        <div class="flex-1">
            <Navbar userName="Dosen" />

            <main class="p-6">
                <div class="mb-4">
                    <Breadcrumb :items="breadcrumbs" />
                </div>
                <Card :title="`Self Assessment - ${projectName} (${batchYear})`">
                    <template #actions>
                        <div class="flex justify-end">
                            <button @click="handleAnswers()"
                                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <font-awesome-icon icon="fa-solid fa-pencil" />
                                Attempt
                            </button>
                        </div>
                        <div class="mt-6 space-y-8">
                            <div v-for="(group, aspect) in groupedAssessments" :key="aspect">
                                <Card :title="aspect || 'Uncategorized'"
                                    :description="'Total Questions: ' + group.length">
                                    <template #actions>
                                        <div>
                                            <div v-for="(assessment, index) in group" :key="assessment.id" class="mt-4">
                                                <h3 class="text-lg font-semibold mb-2">
                                                    {{ index + 1 }}. {{ assessment.question }}
                                                </h3>
                                                <h4 class="text-sm text-gray-600">
                                                    {{ assessment.type_criteria?.criteria }}
                                                </h4>

                                                <div class="overflow-x-auto mt-2">
                                                    <table class="w-full table-auto border-collapse bg-white">
                                                        <thead>
                                                            <tr>
                                                                <th v-for="i in 5" :key="i" class="px-4 py-2 border">
                                                                    Bobot {{ i }}
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td v-for="i in 5" :key="i" class="px-4 py-2 border">
                                                                    {{ assessment.type_criteria?.[`bobot_${i}`] }}
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </Card>
                            </div>
                        </div>
                    </template>
                </Card>
            </main>
        </div>
    </div>
</template>