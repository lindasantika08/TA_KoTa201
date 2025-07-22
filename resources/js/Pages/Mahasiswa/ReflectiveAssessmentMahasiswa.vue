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
                { text: "Reflective Assessment", href: null },
            ],
            headers: [
                { key: "bobot_1", label: "Bobot 1" },
                { key: "bobot_2", label: "Bobot 2" },
                { key: "bobot_3", label: "Bobot 3" },
                { key: "bobot_4", label: "Bobot 4" },
                { key: "bobot_5", label: "Bobot 5" },
            ],
            questions: [],
            currentQuestionIndex: 0,
            answer: "",
            loading: true,
            error: null,
            studentInfo: {},
            temporaryAnswers: {},
            isSubmitting: false,
        };
    },
    computed: {
        currentQuestion() {
            return this.questions[this.currentQuestionIndex] || null;
        },
        canSubmitAll() {
            return (
                this.questions.length > 0 &&
                this.questions.every((question) => {
                    const savedAnswer = this.temporaryAnswers[question.id];
                    return (
                        savedAnswer &&
                        savedAnswer.answer &&
                        savedAnswer.answer.trim() !== ""
                    );
                })
            );
        },
    },
    async created() {
        const savedTemp = localStorage.getItem("reflectiveTemporaryAnswers");
        if (savedTemp) {
            this.temporaryAnswers = JSON.parse(savedTemp);
        }
        await this.fetchQuestions();
        await this.fetchStudentsInfo();
        await this.loadExistingAnswer();
    },
    methods: {
        async fetchQuestions() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get(
                    "/api/reflective-questions",
                    {
                        params: {
                            batch_year: this.batch_year,
                            project_name: this.project_name,
                            assessment_order: this.assessment_order,
                        },
                    }
                );

                if (response.data && Array.isArray(response.data)) {
                    this.questions = response.data;
                    this.loading = false;
                    await this.loadExistingAnswer();
                } else {
                    throw new Error("Invalid response format");
                }
            } catch (error) {
                console.error("Error details:", {
                    message: error.message,
                    response: error.response,
                    status: error.response?.status,
                    data: error.response?.data,
                });

                this.error =
                    error.response?.data?.error ||
                    `Error loading questions: ${error.message}`;
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

        async submitAnswer() {
            if (!this.currentQuestion) return;

            // Save the current answer to temporary storage first
            this.saveTemporaryAnswer();

            try {
                const response = await axios.post(
                    "/api/save-answer-reflective",
                    {
                        answer: [
                            // PERUBAHAN: Nama field dari 'answers' menjadi 'answer' sesuai dengan backend
                            {
                                question_id: this.currentQuestion.id,
                                answer: this.answer,
                                status: "submitted",
                            },
                        ],
                        temporaryAnswer: JSON.stringify(this.temporaryAnswers), // PERUBAHAN: Format sesuai dengan backend
                    }
                );

                if (response.data.success) {
                    // PERUBAHAN: Periksa response.data.success alih-alih message
                    // Remove this answer from temporary storage as it's now saved
                    delete this.temporaryAnswers[this.currentQuestion.id];
                    localStorage.setItem(
                        "reflectiveTemporaryAnswers",
                        JSON.stringify(this.temporaryAnswers)
                    );

                    // Show success toast
                    Swal.fire({
                        toast: true,
                        position: "top-end",
                        icon: "success",
                        title: "Jawaban berhasil disimpan!",
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                    });

                    if (this.currentQuestionIndex < this.questions.length - 1) {
                        this.currentQuestionIndex++;
                        await this.loadExistingAnswer();
                    }
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
        async nextQuestion() {
            await this.saveTemporaryAnswer();
            if (this.currentQuestionIndex < this.questions.length - 1) {
                this.currentQuestionIndex++;
                await this.loadExistingAnswer();
            }
        },
        async prevQuestion() {
            this.saveTemporaryAnswer();
            if (this.currentQuestionIndex > 0) {
                this.currentQuestionIndex--;
                await this.loadExistingAnswer();
            }
        },
        async loadExistingAnswer() {
            if (!this.currentQuestion) return;

            try {
                const response = await axios.get(
                    `/api/get-answer-reflective/${this.currentQuestion.id}`
                );

                const tempAnswer =
                    this.temporaryAnswers[this.currentQuestion.id];
                if (tempAnswer) {
                    this.answer = tempAnswer.answer;

                    return;
                }

                if (response.data && response.data.answer) {
                    this.answer = response.data.answer;
                } else {
                    this.answer = "";
                }
            } catch (error) {
                console.error("Error loading existing answer:", error);
                this.answer = "";
            }
        },
        saveTemporaryAnswer() {
            if (this.currentQuestion) {
                if (this.answer) {
                    this.temporaryAnswers[this.currentQuestion.id] = {
                        answer: this.answer,
                    };

                    localStorage.setItem(
                        "reflectiveTemporaryAnswers",
                        JSON.stringify(this.temporaryAnswers)
                    );
                }
            }
        },
        async handleSubmitAll() {
            this.saveTemporaryAnswer();

            if (!this.canSubmitAll) {
                Swal.fire({
                    title: "Perhatian!",
                    text: "Mohon lengkapi semua jawaban terlebih dahulu",
                    icon: "warning",
                    confirmButtonColor: "#f59e0b",
                    confirmButtonText: "OK",
                });
                return;
            }

            // Show SweetAlert confirmation
            const result = await Swal.fire({
                title: "Konfirmasi Pengiriman",
                text: "Apakah Anda yakin semua jawaban sudah sesuai? Setelah dikirim, jawaban tidak dapat diubah kembali.",
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
                await this.submitAllAnswers();
            }
        },

        async submitAllAnswers() {
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

                const allAnswers = this.questions.map((question) => ({
                    question_id: question.id,
                    answer: this.temporaryAnswers[question.id]?.answer || "",
                    status: "submitted",
                }));

                const response = await axios.post(
                    "/api/save-all-reflective-answers",
                    { answers: allAnswers }
                );

                if (response.data.success) {
                    this.clearFormFields();

                    // Show success alert
                    await Swal.fire({
                        title: "Berhasil!",
                        text: "Semua jawaban berhasil disimpan!",
                        icon: "success",
                        confirmButtonColor: "#10b981",
                        confirmButtonText: "OK",
                        timer: 3000,
                        timerProgressBar: true,
                    });

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
            this.answer = "";
            this.temporaryAnswers = {};
            localStorage.removeItem("reflectiveTemporaryAnswers");
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
    },
};
</script>

<template>
    <div class="flex min-h-screen">
        <SidebarMahasiswa role="mahasiswa" />
        <div class="flex-1">
            <Navbar userName="mahasiswa" />
            <main class="p-6">
                <div class="mb-4">
                    <Breadcrumb :items="breadcrumbs" />
                </div>

                <Card
                    title="FORMULIR PENGISIAN REFLECTIVE ASSESSMENT"
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

                    <Card>
                        <div v-if="loading" class="text-center py-8">
                            <p>Load Questions...</p>
                        </div>

                        <div
                            v-else-if="error"
                            class="text-center py-8 text-red-600"
                        >
                            <p>{{ error }}</p>
                            <button
                                @click="fetchQuestions"
                                class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                            >
                                Try Again
                            </button>
                        </div>

                        <div v-else-if="currentQuestion" class="space-y-6">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="font-semibold text-lg mb-4">
                                    Question {{ currentQuestionIndex + 1 }} from
                                    {{ questions.length }}
                                </h3>

                                <p class="mb-2">
                                    <strong>Kriteria:</strong>
                                    {{ currentQuestion.criteria_reflective }}
                                </p>
                            </div>

                            <div class="overflow-x-auto">
                                <table
                                    class="min-w-full border-collapse border border-gray-200"
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
                                                {{
                                                    currentQuestion[
                                                        header.key.toLowerCase()
                                                    ]
                                                }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="bg-white p-6 rounded-lg shadow-md">
                                <p class="text-gray-700 mb-4">
                                    {{ currentQuestion.question }}
                                </p>
                            </div>

                            <form
                                @submit.prevent="submitAnswer"
                                class="space-y-4"
                            >
                                <div>
                                    <textarea
                                        id="answer"
                                        v-model="answer"
                                        rows="4"
                                        class="block w-full rounded-md border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Berikan refleksi Anda sesuai dengan kriteria..."
                                        required
                                    ></textarea>
                                </div>

                                <div
                                    class="flex justify-between items-center pt-4"
                                >
                                    <button
                                        type="button"
                                        @click="prevQuestion"
                                        :disabled="currentQuestionIndex === 0"
                                        class="px-4 py-2 bg-yellow-400 text-white rounded hover:bg-blue-600"
                                    >
                                        Previous
                                    </button>

                                    <button
                                        type="submit"
                                        class="px-4 py-2 bg-blue-400 text-white rounded hover:bg-blue-600"
                                    >
                                        Save Answer
                                    </button>

                                    <button
                                        v-if="
                                            currentQuestionIndex ===
                                            questions.length - 1
                                        "
                                        type="button"
                                        @click="handleSubmitAll"
                                        :disabled="isSubmitting"
                                        class="px-4 py-2 bg-green-500 text-white rounded hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {{
                                            isSubmitting
                                                ? "Mengirim..."
                                                : "Send"
                                        }}
                                    </button>
                                    <button
                                        v-else
                                        type="button"
                                        @click="nextQuestion"
                                        :disabled="
                                            currentQuestionIndex ===
                                            questions.length - 1
                                        "
                                        class="px-4 py-2 bg-green-500 text-white rounded hover:bg-blue-600"
                                    >
                                        Next
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div v-else class="text-center py-8">
                            <p>No questions available.</p>
                        </div>
                    </Card>
                </Card>
            </main>
        </div>
    </div>
</template>

<style scoped>
.score-container {
    margin: 20px 0;
}

.slider-container {
    position: relative;
    margin: 40px 0;
}

.track {
    width: 100%;
    height: 4px;
    background: #ddd;
    position: relative;
}

.points {
    display: flex;
    justify-content: space-between;
    position: absolute;
    width: 100%;
    top: -8px;
}

.point {
    width: 20px;
    height: 20px;
    background: #fff;
    border: 2px solid #85ccda;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
}

.point.active {
    background: #8be1f3;
    transform: scale(1.2);
    border-color: #85ccda;
}

.point:hover {
    transform: scale(1.1);
}

.values {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
}

.value {
    font-size: 16px;
    color: #666;
    cursor: pointer;
}

.selected-value {
    text-align: center;
    margin-top: 20px;
    font-size: 18px;
    font-weight: bold;
    color: #85ccda;
}
</style>
