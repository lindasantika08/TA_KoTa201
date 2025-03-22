<script>
import { ref, computed } from "vue";
import { router, usePage } from '@inertiajs/vue3';
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
                { text: "Peer Assessment", href: "/sispa/dosen/assessment/projectsPeer" },
                { text: "Detail", href: null }
            ],
        }
    },
    props: {
        tahunAjaran: String,
        namaProyek: String,
        assessments: {
            type: Array,
            required: true
        },
        assessmentOrder: {
            type: Number,
            required: true
        },
        totalOrders: {
            type: Number,
            required: true
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
                if (!groups[assessment.aspect]) {
                    groups[assessment.aspect] = [];
                }
                groups[assessment.aspect].push(assessment);
            });
            return groups;
        });

        const fetchQuestionId = async () => {
            try {
                const response = await axios.get("/sispa/api/get-question-id", {
                    params: {
                        batch_year: props.tahunAjaran,
                        project_name: props.namaProyek,
                        assessment_order: props.assessmentOrder
                    },
                });

                if (response.data) {
                    return response.data.questionId;
                }
            } catch (error) {
                console.error("Error fetching QuestionId:", error);
                alert(
                    "Gagal mendapatkan QuestionId. Periksa kembali data Anda."
                );
                return null;
            }
        };

        const handleAnswers = async () => {
            const questionId = await fetchQuestionId();
            if (!questionId) return;

            if (!props.assessmentOrder) {
                console.error("assessment_order is undefined");
                return;
            }

            const requestData = {
                tahunAjaran: props.tahunAjaran,
                namaProyek: props.namaProyek,
                assessment_order: props.assessmentOrder
            };

            console.log("Data yang akan dikirim:", requestData);

            router.visit("/sispa/dosen/AnswerPeer", {
                method: "get",
                data: requestData,
            });
        };

        return {
            toggleDropdown,
            showDropdown,
            groupedAssessments,
            tahunAjaran: props.tahunAjaran,
            namaProyek: props.namaProyek,
            handleAnswers,
            assessmentOrder: props.assessmentOrder,
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
                <Card :title="`Peer Assessment - ${namaProyek} (${tahunAjaran})`">
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
                                <Card :title="aspect" :description="'Total Pertanyaan: ' + group.length">
                                    <template #actions>
                                        <div>
                                            <div v-for="(assessment, index) in group" :key="assessment.id" class="mt-4">
                                                <h3 class="text-lg font-semibold mb-2">
                                                    {{ index + 1 }}.
                                                    {{ assessment.question }}
                                                </h3>
                                                <h4 class="text-sm text-gray-600">
                                                    {{ assessment.kriteria }}
                                                </h4>

                                                <div class="overflow-x-auto mt-2">
                                                    <table class="w-full table-auto border-collapse bg-white">
                                                        <thead>
                                                            <tr>
                                                                <th class="px-4 py-2 border">
                                                                    Bobot 1
                                                                </th>
                                                                <th class="px-4 py-2 border">
                                                                    Bobot 2
                                                                </th>
                                                                <th class="px-4 py-2 border">
                                                                    Bobot 3
                                                                </th>
                                                                <th class="px-4 py-2 border">
                                                                    Bobot 4
                                                                </th>
                                                                <th class="px-4 py-2 border">
                                                                    Bobot 5
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td class="px-4 py-2 border">
                                                                    {{ assessment.bobot_1 }}
                                                                </td>
                                                                <td class="px-4 py-2 border">
                                                                    {{ assessment.bobot_2 }}
                                                                </td>
                                                                <td class="px-4 py-2 border">
                                                                    {{ assessment.bobot_3 }}
                                                                </td>
                                                                <td class="px-4 py-2 border">
                                                                    {{ assessment.bobot_4 }}
                                                                </td>
                                                                <td class="px-4 py-2 border">
                                                                    {{ assessment.bobot_5 }}
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
