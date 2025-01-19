<script>
import axios from 'axios';
import DataTable from "@/Components/DataTable.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import SidebarMahasiswa from '../../Components/SidebarMahasiswa.vue';
import Breadcrumb from "@/Components/Breadcrumb.vue";

export default {
    components: {
        DataTable,
        Navbar,
        Card,
        SidebarMahasiswa,
        Breadcrumb
    },
    props: {
        studentInfo: {
            type: Object,
            default: () => ({
                'nim': '',
                'name': '',
                'class': '',
                'group': '',
                'project': '',
                'date': '',
            })
        }
    },

    data() {
        return {
            breadcrumbs: [
                { text: "Assessment", href: "/self" },
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
            // studentInfo: {
            //     nim: '',
            //     name: '',
            //     class: '',
            //     group: '',
            //     project: '',
            //     date: ''
            // }
        };
    },
    computed: {
        currentQuestion() {
            console.log('Current question index:', this.currentQuestionIndex);
            console.log('Current question:', this.questions[this.currentQuestionIndex]);
            return this.questions[this.currentQuestionIndex] || null;
        }
    },
    async created() {
        console.log('Component created - starting fetch');
        await this.fetchQuestions();
        await this.fetchStudentsInfo();
    },
    methods: {
        async fetchQuestions() {
            console.log('Fetching questions started');
            this.loading = true;
            this.error = null;
            
            try {
                // Menggunakan URL lengkap
                const response = await axios.get('/api/questions');
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
                const response = await axios.get('/api/user-info');
                if (response.data) {
                    this.studentInfo = response.data;
                }
            } catch (error) {
                console.error ('Failed to fetch student info: ', error);
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
            
            console.log('Submitting answer for question:', this.currentQuestion.id);
            
            axios.post('/api/save-answer', {
                question_id: this.currentQuestion.id,
                answer: this.answer
            }).then((response) => {
                console.log('Answer saved:', response);
                alert('Jawaban berhasil disimpan!');
                this.nextQuestion();
            }).catch(error => {
                console.error('Error saving answer:', error);
                alert('Gagal menyimpan jawaban. Silakan coba lagi.');
            });
        },
        prevQuestion() {
            if (this.currentQuestionIndex > 0) {
                this.currentQuestionIndex--;
                this.answer = '';
                console.log('Moved to previous question:', this.currentQuestionIndex);
            }
        },
        nextQuestion() {
            if (this.currentQuestionIndex < this.questions.length - 1) {
                this.currentQuestionIndex++;
                this.answer = '';
                console.log('Moved to next question:', this.currentQuestionIndex);
            } else {
                alert('Semua pertanyaan telah dijawab!');
            }
        }
    }
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
                    title="FORMULIR PENGISIAN SELF ASSESSMENT"
                    class="w-full"
                >
                    <!-- Student Information -->
                    <div class="grid grid-cols-2 gap-6 text-sm leading-6 mb-6">
                        <div>
                            <p><strong>NIM:</strong> {{ studentInfo.nim }}</p>
                            <p><strong>Nama Lengkap:</strong> {{ studentInfo.name }}</p>
                            <p><strong>Kelas:</strong> {{ studentInfo.class }}</p>
                        </div>
                        <div>
                            <p><strong>Kelompok:</strong> {{ studentInfo.group }}</p>
                            <p><strong>Proyek:</strong> {{ studentInfo.project }}</p>
                            <p><strong>Tanggal Pengisian:</strong> {{ studentInfo.date }}</p>
                        </div>
                    </div>

                    <!-- Debug Info - Akan membantu untuk debugging -->
                    <!-- <div class="mb-4 p-2 bg-gray-100 text-sm">
                        <p>Loading: {{ loading }}</p>
                        <p>Error: {{ error }}</p>
                        <p>Questions Count: {{ questions.length }}</p>
                        <p>Current Index: {{ currentQuestionIndex }}</p>
                    </div> -->

                    <Card>
                    <!-- Loading State -->
                    <div v-if="loading" class="text-center py-8">
                        <p>Memuat pertanyaan...</p>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="error" class="text-center py-8 text-red-600">
                        <p>{{ error }}</p>
                        <button 
                            @click="fetchQuestions"
                            class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                        >
                            Coba Lagi
                        </button>
                    </div>

                    <!-- Questions Display -->
                    <div v-else-if="currentQuestion" class="space-y-6">
                        <!-- Question Information -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-lg mb-4">
                                Pertanyaan {{ currentQuestionIndex + 1 }} dari {{ questions.length }}
                            </h3>
                            <p class="mb-2"><strong>Aspek:</strong> {{ currentQuestion.aspek }}</p>
                            <p><strong>Kriteria:</strong> {{ currentQuestion.kriteria }}</p>
                        </div>

                        <!-- Bobot Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full border-collapse border border-gray-200">
                                <thead>
                                    <tr>
                                        <th v-for="header in headers" 
                                            :key="header.key"
                                            class="border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700"
                                        >
                                            {{ header.label }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td v-for="header in headers" 
                                            :key="header.key"
                                            class="border border-gray-200 px-4 py-2 text-sm text-center"
                                        >
                                            {{ currentQuestion[header.key] }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Question Text -->
                        <div>
                            <p class="text-gray-700">{{ currentQuestion.pertanyaan }}</p>
                        </div>

                        <!-- Menambahkan komponen Rating Slider -->
                        <div class="score-container mt-4">
                            <div class="slider-container">
                                <div class="track"></div>
                                <div class="points">
                                    <div class="point" 
                                        v-for="n in 5" 
                                        :key="n"
                                        :class="{ active: score >= n }"
                                        @click="setScore(n)"
                                        :data-value="n">
                                    </div>
                                </div>
                            </div>
                            <div class="values">
                                <span v-for="n in 5" :key="n" class="value">{{ n }}</span>
                            </div>
                            <!-- <div class="selected-value">
                                <span>{{ score }}</span>
                            </div> -->
                        </div>
                        
                        <!-- Answer Form -->
                        <form @submit.prevent="submitAnswer" class="space-y-4">
                            <div>
                                <textarea
                                    id="answer"
                                    v-model="answer"
                                    rows="4"
                                    class="block w-full rounded-md border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Berikan alasannya... (Apakah Anda menghadapi kesulitan atau kemudahan dalam mengumpulkan iklan)"
                                    required
                                ></textarea>
                            </div>

                            <!-- Navigation -->
                            <div class="flex justify-between items-center pt-4">
                                <button
                                    type="button"
                                    @click="prevQuestion"
                                    :disabled="currentQuestionIndex === 0"
                                    class="px-4 py-2 bg-yellow-400 text-white rounded hover:bg-blue-600"
                                >
                                    Sebelumnya
                                </button>

                                <button
                                    type="submit"
                                    class="px-4 py-2 bg-blue-400 text-white rounded hover:bg-blue-600"
                                >
                                    Simpan Jawaban
                                </button>

                                <button
                                    type="button"
                                    @click="nextQuestion"
                                    :disabled="currentQuestionIndex === questions.length - 1"
                                    class="px-4 py-2 bg-green-500 text-white rounded hover:bg-blue-600"
                                >
                                    Selanjutnya
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- No Questions State -->
                    <div v-else class="text-center py-8">
                        <p>Tidak ada pertanyaan tersedia.</p>
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
    transition: background-color 0.3s;
}

.point.active {
    background: #8be1f3;
}

.values {
    display: flex;
    justify-content: space-between;
    margin-top: 1px;
}

.value {
    font-size: 16px;
    color: #666;
}

.selected-value {
    text-align: center;
    margin-top: 20px;
    font-size: 18px;
    font-weight: bold;
    color: #85ccda;
}
</style>