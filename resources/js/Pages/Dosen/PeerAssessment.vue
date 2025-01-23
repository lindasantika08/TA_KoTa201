<template>
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <Sidebar role="dosen" />

        <!-- Main Content -->
        <div class="flex-1">
            <!-- Navbar -->
            <Navbar userName="Dosen" />

            <!-- Content -->
            <main class="p-6">
                <div class="mb-4">
                    <Breadcrumb :items="breadcrumbs" />
                </div>
                <Card :title="`Peer Assessment - ${namaProyek} (${tahunAjaran})`">
                    <template #actions>
                        <div class="flex justify-end">
                            <button
                                @click="handleAnswers()"
                                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            >
                                <font-awesome-icon icon="fa-solid fa-pencil" />
                                Attempt
                            </button>
                        </div>
                        <!-- Container untuk assessment yang dikelompokkan berdasarkan aspek -->
                        <div class="mt-6 space-y-8">
                            <div v-for="(group, aspek) in groupedAssessments" :key="aspek">
                                <!-- Card untuk setiap aspek -->
                                <Card :title="aspek" :description="'Total Pertanyaan: ' + group.length">
                                    <template #actions>
                                        <div>
                                            <!-- Daftar Pertanyaan -->
                                            <div v-for="(assessment, index) in group" :key="assessment.id" class="mt-4">
                                                <h3 class="text-lg font-semibold mb-2">
                                                    {{ index + 1 }}.
                                                    {{ assessment.pertanyaan }}
                                                </h3>
                                                <h4 class="text-sm text-gray-600">
                                                    {{ assessment.kriteria }}
                                                </h4>

                                                <!-- Tabel Bobot -->
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
                { text: "Peer Assessment", href: "/dosen/assessment/projectsPeer" },
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
        }
    },
    setup(props) {
        const showDropdown = ref(false);
        const page = usePage();

        const toggleDropdown = () => {
            showDropdown.value = !showDropdown.value;
        };

        // Group assessments berdasarkan aspek
        const groupedAssessments = computed(() => {
            const groups = {};
            props.assessments.forEach((assessment) => {
                if (!groups[assessment.aspek]) {
                    groups[assessment.aspek] = [];
                }
                groups[assessment.aspek].push(assessment);
            });
            return groups;
        });

        const fetchQuestionId = async () => {
            try {
                const response = await axios.get("/api/get-question-id", {
                    params: {
                        tahun_ajaran: props.tahunAjaran,
                        nama_proyek: props.namaProyek,
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
            // Ambil QuestionId dari backend
            const questionId = await fetchQuestionId();
            if (!questionId) return;

            // Data yang ingin dikirim
            const data = {
                // QuestionId: questionId,
                tahunAjaran: props.tahunAjaran,
                namaProyek: props.namaProyek,
            };

            // Log data ke konsol untuk debugging
            console.log("Data yang akan dikirim:", data);

            // Navigasi ke halaman pengisian assessment
            router.visit("/dosen/AnswerPeer", {
                method: "get",
                data: data,
            });
        };

        return {
            toggleDropdown,
            showDropdown,
            groupedAssessments,
            tahunAjaran: props.tahunAjaran,
            namaProyek: props.namaProyek,
            handleAnswers,
        };
    },
};
</script>