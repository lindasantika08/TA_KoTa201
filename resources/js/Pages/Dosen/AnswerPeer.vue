<script>
import axios from 'axios';
import DataTable from "@/Components/DataTable.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Sidebar from '../../Components/Sidebar.vue';
import Breadcrumb from "@/Components/Breadcrumb.vue";
import ConfirmModal from '../../Components/ConfirmModal.vue';

export default {
    components: {
        DataTable,
        Navbar,
        Card,
        Sidebar,
        Breadcrumb,
        ConfirmModal
    },
    props: {
        studentInfo: {
            type: Object,
            default: () => ({
                'nip': '',
                'name': '',
                'class': '',
                'group': '',
                'project': '',
                'date': '',
            })
        },
        tahunAjaran: {
            type: String,
            default: ''
        },
        namaProyek: {
            type: String,
            default: ''
        }
    },

    data() {
        return {
            breadcrumbs: [
                { text: "Peer Assessment", href: "/dosen/assessment/projectsPeer" },
                { text: "Detail", href: "/dosen/assessment/data-with-bobot-peer" },
                { text: "Attempt", href: null }
            ],
            headers: [
                { key: 'bobot_1', label: 'Bobot 1' },
                { key: 'bobot_2', label: 'Bobot 2' },
                { key: 'bobot_3', label: 'Bobot 3' },
                { key: 'bobot_4', label: 'Bobot 4' },
                { key: 'bobot_5', label: 'Bobot 5' },
            ],
            questions: [],
            currentQuestionIndex: 0,
            answer: '',
            loading: true,
            error: null,
            studentInfo: {},
            score: 0,
            scaleAnswer: null,
            temporaryAnswers: {},
            showConfirmModal: false,
            isSubmitting: false,
        };
    },
    computed: {
        currentQuestion() {
            return this.questions[this.currentQuestionIndex] || null;
        },
        canSubmitAll() {
            return this.questions.every(question => {
                const temp = this.temporaryAnswers[question.id];
                return temp?.answer && temp?.score;
            });
        },
    },
    async created() {
        await this.fetchQuestions();
        await this.fetchStudentsInfo();
    },

    methods: {
        async fetchQuestions() {
            console.log('Fetching questions started');
            this.loading = true;
            this.error = null;

            try {
                console.log('Tahun Ajaran:', this.tahunAjaran);
                console.log('Nama Proyek:', this.namaProyek);

                const params = {
                    batch_year: this.tahunAjaran,
                    project_name: this.namaProyek
                };

                const response = await axios.get('/api/questions-peer-dosen', { params });

                console.log('API Response:', response);

                if (response.data && Array.isArray(response.data)) {
                    this.questions = response.data;
                    console.log('Questions loaded:', this.questions.length);
                    this.loading = false;
                } else {
                    throw new Error('Invalid response format');
                }
            } catch (error) {
                console.error('Error details:', {
                    message: error.message,
                    response: error.response,
                    status: error.response?.status
                });
                this.error = `Error loading questions: ${error.message}`;
                this.loading = false;
            }
        },

        async fetchStudentsInfo() {
            try {
                const response = await axios.get('/api/user-info-dosen');
                if (response.data) {
                    this.studentInfo = response.data;
                    console.log('Student Info:', this.studentInfo);
                    this.studentInfo.project = this.namaProyek;
                }
            } catch (error) {
                console.error('Failed to fetch student info: ', error);
            }
        },
        setScore(value) {
            this.score = value;
            console.log('Score set to:', value);
        },

        submitAnswer() {
            if (!this.currentQuestion) {
                console.log('No current question available');
                return;
            }

            if (!this.score) {
                alert('Silakan pilih nilai terlebih dahulu');
                return;
            }

            const payload = {
                question_id: this.currentQuestion.id,
                answer: this.answer,
                score: this.score,
                status: 'submitted'
            };

            console.log('Payload:', payload);

            axios.post('/api/save-answer-peer-dosen', payload)
                .then((response) => {
                    console.log('Answer saved:', response);
                    alert(response.data.message);
                    if (response.data.message === 'Answer saved successfully') {
                        this.nextQuestion();
                        this.score = null;
                        this.answer = '';
                    }
                })
                .catch(error => {
                    console.error('Error saving answer:', error);
                    alert('Gagal menyimpan jawaban. Silakan coba lagi.');
                });
        },
        async nextQuestion() {
            this.saveTemporaryAnswer();
            if (this.currentQuestionIndex < this.questions.length - 1) {
                this.currentQuestionIndex++;
                await this.loadExistingAnswer();
            } else {
                this.checkAllAnswered();
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

    // First, clear any previous answers
    this.answer = '';
    this.score = null;

    try {
        const response = await axios.get(`/api/get-answer-peer-dosen/${this.currentQuestion.id}`, {
            params: {
                current_question_id: this.currentQuestion.id
            }
        });

        if (response.data && response.data.question_id == this.currentQuestion.id) {
            this.answer = response.data.answer;
            this.score = response.data.score;
        }
    } catch (error) {
        console.error('Error loading existing answer:', error);
    }
},
        saveTemporaryAnswer() {
            if (this.currentQuestion) {
                this.temporaryAnswers[this.currentQuestion.id] = {
                    answer: this.answer,
                    score: this.score
                };
            }
        },
        checkAllAnswered() {
            this.allAnswered = this.questions.every(question =>
                this.temporaryAnswers[question.id] ||
                (this.currentQuestion?.id === question.id && this.answer && this.score)
            );
        },
        async handleSubmitAll() {
            this.saveTemporaryAnswer();

            if (!this.canSubmitAll) {
                alert('Mohon jawab semua pertanyaan terlebih dahulu');
                return;
            }

            this.showConfirmModal = true;
        },

        async submitAllAnswers() {
            try {
                this.isSubmitting = true;

                const allAnswers = Object.entries(this.temporaryAnswers).map(([questionId, data]) => ({
                    question_id: questionId,
                    answer: data.answer,
                    score: data.score,
                    status: 'submitted'
                }));

                const response = await axios.post('/api/save-all-answers-peer-dosen', { answers: allAnswers });

                if (response.data.success) {
                    this.resetAnswerState();
                    alert('Semua jawaban berhasil disimpan!');
                    this.temporaryAnswers = {};
                    this.$inertia.visit('/dosen/assessment/projectsPeer');
                }
            } catch (error) {
                console.error('Error submitting answers:', error);
                alert('Gagal menyimpan jawaban. Silakan coba lagi.');
            } finally {
                this.isSubmitting = false;
                this.showConfirmModal = false;
            }
        },
        resetAnswerState() {
            this.answer = '';
            this.score = null;
            this.temporaryAnswers = {};
        },

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

                <Card title="FORMULIR PENGISIAN SELF ASSESSMENT" class="w-full">
                    <div class="grid grid-cols-2 gap-6 text-sx leading-6 mb-6">
                        <div>
                            <p class="mb-2"><strong>NIP:</strong> {{ studentInfo.nip }}</p>
                            <p><strong>Nama Lengkap:</strong> {{ studentInfo.name }}</p>
                        </div>
                        <div>
                            <p class="mb-2"><strong>Proyek:</strong> {{ studentInfo.project }}</p>
                            <p><strong>Tanggal Pengisian:</strong> {{ studentInfo.date }}</p>
                        </div>
                    </div>


                    <Card>
                        <div v-if="loading" class="text-center py-8">
                            <p>Load Questions...</p>
                        </div>

                        <div v-else-if="error" class="text-center py-8 text-red-600">
                            <p>{{ error }}</p>
                            <button @click="fetchQuestions"
                                class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                Try Again
                            </button>
                        </div>

                        <div v-else-if="currentQuestion" class="space-y-6">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="font-semibold text-lg mb-4">
                                    Question {{ currentQuestionIndex + 1 }} dari {{ questions.length }}
                                </h3>
                                <p class="mb-2"><strong>Aspek:</strong> {{ currentQuestion.aspect }}</p>
                                <p><strong>Kriteria:</strong> {{ currentQuestion.criteria }}</p>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full border-collapse border border-gray-200">
                                    <thead>
                                        <tr>
                                            <th v-for="header in headers" :key="header.key"
                                                class="border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700">
                                                {{ header.label }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td v-for="header in headers" :key="header.key"
                                                class="border border-gray-200 px-4 py-2 text-sm text-center">
                                                {{ currentQuestion[header.key] }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="bg-white p-6 rounded-lg shadow-md">
                                <p class="text-gray-700 mb-4">{{ currentQuestion.question }}</p>
                                <div class="score-container mt-4">
                                    <div class="slider-container">
                                        <div class="track"></div>
                                        <div class="points">
                                            <div class="point" v-for="scale in [1, 2, 3, 4, 5]" :key="scale"
                                                @click="setScore(scale)" :class="{ active: score === scale }">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="values">
                                        <span v-for="scale in [1, 2, 3, 4, 5]" :key="scale" class="value">
                                            {{ scale }}
                                        </span>
                                    </div>

                                </div>
                            </div>

                            <form @submit.prevent="submitAnswer" class="space-y-4">
                                <div>
                                    <textarea id="answer" v-model="answer" rows="4"
                                        class="block w-full rounded-md border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Berikan alasannya... (Apakah Anda menghadapi kesulitan atau kemudahan dalam mengumpulkan iklan)"
                                        required></textarea>
                                </div>

                                <div class="flex justify-between items-center pt-4">
                                    <button type="button" @click="prevQuestion" :disabled="currentQuestionIndex === 0"
                                        class="px-4 py-2 bg-yellow-400 text-white rounded hover:bg-blue-600">
                                        Previous
                                    </button>

                                    <button type="submit"
                                        class="px-4 py-2 bg-blue-400 text-white rounded hover:bg-blue-600">
                                        Save Answer
                                    </button>

                                    <button v-if="currentQuestionIndex === questions.length - 1" type="button"
                                        @click="handleSubmitAll" :disabled="isSubmitting"
                                        class="px-4 py-2 bg-green-500 text-white rounded hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                        {{ isSubmitting ? 'Mengirim...' : 'Send' }}
                                    </button>
                                    <button v-else type="button" @click="nextQuestion"
                                        :disabled="currentQuestionIndex === questions.length - 1"
                                        class="px-4 py-2 bg-green-500 text-white rounded hover:bg-blue-600">
                                        Next
                                    </button>
                                </div>

                                <ConfirmModal :show="showConfirmModal" title="Konfirmasi Pengiriman"
                                    message="Apakah Anda yakin semua jawaban sudah sesuai? Setelah dikirim, jawaban tidak dapat diubah kembali."
                                    @close="showConfirmModal = false" @confirm="submitAllAnswers" />
                            </form>
                        </div>

                        <div v-else class="text-center py-8">
                            <p>Nothing Question.</p>
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
