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
  data() {
    return {
      breadcrumbs: [
        { text: "Assessment", href: "/self" },
        { text: "Peer Assessment", href: null }
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
      scaleAnswer: null,
      loading: true,
      error: null,
      kelompok: [],
      selectedMember: '',
      studentInfo: {
        nim: '',
        name: '',
        class: '',
        group: '',
        project: '',
        date: ''
      }
    };
  },
  computed: {
    currentQuestion() {
      return this.questions[this.currentQuestionIndex] || null;
    }
  },
  created() {
    this.initializeData();
  },
  methods: {
    async initializeData() {
      this.loading = true;
      this.error = null;

      try {
        // Fetch kelompok data first
        const kelompokResponse = await axios.get("/api/kelompok");
        if (kelompokResponse.data && Array.isArray(kelompokResponse.data)) {
          const currentUser = kelompokResponse.data[0] || {};
          this.studentInfo = {
            nim: currentUser.user?.nim || "",
            name: currentUser.user?.name || "",
            class: currentUser.user?.kelas || "",
            group: currentUser.kelompok || "",
            project: currentUser.nama_proyek || "",
            date: new Date().toLocaleDateString("id-ID"),
          };
          console.log('data :', this.studentInfo);
          this.kelompok = kelompokResponse.data.filter(
            (item) =>
              item.kelompok === currentUser.kelompok &&
              item.user?.id !== currentUser.user?.id
          );
          
          // Only fetch questions after kelompok data is loaded
          const questionsResponse = await axios.get('/api/questions-peer');
          if (questionsResponse.data && Array.isArray(questionsResponse.data)) {
            this.questions = questionsResponse.data;
          } else {
            throw new Error('Invalid questions response format');
          }
        } else {
          throw new Error('Invalid kelompok response format');
        }
      } catch (error) {
        this.error = `Error loading data: ${error.message}`;
        console.error('Error:', error);
      } finally {
        this.loading = false;
      }
    },
    async fetchUserIdByNim(nim) {
      try {
        const response = await axios.get(`/api/users/search?nim=${nim}`);
        return response.data?.user_id || null;
      } catch (error) {
        console.error("Error fetching user data:", error);
        return null;
      }
    },
    async submitAnswer() {
      if (!this.currentQuestion) return;

      const user_id = await this.fetchUserIdByNim(this.studentInfo.nim);  // Pastikan menggunakan await
      if (!user_id) {
        alert('User tidak ditemukan');
        return;
      }

      const peer_id = this.selectedMember; // ID teman yang dinilai
      const score = this.scaleAnswer; // Skor dari jawaban
      const status = 'submitted'; // Status jawaban

      // Menambahkan log untuk melihat data yang akan disubmit
      console.log("Data yang akan disubmit:", {
        user_id: user_id,
        peer_id: peer_id,
        question_id: this.currentQuestion.id,
        answer: this.answer,
        score: score,
        status: status
      });

      try {
        await axios.post('/api/save-answer-peer', {
          user_id: user_id, 
          peer_id: peer_id,
          question_id: this.currentQuestion.id,
          answer: this.answer,
          score: score, 
          status: status,
        });
        alert('Jawaban berhasil disimpan!');
        this.nextQuestion();
      } catch (error) {
        alert('Gagal menyimpan jawaban. Silakan coba lagi.');
      }
    },
    prevQuestion() {
      if (this.currentQuestionIndex > 0) {
        this.currentQuestionIndex--;
        this.answer = '';
        this.scaleAnswer = null;
      }
    },
    nextQuestion() {
      if (this.currentQuestionIndex < this.questions.length - 1) {
        this.currentQuestionIndex++;
        this.answer = '';
        this.scaleAnswer = null;
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

        <Card title="FORMULIR PENGISIAN PEER ASSESSMENT" class="w-full">
          <div v-if="loading" class="text-center py-8">
            <p>Memuat data...</p>
          </div>

          <template v-else>
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

            <div class="mb-6">
              <label for="select-member" class="block text-sm font-medium text-gray-700 mb-2">
                Pilih Teman Kelompok untuk Dinilai
              </label>
              <select
                id="select-member"
                v-model="selectedMember"
                class="block w-full rounded-md border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
              >
                <option disabled value="">-- Pilih Teman Kelompok --</option>
                <option v-for="member in kelompok" :key="member.user.id" :value="member.user.id">
                  {{ member.user.name }}
                </option>
              </select>
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

            <div v-else-if="currentQuestion" class="space-y-6">
              <!-- Rest of the template remains the same -->
              <!-- Question display section -->
              <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold text-lg mb-4">
                  Pertanyaan {{ currentQuestionIndex + 1 }} dari {{ questions.length }}
                </h3>
                <p class="mb-2"><strong>Aspek:</strong> {{ currentQuestion.aspek }}</p>
                <p><strong>Kriteria:</strong> {{ currentQuestion.kriteria }}</p>
              </div>

              <div class="overflow-x-auto">
                <table class="min-w-full border-collapse border border-gray-200">
                  <thead>
                    <tr>
                      <th v-for="header in headers" :key="header.key" class="border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700">
                        {{ header.label }}
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td v-for="header in headers" :key="header.key" class="border border-gray-200 px-4 py-2 text-sm text-center">
                        {{ currentQuestion[header.key] }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="bg-white p-6 rounded-lg shadow-md">
                <p class="text-gray-700 mb-4">{{ currentQuestion.pertanyaan }}</p>
                <div class="flex justify-center space-x-4">
                  <button
                    v-for="scale in [1, 2, 3, 4, 5]"
                    :key="scale"
                    @click="scaleAnswer = scale" 
                    :class="[ 
                      'px-6 py-3 rounded-full text-sm font-medium transition',
                      scaleAnswer === scale
                        ? 'bg-blue-500 text-white'
                        : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                    ]"
                  >
                    {{ scale }}
                  </button>
                </div>
              </div>

              <form @submit.prevent="submitAnswer" class="space-y-4">
                <div>
                  <label for="answer" class="block text-sm font-medium text-gray-700 mb-2">
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

                <div class="flex justify-between items-center pt-4">
                  <button
                    type="button"
                    @click="prevQuestion"
                    :disabled="currentQuestionIndex === 0"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 disabled:opacity-50"
                  >
                    Sebelumnya
                  </button>

                  <button
                    type="submit"
                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                  >
                    Simpan Jawaban
                  </button>

                  <button
                    type="button"
                    @click="nextQuestion"
                    :disabled="currentQuestionIndex === questions.length - 1"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 disabled:opacity-50"
                  >
                    Selanjutnya
                  </button>
                </div>
              </form>
            </div>

            <div v-else class="text-center py-8">
              <p>Tidak ada pertanyaan tersedia.</p>
            </div>
          </template>
        </Card>
      </main>
    </div>
  </div>
</template>

