<script>
import axios from "axios";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import SidebarMahasiswa from "@/Components/SidebarMahasiswa.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import ConfirmModal from "@/Components/ConfirmModal.vue";

export default {
    components: {
        Navbar,
        Card,
        SidebarMahasiswa,
        Breadcrumb,
        ConfirmModal,
    },
    props: {
        batch_year: {
            type: String,
            required: true,
        },
        project_name: {
            type: String,
            required: true,
        },
        assessment_order: {
            type: String,
            required: true,
        },
    },

    data() {
        return {
            breadcrumbs: [
                {
                    text: "Assessment",
                    href: "/sispa/mahasiswa/assessment/reflective",
                },
                { text: "Reflective Writing", href: null },
            ],
            headers: [
                { key: "point_1", label: "Point 1" },
                { key: "point_2", label: "Point 2" },
                { key: "point_3", label: "Point 3" },
                { key: "point_4", label: "Point 4" },
                { key: "point_5", label: "Point 5" },
            ],
            writingItems: {}, // Object to store writing items by order
            answers: {}, // Object to store answers for each item
            loading: true,
            error: null,
            studentInfo: {},
            temporaryAnswers: {},
            showConfirmModal: false,
            isSubmitting: false,
            totalAssessmentOrders: 0,
        };
    },
    computed: {
        canSubmit() {
            // Check if all required answers are filled
            if (Object.keys(this.writingItems).length === 0) return false;

            // Flatten all writing items into a single array
            const allItems = Object.values(this.writingItems).flat();

            return allItems.every(
                (item) =>
                    this.answers[item.id] && this.answers[item.id].trim() !== ""
            );
        },
    },
    async created() {
        // Load temporary answers from localStorage
        const savedTemp = localStorage.getItem(
            "reflectiveWritingTemporaryAnswers"
        );
        if (savedTemp) {
            this.temporaryAnswers = JSON.parse(savedTemp);
        }

        await this.fetchTotalAssessmentOrders();
        await this.fetchAllWritingData();
        await this.fetchStudentsInfo();
        await this.loadExistingAnswers();
    },
    methods: {
        async fetchAllWritingData() {
            this.loading = true;
            this.error = null;
            this.writingItems = {};

            try {
                // Fetch all reflective writing data at once
                for (let i = 1; i <= this.totalAssessmentOrders; i++) {
                    try {
                        const response = await axios.get(
                            "/sispa/api/reflective-writing-points",
                            {
                                params: {
                                    batch_year: this.batch_year,
                                    project_name: this.project_name,
                                    assessment_order: i.toString(),
                                },
                            }
                        );

                        if (
                            response.data &&
                            Array.isArray(response.data) &&
                            response.data.length > 0
                        ) {
                            // Store all items for this order, attaching the assessment_order to each
                            const itemsWithOrder = response.data.map((item) => {
                                return {
                                    ...item,
                                    assessment_order: i,
                                };
                            });

                            // Store by assessment order
                            this.writingItems[i] = itemsWithOrder;
                        }
                    } catch (itemError) {
                        console.error(
                            `Error fetching writing data for order ${i}:`,
                            itemError
                        );
                    }
                }

                if (Object.keys(this.writingItems).length === 0) {
                    throw new Error("No writing data available");
                }

                this.loading = false;
            } catch (error) {
                console.error("Error details:", {
                    message: error.message,
                    response: error.response,
                    status: error.response?.status,
                    data: error.response?.data,
                });

                this.error =
                    error.response?.data?.error ||
                    `Error loading writing data: ${error.message}`;
                this.loading = false;
            }
        },

        async fetchStudentsInfo() {
            try {
                const batch_year =
                    this.$page.props.batch_year || this.$route.query.batch_year;
                const project_name =
                    this.$page.props.project_name ||
                    this.$route.query.project_name;

                const response = await axios.get("/sispa/api/user-info", {
                    params: {
                        batch_year: batch_year,
                        project_name: project_name,
                    },
                });

                if (response.data) {
                    this.studentInfo = response.data;
                }
            } catch (error) {
                console.error("Failed to fetch student info: ", error);
            }
        },

        async saveAnswer(item) {
            if (!item || !item.id) return;

            // Save current answer to temporary storage
            const itemId = item.id;
            const answer = this.answers[itemId];

            if (answer) {
                this.temporaryAnswers[itemId] = {
                    answer: answer,
                    assessmentOrder: item.assessment_order,
                };

                localStorage.setItem(
                    "reflectiveWritingTemporaryAnswers",
                    JSON.stringify(this.temporaryAnswers)
                );
            }

            try {
                // Format the request to match the expected structure in the controller
                const response = await axios.post(
                    "/sispa/api/save-answer-reflective-writing",
                    {
                        answer: [
                            {
                                reflectiveWriting_id: itemId,
                                answer: answer,
                                status: "draft", // Save as draft
                            },
                        ],
                        temporaryAnswer: JSON.stringify(this.temporaryAnswers),
                    }
                );

                if (response.data.success) {
                    alert(
                        `Jawaban #${
                            item.assessment_order
                        } (${this.getQuestionNumber(item)}) berhasil disimpan!`
                    );

                    // Remove from temporary storage after successful save
                    delete this.temporaryAnswers[itemId];
                    localStorage.setItem(
                        "reflectiveWritingTemporaryAnswers",
                        JSON.stringify(this.temporaryAnswers)
                    );
                }
            } catch (error) {
                console.error("Error saving answer:", error);
                const errorMessage =
                    error.response?.data?.error ||
                    "Gagal menyimpan jawaban. Silakan coba lagi.";
                alert(errorMessage);
            }
        },

        async fetchTotalAssessmentOrders() {
            try {
                const response = await axios.get(
                    "/sispa/api/reflective-writing-count",
                    {
                        params: {
                            batch_year: this.batch_year,
                            project_name: this.project_name,
                        },
                    }
                );

                if (response.data && response.data.count) {
                    this.totalAssessmentOrders = response.data.count;
                } else {
                    // Fallback value if the count is not returned properly
                    console.warn(
                        "No count returned from API, using fallback value"
                    );
                    this.totalAssessmentOrders = 2; // Default to at least 2 for testing
                }
            } catch (error) {
                console.error("Error fetching total assessment orders:", error);
                // Fallback value in case of error
                this.totalAssessmentOrders = 2; // Default to at least 2 for testing
            }
        },

        async loadExistingAnswers() {
            // Get all writing items as a flat array
            const allItems = Object.values(this.writingItems).flat();

            // Load all existing answers for each writing item
            for (const item of allItems) {
                if (!item || !item.id) continue;

                try {
                    // Check if we have a temporary answer first
                    const writingId = item.id;
                    if (this.temporaryAnswers[writingId]) {
                        this.answers[writingId] =
                            this.temporaryAnswers[writingId].answer || "";
                        continue;
                    }

                    // If no temporary answer, try to fetch from API
                    // Based on your controller, it expects the reflectiveWriting_id directly, not the assessment_order
                    const response = await axios.get(
                        `/sispa/api/get-answer-reflective-writing/${writingId}`,
                        {
                            params: {
                                batch_year: this.batch_year,
                                project_name: this.project_name,
                            },
                        }
                    );

                    if (response.data && response.data.answer) {
                        this.answers[writingId] = response.data.answer;
                    } else {
                        this.answers[writingId] = "";
                    }
                } catch (error) {
                    console.error(
                        `Error loading existing answer for item ${item.id}:`,
                        error
                    );
                    this.answers[item.id] = "";
                }
            }
        },
        async submitAllAnswers() {
            // Show confirmation modal directly without saving each answer first
            this.showConfirmModal = true;
        },

        async submitConfirmed() {
            try {
                this.isSubmitting = true;

                // Prepare all answers for submission
                const allAnswers = [];
                const allItems = Object.values(this.writingItems).flat();

                // Add each answer to submission array
                for (const item of allItems) {
                    allAnswers.push({
                        reflectiveWriting_id: item.id,
                        answer: this.answers[item.id] || "",
                        status: "submitted",
                    });
                }

                const response = await axios.post(
                    "/sispa/api/submit-all-reflective-writing",
                    {
                        batch_year: this.batch_year,
                        project_name: this.project_name,
                        answers: allAnswers,
                    }
                );

                if (response.data.success) {
                    this.clearFormFields();
                    alert("Semua reflective writing berhasil disimpan!");
                    this.$inertia.visit(
                        "/sispa/mahasiswa/reflective-assessment"
                    );
                }
            } catch (error) {
                console.error("Error submitting answers:", error);
                alert("Gagal menyimpan jawaban. Silakan coba lagi.");
            } finally {
                this.isSubmitting = false;
                this.showConfirmModal = false;
            }
        },

        clearFormFields() {
            this.answers = {};
            this.temporaryAnswers = {};
            localStorage.removeItem("reflectiveWritingTemporaryAnswers");
        },

        // Helper method to get the question number within an assessment order
        getQuestionNumber(item) {
            if (!item || !item.assessment_order) return "";

            const orderItems = this.writingItems[item.assessment_order] || [];
            const index = orderItems.findIndex((i) => i.id === item.id);
            return `Pertanyaan ${index + 1} dari ${orderItems.length}`;
        },
    },
    mounted() {
        window.addEventListener("beforeunload", (event) => {
            if (Object.keys(this.temporaryAnswers).length > 0) {
                event.preventDefault();
                event.returnValue = "";
            }
        });
    },
    beforeDestroy() {
        window.removeEventListener("beforeunload");
    },
};
</script>

<template>
    <div class="flex min-h-screen">
        <SidebarMahasiswa role="mahasiswa" />
        <div class="flex-1">
            <Navbar userName="mahasiswa" />
            <main class="p-6">
                <Breadcrumb :items="breadcrumbs" class="mb-4" />
                <Card
                    title="FORMULIR PENGISIAN REFLECTIVE WRITING"
                    class="w-full"
                >
                    <div class="grid grid-cols-2 gap-6 text-sm leading-6 mb-6">
                        <div>
                            <p><strong>NIM:</strong> {{ studentInfo.nim }}</p>
                            <p>
                                <strong>Nama Lengkap:</strong>
                                {{ studentInfo.name }}
                            </p>
                            <p>
                                <strong>Kelas:</strong> {{ studentInfo.class }}
                            </p>
                        </div>
                        <div>
                            <p>
                                <strong>Kelompok:</strong>
                                {{ studentInfo.group }}
                            </p>
                            <p>
                                <strong>Proyek:</strong>
                                {{ studentInfo.project_name }}
                            </p>
                            <p>
                                <strong>Tanggal Pengisian:</strong>
                                {{ studentInfo.date }}
                            </p>
                        </div>
                    </div>

                    <Card v-if="loading">
                        <p class="text-center py-8">Loading...</p>
                    </Card>

                    <Card v-else-if="error">
                        <p class="text-center py-8 text-red-600">{{ error }}</p>
                        <div class="text-center">
                            <button
                                @click="fetchAllWritingData"
                                class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                            >
                                Try Again
                            </button>
                        </div>
                    </Card>

                    <div
                        v-else-if="Object.keys(writingItems).length > 0"
                        class="space-y-8"
                    >
                        <!-- Loop through each assessment order -->
                        <template
                            v-for="(items, orderNumber) in writingItems"
                            :key="orderNumber"
                        >
                            <Card class="mb-6">
                                <h3 class="font-semibold text-lg mb-4">
                                    Reflective Writing {{ orderNumber }} dari
                                    {{ totalAssessmentOrders }}
                                </h3>

                                <!-- Loop through all questions for this order -->
                                <div
                                    v-for="(item, qIndex) in items"
                                    :key="item.id"
                                    class="mb-10"
                                >
                                    <h4
                                        class="font-medium text-md mb-3 text-blue-600"
                                    >
                                        Pertanyaan {{ qIndex + 1 }} dari
                                        {{ items.length }}
                                    </h4>

                                    <table
                                        class="min-w-full border-collapse border border-gray-200 mb-6"
                                    >
                                        <thead>
                                            <tr>
                                                <th
                                                    v-for="header in headers"
                                                    :key="header.key"
                                                    class="border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700"
                                                >
                                                    {{ header.label }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td
                                                    v-for="header in headers"
                                                    :key="header.key"
                                                    class="border border-gray-200 px-4 py-2 text-sm text-center"
                                                >
                                                    {{ item[header.key] }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <p class="text-gray-700 mb-4">
                                        <strong>Instruksi:</strong> Buatkan
                                        tulisan yang mencakup Reflective Writing
                                        sesuai dengan Point yang telah
                                        ditentukan
                                    </p>

                                    <div class="space-y-4">
                                        <textarea
                                            v-model="answers[item.id]"
                                            rows="12"
                                            class="block w-full rounded-md border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Tuliskan reflective writing Anda sesuai dengan point-point yang ditentukan..."
                                            required
                                        ></textarea>

                                        <div
                                            class="flex justify-end items-center pt-4"
                                        >
                                            <button
                                                type="button"
                                                @click="saveAnswer(item)"
                                                :disabled="
                                                    isSubmitting ||
                                                    !answers[item.id]
                                                "
                                                class="px-4 py-2 bg-blue-400 text-white rounded hover:bg-blue-600 disabled:opacity-50"
                                            >
                                                Save Answer
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Add divider between questions except for the last one -->
                                    <hr
                                        v-if="qIndex < items.length - 1"
                                        class="my-8 border-gray-200"
                                    />
                                </div>
                            </Card>
                        </template>

                        <!-- Submit all button at the bottom -->
                        <div class="flex justify-center items-center pt-8 pb-4">
                            <button
                                type="button"
                                @click="submitAllAnswers"
                                :disabled="isSubmitting || !canSubmit"
                                class="px-6 py-3 bg-green-500 text-white rounded hover:bg-green-600 disabled:opacity-50 font-medium"
                            >
                                {{
                                    isSubmitting
                                        ? "Mengirim..."
                                        : "Kirim Semua Jawaban"
                                }}
                            </button>
                        </div>
                    </div>

                    <Card v-else>
                        <p class="text-center py-8">
                            No writing data available.
                        </p>
                    </Card>

                    <ConfirmModal
                        :show="showConfirmModal"
                        title="Konfirmasi Pengiriman"
                        message="Apakah Anda yakin tulisan reflektif Anda sudah sesuai? Setelah dikirim, tulisan tidak dapat diubah kembali."
                        @close="showConfirmModal = false"
                        @confirm="submitConfirmed"
                    />
                </Card>
            </main>
        </div>
    </div>
</template>
