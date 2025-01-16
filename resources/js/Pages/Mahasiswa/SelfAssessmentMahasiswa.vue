<script>
import axios from 'axios';
import DataTable from "@/Components/DataTable.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import SidebarMahasiswa from '../../Components/SidebarMahasiswa.vue';
import Breadcrumb from "@/Components/Breadcrumb.vue";
import { router } from '@inertiajs/vue3';

export default {
    components: {
        DataTable,
        Navbar,
        Card,
        SidebarMahasiswa,
        Breadcrumb
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
            items: [],
            questions: [],
            currentQuestionIndex: 0,
            answer: '',
            bobot: []
        };
    },
    mounted() {
        // Ambil data bobot untuk DataTable
        axios.get('/api/bobot', { params: { nama_proyek: '' } })
        .then(response => {
            if (Array.isArray(response.data)) {
                this.questions = response.data;
            } else {
                console.error('Invalid questions data format');
                this.questions = [];
            }
        })
        .catch(error => {
            console.error('Error fetching questions:', error);
            this.questions = [];
        });
    },
    watch: {
        currentQuestionIndex(newIndex) {
            if (this.questions.length > 0) {
                const currentQuestion = this.questions[newIndex];
                axios.get('/api/bobot', {
                    params: {
                        aspek: currentQuestion.aspek,
                        kriteria: currentQuestion.kriteria
                    }
                }).then(response => {
                    this.bobot = response.data;
                }).catch(error => {
                    console.error('Error fetching bobot:', error);
                });
            }
        }
    },
    methods: {
        submitAnswer() {
            if (this.questions.length === 0) return;
            
            const currentQuestion = this.questions[this.currentQuestionIndex];
            axios.post('/api/save-answer', {
                question_id: currentQuestion.id,
                answer: this.answer
            }).then(() => {
                alert('Jawaban disimpan!');
                this.nextQuestion();
            }).catch(error => {
                console.error('Error saving answer:', error);
            });
        },
        prevQuestion() {
            if (this.currentQuestionIndex > 0) {
                this.currentQuestionIndex--;
            }
        },
        nextQuestion() {
            if (this.currentQuestionIndex < this.questions.length - 1) {
                this.currentQuestionIndex++;
                this.answer = ''; // Reset jawaban setelah pindah ke pertanyaan berikutnya
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
                    description=""
                    class="w-full"
                >
                    <div class="grid grid-cols-2 gap-6 text-sm leading-6 mb-6">
                        <div>
                            <p><strong>NIM:</strong> 221511034</p>
                            <p><strong>Nama Lengkap:</strong>Linda Santika</p>
                            <p><strong>Kelas:</strong> 1B</p>
                        </div>
                        <div>
                            <p><strong>Kelompok:</strong> 1 (Satu)</p>
                            <p><strong>Proyek:</strong> Aplikasi Perkantoran</p>
                            <p><strong>Tanggal Pengisian:</strong> 25 Juli 2024</p>
                        </div>
                    </div>
                    
                    <!-- Tabel Bobot -->
                    <DataTable 
                        :headers="headers"
                        :items="items"
                        @view="handleView"
                    />
                    
                    <!-- Pertanyaan -->
                    <Card v-if="questions.length > 0" 
                          :title="`Pertanyaan ${currentQuestionIndex + 1}`" 
                          description="Isi sesuai dengan kriteria dan aspek">
                        <p>{{ questions[currentQuestionIndex].pertanyaan }}</p>
                    </Card>

                    <form @submit.prevent="submitAnswer" v-if="questions.length > 0">
                        <textarea v-model="answer" placeholder="Masukkan jawaban Anda"></textarea>
                        <button type="submit">Simpan Jawaban</button>
                    </form>

                    <div class="navigation" v-if="questions.length > 0">
                        <button @click="prevQuestion" :disabled="currentQuestionIndex === 0">Sebelumnya</button>
                        <button @click="nextQuestion" :disabled="currentQuestionIndex === questions.length - 1">Selanjutnya</button>
                    </div>
                </Card>
            </main>
        </div>
    </div>
</template>

<style scoped>
/* Tambahkan styling sesuai kebutuhan */
</style>
