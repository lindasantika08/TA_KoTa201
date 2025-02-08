<script>
import axios from 'axios';
import DataTable from "@/Components/DataTable.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import SidebarMahasiswa from '../../Components/SidebarMahasiswa.vue';
import Breadcrumb from "@/Components/Breadcrumb.vue";
import ConfirmModal from '../../Components/ConfirmModal.vue';

export default {
    components: {
        DataTable,
        Navbar,
        Card,
        SidebarMahasiswa,
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
        batch_year: {
            type: String,
            default: ''
        },
        project_name: {
            type: String,
            default: ''
        }
    },

    data() {
        return {
            breadcrumbs: [
                { text: "Assessment", href: "/mahasiswa/assessment/self" },
                { text: "Self Assessment", href: null }
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
            return this.questions.length > 0 &&
                this.questions.every(question => {
                    const savedAnswer = this.temporaryAnswers[question.id];
                    return savedAnswer &&
                        savedAnswer.answer &&
                        savedAnswer.answer.trim() !== '' &&
                        savedAnswer.score !== null;
                });
        },
    },
    async created() {
        const savedTemp = localStorage.getItem('temporaryAnswers');
        if (savedTemp) {
            this.temporaryAnswers = JSON.parse(savedTemp);
        }
        await this.fetchQuestions();
        await this.fetchStudentsInfo();
        await this.loadExistingAnswer();
    },
    methods: {

        async fetchQuestions() {
            console.log('Fetching questions started');
            this.loading = true;
            this.error = null;

            try {
                console.log('Tahun Ajaran:', this.batch_year);
                console.log('Nama Proyek:', this.project_name);

                const response = await axios.get('/api/questions', {
                    params: {
                        batch_year: this.batch_year,
                        project_name: this.project_name
                    }
                });

                console.log('API Response:', response);

                if (response.data && Array.isArray(response.data)) {
                    this.questions = response.data;
                    console.log('Questions loaded:', this.questions.length);
                    this.loading = false;
                    await this.loadExistingAnswer();
                } else {
                    throw new Error('Invalid response format');
                }
            } catch (error) {
                console.error('Error details:', {
                    message: error.message,
                    response: error.response,
                    status: error.response?.status,
                    data: error.response?.data
                });

                this.error = error.response?.data?.error || `Error loading questions: ${error.message}`;
                this.loading = false;
            }
        },
        async fetchStudentsInfo() {
            try {
                const batch_year = this.$page.props.batch_year || this.$route.query.batch_year;
                const project_name = this.$page.props.project_name || this.$route.query.project_name;

                const response = await axios.get('/api/user-info', {
                    params: {
                        batch_year: batch_year,
                        project_name: project_name
                    }
                });

                if (response.data) {
                    this.studentInfo = response.data;
                }
            } catch (error) {
                console.error('Failed to fetch student info: ', error);
            }
        },
        setScore(value) {
            this.score = value;
            console.log('Score set to:', value);
        },

        async submitAnswer() {
            if (!this.currentQuestion) return;

            if (!this.score) {
                alert('Silakan pilih nilai terlebih dahulu');
                return;
            }

            try {
                const response = await axios.post('/api/save-answer-mhs', {
                    answers: [{
                        question_id: this.currentQuestion.id,
                        answer: this.answer,
                        score: this.score,
                        status: 'submitted'
                    }]
                });

                if (response.data.message.includes('successfully')) {
                    delete this.temporaryAnswers[this.currentQuestion.id];
                    localStorage.setItem('temporaryAnswers', JSON.stringify(this.temporaryAnswers));

                    alert(response.data.message);

                    if (this.currentQuestionIndex < this.questions.length - 1) {
                        this.currentQuestionIndex++;
                        await this.loadExistingAnswer();
                    }
                }
            } catch (error) {
                console.error('Error saving answer:', error);
                const errorMessage = error.response?.data?.message || 'Gagal menyimpan jawaban. Silakan coba lagi.';
                alert(errorMessage);
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
                const response = await axios.get(`/api/get-answer-mhs/${this.currentQuestion.id}`);

                const tempAnswer = this.temporaryAnswers[this.currentQuestion.id];
                if (tempAnswer) {
                    this.answer = tempAnswer.answer;
                    this.score = tempAnswer.score;
                    return;
                }

                if (response.data && response.data.answer) {
                    this.answer = response.data.answer;
                    this.score = response.data.score;
                } else {
                    this.answer = '';
                    this.score = null;
                }
            } catch (error) {
                console.error('Error loading existing answer:', error);
                this.answer = '';
                this.score = null;
            }
        },
        saveTemporaryAnswer() {
            if (this.currentQuestion) {
                if (this.answer || this.score) {
                    this.temporaryAnswers[this.currentQuestion.id] = {
                        answer: this.answer,
                        score: this.score
                    };

                    localStorage.setItem('temporaryAnswers', JSON.stringify(this.temporaryAnswers));
                }
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
                alert('Mohon lengkapi semua jawaban terlebih dahulu');
                return;
            }

            this.showConfirmModal = true;
        },

        async submitAllAnswers() {
            try {
                const allAnswers = this.questions.map(question => ({
                    question_id: question.id,
                    answer: this.temporaryAnswers[question.id]?.answer || '',
                    score: this.temporaryAnswers[question.id]?.score || null,
                    status: 'submitted'
                }));

                const response = await axios.post('/api/save-all-answers', { answers: allAnswers });

                if (response.data.success) {
                    this.clearFormFields();
                    alert('Semua jawaban berhasil disimpan!');
                    this.$inertia.visit('/mahasiswa/assessment/self');
                }
            } catch (error) {
                console.error('Error submitting answers:', error);
                alert('Gagal menyimpan jawaban. Silakan coba lagi.');
            } finally {
                this.isSubmitting = false;
                this.showConfirmModal = false;
            }
        },

        clearFormFields() {
            this.answer = '';
            this.score = null;
            this.temporaryAnswers = {};
            localStorage.removeItem('temporaryAnswers');
        },

        mounted() {
            window.addEventListener('beforeunload', (event) => {
                if (Object.keys(this.temporaryAnswers).length > 0) {
                    event.preventDefault();
                    event.returnValue = '';
                }
            });
        },

        beforeDestroy() {
            window.removeEventListener('beforeunload');
        }

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

                <Card title="FORMULIR PENGISIAN SELF ASSESSMENT" class="w-full">
                    <div class="grid grid-cols-2 gap-6 text-sm leading-6 mb-6">
                        <div>
                            <p><strong>NIM:</strong> {{ studentInfo.nim }}</p>
                            <p><strong>Nama Lengkap:</strong> {{ studentInfo.name }}</p>
                            <p><strong>Kelas:</strong> {{ studentInfo.class }}</p>
                        </div>
                        <div>
                            <p><strong>Kelompok:</strong> {{ studentInfo.group }}</p>
                            <p><strong>Proyek:</strong> {{ studentInfo.project_name }}</p>
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
                                <p class="mb-2"><strong>Aspek:</strong> {{ currentQuestion.aspek }}</p>
                                <p><strong>Kriteria:</strong> {{ currentQuestion.kriteria }}</p>
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
