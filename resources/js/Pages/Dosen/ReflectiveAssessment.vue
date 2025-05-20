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
                { key: "order", label: "Order" }, // Generic key for conditional rendering
                { key: "status", label: "Status" },
                { key: "date", label: "Created Date" },
                { key: "publish", label: "Publish" },
                { key: "actions", label: "Actions" },
            ],
            loading: false,
            items: [],
            listReflectiveType: "Reflective Assessment", // Default reflective type for list view
        };
    },
    setup() {
        const projects = ref([]);
        const inputMode = ref("export");
        const selectedProject = ref(null);
        const selectedReflectiveType = ref("Reflective Assessment");

        const defaultEndDate = new Date();
        defaultEndDate.setDate(defaultEndDate.getDate() + 7);
        const endDate = ref(defaultEndDate.toISOString().split("T")[0]);

        const allProjects = computed(() => {
            return projects.value.map((project) => ({
                ...project,
                display: `${project.batch_year} - ${project.project_name} (${project.status})`,
            }));
        });

        const reflectiveTypes = [
            { value: "Reflective Assessment", label: "Reflective Assessment" },
            { value: "Reflective Writing", label: "Reflective Writing" },
        ];

        const downloadTemplate = async () => {
            if (selectedProject.value) {
                try {
                    const token = localStorage.getItem("auth_token");
                    const response = await axios.get(
                        "/sispa/api/export-reflective-assessment",
                        {
                            params: {
                                batch_year: selectedProject.value.batch_year,
                                project_name:
                                    selectedProject.value.project_name,
                                type: selectedProject.value.status, // Active or NonActive
                                reflective_type: selectedReflectiveType.value,
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
                        `${selectedReflectiveType.value
                            .toLowerCase()
                            .replace(" ", "-")}-${
                            selectedProject.value.status
                        }.xlsx`
                    );
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);
                } catch (error) {
                    console.error("Download error:", error);
                    alert("There was an error downloading the Excel file.");
                }
            } else {
                alert("Please select a project.");
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
            allProjects,
            selectedProject,
            selectedReflectiveType,
            reflectiveTypes,
            downloadTemplate,
            handleFileUpload,
            inputMode,
            endDate,
        };
    },
    methods: {
        handleDetail(item) {
            // Handle detail differently based on reflective type
            if (this.listReflectiveType === "Reflective Assessment") {
                axios
                    .get("/sispa/dosen/reflectiveAssessment/detail", {
                        params: {
                            batch_year: item.batch_year,
                            project_name: item.project_name,
                            reflective_assessment_order: item.order_value,
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
            } else {
                // For Reflective Writing
                axios
                    .get("/sispa/dosen/reflectiveWriting/detail", {
                        params: {
                            batch_year: item.batch_year,
                            project_name: item.project_name,
                            reflective_writing_order: item.order_value,
                        },
                    })
                    .then((response) => {
                        window.location.href = response.request.responseURL;
                    })
                    .catch((error) => {
                        console.error(
                            "Error loading reflective writing details:",
                            error
                        );
                    });
            }
        },

        handleListAnswer(item) {
            if (this.listReflectiveType === "Reflective Assessment") {
                router.get(
                    "/sispa/dosen/reflectiveAssessment/detail-answer",
                    {
                        batch_year: item.batch_year,
                        project_name: item.project_name,
                        reflective_assessment_order: item.order_value,
                    },
                    {
                        preserveState: true,
                    }
                );
            } else {
                // For Reflective Writing
                router.get(
                    "/sispa/dosen/reflectiveWriting/detail-answer",
                    {
                        batch_year: item.batch_year,
                        project_name: item.project_name,
                        reflective_writing_order: item.order_value,
                    },
                    {
                        preserveState: true,
                    }
                );
            }
        },

        handleTogglePublish(item) {
            const newStatus = !item.is_published;

            this.items = this.items.map((i) => {
                if (i.uniqueKey === item.uniqueKey) {
                    return { ...i, is_published: newStatus };
                }
                return i;
            });

            // Different API endpoints based on the type
            const endpoint =
                this.listReflectiveType === "Reflective Assessment"
                    ? "/sispa/api/toggle-publish-reflective-assessment"
                    : "/sispa/api/toggle-publish-reflective-writing";

            // Different payload based on the type
            const payload = {
                project_id: item.id,
                batch_year: item.batch_year,
                project_name: item.project_name,
                is_published: newStatus,
            };

            // Add the appropriate order field based on type
            if (this.listReflectiveType === "Reflective Assessment") {
                payload.reflective_assessment_order = item.order_value;
            } else {
                payload.reflective_writing_order = item.order_value;
            }

            axios
                .post(endpoint, payload)
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
            this.items = data.map((item, index) => {
                // Determine which order field to use based on the reflective type
                const orderField =
                    this.listReflectiveType === "Reflective Assessment"
                        ? item.reflective_assessment_order
                        : item.reflective_writing_order;

                return {
                    no: index + 1,
                    id: item.id,
                    batch_year: item.batch_year,
                    project_name: item.project_name,
                    order: orderField, // Display value for table
                    order_value: orderField, // Actual value to use in API calls
                    status: item.status,
                    uniqueKey: item.unique_key || `${item.id}-${orderField}`,
                    is_published: Boolean(item.is_published),
                    date: dayjs(item.created_at).format("DD MMMM YYYY"),
                };
            });
        },

        fetchData() {
            this.loading = true;

            // Different API endpoints based on type
            const endpoint =
                this.listReflectiveType === "Reflective Assessment"
                    ? "/sispa/api/reflective-assessment-list"
                    : "/sispa/api/reflective-writing-list";

            axios
                .get(endpoint, {
                    params: {
                        reflective_type: this.listReflectiveType,
                    },
                })
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
        listReflectiveType() {
            if (this.inputMode === "List") {
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
                            <div class="space-y-4">
                                <!-- Combined Projects Dropdown -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Pilih Proyek
                                    </label>
                                    <select
                                        v-model="selectedProject"
                                        class="mt-2 p-2 border border-gray-300 rounded w-full"
                                        required
                                    >
                                        <option value="" disabled selected>
                                            Pilih Proyek
                                        </option>
                                        <option
                                            v-for="project in allProjects"
                                            :key="`${project.batch_year}-${project.project_name}`"
                                            :value="project"
                                        >
                                            {{ project.display }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Type Reflective Radio Buttons -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Type Reflective
                                    </label>
                                    <div class="flex w-full mt-2">
                                        <label
                                            v-for="type in reflectiveTypes"
                                            :key="type.value"
                                            class="flex-1 text-center py-2 border cursor-pointer"
                                            :class="{
                                                'bg-blue-500 text-white':
                                                    selectedReflectiveType ===
                                                    type.value,
                                                'bg-white text-gray-700 border-gray-300':
                                                    selectedReflectiveType !==
                                                    type.value,
                                            }"
                                        >
                                            <input
                                                type="radio"
                                                v-model="selectedReflectiveType"
                                                :value="type.value"
                                                class="hidden"
                                            />
                                            {{ type.label }}
                                        </label>
                                    </div>
                                </div>

                                <!-- Download Button -->
                                <div class="mt-6">
                                    <button
                                        @click="downloadTemplate"
                                        class="w-full px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                        :disabled="!selectedProject"
                                    >
                                        <font-awesome-icon
                                            :icon="['fas', 'file-excel']"
                                            class="mr-2"
                                        />
                                        Download Template
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="inputMode === 'import'" class="mt-8">
                            <!-- End Date Input -->
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
                            <!-- Type Reflective Radio Buttons for List mode -->
                            <div class="mb-4">
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Type Reflective
                                </label>
                                <div class="flex w-full mt-2">
                                    <label
                                        v-for="type in reflectiveTypes"
                                        :key="type.value"
                                        class="flex-1 text-center py-2 border cursor-pointer"
                                        :class="{
                                            'bg-blue-500 text-white':
                                                listReflectiveType ===
                                                type.value,
                                            'bg-white text-gray-700 border-gray-300':
                                                listReflectiveType !==
                                                type.value,
                                        }"
                                    >
                                        <input
                                            type="radio"
                                            v-model="listReflectiveType"
                                            :value="type.value"
                                            class="hidden"
                                        />
                                        {{ type.label }}
                                    </label>
                                </div>
                            </div>

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
                                No {{ listReflectiveType }} available
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
