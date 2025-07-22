<script>
import axios from "axios";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import SidebarMahasiswa from "@/Components/SidebarMahasiswa.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import Swal from "sweetalert2";

export default {
    components: {
        Navbar,
        Card,
        SidebarMahasiswa,
        Breadcrumb,
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
                    href: "/mahasiswa/assessment/reflective",
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
            isSubmitting: false,
            totalAssessmentOrders: 0,
            // AI Feedback related
            showFeedbackModal: false,
            aiFeedback: null,
            loadingFeedback: false,
            hasFeedback: false,
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
        await this.checkFeedbackAvailability();
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
                            "/api/reflective-writing-points",
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

                const response = await axios.get("/api/user-info", {
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
                    "/api/save-answer-reflective-writing",
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
                    // Show success alert
                    Swal.fire({
                        toast: true,
                        position: "top-end",
                        icon: "success",
                        title: `Jawaban #${
                            item.assessment_order
                        } (${this.getQuestionNumber(item)}) berhasil disimpan!`,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                    });

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

                // Show error alert
                Swal.fire({
                    title: "Error!",
                    text: errorMessage,
                    icon: "error",
                    confirmButtonColor: "#ef4444",
                    confirmButtonText: "OK",
                });
            }
        },

        async fetchTotalAssessmentOrders() {
            try {
                const response = await axios.get(
                    "/api/reflective-writing-count",
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
                        `/api/get-answer-reflective-writing/${writingId}`,
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
            // Show SweetAlert confirmation instead of modal
            const result = await Swal.fire({
                title: "Konfirmasi Pengiriman",
                text: "Apakah Anda yakin tulisan reflektif Anda sudah sesuai? Setelah dikirim, tulisan tidak dapat diubah kembali.",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#10b981",
                cancelButtonColor: "#ef4444",
                confirmButtonText: "Ya, Kirim!",
                cancelButtonText: "Batal",
                allowOutsideClick: false,
                allowEscapeKey: false,
            });

            if (result.isConfirmed) {
                await this.submitConfirmed();
            }
        },

        async submitConfirmed() {
            try {
                this.isSubmitting = true;

                // Show loading alert
                Swal.fire({
                    title: "Mengirim Jawaban...",
                    text: "Mohon tunggu, jawaban sedang diproses.",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    },
                });

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
                    "/api/submit-all-reflective-writing",
                    {
                        batch_year: this.batch_year,
                        project_name: this.project_name,
                        answers: allAnswers,
                    }
                );

                if (response.data.success) {
                    this.clearFormFields();

                    // Show success alert with feedback option
                    const successResult = await Swal.fire({
                        title: "Berhasil!",
                        text: "Semua reflective writing berhasil disimpan!",
                        icon: "success",
                        confirmButtonColor: "#10b981",
                        confirmButtonText: "OK",
                        timer: 3000,
                        timerProgressBar: true,
                    });

                    // Check for feedback availability after successful submission
                    await this.checkFeedbackAvailability();

                    if (this.hasFeedback) {
                        const feedbackResult = await Swal.fire({
                            title: "Analisis AI Tersedia!",
                            text: "Sistem AI telah menganalisis jawaban Anda. Apakah ingin melihat feedback sekarang?",
                            icon: "info",
                            showCancelButton: true,
                            confirmButtonColor: "#3b82f6",
                            cancelButtonColor: "#6b7280",
                            confirmButtonText: "Lihat Feedback",
                            cancelButtonText: "Nanti Saja",
                        });

                        if (feedbackResult.isConfirmed) {
                            await this.fetchAIFeedback();
                            return; // Don't redirect yet if showing feedback
                        }
                    } else {
                        // Give AI more time to process, then check again
                        setTimeout(async () => {
                            await this.checkFeedbackAvailability();
                            if (this.hasFeedback) {
                                // Show toast notification about available feedback
                                Swal.fire({
                                    toast: true,
                                    position: "top-end",
                                    icon: "info",
                                    title: "Feedback AI sudah tersedia!",
                                    text: "Anda dapat melihatnya dari halaman utama.",
                                    showConfirmButton: false,
                                    timer: 5000,
                                    timerProgressBar: true,
                                });
                            }
                        }, 5000);
                    }

                    // Redirect to previous page
                    this.redirectToPreviousPage();
                }
            } catch (error) {
                console.error("Error submitting answers:", error);

                // Show error alert
                Swal.fire({
                    title: "Error!",
                    text: "Gagal menyimpan jawaban. Silakan coba lagi.",
                    icon: "error",
                    confirmButtonColor: "#ef4444",
                    confirmButtonText: "OK",
                });
            } finally {
                this.isSubmitting = false;
            }
        },

        clearFormFields() {
            this.answers = {};
            this.temporaryAnswers = {};
            localStorage.removeItem("reflectiveWritingTemporaryAnswers");
        },

        async fetchAIFeedback() {
            this.loadingFeedback = true;
            try {
                const response = await axios.get(
                    "/api/reflective-writing-feedback",
                    {
                        params: {
                            batch_year: this.batch_year,
                            project_name: this.project_name,
                        },
                    }
                );

                if (response.data.success && response.data.has_feedback) {
                    this.aiFeedback = response.data.feedback;
                    this.hasFeedback = true;
                    this.showFeedbackModal = true;
                } else {
                    this.hasFeedback = false;
                    Swal.fire({
                        title: "Info",
                        text:
                            response.data.message ||
                            "No feedback available yet. Please submit your reflective writing first.",
                        icon: "info",
                        confirmButtonColor: "#3b82f6",
                        confirmButtonText: "OK",
                    });
                }
            } catch (error) {
                console.error("Error fetching AI feedback:", error);
                Swal.fire({
                    title: "Error!",
                    text:
                        "Error loading feedback: " +
                        (error.response?.data?.message || error.message),
                    icon: "error",
                    confirmButtonColor: "#ef4444",
                    confirmButtonText: "OK",
                });
            } finally {
                this.loadingFeedback = false;
            }
        },

        closeFeedbackModal() {
            this.showFeedbackModal = false;

            // After closing feedback modal, redirect to previous page
            setTimeout(() => {
                this.redirectToPreviousPage();
            }, 300); // Small delay to allow modal to close smoothly
        },

        async checkFeedbackAvailability() {
            try {
                const response = await axios.get(
                    "/api/reflective-writing-feedback",
                    {
                        params: {
                            batch_year: this.batch_year,
                            project_name: this.project_name,
                        },
                    }
                );

                this.hasFeedback =
                    response.data.success && response.data.has_feedback;
            } catch (error) {
                console.error("Error checking feedback availability:", error);
                this.hasFeedback = false;
            }
        },

        // Helper method to get the question number within an assessment order
        getQuestionNumber(item) {
            if (!item || !item.assessment_order) return "";

            const orderItems = this.writingItems[item.assessment_order] || [];
            const index = orderItems.findIndex((i) => i.id === item.id);
            return `Pertanyaan ${index + 1} dari ${orderItems.length}`;
        },

        // Helper method to format feedback into sections for better display
        formatFeedbackSections(summary) {
            if (!summary) return [];

            const sections = [];
            const lines = summary.split("\n");
            let currentSection = null;
            let currentContent = [];

            for (let line of lines) {
                // Check if line is a section header
                if (line.startsWith("===") && line.endsWith("===")) {
                    // Save previous section
                    if (currentSection) {
                        sections.push({
                            title: currentSection,
                            content: currentContent.join("\n").trim(),
                        });
                    }

                    // Start new section
                    currentSection = line.replace(/===/g, "").trim();
                    currentContent = [];
                } else if (line.startsWith("---") && line.endsWith("---")) {
                    // Save previous section
                    if (currentSection) {
                        sections.push({
                            title: currentSection,
                            content: currentContent.join("\n").trim(),
                        });
                    }

                    // Start new section
                    currentSection = line.replace(/---/g, "").trim();
                    currentContent = [];
                } else {
                    // Add line to current content
                    currentContent.push(line);
                }
            }

            // Don't forget the last section
            if (currentSection) {
                sections.push({
                    title: currentSection,
                    content: currentContent.join("\n").trim(),
                });
            }

            // If no sections found, return the whole summary as one section
            if (sections.length === 0) {
                sections.push({
                    title: "Analisis AI",
                    content: summary,
                });
            }

            return sections;
        },

        // Method to redirect to previous page
        redirectToPreviousPage() {
            // Use history.back() for better user experience or fallback to specific page
            if (window.history.length > 1) {
                window.history.back();
            } else {
                // Fallback to reflective assessment page
                this.$inertia.visit("/mahasiswa/reflective-assessment");
            }
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
                        <div
                            class="flex justify-center items-center gap-4 pt-8 pb-4"
                        >
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

                            <!-- AI Feedback Button -->
                            <button
                                v-if="hasFeedback"
                                type="button"
                                @click="fetchAIFeedback"
                                :disabled="loadingFeedback"
                                class="px-6 py-3 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50 font-medium flex items-center gap-2"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                                    />
                                </svg>
                                {{
                                    loadingFeedback
                                        ? "Loading..."
                                        : "Lihat Feedback AI"
                                }}
                            </button>
                        </div>
                    </div>

                    <Card v-else>
                        <p class="text-center py-8">
                            No writing data available.
                        </p>
                    </Card>

                    <!-- AI Feedback Modal -->
                    <div
                        v-if="showFeedbackModal"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
                        @click.self="closeFeedbackModal"
                    >
                        <div
                            class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden"
                        >
                            <div
                                class="flex items-center justify-between p-6 border-b"
                            >
                                <div class="flex items-center gap-3">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-6 w-6 text-blue-500"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                                        />
                                    </svg>
                                    <h3
                                        class="text-xl font-semibold text-gray-900"
                                    >
                                        Analisis AI - Feedback Reflective
                                        Writing
                                    </h3>
                                </div>
                                <button
                                    @click="closeFeedbackModal"
                                    class="text-gray-400 hover:text-gray-600 transition-colors"
                                >
                                    <svg
                                        class="w-6 h-6"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        ></path>
                                    </svg>
                                </button>
                            </div>

                            <div class="p-6 overflow-y-auto max-h-[70vh]">
                                <div v-if="aiFeedback" class="space-y-6">
                                    <!-- Project Info -->
                                    <div
                                        class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-400"
                                    >
                                        <div
                                            class="flex items-center gap-2 mb-2"
                                        >
                                            <svg
                                                class="w-5 h-5 text-blue-600"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                ></path>
                                            </svg>
                                            <h4
                                                class="font-medium text-blue-800"
                                            >
                                                Informasi Analisis
                                            </h4>
                                        </div>
                                        <div
                                            class="grid grid-cols-2 gap-4 text-sm"
                                        >
                                            <div>
                                                <span
                                                    class="font-medium text-blue-700"
                                                    >Proyek:</span
                                                >
                                                <span
                                                    class="text-blue-600 ml-2"
                                                    >{{
                                                        aiFeedback.project_name
                                                    }}</span
                                                >
                                            </div>
                                            <div>
                                                <span
                                                    class="font-medium text-blue-700"
                                                    >Tahun Angkatan:</span
                                                >
                                                <span
                                                    class="text-blue-600 ml-2"
                                                    >{{
                                                        aiFeedback.batch_year
                                                    }}</span
                                                >
                                            </div>
                                            <div class="col-span-2">
                                                <span
                                                    class="font-medium text-blue-700"
                                                    >Dianalisis pada:</span
                                                >
                                                <span
                                                    class="text-blue-600 ml-2"
                                                    >{{
                                                        new Date(
                                                            aiFeedback.generated_at
                                                        ).toLocaleString(
                                                            "id-ID"
                                                        )
                                                    }}</span
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <!-- AI Analysis Content -->
                                    <div class="bg-white border rounded-lg">
                                        <div
                                            class="bg-gray-50 px-4 py-3 border-b"
                                        >
                                            <h4
                                                class="font-medium text-gray-800 flex items-center gap-2"
                                            >
                                                <svg
                                                    class="w-5 h-5 text-green-600"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                                    ></path>
                                                </svg>
                                                Hasil Analisis AI
                                            </h4>
                                        </div>
                                        <div class="p-4">
                                            <div class="prose max-w-none">
                                                <!-- Format the summary for better readability -->
                                                <div class="space-y-4">
                                                    <div
                                                        v-for="(
                                                            section, index
                                                        ) in formatFeedbackSections(
                                                            aiFeedback.summary
                                                        )"
                                                        :key="index"
                                                        class="border rounded-lg"
                                                    >
                                                        <div
                                                            class="bg-gray-50 px-3 py-2 border-b"
                                                        >
                                                            <h5
                                                                class="font-medium text-gray-700 text-sm"
                                                            >
                                                                {{
                                                                    section.title
                                                                }}
                                                            </h5>
                                                        </div>
                                                        <div class="p-3">
                                                            <pre
                                                                class="whitespace-pre-wrap font-sans text-sm text-gray-600 leading-relaxed"
                                                                >{{
                                                                    section.content
                                                                }}</pre
                                                            >
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Tips -->
                                    <div
                                        class="bg-amber-50 border border-amber-200 rounded-lg p-4"
                                    >
                                        <div
                                            class="flex items-center gap-2 mb-2"
                                        >
                                            <svg
                                                class="w-5 h-5 text-amber-600"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.728-.833-2.498 0L3.316 16.5c-.77.833.192 2.5 1.732 2.5z"
                                                ></path>
                                            </svg>
                                            <h4
                                                class="font-medium text-amber-800"
                                            >
                                                Tips untuk Perbaikan
                                            </h4>
                                        </div>
                                        <div
                                            class="text-sm text-amber-700 space-y-2"
                                        >
                                            <p>
                                                • Perhatikan poin-poin yang
                                                belum tercakup dalam analisis di
                                                atas
                                            </p>
                                            <p>
                                                • Gunakan saran spesifik yang
                                                diberikan AI untuk memperbaiki
                                                jawaban
                                            </p>
                                            <p>
                                                • Pastikan setiap poin reflektif
                                                dijawab dengan lengkap dan
                                                sesuai konteks
                                            </p>
                                            <p>
                                                • Jika ada poin yang tidak
                                                tercakup, tambahkan pembahasan
                                                yang relevan
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="text-center py-8">
                                    <div
                                        class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"
                                    ></div>
                                    <p class="mt-2 text-gray-600">
                                        Loading feedback...
                                    </p>
                                </div>
                            </div>

                            <div
                                class="border-t p-4 bg-gray-50 flex justify-end"
                            >
                                <button
                                    @click="closeFeedbackModal"
                                    class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors"
                                >
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </div>
                </Card>
            </main>
        </div>
    </div>
</template>
