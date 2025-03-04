<script>
import { usePage } from "@inertiajs/vue3";
import axios from "axios";
import DataTable from "@/Components/DataTable.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import SidebarMahasiswa from "../../Components/SidebarMahasiswa.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import ConfirmModal from "../../Components/ConfirmModal.vue";

export default {
    components: {
        DataTable,
        Navbar,
        Card,
        SidebarMahasiswa,
        Breadcrumb,
        ConfirmModal,
    },

    setup() {
        const page = usePage();
        const tahunAjaran = page.props.tahun_ajaran;
        const proyek = page.props.proyek;

        return {
            tahunAjaran,
            proyek,
        };
    },

    data() {
        return {
            breadcrumbs: [
                { text: "Assessment", href: "/mahasiswa/assessment/peer" },
                { text: "Peer Assessment", href: null },
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
            kelompok: [],
            selectedMember: "", // Ensure this is an empty string by default
            studentInfo: {
                nim: "",
                name: "",
                class: "",
                group: "",
                project: "",
                date: "",
            },
            score: 0,
            temporaryAnswers: {},
            showConfirmModal: false,
            modalTitle: "Konfirmasi Pengiriman",
            modalMessage: "Apakah Anda yakin semua jawaban sudah sesuai?",
            isSubmitting: false,
            answeredPeers: [], // Add this to track answered peers
        };
    },

    computed: {
        currentQuestion() {
            return this.questions[this.currentQuestionIndex] || null;
        },

        allQuestionsAnswered() {
            if (
                !this.selectedMember ||
                !this.temporaryAnswers[this.selectedMember]
            ) {
                return false;
            }
            return this.questions.every(
                (q) =>
                    this.temporaryAnswers[this.selectedMember][q.id]?.answer !==
                        undefined &&
                    this.temporaryAnswers[this.selectedMember][q.id]?.score !==
                        undefined
            );
        },
        availableMembers() {
            console.log("Kelompok:", this.kelompok);
            console.log("Answered Peers:", this.answeredPeers);

            if (!this.kelompok || !this.answeredPeers) return [];

            const filtered = this.kelompok.filter(
                (member) => !this.answeredPeers.includes(member.mahasiswa_id)
            );

            console.log("Available Members:", filtered);
            return filtered;
        },
    },

    watch: {
        kelompok: {
            immediate: true,
            handler(newVal) {
                console.log("Kelompok updated:", newVal);
            },
        },
        selectedMember: {
            immediate: true,
            handler(newVal) {
                if (newVal) {
                    try {
                        this.currentQuestionIndex = 0;
                        this.loadTemporaryAnswer();
                        this.checkExistingAnswer();
                        this.saveCurrentState();
                    } catch (error) {
                        console.error("Error handling selected member:", error);
                    }
                } else {
                    this.resetForm();
                }
            },
        },

        questions: {
            immediate: true,
            handler(newVal) {
                if (newVal && newVal.length > 0) {
                    this.currentQuestionIndex = 0;
                    this.loadTemporaryAnswer();
                }
            },
        },
    },

    created() {
        this.initializeData();
        this.fetchAnsweredPeers();
    },

    methods: {
        async initializeData() {
            this.loading = true;
            this.error = null;
            // Clear the selected member to ensure dropdown starts empty
            this.selectedMember = "";

      try {
        const batch_year = this.$page.props.batch_year;
        const project_name = this.$page.props.project_name;

        console.log('Batch Year:', batch_year);
        console.log('Project Name:', project_name);

                const userInfoResponse = await axios.get(
                    "/api/user-info-peer",
                    {
                        params: {
                            batch_year: batch_year,
                            project_name: project_name,
                        },
                    }
                );

                const userInfo = userInfoResponse.data;

                console.log("User info received:", userInfo);

                this.currentUserId = userInfo.id;
                this.studentInfo = {
                    nim: userInfo.nim || "",
                    name: userInfo.name || "",
                    class: userInfo.kelas || "",
                    group: userInfo.group || "",
                    project_name: userInfo.project_name || "",
                    date: new Date().toLocaleDateString("id-ID"),
                };

                this.batch_year = userInfo.batch_year;

                console.log("Student info set:", this.studentInfo);
                console.log("Batch year set:", this.batch_year);

                if (this.batch_year && this.studentInfo.project_name) {
                    const kelompokResponse = await axios.get("/api/groups", {
                        params: {
                            batch_year: this.batch_year,
                            project_name: this.studentInfo.project_name,
                        },
                    });

                    if (kelompokResponse.data) {
                        this.kelompok = kelompokResponse.data;
                        console.log("Kelompok data:", this.kelompok);
                    }
                } else {
                    console.error("Missing batch_year or project_name:", {
                        batch_year: this.batch_year,
                        project_name: this.studentInfo.project_name,
                    });
                }

        await this.loadQuestions();
        await this.loadExistingAnswers();
        await this.checkExistingAnswer();
        this.loadSavedState();

      } catch (error) {
        this.error = `Error loading data: ${error.message}`;
        console.error("Error details:", error);
        if (error.response) {
          console.error("Response data:", error.response.data);
        }
      } finally {
        this.loading = false;
      }
    },

        async loadQuestions(retryCount = 3) {
            for (let i = 0; i < retryCount; i++) {
                try {
                    console.log("Loading questions with params:", {
                        batch_year: this.batch_year,
                        project_name: this.studentInfo.project_name,
                    });

                    const response = await axios.get("/api/questions-peer", {
                        params: {
                            batch_year: this.batch_year,
                            project_name: this.studentInfo.project_name,
                        },
                    });

                    console.log("Raw API response:", response.data);

                    if (!response.data || !response.data.data) {
                        console.error(
                            "Invalid response structure:",
                            response.data
                        );
                        throw new Error("Invalid response format");
                    }

                    const { assessments, group_members, project_details } =
                        response.data.data;

                    if (!Array.isArray(assessments)) {
                        console.error(
                            "Assessments is not an array:",
                            assessments
                        );
                        throw new Error("Invalid assessments format");
                    }

          this.questions = assessments.map(q => ({
            id: q.id,
            type: q.type,
            question: q.question,
            aspect: q.aspect,
            criteria: q.criteria,
            skill_type: q.skill_type,
            bobot_1: q.bobot_1,
            bobot_2: q.bobot_2,
            bobot_3: q.bobot_3,
            bobot_4: q.bobot_4,
            bobot_5: q.bobot_5
          }));

                    if (Array.isArray(group_members)) {
                        this.groupMembers = group_members;
                    }

                    if (project_details) {
                        this.projectDetails = project_details;
                    }

                    console.log("Processed questions:", this.questions);
                    console.log("Group members:", this.groupMembers);
                    console.log("Project details:", this.projectDetails);

                    return;
                } catch (error) {
                    console.error(`Attempt ${i + 1} failed:`, error);
                    if (error.response) {
                        console.error("Error response:", error.response.data);
                    }
                    if (i === retryCount - 1) throw error;
                    await new Promise((resolve) => setTimeout(resolve, 1000));
                }
            }
        },

        setScore(value) {
            this.score = value;
            this.saveTemporaryAnswer();
        },

        async submitAnswer() {
            if (!this.currentQuestion || !this.selectedMember) return;

            const mahasiswaData = await this.fetchUserIdByNim(
                this.studentInfo.nim
            );
            if (!mahasiswaData?.mahasiswa_id) {
                alert("Data mahasiswa tidak ditemukan");
                return;
            }

            try {
                this.saveTemporaryAnswer();

                const answersToSubmit = [];

                const peerAnswers =
                    this.temporaryAnswers[this.selectedMember] || {};

                for (const [questionId, answerData] of Object.entries(
                    peerAnswers
                )) {
                    if (!answerData.answer && answerData.score === 0) continue;

                    answersToSubmit.push({
                        mahasiswa_id: mahasiswaData.mahasiswa_id,
                        peer_id: this.selectedMember,
                        question_id: questionId,
                        answer: answerData.answer,
                        score: answerData.score,
                        status: "submitted",
                    });
                }

                const responses = await Promise.all(
                    answersToSubmit.map((answer) =>
                        axios.post("/api/save-answer-peer", answer)
                    )
                );

                const allSuccess = responses.every(
                    (response) => response.data.success
                );

                if (allSuccess) {
                    this.temporaryAnswers[this.selectedMember] = {};
                    localStorage.setItem(
                        "temporaryAnswers",
                        JSON.stringify(this.temporaryAnswers)
                    );

                    this.nextQuestion();
                    this.saveCurrentState();

                    alert("Answer saved successfully");
                } else {
                    alert("Some answers failed to save. Please try again.");
                }
            } catch (error) {
                console.error("Error submitting answers:", error);
                alert("Failed to save the answer. Please try again.");
            }
        },

        async fetchUserIdByNim(nim) {
            try {
                const response = await axios.get(
                    `/api/users/search?nim=${nim}`
                );
                return response.data;
            } catch (error) {
                console.error("Error fetching mahasiswa data:", error);
                return null;
            }
        },

        nextQuestion() {
            this.saveTemporaryAnswer();
            if (this.currentQuestionIndex < this.questions.length - 1) {
                this.currentQuestionIndex++;
                this.loadTemporaryAnswer();
                this.loadExistingAnswers();
                this.saveCurrentState();
            }
        },

        prevQuestion() {
            this.saveTemporaryAnswer();
            if (this.currentQuestionIndex > 0) {
                this.currentQuestionIndex--;
                this.loadTemporaryAnswer();
                this.loadExistingAnswers();
                this.saveCurrentState();
            }
        },
        saveCurrentState() {
            const stateToSave = {
                selectedMember: this.selectedMember,
                currentQuestionIndex: this.currentQuestionIndex,
                answers: this.answers,
            };
            localStorage.setItem(
                "peerAssessmentState",
                JSON.stringify(stateToSave)
            );
        },

        loadSavedState() {
            const savedState = localStorage.getItem("peerAssessmentState");
            if (savedState) {
                const state = JSON.parse(savedState);
                // Only restore the state if we want to - removed for initial load
                this.selectedMember = state.selectedMember;
                this.currentQuestionIndex = state.currentQuestionIndex;
                this.answers = state.answers || {};
            }
        },

        async checkExistingAnswer() {
            if (!this.selectedMember || !this.currentQuestion) return;

            try {
                const mahasiswaData = await this.fetchUserIdByNim(
                    this.studentInfo.nim
                );
                if (!mahasiswaData) {
                    console.error("Failed to fetch user ID");
                    return;
                }

                const response = await axios.get("/api/existing-peer-answers", {
                    params: {
                        mahasiswa_id: mahasiswaData.mahasiswa_id,
                        peer_id: this.selectedMember,
                        question_id: this.currentQuestion.id,
                    },
                });

                console.log("Check Existing Answer Response:", response.data);

                if (response.data && response.data.length > 0) {
                    const existingAnswer = response.data[0];
                    this.answer = existingAnswer.answer || "";
                    this.score = existingAnswer.score || 0;

                    if (!this.temporaryAnswers[this.selectedMember]) {
                        this.temporaryAnswers[this.selectedMember] = {};
                    }

                    this.temporaryAnswers[this.selectedMember][
                        this.currentQuestion.id
                    ] = {
                        answer: this.answer,
                        score: this.score,
                    };

                    localStorage.setItem(
                        "temporaryAnswers",
                        JSON.stringify(this.temporaryAnswers)
                    );
                } else {
                    this.answer = "";
                    this.score = 0;
                }
            } catch (error) {
                console.error("Error checking existing answer:", error);
                this.answer = "";
                this.score = 0;
            }
        },

        loadTemporaryAnswer() {
            if (!this.selectedMember || !this.currentQuestion) return;

            const memberAnswers = this.temporaryAnswers[this.selectedMember];
            if (memberAnswers?.[this.currentQuestion.id]) {
                const savedAnswer = memberAnswers[this.currentQuestion.id];
                this.answer = savedAnswer.answer;
                this.score = savedAnswer.score;
            } else {
                this.answer = "";
                this.score = 0;
            }
        },
        saveTemporaryAnswer() {
            if (!this.selectedMember || !this.currentQuestion) return;

            if (!this.temporaryAnswers[this.selectedMember]) {
                this.temporaryAnswers[this.selectedMember] = {};
            }

            this.temporaryAnswers[this.selectedMember][
                this.currentQuestion.id
            ] = {
                answer: this.answer,
                score: this.score,
            };

            localStorage.setItem(
                "temporaryAnswers",
                JSON.stringify(this.temporaryAnswers)
            );
        },

        showSubmitConfirmation() {
            this.saveTemporaryAnswer();
            if (this.allQuestionsAnswered) {
                this.showConfirmModal = true;
            } else {
                alert("Mohon jawab semua question terlebih dahulu");
            }
        },
        async loadExistingAnswers() {
            if (!this.selectedMember || !this.currentQuestion) return;

            try {
                const mahasiswaData = await this.fetchUserIdByNim(
                    this.studentInfo.nim
                );
                if (!mahasiswaData?.mahasiswa_id) {
                    alert("Data mahasiswa tidak ditemukan");
                    return;
                }

                this.answer = "";
                this.score = 0;

                if (
                    this.temporaryAnswers[this.selectedMember]?.[
                        this.currentQuestion.id
                    ]
                ) {
                    const tempData =
                        this.temporaryAnswers[this.selectedMember][
                            this.currentQuestion.id
                        ];
                    this.answer = tempData.answer;
                    this.score = tempData.score;
                    return;
                }

                const response = await axios.get(
                    `/api/get-answer-peer/${this.currentQuestion.id}`,
                    {
                        params: {
                            mahasiswa_id: mahasiswaData.mahasiswa_id,
                            peer_id: this.selectedMember,
                        },
                    }
                );

                if (!this.temporaryAnswers[this.selectedMember]) {
                    this.temporaryAnswers[this.selectedMember] = {};
                }

                if (response.data) {
                    this.temporaryAnswers[this.selectedMember][
                        this.currentQuestion.id
                    ] = {
                        answer: response.data.answer || "",
                        score: response.data.score || 0,
                    };

                    this.answer = response.data.answer || "";
                    this.score = response.data.score || 0;
                }

                localStorage.setItem(
                    "temporaryAnswers",
                    JSON.stringify(this.temporaryAnswers)
                );
            } catch (error) {
                console.error("Error loading existing answer:", error);
                this.answer = "";
                this.score = 0;
            }
        },

        async handleMemberChange(newMemberId) {
            this.selectedMember = newMemberId;
            await this.loadExistingAnswers();
        },

        async submitAllAnswers() {
            this.saveTemporaryAnswer();

            try {
                this.isSubmitting = true;

                const mahasiswaData = await this.fetchUserIdByNim(
                    this.studentInfo.nim
                );
                if (!mahasiswaData?.mahasiswa_id) {
                    alert("Data mahasiswa tidak ditemukan");
                    return;
                }

                if (!this.temporaryAnswers[this.selectedMember]) {
                    console.error("No temporary answers found");
                    return;
                }

                const emptyQuestions = this.questions.filter((question) => {
                    const savedAnswer =
                        this.temporaryAnswers[this.selectedMember][question.id];
                    return !savedAnswer?.answer?.trim();
                });

                if (emptyQuestions.length > 0) {
                    alert(
                        `Mohon isi jawaban untuk pertanyaan berikut:\n${emptyQuestions
                            .map((q) => q.question)
                            .join("\n")}`
                    );
                    return;
                }

                const answers = this.questions.map((question) => {
                    const savedAnswer =
                        this.temporaryAnswers[this.selectedMember][question.id];
                    return {
                        mahasiswa_id: String(mahasiswaData.mahasiswa_id),
                        peer_id: String(this.selectedMember),
                        question_id: question.id,
                        answer: savedAnswer.answer.trim(),
                        score: savedAnswer.score || 0,
                        status: "submitted",
                    };
                });

                await axios.post("/api/save-all-answers-peer", {
                    answers: answers,
                });

                localStorage.removeItem("temporaryAnswers");
                localStorage.removeItem("peerAssessmentState");

                window.location.href = "/mahasiswa/assessment/peer";
            } catch (error) {
                console.error("Error submitting answers:", error);
                alert(
                    error.response?.data?.message ||
                        "Gagal menyimpan jawaban. Silakan coba lagi."
                );
            } finally {
                this.isSubmitting = false;
            }
        },
        async fetchAnsweredPeers() {
            try {
                const response = await axios.get("/api/answered-peers", {
                    params: {
                        project_id: this.namaProyek,
                    },
                });
                this.answeredPeers = response.data.answered_peers;
                console.log("Answered Peers:", this.answeredPeers);
            } catch (error) {
                console.error("Error fetching answered peers:", error);
            }
        },

        resetForm() {
            this.temporaryAnswers = {};
            this.answer = "";
            this.score = 0;
            this.currentQuestionIndex = 0;
            this.selectedMember = ""; // Reset selection when form is reset
        },
    },
};

// console.log(currentQuestion.skill_type)
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

                <Card title="FORMULIR PENGISIAN PEER ASSESSMENT" class="w-full">
                    <div v-if="loading" class="text-center py-8">
                        <p>Memuat data...</p>
                    </div>

                    <template v-else>
                        <div
                            class="grid grid-cols-2 gap-6 text-sx leading-6 mb-6"
                        >
                            <div>
                                <p>
                                    <strong>NIM:</strong> {{ studentInfo.nim }}
                                </p>
                                <p>
                                    <strong>Nama Lengkap:</strong>
                                    {{ studentInfo.name }}
                                </p>
                                <p>
                                    <strong>Kelas:</strong>
                                    {{ studentInfo.class }}
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

                        <div v-if="error" class="text-center py-8 text-red-600">
                            <p>{{ error }}</p>
                            <button
                                @click="initializeData"
                                class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                            >
                                Coba Lagi
                            </button>
                        </div>

                        <!-- Peer selection section -->
                        <div class="mb-6">
                            <label
                                for="select-member"
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                Choose a Friend Group to Assess
                            </label>
                            <select
                                id="select-member"
                                v-model="selectedMember"
                                class="block w-full rounded-md border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option disabled value="">
                                    -- Pilih Teman Kelompok --
                                </option>
                                <option
                                    v-for="member in availableMembers"
                                    :key="member.mahasiswa_id"
                                    :value="member.mahasiswa_id"
                                >
                                    {{ member.name }} ({{ member.nim }})
                                </option>
                            </select>
                        </div>

                        <!-- No data available message -->
                        <div
                            v-if="availableMembers.length === 0"
                            class="text-center py-8 bg-gray-50 rounded-lg"
                        >
                            <p class="text-gray-600 font-medium">
                                Tidak ada teman kelompok yang tersedia untuk
                                dinilai.
                            </p>
                            <p class="text-gray-500 mt-2">
                                Semua teman kelompok sudah selesai dinilai atau
                                belum ada teman kelompok yang terdaftar.
                            </p>
                        </div>

                        <!-- Only show questions if a member is selected AND availableMembers exists -->
                        <div
                            v-if="
                                selectedMember &&
                                currentQuestion &&
                                availableMembers.length > 0
                            "
                            class="space-y-6"
                        >
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="font-semibold text-lg mb-4 flex items-center justify-between">
                                    <span>
                    Question {{ currentQuestionIndex + 1 }} dari
                                    {{ questions.length }}
                                  </span>

                  <!-- Badge Skill Type -->
                  <span class="px-3 py-1 rounded-full text-white text-sm ml-auto" :class="{
                    'bg-blue-500': currentQuestion.skill_type === 'hardskill',
                    'bg-green-500': currentQuestion.skill_type === 'softskill'
                  }">
                    {{ currentQuestion.skill_type === 'softskill' ? 'Soft Skill' : 'Hard Skill' }}
                  </span>
                </h3>
                
                <p class="mb-2">
                                    <strong>Aspek:</strong>
                                    {{ currentQuestion.aspect }}
                                </p>
                                <p>
                                    <strong>Kriteria:</strong>
                                    {{ currentQuestion.criteria }}
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
                                                    currentQuestion[header.key]
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
                                <div class="score-container mt-4">
                                    <div class="slider-container">
                                        <div class="track"></div>
                                        <div class="points">
                                            <div
                                                class="point"
                                                v-for="scale in [1, 2, 3, 4, 5]"
                                                :key="scale"
                                                @click="setScore(scale)"
                                                :class="{
                                                    active: score === scale,
                                                }"
                                            ></div>
                                        </div>
                                    </div>
                                    <div class="values">
                                        <span
                                            v-for="scale in [1, 2, 3, 4, 5]"
                                            :key="scale"
                                            class="value"
                                        >
                                            {{ scale }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <form
                                @submit.prevent="submitAnswer"
                                class="space-y-4"
                            >
                                <div>
                                    <label
                                        for="answer"
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Jawaban Anda:
                                    </label>
                                    <textarea
                                        id="answer"
                                        v-model="answer"
                                        rows="4"
                                        class="block w-full rounded-md border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Berikan alasan..."
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
                                        @click="showSubmitConfirmation"
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

                                <ConfirmModal
                                    :show="showConfirmModal"
                                    title="Konfirmasi Pengiriman"
                                    message="Apakah Anda yakin semua jawaban sudah sesuai? Setelah dikirim, jawaban tidak dapat diubah kembali."
                                    @close="showConfirmModal = false"
                                    @confirm="submitAllAnswers"
                                />
                            </form>
                        </div>
                    </template>
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
