<script>
import axios from "axios";
import DataTable from "@/Components/DataTable.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import SidebarMahasiswa from "@/Components/SidebarMahasiswa.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import dayjs from "dayjs";
import { router } from "@inertiajs/vue3";

export default {
    components: {
        DataTable,
        Navbar,
        Card,
        SidebarMahasiswa,
        Breadcrumb,
    },
    data() {
        return {
            breadcrumbs: [
                {
                    text: "Assessment",
                    href: "",
                },
                { text: "Reflective Tasks", href: null },
            ],
            selectedTab: "assessment",
            isLoading: false,
            headers: [
                { key: "no", label: "No" },
                { key: "batch_year", label: "Tahun Ajaran" },
                { key: "project_name", label: "Proyek" },
                { key: "assessment_order", label: "Order" },
                { key: "status", label: "Status" },
                { key: "date", label: "Tanggal Pengisian" },
                { key: "actions", label: "Actions" },
            ],
            reflectiveAssessmentItems: [],
            reflectiveWritingItems: [],
        };
    },
    computed: {
        displayedItems() {
            return this.selectedTab === "assessment"
                ? this.reflectiveAssessmentItems
                : this.reflectiveWritingItems;
        },
        tabTitle() {
            return this.selectedTab === "assessment"
                ? "LIST REFLECTIVE ASSESSMENT"
                : "LIST REFLECTIVE WRITING";
        },
    },
    methods: {
        async switchTab(tab) {
            this.isLoading = true;
            this.selectedTab = tab;

            // Simulasi loading minimal 300ms biar terasa UX-nya
            setTimeout(() => {
                this.isLoading = false;
            }, 300);
        },

        handleAnswer(item) {
            const route =
                this.selectedTab === "assessment"
                    ? "/sispa/mahasiswa/assessment/reflective-assessment"
                    : "/sispa/mahasiswa/assessment/reflective-writing";

            router.visit(route, {
                method: "get",
                data: {
                    batch_year: item.batch_year,
                    project_name: item.project_name,
                    assessment_order: item.assessment_order,
                    project_id: item.project_id,
                    group_id: item.group_id,
                },
                preserveState: true,
                onError: (error) => {
                    console.error("Navigation error:", error);
                },
            });
        },

        handleDetail(item) {
            const route =
                this.selectedTab === "assessment"
                    ? "/sispa/mahasiswa/reflective-detail"
                    : "/sispa/mahasiswa/reflective-writing-detail";

            router.visit(route, {
                method: "get",
                data: {
                    batch_year: item.batch_year,
                    project_name: item.project_name,
                    project_id: item.project_id,
                    group_id: item.group_id,
                    assessment_order: item.assessment_order,
                },
                preserveState: true,
            });
        },

        fetchReflectiveAssessment() {
            axios
                .get("/sispa/api/reflective-assessment")
                .then((response) => {
                    if (response.data.success) {
                        this.reflectiveAssessmentItems =
                            response.data.assessments.map((item, index) => ({
                                id: `${item.project_id}-${item.assessment_order}`,
                                group_id: item.group_id,
                                project_id: item.project_id,
                                no: index + 1,
                                batch_year: item.batch_year,
                                project_name: item.project_name,
                                assessment_order: item.assessment_order || "1",
                                status: item.status,
                                date: dayjs(item.created_at).format(
                                    "DD MMMM YYYY HH:mm"
                                ),
                                total_questions: item.total_questions,
                                total_orders: item.total_orders,
                            }));
                    } else {
                        console.error(
                            "API returned error:",
                            response.data.message
                        );
                    }
                })
                .catch((error) => {
                    console.error(
                        "Error fetching reflective assessment:",
                        error
                    );
                });
        },

        fetchReflectiveWriting() {
            axios
                .get("/sispa/api/reflective-writing")
                .then((response) => {
                    if (response.data.success) {
                        this.reflectiveWritingItems =
                            response.data.assessments.map((item, index) => ({
                                id: `${item.project_id}-${item.assessment_order}`,
                                group_id: item.group_id,
                                project_id: item.project_id,
                                no: index + 1,
                                batch_year: item.batch_year,
                                project_name: item.project_name,
                                assessment_order: item.assessment_order || "1",
                                status: item.status,
                                date: dayjs(item.created_at).format(
                                    "DD MMMM YYYY HH:mm"
                                ),
                                total_questions: item.total_questions,
                                total_orders: item.total_orders,
                            }));
                    } else {
                        console.error(
                            "API returned error:",
                            response.data.message
                        );
                    }
                })
                .catch((error) => {
                    console.error("Error fetching reflective writing:", error);
                });
        },
    },
    mounted() {
        this.fetchReflectiveAssessment();
        this.fetchReflectiveWriting();
    },
};
</script>

<template>
    <div class="flex min-h-screen">
        <SidebarMahasiswa role="mahasiswa" />
        <div class="flex-1">
            <Navbar userName="Mahasiswa" />
            <main class="p-6">
                <div class="mb-4">
                    <Breadcrumb :items="breadcrumbs" />
                </div>

                <!-- Tab Navigation -->
                <div class="flex border-b mb-6">
                    <button
                        @click="switchTab('assessment')"
                        :class="[
                            'px-4 py-2 font-medium',
                            selectedTab === 'assessment'
                                ? 'border-b-2 border-blue-500 text-blue-600'
                                : 'text-gray-600 hover:text-blue-500',
                        ]"
                    >
                        Reflective Assessment
                    </button>
                    <button
                        @click="switchTab('writing')"
                        :class="[
                            'px-4 py-2 font-medium',
                            selectedTab === 'writing'
                                ? 'border-b-2 border-blue-500 text-blue-600'
                                : 'text-gray-600 hover:text-blue-500',
                        ]"
                    >
                        Reflective Writing
                    </button>
                </div>

                <Card :title="tabTitle" description="" class="w-full">
                    <div
                        v-if="isLoading"
                        class="text-center py-10 text-gray-500"
                    >
                        Loading...
                    </div>
                    <div v-else>
                        <DataTable
                            :headers="headers"
                            :items="displayedItems"
                            class="mt-10"
                        >
                            <template #column-actions="{ item }">
                                <div class="flex justify-center space-x-2">
                                    <button
                                        v-if="item.status === 'Active'"
                                        @click="handleAnswer(item)"
                                        class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    >
                                        <font-awesome-icon
                                            icon="fa-solid fa-pen"
                                            class="mr-2"
                                        />
                                        Answer
                                    </button>
                                    <button
                                        @click="handleDetail(item)"
                                        class="px-3 py-1 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                                    >
                                        <font-awesome-icon
                                            icon="fa-solid fa-eye"
                                            class="mr-2"
                                        />
                                        Detail
                                    </button>
                                </div>
                            </template>

                            <template #column-status="{ item }">
                                <span
                                    :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        item.status === 'Active'
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-red-100 text-red-800',
                                    ]"
                                >
                                    {{ item.status }}
                                </span>
                            </template>

                            <template #column-assessment_order="{ item }">
                                <span>
                                    {{ item.assessment_order }}
                                </span>
                            </template>
                        </DataTable>
                    </div>
                </Card>
            </main>
        </div>
    </div>
</template>