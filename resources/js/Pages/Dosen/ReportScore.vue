<script>
import axios from "axios";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

export default {
  props: {
    tahunAjaran: {
      type: String,
      required: true,
    },
    namaProyek: {
      type: String,
      required: true,
    },
    kelompok: {
      type: String,
      required: true,
    },
  },
  components: {
    Sidebar,
    Navbar,
    Card,
    Breadcrumb,
  },
  data() {
    return {
      breadcrumbs: [{ text: "Laporan", href: "/dosen/laporan" }],
      userAnalysis: {},
      loading: false,
      error: null,
      questions: {},
      peerQuestions: {},
    };
  },
  methods: {
    fetchKelompokAnalysis() {
      this.loading = true;
      this.error = null;

      if (this.tahunAjaran && this.namaProyek && this.kelompok) {
        axios
          .get("/api/report/kelompok/answers", {
            params: {
              tahun_ajaran: this.tahunAjaran,
              nama_proyek: this.namaProyek,
              kelompok: this.kelompok,
            },
          })
          .then((response) => {
            this.userAnalysis = response.data;
            this.loading = false;
          })
          .catch((error) => {
            console.error("Gagal mengambil analisis:", error);
            this.error = "Gagal memuat data";
            this.loading = false;
          });
      } else {
        this.error = "Tahun Ajaran, Nama Proyek, atau Kelompok tidak valid";
        this.loading = false;
      }
    },
    fetchQuestions() {
      axios
        .get("/api/questions")
        .then((response) => {
          this.questions = response.data;
        })
        .catch((error) => {
          console.error("Gagal memuat pertanyaan:", error);
        });
    },
    fetchPeerQuestions() {
      console.log("Fetching Peer Questions with Parameters:", {
        tahunAjaran: this.tahunAjaran,
        namaProyek: this.namaProyek,
      });
      axios
        .get("/api/questions-peer", {
          params: {
            tahun_ajaran: this.tahunAjaran,
            nama_proyek: this.namaProyek,
          },
        })
        .then((response) => {
          console.log("Peer Questions Response:", response.data);
          this.peerQuestions = response.data.reduce((acc, question) => {
            acc[question.id] = question.pertanyaan;
            return acc;
          }, {});
        })
        .catch((error) => {
          console.error("Gagal memuat pertanyaan peer:", error);
          console.log("Error Parameters:", {
            tahunAjaran: this.tahunAjaran,
            namaProyek: this.namaProyek,
          });
        });
    },
    getQuestionText(questionId) {
      return this.questions[questionId] || "Pertanyaan tidak ditemukan";
    },
    getPeerQuestionText(questionId) {
      if (!this.peerQuestions || !this.peerQuestions[questionId]) {
        console.warn(`Pertanyaan dengan ID ${questionId} tidak ditemukan`);
        return "Pertanyaan tidak ditemukan";
      }
      return this.peerQuestions[questionId];
    },
    calculateTotalAverage(userId) {
      const userData = this.userAnalysis[userId];
      if (
        !userData ||
        !userData.evaluated_by_peers ||
        !userData.evaluated_by_peers.length
      )
        return "N/A";

      const totalScores = userData.evaluated_by_peers.map(
        (group) => group.total_score || 0
      );
      const averageTotal =
        totalScores.reduce((sum, score) => sum + score, 0) / totalScores.length;

      return averageTotal.toFixed(2);
    },
    groupPeerEvaluations(evaluatedByPeers) {
      if (!evaluatedByPeers || !Array.isArray(evaluatedByPeers)) {
        return {};
      }

      return evaluatedByPeers.map((group) => {
        const names = Object.keys(group.evaluated_by || {}).map(
          (userId) => group.evaluated_by[userId].name
        );

        const answers = Object.values(group.evaluated_by || {}).flatMap(
          (evaluator) => evaluator.answers
        );

        return {
          aspek: group.aspek,
          kriteria: group.kriteria,
          names: names,
          total_score: group.total_score,
          answers: answers,
        };
      });
    },
  },
  mounted() {
    if (this.tahunAjaran && this.namaProyek && this.kelompok) {
      this.fetchKelompokAnalysis();
      this.fetchQuestions();
      this.fetchPeerQuestions();
    } else {
      this.error = "Tahun Ajaran, Nama Proyek, atau Kelompok tidak valid";
    }
  },
};
</script>


<template>
  <div class="flex min-h-screen">
    <Sidebar role="dosen" />
    <div class="flex-1">
      <Navbar userName="Dosen" />
      <main class="p-6">
        <div class="mb-4">
          <Breadcrumb :items="breadcrumbs" />
        </div>
        <Card title="Detail Kelompok">
          <div v-if="tahunAjaran && namaProyek && kelompok">
            <p><strong>Tahun Ajaran:</strong> {{ tahunAjaran }}</p>
            <p><strong>Nama Proyek:</strong> {{ namaProyek }}</p>
            <p><strong>Kelompok:</strong> {{ kelompok }}</p>
          </div>
          <div v-else>
            <p>Tidak ada data yang tersedia</p>
          </div>
        </Card>
        <div v-if="loading" class="text-center mt-4">Memuat...</div>
        <div v-else-if="error" class="text-red-500 mt-4">{{ error }}</div>
        <div v-else>
          <template v-for="(userData, userId) in userAnalysis" :key="userId">
            <Card :title="`Analisis Jawaban - ${userData.name}`" class="mt-4">
              <!-- Self Assessment Section -->
              <div
                v-if="
                  userData.self_assessment && userData.self_assessment.length
                "
              >
                <table class="w-full border-collapse">
                  <thead>
                    <tr class="bg-gray-200">
                      <th class="border p-2">Aspek</th>
                      <th class="border p-2">Kriteria</th>
                      <th class="border p-2">Skor Total</th>
                      <th class="border p-2">Detail Pertanyaan</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="(aspek, index) in userData.self_assessment"
                      :key="index"
                    >
                      <td class="border p-2">{{ aspek.aspek }}</td>
                      <td class="border p-2">{{ aspek.kriteria }}</td>
                      <td class="border p-2">
                        {{
                          aspek.total_score
                            ? aspek.total_score.toFixed(2)
                            : "N/A"
                        }}
                      </td>
                      <td class="border p-2">
                        <table class="w-full">
                          <thead>
                            <tr>
                              <th>Pertanyaan</th>
                              <th>Skor</th>
                              <th>Jawaban</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr
                              v-for="pertanyaan in aspek.questions"
                              :key="pertanyaan.question_id"
                            >
                              <td>{{ pertanyaan.pertanyaan }}</td>
                              <td>{{ pertanyaan.score || "N/A" }}</td>
                              <td>{{ pertanyaan.answer || "-" }}</td>
                            </tr>
                          </tbody>
                        </table>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <p v-else class="text-center">Tidak ada data analisis</p>

              <!-- Peer Evaluation Section -->
              <div class="mt-4">
                <div class="flex items-center mb-2">
                  <h3 class="font-bold mr-2">Evaluasi dari Peer</h3>
                  <span class="text-gray-600"
                    >(Total Average: {{ calculateTotalAverage(userId) }})</span
                  >
                </div>
                <div
                  v-if="
                    userData.evaluated_by_peers &&
                    userData.evaluated_by_peers.length
                  "
                >
                  <table class="w-full border-collapse">
                    <thead>
                      <tr class="bg-gray-200">
                        <th class="border p-2">Aspek</th>
                        <th class="border p-2">Kriteria</th>
                        <th class="border p-2">Skor Total</th>
                        <th class="border p-2">Dievaluasi Oleh</th>
                        <th class="border p-2">Detail Pertanyaan</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="(peerGroup, index) in groupPeerEvaluations(
                          userData.evaluated_by_peers
                        )"
                        :key="index"
                      >
                        <td class="border p-2">{{ peerGroup.aspek }}</td>
                        <td class="border p-2">{{ peerGroup.kriteria }}</td>
                        <td class="border p-2">
                          {{
                            peerGroup.total_score
                              ? peerGroup.total_score.toFixed(2)
                              : "N/A"
                          }}
                        </td>
                        <td class="border p-2">
                          {{ peerGroup.names.join(", ") }}
                        </td>
                        <td class="border p-2">
                          <table class="w-full">
                            <thead>
                              <tr>
                                <th>Pertanyaan</th>
                                <th>Skor</th>
                                <th>Jawaban</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr
                                v-for="pertanyaan in peerGroup.answers"
                                :key="pertanyaan.question_id"
                              >
                                <td>
                                  {{
                                    getPeerQuestionText(pertanyaan.question_id)
                                  }}
                                </td>
                                <td>{{ pertanyaan.score || "N/A" }}</td>
                                <td>{{ pertanyaan.answer || "-" }}</td>
                              </tr>
                            </tbody>
                          </table>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <p v-else class="text-center">Tidak ada data evaluasi peer</p>
              </div>
            </Card>
          </template>
        </div>
      </main>
    </div>
  </div>
</template>

<style>
.text-center {
  text-align: center;
}
.text-red-500 {
  color: #f56565;
}
</style>