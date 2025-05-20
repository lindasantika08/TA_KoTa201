<script>
import Navbar from "@/Components/Navbar.vue";
import Sidebar from "@/Components/Sidebar.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import DataTable from "@/Components/DataTable.vue";
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import axios from "axios";

export default {
    components: {
        DataTable,
        Navbar,
        Sidebar,
        Breadcrumb,
    },
    data() {
        return {
            breadcrumbs: [
                {
                    text: "Reflective Assessment",
                    href: "/sispa/dosen/assessment/projects-self",
                },
                {
                    text: "List Reflective Assessment Answer",
                    href: "/sispa/dosen/assessment/projects-self",
                },
            ],
            loading: true,
            assessmentData: null,
            error: null,
            columns: [
                { key: "nim", label: "NIM" },
                { key: "name", label: "Nama Mahasiswa" },
                { key: "status", label: "Status" },
                { key: "actions", label: "Actions" },
            ],
            studentsData: [],
        };
    },
    setup() {
        const page = usePage();
        return {
            batch_year: computed(() => page.props.batchYear),
            project_name: computed(() => page.props.projectName),
            reflective_order: computed(
                () => page.props.reflective_assessment_order
            ),
        };
    },

    methods: {
        async fetchDataAnswer() {
            this.loading = true;
            try {
                const response = await axios.get(
                    "/sispa/api/get-reflective-answer",
                    {
                        params: {
                            batchYear: this.batch_year,
                            projectName: this.project_name,
                            reflective_assessment_order: this.reflective_order,
                        },
                    }
                );

                this.assessmentData = response.data;

                // Process students data for the table
                this.studentsData = response.data.students.map((student) => {
                    return {
                        id: student.mahasiswa.id,
                        nim: student.mahasiswa.nim,
                        name: student.mahasiswa.user
                            ? student.mahasiswa.user.name
                            : "Unknown",
                        status: this.getStatusBadge(student.status),
                        answer:
                            student.answers.length > 0
                                ? student.answers[0]
                                : null,
                        actions: this.getActionButtons(student),
                    };
                });

                this.loading = false;
                console.log("Fetched answer data:", response.data);
            } catch (error) {
                this.error = "Failed to load reflective assessment data";
                this.loading = false;
                console.error("Download error:", error);
            }
        },

        getStatusBadge(status) {
            if (status === "submitted") {
                return `<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Submitted</span>`;
            } else {
                return `<span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Unsubmitted</span>`;
            }
        },

        getActionButtons(student) {
            if (student.status === "submitted") {
                return `<button class="px-3 py-1 text-xs font-medium rounded bg-blue-500 text-white mr-2" @click="viewAnswer('${student.mahasiswa.id}')">View Answer</button>`;
            } else {
                return `<span class="text-gray-400 text-xs">No Answer Available</span>`;
            }
        },

        viewAnswer(studentId) {
            this.loading = true; // Pastikan loading state diatur sebelum request

            // Make the API call with parameter names matching the controller's validation
            router
                .get("/sispa/api/reflective-assessment-answer-details", {
                    batch_year: this.batch_year,
                    project_name: this.project_name,
                    reflective_assessment_order: this.reflective_order,
                    mahasiswaId: studentId,
                })

                .catch((error) => {
                    console.error("Error fetching answer details:", error);
                    if (this.$toast) {
                        this.$toast.error("Gagal memuat detail jawaban");
                    }
                })
                .finally(() => {
                    this.loading = false;
                });
        },
    },

    mounted() {
        this.fetchDataAnswer();
    },
};
</script>

<template>
    <div class="flex min-screen">
        <Sidebar role="dosen"></Sidebar>
        <div class="flex-1">
            <Navbar userName="dosen" />
            <main class="p-6">
                <div class="mb-4">
                    <Breadcrumb :items="breadcrumbs" />
                </div>

                <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                    <h1 class="text-2xl font-bold text-gray-800 mb-4">
                        Answers Reflective Assessment
                    </h1>

                    <div class="grid md:grid-cols-3 gap-4 mb-6">
                        <div>
                            <p class="text-sm text-gray-600">Batch Year</p>
                            <p class="font-semibold text-lg">
                                {{ batch_year }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Project Name</p>
                            <p class="font-semibold text-lg">
                                {{ project_name }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">
                                Reflective Assessment Order
                            </p>
                            <p class="font-semibold text-lg">
                                {{ reflective_order }}
                            </p>
                        </div>
                    </div>

                    <!-- Loading and Error States -->
                    <div v-if="loading" class="flex justify-center my-8">
                        <div
                            class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"
                        ></div>
                    </div>

                    <div
                        v-else-if="error"
                        class="bg-red-100 text-red-700 p-4 rounded-lg mb-6"
                    >
                        {{ error }}
                    </div>

                    <!-- Student Answers Table -->
                    <div
                        v-else-if="studentsData.length > 0"
                        class="overflow-x-auto"
                    >
                        <table class="min-w-full bg-white">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        v-for="column in columns"
                                        :key="column.key"
                                        class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        {{ column.label }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr
                                    v-for="(student, index) in studentsData"
                                    :key="index"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        {{ student.nim }}
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        {{ student.name }}
                                    </td>
                                    <td
                                        class="py-3 px-4 whitespace-nowrap"
                                        v-html="student.status"
                                    ></td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="flex justify-center">
                                            <button
                                                v-if="student.answer"
                                                @click="viewAnswer(student.id)"
                                                class="px-4 py-2 text-white bg-blue-500 rounded-lg hover:bg-blue-700 transition-colors"
                                            >
                                                Detail
                                            </button>
                                            <span
                                                v-else
                                                class="text-gray-400 text-xs"
                                                >No Answer Available</span
                                            >
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="text-center py-8 text-gray-500">
                        No students found in this project group.
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>
