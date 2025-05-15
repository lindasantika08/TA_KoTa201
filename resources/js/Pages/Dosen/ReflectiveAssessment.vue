<script>
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import DataTable from "@/Components/DataTable.vue";
import dayjs from "dayjs";
import { router } from "@inertiajs/vue3";

export default {
    components: {
        Sidebar,
        Navbar,
        Card,
        Breadcrumb,
        DataTable,
    },
    data() {
        return {
            breadcrumbs: [
                {
                    text: "Reflective Assessment",
                    href: "",
                },
            ],
            headers: [
                { key: "no", label: "No" },
                { key: "batch_year", label: "Batch Year" },
                { key: "project_name", label: "Project Name" },
                { key: "reflective_assessment_order", label: "Order" },
                { key: "status", label: "Status" },
                { key: "date", label: "Created Date" },
                { key: "publish", label: "Publish" },
                { key: "actions", label: "Actions" },
            ],
            loading: false,
            items: [],
        };
    },
    setup() {
        const projects = ref([]);
        const inputMode = ref("export");
        const selectedActiveProject = ref(null);
        const selectedInactiveProject = ref(null);

        const defaultEndDate = new Date();
        defaultEndDate.setDate(defaultEndDate.getDate() + 7);
        const endDate = ref(defaultEndDate.toISOString().split("T")[0]);

        const activeProjects = computed(() => {
            return projects.value.filter(
                (project) => project.status === "Active"
            );
        });

        const inactiveProjects = computed(() => {
            return projects.value.filter(
                (project) => project.status !== "Active"
            );
        });

        const downloadActiveTemplate = async () => {
            if (selectedActiveProject.value) {
                await downloadTemplate(selectedActiveProject.value, "Active");
            } else {
                alert("Please select an active project.");
            }
        };

        const downloadInactiveTemplate = async () => {
            if (selectedInactiveProject.value) {
                await downloadTemplate(
                    selectedInactiveProject.value,
                    "NonActive"
                );
            } else {
                alert("Please select a non-active project.");
            }
        };

        const downloadTemplate = async (project, type = "template") => {
            try {
                const token = localStorage.getItem("auth_token");
                const response = await axios.get(
                    "/sispa/api/export-reflective-assessment",
                    {
                        params: {
                            batch_year: project.batch_year,
                            project_name: project.project_name,
                            type: type, // Only affects the filename
                        },
                        headers: {
                            Authorization: `Bearer ${token}`,
                            Accept: "application/json",
                        },
                        responseType: "blob",
                    }
                );

                const blob = new Blob([response.data], {
                    type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                });

                const url = window.URL.createObjectURL(blob);
                const link = document.createElement("a");
                link.href = url;
                link.setAttribute(
                    "download",
                    `reflective-assessment-${type}.xlsx`
                );
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
            } catch (error) {
                console.error("Download error:", error);
                alert("There was an error downloading the Excel file.");
            }
        };

        const handleFileUpload = async (event) => {
            const file = event.target.files[0];
            if (!file) return;

            const selectedEndDate =
                endDate.value || defaultEndDate.toISOString().split("T")[0];

            const formData = new FormData();
            formData.append("file", file);
            formData.append("end_date", selectedEndDate);

            try {
                const token = localStorage.getItem("auth_token");
                const response = await axios.post(
                    "/sispa/dosen/reflectiveAssessment/import",
                    formData,
                    {
                        headers: {
                            "Content-Type": "multipart/form-data",
                            Authorization: `Bearer ${token}`,
                        },
                    }
                );

                alert(response.data.message || "Data imported successfully.");
                event.target.value = "";
            } catch (error) {
                console.error("Import error:", error);
                alert("There was an error importing the data.");
            }
        };

        onMounted(async () => {
            try {
                const response = await axios.get("/sispa/api/projects");
                projects.value = response.data;
            } catch (error) {
                console.error("Error fetching projects:", error);
            }
        });

        return {
            projects,
            activeProjects,
            inactiveProjects,
            selectedActiveProject,
            selectedInactiveProject,
            downloadActiveTemplate,
            downloadInactiveTemplate,
            handleFileUpload,
            inputMode,
            endDate,
        };
    },
    methods: {
        handleDetail(item) {
            // With axios.get, parameters should be in the params property of the config object
            axios
                .get("/sispa/dosen/reflectiveAssessment/detail", {
                    params: {
                        batch_year: item.batch_year,
                        project_name: item.project_name,
                        reflective_assessment_order:
                            item.reflective_assessment_order,
                    },
                })
                .then((response) => {
                    window.location.href = response.request.responseURL;
                })
                .catch((error) => {
                    console.error(
                        "Error loading reflective assessment details:",
                        error
                    );
                });
        },

        handleListAnswer(item) {
            router.get(
                "/sispa/dosen/reflectiveAssessment/detail-answer",
                {
                    batch_year: item.batch_year,
                    project_name: item.project_name,
                    reflective_assessment_order:
                        item.reflective_assessment_order,
                },
                {
                    preserveState: true,
                }
            );
        },

        handleTogglePublish(item) {
            const newStatus = !item.is_published;

            this.items = this.items.map((i) => {
                if (i.uniqueKey === item.uniqueKey) {
                    return { ...i, is_published: newStatus };
                }
                return i;
            });

            axios
                .post("/sispa/api/toggle-publish-reflective-assessment", {
                    project_id: item.id,
                    batch_year: item.batch_year,
                    project_name: item.project_name,
                    reflective_assessment_order:
                        item.reflective_assessment_order,
                    is_published: newStatus,
                })
                .then((response) => {
                    // Success handling
                })
                .catch((error) => {
                    console.error("Error toggling publish status:", error);

                    this.items = this.items.map((i) => {
                        if (i.uniqueKey === item.uniqueKey) {
                            return { ...i, is_published: !newStatus };
                        }
                        return i;
                    });

                    if (this.$toast) {
                        this.$toast.error("Gagal memperbarui status publikasi");
                    } else {
                        console.error("Gagal memperbarui status publikasi");
                    }
                });
        },

        updateItems(data) {
            this.items = data.map((item, index) => ({
                no: index + 1,
                id: item.id,
                batch_year: item.batch_year,
                project_name: item.project_name,
                reflective_assessment_order: item.reflective_assessment_order,
                status: item.status,
                uniqueKey:
                    item.unique_key ||
                    `${item.id}-${item.reflective_assessment_order}`,
                is_published: Boolean(item.is_published),
                date: dayjs(item.created_at).format("DD MMMM YYYY"),
            }));
        },

        fetchData() {
            this.loading = true;
            axios
                .get("/sispa/api/reflective-assessment-list")
                .then((response) => {
                    this.updateItems(response.data);
                })
                .catch((error) => {
                    console.error("Error fetching data:", error);
                    if (this.$toast) {
                        this.$toast.error("Gagal memuat data");
                    }
                })
                .finally(() => {
                    this.loading = false;
                });
        },
    },
    mounted() {
        if (this.inputMode === "List") {
            this.fetchData();
        }
    },
    watch: {
        inputMode(newValue) {
            if (newValue === "List") {
                this.fetchData();
            }
        },
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
                <Card title="Reflective Assessment">
                    <template #actions>
                        <!-- Segmented Radio Buttons -->
                        <div class="flex w-full mb-4">
                            <label
                                class="w-1/2 text-center py-2 border cursor-pointer"
                                :class="{
                                    'bg-blue-500 text-white':
                                        inputMode === 'export',
                                    'bg-white text-gray-700 border-gray-300':
                                        inputMode !== 'export',
                                }"
                            >
                                <input
                                    type="radio"
                                    v-model="inputMode"
                                    value="export"
                                    class="hidden"
                                />
                                Export
                            </label>
                            <label
                                class="w-1/2 text-center py-2 border cursor-pointer"
                                :class="{
                                    'bg-blue-500 text-white':
                                        inputMode === 'import',
                                    'bg-white text-gray-700 border-gray-300':
                                        inputMode !== 'import',
                                }"
                            >
                                <input
                                    type="radio"
                                    v-model="inputMode"
                                    value="import"
                                    class="hidden"
                                />
                                Import
                            </label>
                            <label
                                class="w-1/2 text-center py-2 border cursor-pointer"
                                :class="{
                                    'bg-blue-500 text-white':
                                        inputMode === 'List',
                                    'bg-white text-gray-700 border-gray-300':
                                        inputMode !== 'List',
                                }"
                            >
                                <input
                                    type="radio"
                                    v-model="inputMode"
                                    value="List"
                                    class="hidden"
                                />
                                List Reflective
                            </label>
                        </div>

                        <div v-if="inputMode === 'export'">
                            <div class="grid grid-cols-2 gap-8">
                                <div class="border-r pr-4">
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Proyek Aktif
                                    </label>
                                    <select
                                        v-model="selectedActiveProject"
                                        class="mt-2 p-2 border border-gray-300 rounded w-full"
                                        required
                                    >
                                        <option value="" disabled selected>
                                            Pilih Proyek Aktif
                                        </option>
                                        <option
                                            v-for="project in activeProjects"
                                            :key="`active-${project.batch_year}-${project.project_name}`"
                                            :value="project"
                                        >
                                            {{ project.batch_year }} -
                                            {{ project.project_name }}
                                        </option>
                                    </select>
                                    <div class="mt-4">
                                        <button
                                            @click="downloadActiveTemplate"
                                            class="w-full px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                            :disabled="!selectedActiveProject"
                                        >
                                            <font-awesome-icon
                                                :icon="['fas', 'file-excel']"
                                                class="mr-2"
                                            />
                                            Download Template Aktif
                                        </button>
                                    </div>
                                </div>

                                <div class="pl-4">
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Proyek Tidak Aktif
                                    </label>
                                    <select
                                        v-model="selectedInactiveProject"
                                        class="mt-2 p-2 border border-gray-300 rounded w-full"
                                        required
                                    >
                                        <option value="" disabled selected>
                                            Pilih Proyek Tidak Aktif
                                        </option>
                                        <option
                                            v-for="project in inactiveProjects"
                                            :key="`inactive-${project.batch_year}-${project.project_name}`"
                                            :value="project"
                                        >
                                            {{ project.batch_year }} -
                                            {{ project.project_name }}
                                        </option>
                                    </select>
                                    <div class="mt-4">
                                        <button
                                            @click="downloadInactiveTemplate"
                                            class="w-full px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                            :disabled="!selectedInactiveProject"
                                        >
                                            <font-awesome-icon
                                                :icon="['fas', 'file-excel']"
                                                class="mr-2"
                                            />
                                            Download Template Tidak Aktif
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="inputMode === 'import'" class="mt-8">
                            <!-- New End Date Input -->
                            <div class="mb-4">
                                <label
                                    for="end-date"
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Tanggal Akhir Pengisian Assessment
                                </label>
                                <input
                                    type="date"
                                    id="end-date"
                                    v-model="endDate"
                                    class="mt-1 p-2 border border-gray-300 rounded w-full"
                                    required
                                />
                            </div>

                            <label
                                for="file-upload"
                                class="block text-sm font-medium text-gray-700"
                            >
                                Import Data Excel (File .xlsx/.xls)
                            </label>
                            <input
                                type="file"
                                id="file-upload"
                                accept=".xlsx, .xls"
                                @change="handleFileUpload"
                                class="mt-2 p-2 border border-gray-300 rounded w-full"
                                :disabled="!endDate"
                            />
                        </div>

                        <div v-if="inputMode === 'List'" class="mt-8">
                            <div
                                v-if="loading"
                                class="text-center text-gray-500 py-6"
                            >
                                Loading...
                            </div>
                            <div
                                v-else-if="items.length === 0"
                                class="text-center text-gray-500 py-6"
                            >
                                No reflective assessment available
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
                                                @change="
                                                    handleTogglePublish(item)
                                                "
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
                        </div>
                    </template>
                </Card>
            </main>
        </div>
    </div>
</template>
