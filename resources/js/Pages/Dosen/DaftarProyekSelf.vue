<script>
import axios from "axios";
import DataTable from "@/Components/DataTable.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Sidebar from "@/Components/Sidebar.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import dayjs from "dayjs";
import { router } from "@inertiajs/vue3";

export default {
    components: {
        DataTable,
        Navbar,
        Card,
        Sidebar,
        Breadcrumb,
    },
    data() {
        return {
            breadcrumbs: [
                {
                    text: "Self Assessment",
                    href: "/dosen/assessment/projectsSelf",
                },
            ],
            headers: [
                { key: "no", label: "No" },
                { key: "batch_year", label: "Batch Year" },
                { key: "project_name", label: "Project Name" },
                { key: "assessment_order", label: "Order" },
                { key: "status", label: "Status" },
                { key: "date", label: "Created Date" },
                { key: "publish", label: "Publish" },
                { key: "actions", label: "Actions" },
            ],
            items: [],
        };
    },
    methods: {
        handleDetail(item) {
            router.get(
                "/dosen/assessment/data-with-bobot-self",
                {
                    batch_year: item.batch_year,
                    project_name: item.project_name,
                    assessment_order: item.assessment_order,
                },
                {
                    preserveState: true,
                }
            );
        },

        handleListAnswer(item) {
            router.get(
                "/dosen/answers-self-assessment",
                {
                    batch_year: item.batch_year,
                    project_name: item.project_name,
                    assessment_order: item.assessment_order,
                },
                {
                    preserveState: true,
                }
            );
        },

        handleTogglePublish(item) {
            const newStatus = !item.is_published;
            axios
                .post("/api/toggle-publish-assessment", {
                    batch_year: item.batch_year,
                    project_name: item.project_name,
                    assessment_order: item.assessment_order,
                    is_published: newStatus,
                })
                .then((response) => {
                    const index = this.items.findIndex(
                        (i) =>
                            i.batch_year === item.batch_year &&
                            i.project_name === item.project_name &&
                            i.assessment_order === item.assessment_order
                    );

                    if (index !== -1) {
                        this.$set(this.items, index, {
                            ...item,
                            is_published: newStatus,
                        });
                    }
                })
                .catch((error) => {
                    console.error("Error toggling publish status:", error);
                });
        },

        updateItems(data) {
            this.items = data.map((item, index) => ({
                no: index + 1,
                batch_year: item.batch_year,
                project_name: item.project_name,
                assessment_order: item.assessment_order,
                status: item.status,
                is_published: Boolean(item.is_published),
                date: dayjs(item.created_at).format("DD MMMM YYYY HH:mm"),
            }));
        },
    },
    mounted() {
        axios
            .get("/api/proyek-self-assessment")
            .then((response) => {
                this.updateItems(response.data);
            })
            .catch((error) => {
                console.error("Error fetching data:", error);
            });
    },
};
</script>

<template>
    <div class="flex min-h-screen">
        <Sidebar role="dosen" />
        <div class="flex-1">
            <Navbar userName="dosen" />
            <main class="p-6">
                <div class="mb-4">
                    <Breadcrumb :items="breadcrumbs" />
                </div>

                <Card
                    title="SELF ASSESSMENT PROJECT LIST"
                    description=""
                    class="w-full"
                >
                    <div
                        v-if="items.length === 0"
                        class="text-center text-gray-500 py-6"
                    >
                        No assessment available
                    </div>
                    <div v-else>
                        <DataTable
                            :headers="headers"
                            :items="items"
                            class="mt-10"
                        >
                            <template #column-publish="{ item }">
                                <label
                                    class="relative inline-flex items-center cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="item.is_published"
                                        @change="handleTogglePublish(item)"
                                        class="sr-only peer"
                                    />
                                    <div
                                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"
                                    ></div>
                                </label>
                            </template>

                            <template #column-actions="{ item }">
                                <button
                                    @click="handleDetail(item)"
                                    class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                >
                                    <font-awesome-icon
                                        icon="fa-solid fa-eye"
                                        class="mr-2"
                                    />
                                    Detail
                                </button>
                                <button
                                    @click="handleListAnswer(item)"
                                    class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 ml-2"
                                >
                                    <font-awesome-icon
                                        icon="fa-solid fa-file"
                                        class="mr-2"
                                    />
                                    List Answer
                                </button>
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
                        </DataTable>
                    </div>
                </Card>
            </main>
        </div>
    </div>
</template>
