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
                { text: "Assessment", href: "/dosen/assessment/projectsSelf" },
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
            return this.questions.every(question => {
                const temp = this.temporaryAnswers[question.id];
                return temp?.answer && temp?.score;
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
                const params = {
                    batch_year: this.batch_year,
                    project_name: this.project_name
                };

                console.log('Request params:', params);

                const response = await axios.get('/api/questions-dosen', { params });
                console.log('Raw API Response:', response.data);

                if (response.data && Array.isArray(response.data)) {
                    this.questions = response.data.map(question => ({
                        id: question.id,
                        aspect: question.aspek || '',
                        criteria: question.kriteria || '',
                        question: question.question || '',
                        bobot_1: question.bobot_1 || '',
                        bobot_2: question.bobot_2 || '',
                        bobot_3: question.bobot_3 || '',
                        bobot_4: question.bobot_4 || '',
                        bobot_5: question.bobot_5 || '',
                    }));
                    console.log('Processed questions:', this.questions);
                } else {
                    throw new Error('Invalid response format - expected array');
                }
            } catch (error) {
                console.error('Error fetching questions:', {
                    message: error.message,
                    response: error.response?.data,
                    status: error.response?.status,
                    params: {
                        batch_year: this.batch_year,
                        project_name: this.project_name
                    }
                });
                this.error = `Error loading questions: ${error.message}`;
            } finally {
                this.loading = false;
            }
        },

        async fetchStudentsInfo() {
            try {
                const response = await axios.get('/api/user-info-dosen');
                if (response.data) {
                    this.studentInfo = response.data;
                    this.studentInfo.project = this.project_name;
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
                const response = await axios.post('/api/save-answer', {
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
            this.saveTemporaryAnswer();
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
                const response = await axios.get(`/api/get-answer/${this.currentQuestion.id}`);

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
                alert('Mohon jawab semua pertanyaan terlebih dahulu');
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

                const response = await axios.post('/api/save-all-answers-dosen', { answers: allAnswers });

                if (response.data.success) {
                    this.clearFormFields();
                    alert('Semua jawaban berhasil disimpan!');
                    this.$inertia.visit('/dosen/assessment/projectsSelf');
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
    <div class="flex min-h-screen bg-gray-50">
        <Sidebar role="dosen" />
        <div class="flex-1">
            <Navbar userName="dosen" />
            <main class="p-6">
                <div class="mb-6">
                    <Breadcrumb :items="breadcrumbs" />
                </div>

                <Card title="Self Assessment Form" class="w-full">
                    <!-- User Info Section -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mb-6">
                        <h2 class="text-xl font-semibold mb-4 text-gray-800">Lecturer Information</h2>
                        <div class="grid md:grid-cols-2 gap-6 text-sm">
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <span class="w-32 text-gray-600 font-medium">NIP:</span>
                                    <span class="text-gray-900">{{ studentInfo.nip }}</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="w-32 text-gray-600 font-medium">Full Name:</span>
                                    <span class="text-gray-900">{{ studentInfo.name }}</span>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <span class="w-32 text-gray-600 font-medium">Project:</span>
                                    <span class="text-gray-900">{{ studentInfo.project }}</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="w-32 text-gray-600 font-medium">Date:</span>
                                    <span class="text-gray-900">{{ studentInfo.date }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <Card>
                        <!-- Loading State -->
                        <div v-if="loading" class="flex items-center justify-center py-12">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                        </div>

                        <!-- Error State -->
                        <div v-else-if="error" class="text-center py-8">
                            <div class="text-red-600 mb-4">{{ error }}</div>
                            <button @click="fetchQuestions"
                                class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                Try Again
                            </button>
                        </div>

                        <!-- Question Display -->
                        <div v-else-if="currentQuestion" class="space-y-8">
                            <!-- Progress Bar -->
                            <div class="mb-6">
                                <div class="flex justify-between text-sm text-gray-600 mb-2">
                                    <span>Question Progress</span>
                                    <span>{{ currentQuestionIndex + 1 }} of {{ questions.length }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-blue-500 h-2.5 rounded-full transition-all duration-300"
                                        :style="{ width: `${((currentQuestionIndex + 1) / questions.length) * 100}%` }">
                                    </div>
                                </div>
                            </div>

                            <!-- Question Content -->
                            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                                <div class="space-y-4">
                                    <div class="flex gap-4">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-gray-900">Aspect</h3>
                                            <p class="text-gray-700">{{ currentQuestion.aspect }}</p>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-gray-900">Criteria</h3>
                                            <p class="text-gray-700">{{ currentQuestion.criteria }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-6">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Question</h3>
                                        <p class="text-gray-700 text-lg">{{ currentQuestion.question }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Scoring Criteria Table -->
                            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Scoring Criteria</h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead>
                                            <tr>
                                                <th v-for="header in headers" :key="header.key"
                                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {{ header.label }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr>
                                                <td v-for="header in headers" :key="header.key"
                                                    class="px-6 py-4 whitespace-pre-wrap text-sm text-gray-700">
                                                    {{ currentQuestion[header.key] }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Score Selection -->
                            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Score Selection</h3>
                                <div class="score-container">
                                    <div class="slider-container">
                                        <div class="track"></div>
                                        <div class="points">
                                            <div v-for="scale in [1, 2, 3, 4, 5]" :key="scale"
                                                @click="setScore(scale)"
                                                class="point-wrapper">
                                                <div class="point" :class="{ active: score === scale }"></div>
                                                <span class="point-label" :class="{ 'text-blue-600 font-medium': score === scale }">
                                                    {{ scale }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Answer Form -->
                            <form @submit.prevent="submitAnswer" class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Justification</h3>
                                <div class="space-y-4">
                                    <textarea
                                        id="answer"
                                        v-model="answer"
                                        rows="4"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Please provide your reasoning..."
                                        required
                                    ></textarea>

                                    <div class="flex justify-between items-center pt-4">
                                        <button
                                            type="button"
                                            @click="prevQuestion"
                                            :disabled="currentQuestionIndex === 0"
                                            class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                            Previous
                                        </button>

                                        <button
                                            type="submit"
                                            class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                            Save Answer
                                        </button>

                                        <button
                                            v-if="currentQuestionIndex === questions.length - 1"
                                            type="button"
                                            @click="handleSubmitAll"
                                            :disabled="isSubmitting"
                                            class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                            {{ isSubmitting ? 'Submitting...' : 'Submit All' }}
                                        </button>
                                        <button
                                            v-else
                                            type="button"
                                            @click="nextQuestion"
                                            class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                            Next
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- No Questions State -->
                        <div v-else class="text-center py-12">
                            <div class="text-gray-500 text-lg">No questions available.</div>
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
    padding: 0 10px;
}

.track {
    width: 100%;
    height: 4px;
    background: #e5e7eb;
    position: relative;
}

.points {
    display: flex;
    justify-content: space-between;
    position: absolute;
    width: 100%;
    top: -10px;
}

.point-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
}

.point {
    width: 24px;
    height: 24px;
    background: #fff;
    border: 2px solid #3b82f6;
    border-radius: 50%;
    transition: all 0.2s ease;
    margin-bottom: 8px;
}

.point.active {
    background: #3b82f6;
    transform: scale(1.2);
}

.point:hover {
    transform: scale(1.1);
    background: #bfdbfe;
}

.point-label {
    font-size: 14px;
    color: #6b7280;
    transition: all 0.2s ease;
}

.selected-value {
    text-align: center;
    margin-top: 20px;
    font-size: 18px;
    font-weight: 600;
    color: #3b82f6;
}
</style>