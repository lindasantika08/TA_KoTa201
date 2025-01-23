<script>
import { usePage } from '@inertiajs/vue3';
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
      proyek
    }
  },

  data() {
    return {
      breadcrumbs: [
        { text: "Assessment", href: "/mahasiswa/assessment/peer" },
        { text: "Peer Assessment", href: null }
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
      selectedMember: "",
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
    };
  },

  computed: {
    currentQuestion() {
      return this.questions[this.currentQuestionIndex] || null;
    },

    allQuestionsAnswered() {
      if (!this.selectedMember || !this.temporaryAnswers[this.selectedMember]) {
        return false;
      }
      return this.questions.every(q =>
        this.temporaryAnswers[this.selectedMember][q.id]?.answer !== undefined &&
        this.temporaryAnswers[this.selectedMember][q.id]?.score !== undefined
      );
    },
  },

  watch: {
    selectedMember: {
      immediate: true,
      handler(newVal) {
        if (newVal) {
          this.currentQuestionIndex = 0;
          this.loadTemporaryAnswer();
          this.checkExistingAnswer();
        } else {
          this.resetForm();
        }
      }
    },

    questions: {
      immediate: true,
      handler(newVal) {
        if (newVal && newVal.length > 0) {
          this.currentQuestionIndex = 0;
          this.loadTemporaryAnswer();
        }
      }
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
        const userResponse = await axios.get("/api/user");
        const currentUser = userResponse.data;

        this.studentInfo = {
          nim: currentUser.nim || "",
          name: currentUser.name || "",
          class: currentUser.kelas || "",
          group: "",
          project: this.proyek,
          date: new Date().toLocaleDateString("id-ID"),
        };

        const kelompokResponse = await axios.get("/api/kelompok", {
          params: {
            tahun_ajaran: this.tahunAjaran,
            proyek: this.studentInfo.project
          }
        });

        if (kelompokResponse.data?.length > 0) {
          const userKelompok = kelompokResponse.data[0];
          this.studentInfo.group = userKelompok.kelompok || "";

          const filteredKelompok = kelompokResponse.data.filter(item =>
            item.tahun_ajaran === this.tahunAjaran &&
            item.nama_proyek === this.studentInfo.project
          );

          const answeredPeersResponse = await axios.get("/api/answered-peers");
          const answeredPeerIds = answeredPeersResponse.data || [];

          this.kelompok = filteredKelompok.filter(item =>
            item.kelompok === this.studentInfo.group &&
            item.user?.id !== currentUser.id &&
            !answeredPeerIds.includes(item.user?.id)
          );
        }

        await this.loadQuestions();
        await this.loadExistingAnswers();
        await this.checkExistingAnswer();

      } catch (error) {
        this.error = `Error loading data: ${error.message}`;
        console.error("Error:", error);
      } finally {
        this.loading = false;
      }
    },

    async loadQuestions(retryCount = 3) {
      for (let i = 0; i < retryCount; i++) {
        try {
          const response = await axios.get("/api/questions-peer", {
            params: {
              tahun_ajaran: this.tahunAjaran,
              nama_proyek: this.studentInfo.project || ""
            }
          });

          if (response.data && Array.isArray(response.data)) {
            this.questions = response.data;
            return;
          }
        } catch (error) {
          console.error(`Attempt ${i + 1} failed:`, error);
          if (i === retryCount - 1) throw error;
          await new Promise(resolve => setTimeout(resolve, 1000));
        }
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

    setScore(value) {
      this.score = value;
      this.saveTemporaryAnswer();
    },

    async submitAnswer() {
      if (!this.currentQuestion || !this.selectedMember) return;

      const user_id = await this.fetchUserIdByNim(this.studentInfo.nim);
      if (!user_id) {
        alert("User tidak ditemukan");
        return;
      }

      try {
        const response = await axios.post("/api/save-answer-peer", {
          user_id: user_id,
          peer_id: this.selectedMember,
          question_id: this.currentQuestion.id,
          answer: this.answer,
          score: this.score,
          status: "submitted",
        });

        if (response.data.success) {
          this.nextQuestion();
        } else {
          alert("Gagal menyimpan jawaban: " + response.data.message);
        }
      } catch (error) {
        console.error("Error submitting answer:", error);
        alert("Gagal menyimpan jawaban. Silakan coba lagi.");
      }
    },

    nextQuestion() {
      this.saveTemporaryAnswer();
      if (this.currentQuestionIndex < this.questions.length - 1) {
        this.currentQuestionIndex++;
        this.loadTemporaryAnswer();
        this.loadExistingAnswers();
      }
    },

    prevQuestion() {
      this.saveTemporaryAnswer();
      if (this.currentQuestionIndex > 0) {
        this.currentQuestionIndex--;
        this.loadTemporaryAnswer();
        this.loadExistingAnswers();
      }
    },

    async checkExistingAnswer() {
      if (!this.selectedMember || !this.currentQuestion) return;

      try {
        const response = await axios.get("/api/existing-peer-answers", {
          params: {
            user_id: await this.fetchUserIdByNim(this.studentInfo.nim),
            peer_id: this.selectedMember,
            question_id: this.currentQuestion.id,
          },
        });

        console.log('Check Existing Answer Response:', response.data);

        if (response.data && response.data.length > 0) {
          const existingAnswer = response.data[0];
          this.answer = existingAnswer.answer || "";
          this.score = existingAnswer.score || 0;

          this.$nextTick(() => {
            this.answer = this.answer;
            this.score = this.score;
          });
        }
      } catch (error) {
        console.error("Error checking existing answer:", error);
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

      this.temporaryAnswers[this.selectedMember][this.currentQuestion.id] = {
        answer: this.answer,
        score: this.score,
      };
    },

    showSubmitConfirmation() {
      if (this.allQuestionsAnswered) {
        this.showConfirmModal = true;
      } else {
        alert("Mohon jawab semua pertanyaan terlebih dahulu");
      }
    },
    async loadExistingAnswers() {
      if (!this.selectedMember) return;

      try {
        const user_id = await this.fetchUserIdByNim(this.studentInfo.nim);
        if (!user_id) return;

        const response = await axios.get("/api/existing-peer-answers", {
          params: {
            user_id: user_id,
            peer_id: this.selectedMember,
            question_id: this.currentQuestion.id
          }
        });

        console.log('Existing Answer Response:', response.data);

        if (response.data && response.data.length > 0) {
          const existingAnswer = response.data[0];

          this.answer = existingAnswer.answer || "";
          this.score = existingAnswer.score || 0;

          if (!this.temporaryAnswers[this.selectedMember]) {
            this.temporaryAnswers[this.selectedMember] = {};
          }
          this.temporaryAnswers[this.selectedMember][this.currentQuestion.id] = {
            answer: this.answer,
            score: this.score
          };

          this.$nextTick(() => {
            this.answer = this.answer;
            this.score = this.score;
          });
        }
      } catch (error) {
        console.error("Error loading existing answers:", error);
      }
    },

    async submitAllAnswers() {
      try {
        this.isSubmitting = true;

        const user_id = await this.fetchUserIdByNim(this.studentInfo.nim);
        if (!user_id) {
          alert("User tidak ditemukan");
          return;
        }

        if (!this.temporaryAnswers[this.selectedMember]) {
          console.error("No temporary answers found");
          return;
        }

        const answers = this.questions.map(question => {
          const savedAnswer = this.temporaryAnswers[this.selectedMember][question.id];
          return {
            user_id: user_id,
            peer_id: this.selectedMember,
            question_id: question.id,
            answer: savedAnswer.answer,
            score: savedAnswer.score,
            status: "submitted",
          };
        });

        await Promise.all(
          answers.map(answer => axios.post("/api/save-answer-peer", answer))
        );

        window.location.href = "/mahasiswa/assessment/peer";
      } catch (error) {
        console.error("Error submitting answers:", error);
        alert("Gagal menyimpan jawaban. Silakan coba lagi.");
      } finally {
        this.isSubmitting = false;
      }
    },

    resetForm() {
      this.temporaryAnswers = {};
      this.answer = "";
      this.score = 0;
      this.currentQuestionIndex = 0;
      this.initializeData();
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
                <p>
                  <strong>Tanggal Pengisian:</strong> {{ studentInfo.date }}
                </p>
              </div>
            </div>

            <div class="mb-6" v-if="currentQuestionIndex === 0">
              <label for="select-member" class="block text-sm font-medium text-gray-700 mb-2">
                Pilih Teman Kelompok untuk Dinilai
              </label>
              <select id="select-member" v-model="selectedMember"
                class="block w-full rounded-md border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                <option disabled value="">-- Pilih Teman Kelompok --</option>
                <option v-for="member in kelompok" :key="member.user.id" :value="member.user.id">
                  {{ member.user.name }}
                </option>
              </select>
            </div>

            <div v-if="error" class="text-center py-8 text-red-600">
              <p>{{ error }}</p>
              <button @click="initializeData" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                Coba Lagi
              </button>
            </div>

            <!-- Only show questions if a member is selected -->
            <div v-else-if="selectedMember && currentQuestion" class="space-y-6">
              <!-- Question display section -->
              <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold text-lg mb-4">
                  Pertanyaan {{ currentQuestionIndex + 1 }} dari
                  {{ questions.length }}
                </h3>
                <p class="mb-2">
                  <strong>Aspek:</strong> {{ currentQuestion.aspek }}
                </p>
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
                <p class="text-gray-700 mb-4">
                  {{ currentQuestion.pertanyaan }}
                </p>
                <div class="score-container mt-4">
                  <div class="slider-container">
                    <div class="track"></div>
                    <div class="points">
                      <div class="point" v-for="scale in [1, 2, 3, 4, 5]" :key="scale" @click="setScore(scale)"
                        :class="{ active: score === scale }"></div>
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
                  <label for="answer" class="block text-sm font-medium text-gray-700 mb-2">
                    Jawaban Anda:
                  </label>
                  <textarea id="answer" v-model="answer" rows="4"
                    class="block w-full rounded-md border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Berikan alasan..." required></textarea>
                </div>

                <div class="flex justify-between items-center pt-4">
                  <button type="button" @click="prevQuestion" :disabled="currentQuestionIndex === 0"
                    class="px-4 py-2 bg-yellow-400 text-white rounded hover:bg-blue-600">
                    Previous
                  </button>

                  <button type="submit" class="px-4 py-2 bg-blue-400 text-white rounded hover:bg-blue-600">
                    Save Answer
                  </button>

                  <button v-if="currentQuestionIndex === questions.length - 1" type="button"
                    @click="showSubmitConfirmation" :disabled="isSubmitting"
                    class="px-4 py-2 bg-green-500 text-white rounded hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ isSubmitting ? "Mengirim..." : "Send" }}
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
              <p>Tidak ada pertanyaan tersedia.</p>
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
