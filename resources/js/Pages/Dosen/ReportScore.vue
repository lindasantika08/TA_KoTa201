<script>
import axios from "axios";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import ApexChart from "apexcharts";
import VueApexCharts from "vue3-apexcharts";
import Swal from "sweetalert2";

export default {
    props: {
        batch_year: {
            type: String,
            required: true,
        },
        project_name: {
            type: String,
            required: true,
        },
        kelompok: {
            type: String,
            required: true,
        },
        initialData: {
            type: Object,
            required: true,
        },
    },
    components: {
        Sidebar,
        Navbar,
        Card,
        Breadcrumb,
        ApexChart: VueApexCharts,
    },
    data() {
        return {
            breadcrumbs: [{ text: "Laporan", href: "/dosen/laporan" }],
            userAnalysis: {},
            loading: false,
            error: null,
            questions: {},
            peerQuestions: {},
            selectedUserId: null,
            successMessage: null,
            isLoading: false,
        };
    },
    computed: {
        userIds() {
            return Object.keys(this.userAnalysis);
        },
        selectedUserData() {
            return this.selectedUserId
                ? this.userAnalysis[this.selectedUserId]
                : null;
        },
    },
    watch: {
        selectedUserData: {
            handler() {
                this.setupFinalScoresSelf();
                this.setupFinalScoresPeer();
            },
            deep: true,
        },
    },
    mounted() {
        this.setupFinalScoresSelf();
        this.setupFinalScoresPeer();

        if (this.batch_year && this.project_name && this.kelompok) {
            this.fetchPeerQuestions();
            this.fetchKelompokAnalysis();
        } else {
            this.error = "Tahun Ajaran, Nama Proyek, atau Kelompok tidak valid";
        }
    },
    methods: {
        async saveAnswerSelf() {
            if (this.isLoading) {
                Swal.fire({
                    icon: "warning",
                    title: "Mohon Tunggu",
                    text: "Permintaan sedang diproses, mohon tunggu...",
                    showConfirmButton: false,
                    timer: 2000,
                });
                return;
            }

            this.isLoading = true;

            try {
                if (!this.selectedUserId || !this.selectedUserData) {
                    Swal.fire({
                        icon: "warning",
                        title: "Pilih Mahasiswa",
                        text: "Mohon pilih mahasiswa terlebih dahulu",
                        confirmButtonColor: "#3085d6",
                    });
                    this.isLoading = false;
                    return;
                }

                const mahasiswaId = this.selectedUserData.mahasiswa_id;
                const answers = [];

                this.selectedUserData.self_assessment.forEach((aspek) => {
                    if (!aspek.questions || !Array.isArray(aspek.questions)) {
                        return;
                    }

                    aspek.questions.forEach((pertanyaan) => {
                        // Determine the final score based on user selection
                        let finalScore;

                        if (pertanyaan._userSelected === "score_SLA") {
                            finalScore = pertanyaan.score_SLA;
                        } else if (pertanyaan._userSelected === "score") {
                            finalScore = pertanyaan.score;
                        } else {
                            // If no explicit selection, use final_score or default logic
                            finalScore = pertanyaan.final_score;

                            if (!finalScore) {
                                if (pertanyaan.score == pertanyaan.score_SLA) {
                                    // If scores are equal, default to score
                                    finalScore = pertanyaan.score;
                                } else {
                                    // If scores are different, use score as default
                                    finalScore = pertanyaan.score;
                                }
                            }
                        }

                        if (finalScore) {
                            // Get the typeCriteria_id from the aspek data
                            let typeCriteriaId = aspek.typeCriteria_id;

                            // If not available, try to get from question data or use criteria name
                            if (!typeCriteriaId) {
                                typeCriteriaId =
                                    pertanyaan.typeCriteria_id ||
                                    aspek.kriteria;
                            }

                            console.log(
                                `Preparing to save - Question ID: ${pertanyaan.question_id}, _userSelected: ${pertanyaan._userSelected}, finalScore: ${finalScore}, SKOR: ${pertanyaan.score}, SKOR SLA: ${pertanyaan.score_SLA}, mahasiswa_id: ${mahasiswaId}`
                            );

                            answers.push({
                                mahasiswa_id: mahasiswaId,
                                typeCriteria_id: typeCriteriaId,
                                question_id: pertanyaan.question_id,
                                final_score_self: parseInt(finalScore),
                                selected_score_type:
                                    pertanyaan._userSelected || "score", // Tambahkan informasi pilihan user
                            });

                            // Update the final_score_self value for this question directly
                            pertanyaan.final_score_self = finalScore;
                            // If there's a report object, update it too
                            if (pertanyaan.report) {
                                pertanyaan.report.final_score_self = finalScore;
                            }
                        }
                    });
                });

                if (answers.length === 0) {
                    Swal.fire({
                        icon: "warning",
                        title: "Tidak Ada Data",
                        text: "Mohon pilih minimal satu skor untuk disimpan",
                        confirmButtonColor: "#3085d6",
                    });
                    this.isLoading = false;
                    return;
                }

                console.log(
                    "Final answers array being sent to backend:",
                    answers
                );

                // Show loading alert
                Swal.fire({
                    title: "Menyimpan Data...",
                    text: "Mohon tunggu sebentar",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    },
                });

                const response = await axios.post(
                    "/api/report/save-final-scores-self",
                    { answers }
                );

                if (response.data.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Berhasil Disimpan!",
                        text:
                            response.data.message ||
                            "Jawaban Self Assessment berhasil disimpan",
                        confirmButtonColor: "#28a745",
                        timer: 3000,
                        timerProgressBar: true,
                    });
                    // Refresh data setelah berhasil simpan
                    await this.fetchKelompokAnalysis();
                    // Recalculate total scores after save
                    this.setupFinalScoresSelf();
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Gagal Menyimpan",
                        text:
                            response.data.message ||
                            "Terjadi kesalahan saat menyimpan jawaban",
                        confirmButtonColor: "#dc3545",
                    });
                }
            } catch (error) {
                console.error("Error saving answers:", error);

                let errorMessage = "Terjadi kesalahan yang tidak diketahui";

                if (error.response) {
                    errorMessage =
                        "Terjadi kesalahan pada server: " +
                        (error.response.data.message ||
                            error.response.statusText);
                } else if (error.request) {
                    errorMessage =
                        "Tidak dapat terhubung ke server. Periksa koneksi internet Anda.";
                } else {
                    errorMessage = "Terjadi kesalahan: " + error.message;
                }

                Swal.fire({
                    icon: "error",
                    title: "Terjadi Kesalahan",
                    text: errorMessage,
                    confirmButtonColor: "#dc3545",
                });
            } finally {
                this.isLoading = false;
            }
        },

        setupFinalScoresSelf() {
            if (
                !this.selectedUserData ||
                !this.selectedUserData.self_assessment
            ) {
                return;
            }

            console.log(
                "Setting up final scores for user:",
                this.selectedUserData
            );

            this.selectedUserData.self_assessment.forEach((aspek) => {
                if (!aspek.questions || !Array.isArray(aspek.questions)) {
                    return;
                }

                aspek.questions.forEach((pertanyaan) => {
                    // Skip if user has already made an explicit selection
                    if (
                        pertanyaan._userSelected &&
                        pertanyaan._userExplicitlySelected
                    ) {
                        console.log(
                            `Skipping question ${pertanyaan.question_id} - user already selected`
                        );
                        return; // Don't override user's choice
                    }

                    // Tentukan pilihan berdasarkan data yang tersimpan
                    let savedScore = null;
                    let dataSource = "none";

                    // Cek data dari report object terlebih dahulu (prioritas utama)
                    if (
                        pertanyaan.report &&
                        typeof pertanyaan.report.final_score_self !==
                            "undefined" &&
                        pertanyaan.report.final_score_self !== null
                    ) {
                        savedScore = pertanyaan.report.final_score_self;
                        dataSource = "report";
                    }
                    // Fallback ke final_score_self jika tersedia
                    else if (
                        typeof pertanyaan.final_score_self !== "undefined" &&
                        pertanyaan.final_score_self !== null
                    ) {
                        savedScore = pertanyaan.final_score_self;
                        dataSource = "fallback";
                    }

                    // Logika sederhana untuk menentukan pilihan
                    if (savedScore !== null) {
                        pertanyaan.final_score = savedScore;

                        // Jika saved score sama dengan SKOR SLA DAN berbeda dengan SKOR biasa
                        // maka pilih SKOR SLA
                        if (
                            savedScore == pertanyaan.score_SLA &&
                            savedScore != pertanyaan.score
                        ) {
                            pertanyaan._userSelected = "score_SLA";
                        } else {
                            // Untuk semua kasus lainnya, pilih SKOR
                            // Termasuk:
                            // - saved score sama dengan SKOR biasa
                            // - saved score sama dengan keduanya
                            // - saved score tidak sama dengan keduanya
                            pertanyaan._userSelected = "score";
                        }

                        console.log(
                            `Loading from ${dataSource} - Question ID: ${pertanyaan.question_id}, Saved Score: ${savedScore}, SKOR: ${pertanyaan.score}, SKOR SLA: ${pertanyaan.score_SLA}, Selected: ${pertanyaan._userSelected}`
                        );
                    } else {
                        // Jika tidak ada data tersimpan di tabel report, default ke SKOR
                        if (pertanyaan.score) {
                            pertanyaan.final_score = pertanyaan.score;
                            pertanyaan._userSelected = "score";
                            console.log(
                                `No saved data - Question ID: ${pertanyaan.question_id}, Defaulting to SKOR: ${pertanyaan.score}, Selected: ${pertanyaan._userSelected}`
                            );
                        }
                    }
                });

                // Recalculate total score for this aspek after setup
                this.recalculateAspekTotalScore(aspek);
            });
        },

        async saveAnswerPeer() {
            if (this.isLoading) {
                Swal.fire({
                    icon: "warning",
                    title: "Mohon Tunggu",
                    text: "Permintaan sedang diproses, mohon tunggu...",
                    showConfirmButton: false,
                    timer: 2000,
                });
                return;
            }

            this.isLoading = true;

            try {
                const mahasiswaId = this.selectedUserData.mahasiswa_id;
                const answersPeer = [];

                // Process data from evaluated_by_peers which has the structure we need
                if (
                    this.selectedUserData.evaluated_by_peers &&
                    Array.isArray(this.selectedUserData.evaluated_by_peers)
                ) {
                    // Loop through each peer evaluation group
                    this.selectedUserData.evaluated_by_peers.forEach(
                        (group) => {
                            if (!group.evaluated_by) return;

                            // Get all evaluators for this group
                            // Here we store both the key (peer_id) and the value (evaluator data)
                            Object.entries(group.evaluated_by).forEach(
                                ([peerId, evaluator]) => {
                                    // Handle nested evaluator structure if present
                                    const actualEvaluators =
                                        evaluator.evaluated_by
                                            ? Object.entries(
                                                  evaluator.evaluated_by
                                              )
                                            : [[peerId, evaluator]];

                                    // Process each actual evaluator with its id
                                    actualEvaluators.forEach(
                                        ([actualPeerId, actualEvaluator]) => {
                                            if (
                                                !actualEvaluator.answers ||
                                                !Array.isArray(
                                                    actualEvaluator.answers
                                                )
                                            )
                                                return;

                                            // Ganti dengan kode ini:
                                            actualEvaluator.answers.forEach(
                                                (answer) => {
                                                    // Gunakan final_peer berdasarkan pilihan user
                                                    let finalPeer =
                                                        answer.final_peer;

                                                    // Only add to save list if we have a valid score
                                                    if (finalPeer) {
                                                        // Variable untuk menyimpan kriteria_id
                                                        let criteriaId = "";

                                                        // Cari question_id ini di peer_assessment untuk mendapatkan kriteria_id
                                                        if (
                                                            this
                                                                .selectedUserData
                                                                .peer_assessment &&
                                                            Array.isArray(
                                                                this
                                                                    .selectedUserData
                                                                    .peer_assessment
                                                            )
                                                        ) {
                                                            for (const peerAspek of this
                                                                .selectedUserData
                                                                .peer_assessment) {
                                                                // Cek jika aspek ini memiliki questions
                                                                if (
                                                                    peerAspek.questions &&
                                                                    Array.isArray(
                                                                        peerAspek.questions
                                                                    )
                                                                ) {
                                                                    // Cari question yang matching dengan question_id
                                                                    const matchingQuestion =
                                                                        peerAspek.questions.find(
                                                                            (
                                                                                q
                                                                            ) =>
                                                                                q.question_id ===
                                                                                answer.question_id
                                                                        );
                                                                    if (
                                                                        matchingQuestion
                                                                    ) {
                                                                        // Jika ditemukan, ambil kriteria_id dari aspek
                                                                        if (
                                                                            peerAspek.kriteria_id
                                                                        ) {
                                                                            criteriaId =
                                                                                peerAspek.kriteria_id;
                                                                        } else if (
                                                                            peerAspek.aspek
                                                                        ) {
                                                                            // Jika tidak ada kriteria_id, gunakan aspek sebagai fallback
                                                                            criteriaId =
                                                                                peerAspek.aspek;
                                                                        }
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                        }

                                                        // Hanya tambahkan jika ada nilai finalPeer yang valid
                                                        if (
                                                            finalPeer !==
                                                                null &&
                                                            finalPeer !==
                                                                undefined
                                                        ) {
                                                            // Gunakan peer ID dari structure evaluated_by

                                                            answersPeer.push({
                                                                mahasiswa_id:
                                                                    mahasiswaId,
                                                                typeCriteria_id:
                                                                    criteriaId ||
                                                                    "unknown_criteria",
                                                                question_id:
                                                                    answer.question_id,
                                                                peer_id:
                                                                    actualPeerId,
                                                                final_score_peer:
                                                                    Number(
                                                                        finalPeer
                                                                    ),
                                                            });
                                                        }
                                                    }
                                                }
                                            );
                                        }
                                    );
                                }
                            );
                        }
                    );
                }

                if (answersPeer.length === 0) {
                    Swal.fire({
                        icon: "warning",
                        title: "Tidak Ada Data",
                        text: "Mohon pilih minimal satu skor untuk disimpan",
                        confirmButtonColor: "#3085d6",
                    });
                    this.isLoading = false;
                    return;
                }

                // Periksa jika ada data yang tidak memiliki typeCriteria_id atau peer_id
                const incompletePeers = answersPeer.filter(
                    (a) => !a.typeCriteria_id || !a.peer_id
                );
                if (incompletePeers.length > 0) {
                    console.warn(
                        "Ada data dengan field wajib yang kosong:",
                        incompletePeers
                    );

                    // Tambahkan nilai default jika diperlukan
                    for (const item of answersPeer) {
                        // Pastikan semua field wajib memiliki nilai
                        if (!item.typeCriteria_id)
                            item.typeCriteria_id = "default_kriteria";
                        if (!item.peer_id) item.peer_id = "default_peer";
                    }
                }

                console.log(
                    "Final answersPeer array being sent to backend:",
                    answersPeer
                );

                // Show loading alert
                Swal.fire({
                    title: "Menyimpan Data...",
                    text: "Mohon tunggu sebentar",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    },
                });

                const response = await axios.post(
                    "/api/report/save-final-scores-peer",
                    { answersPeer }
                );

                if (response.data.success) {
                    this.successMessage =
                        response.data.message || "Jawaban berhasil disimpan";
                    Swal.fire({
                        icon: "success",
                        title: "Berhasil Disimpan!",
                        text:
                            response.data.message ||
                            "Jawaban Peer Assessment berhasil disimpan",
                        confirmButtonColor: "#28a745",
                        timer: 3000,
                        timerProgressBar: true,
                    });
                    // Refresh data setelah berhasil simpan
                    await this.fetchKelompokAnalysis();
                    // Recalculate peer scores after save
                    this.setupFinalScoresPeer();
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Gagal Menyimpan",
                        text:
                            response.data.message ||
                            "Terjadi kesalahan saat menyimpan jawaban",
                        confirmButtonColor: "#dc3545",
                    });
                }
            } catch (error) {
                console.error("Error saving answers:", error);

                let errorMessage = "Terjadi kesalahan yang tidak diketahui";

                if (error.response) {
                    errorMessage =
                        "Terjadi kesalahan pada server: " +
                        (error.response.data.message ||
                            error.response.statusText);
                } else if (error.request) {
                    errorMessage =
                        "Tidak dapat terhubung ke server. Periksa koneksi internet Anda.";
                } else {
                    errorMessage = "Terjadi kesalahan: " + error.message;
                }

                Swal.fire({
                    icon: "error",
                    title: "Terjadi Kesalahan",
                    text: errorMessage,
                    confirmButtonColor: "#dc3545",
                });
            } finally {
                this.isLoading = false;
            }
        },

        setupFinalScoresPeer() {
            if (
                !this.selectedUserData ||
                !this.selectedUserData.evaluated_by_peers
            ) {
                return;
            }

            console.log("Setting up final scores for peer assessment");

            this.selectedUserData.evaluated_by_peers.forEach((group) => {
                if (!group.evaluated_by) return;
                Object.values(group.evaluated_by).forEach((evaluator) => {
                    const evaluators = evaluator.evaluated_by
                        ? Object.values(evaluator.evaluated_by)
                        : [evaluator];
                    evaluators.forEach((actualEvaluator) => {
                        if (!actualEvaluator.answers) return;
                        actualEvaluator.answers.forEach((answer) => {
                            // Skip if user has already made an explicit selection
                            if (
                                answer._userSelected &&
                                answer._userExplicitlySelected
                            ) {
                                console.log(
                                    `Skipping peer answer ${answer.question_id}_${answer.evaluator_name} - user already selected`
                                );
                                return;
                            }

                            // Tentukan pilihan berdasarkan data yang tersimpan
                            let savedScore = null;
                            let dataSource = "none";

                            // Cek data dari report object terlebih dahulu (prioritas utama)
                            if (
                                answer.report &&
                                typeof answer.report.final_score_peer !==
                                    "undefined" &&
                                answer.report.final_score_peer !== null
                            ) {
                                savedScore = answer.report.final_score_peer;
                                dataSource = "report";
                            }
                            // Fallback ke final_score_peer jika tersedia
                            else if (
                                typeof answer.final_score_peer !==
                                    "undefined" &&
                                answer.final_score_peer !== null
                            ) {
                                savedScore = answer.final_score_peer;
                                dataSource = "fallback";
                            }

                            // Logika sederhana untuk menentukan pilihan
                            if (savedScore !== null) {
                                answer.final_peer = savedScore;

                                // Jika saved score sama dengan SKOR SLA DAN berbeda dengan SKOR biasa
                                // maka pilih SKOR SLA
                                if (
                                    savedScore == answer.score_SLA &&
                                    savedScore != answer.score
                                ) {
                                    answer._userSelected = "score_SLA";
                                } else {
                                    // Untuk semua kasus lainnya, pilih SKOR
                                    answer._userSelected = "score";
                                }

                                console.log(
                                    `Loading peer from ${dataSource} - Question ID: ${answer.question_id}, Evaluator: ${answer.evaluator_name}, Saved Score: ${savedScore}, SKOR: ${answer.score}, SKOR SLA: ${answer.score_SLA}, Selected: ${answer._userSelected}`
                                );
                            } else {
                                // Jika tidak ada data tersimpan di tabel report, default ke SKOR
                                if (answer.score) {
                                    answer.final_peer = answer.score;
                                    answer._userSelected = "score";
                                    console.log(
                                        `No saved peer data - Question ID: ${answer.question_id}, Evaluator: ${answer.evaluator_name}, Defaulting to SKOR: ${answer.score}, Selected: ${answer._userSelected}`
                                    );
                                }
                            }
                        });
                    });
                });

                // Recalculate total score for this peer group after setup
                this.recalculatePeerGroupTotalScore(group);
            });

            // Force update untuk memastikan perubahan terlihat
            this.$forceUpdate();
        },

        shouldHighlightPeerScore(answer) {
            return answer._userSelected === "score";
        },

        shouldHighlightPeerScoreSLA(answer) {
            return answer._userSelected === "score_SLA";
        },

        onPeerScoreRadioChange(answer, scoreType, peerGroup) {
            // Mark that user explicitly selected this option
            answer._userSelected = scoreType;
            // Add a flag to indicate this was explicitly set by user
            answer._userExplicitlySelected = true;

            // Update final_peer based on selection
            if (scoreType === "score") {
                answer.final_peer = answer.score;
            } else if (scoreType === "score_SLA") {
                answer.final_peer = answer.score_SLA;
            }

            // Get the actual selected value based on scoreType
            const selectedValue =
                scoreType === "score" ? answer.score : answer.score_SLA;

            console.log(
                `Peer Score Selection - Question ID: ${answer.question_id}, Evaluator: ${answer.evaluator_name}, Selected: ${scoreType}, Value: ${selectedValue}, SKOR: ${answer.score}, SKOR SLA: ${answer.score_SLA}, final_peer: ${answer.final_peer}`
            );

            // Force reactivity update untuk total score
            this.$forceUpdate();
        },

        recalculatePeerGroupTotalScore(peerGroup) {
            if (!peerGroup.answers || !Array.isArray(peerGroup.answers)) {
                return;
            }

            // Hitung total score berdasarkan final_peer dari setiap answer
            let totalScore = 0;
            let answerCount = 0;

            peerGroup.answers.forEach((answer) => {
                if (
                    answer.final_peer !== null &&
                    answer.final_peer !== undefined
                ) {
                    totalScore += answer.final_peer;
                    answerCount++;
                }
            });

            // Hitung rata-rata
            if (answerCount > 0) {
                peerGroup.total_score = totalScore / answerCount;
            } else {
                peerGroup.total_score = 0;
            }

            console.log(
                `Recalculated peer total score for group ${peerGroup.aspek} - ${peerGroup.kriteria}: ${peerGroup.total_score} (based on ${answerCount} answers)`
            );
        },

        async fetchKelompokAnalysis() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get(
                    "/api/report/kelompok/answers",
                    {
                        params: {
                            batch_year: this.batch_year,
                            project_name: this.project_name,
                            kelompok: this.kelompok,
                        },
                    }
                );
                this.userAnalysis = response.data;
            } catch (error) {
                this.error = "Gagal memuat data";
            } finally {
                this.loading = false;
            }
        },

        async fetchPeerQuestions() {
            try {
                const response = await axios.get(
                    "/api/questions-peer-dosen-report",
                    {
                        params: {
                            batch_year: this.batch_year,
                            project_name: this.project_name,
                        },
                    }
                );

                this.peerQuestions = Object.fromEntries(
                    response.data.map((question) => [
                        question.id,
                        {
                            id: question.id,
                            pertanyaan: question.question,
                            type: question.type,
                            aspect: question.aspect,
                            criteria: question.criteria,
                        },
                    ])
                );
            } catch (error) {
                console.error("Gagal memuat pertanyaan peer:", error);
            }
        },
        getPeerQuestionText(questionId) {
            if (!questionId) {
                console.warn("Invalid questionId:", questionId);
                return "Pertanyaan tidak ditemukan";
            }

            const question = this.peerQuestions[questionId];
            if (!question) {
                console.warn("Question not found for ID:", questionId);
                return "Pertanyaan tidak ditemukan";
            }

            return (
                question.pertanyaan ||
                question.question ||
                "Pertanyaan tidak ditemukan"
            );
        },
        groupPeerEvaluations(evaluatedByPeers) {
            if (!evaluatedByPeers || !Array.isArray(evaluatedByPeers)) {
                return [];
            }

            return evaluatedByPeers.map((group) => {
                const evaluatorDetails = Object.values(
                    group.evaluated_by || {}
                ).flatMap((evaluator) => {
                    if (evaluator.evaluated_by) {
                        return Object.values(evaluator.evaluated_by);
                    }
                    return [evaluator];
                });

                const names = evaluatorDetails.map(
                    (evaluator) => evaluator.name
                );

                const processedAnswers = evaluatorDetails.flatMap((evaluator) =>
                    evaluator.answers.map((answer) => {
                        // Jangan spread, gunakan reference langsung agar perubahan tersimpan
                        answer.evaluator_name = evaluator.name;
                        answer.pertanyaan = this.getPeerQuestionText(
                            answer.question_id
                        );
                        return answer;
                    })
                );

                // Hitung ulang total score berdasarkan final_peer
                let totalScore = 0;
                let answerCount = 0;

                processedAnswers.forEach((answer) => {
                    if (
                        answer.final_peer !== null &&
                        answer.final_peer !== undefined
                    ) {
                        totalScore += answer.final_peer;
                        answerCount++;
                    }
                });

                const calculatedTotalScore =
                    answerCount > 0 ? totalScore / answerCount : 0;

                return {
                    aspek: group.aspek,
                    kriteria: group.kriteria,
                    names: names,
                    total_score: calculatedTotalScore, // Gunakan calculated total score
                    answers: processedAnswers,
                };
            });
        },
        calculateTotalAverage(userId) {
            const userData = this.userAnalysis[userId];
            if (
                !userData ||
                !userData.evaluated_by_peers ||
                !userData.evaluated_by_peers.length
            )
                return "N/A";

            // Gunakan groupPeerEvaluations untuk mendapatkan total score yang sudah dihitung ulang
            const groupedPeerEvaluations = this.groupPeerEvaluations(
                userData.evaluated_by_peers
            );
            const totalScores = groupedPeerEvaluations.map(
                (group) => group.total_score || 0
            );
            const averageTotal =
                totalScores.reduce((sum, score) => sum + score, 0) /
                totalScores.length;

            return averageTotal.toFixed(2);
        },
        calculateAverageSelfScores() {
            const userIds = Object.keys(this.userAnalysis);

            const allAspects = userIds.flatMap(
                (userId) => this.userAnalysis[userId].self_assessment || []
            );

            const aspectGroups = allAspects.reduce((acc, aspect) => {
                const key = `${aspect.aspek}-${aspect.kriteria}`;
                if (!acc[key]) {
                    acc[key] = {
                        aspek: aspect.aspek,
                        kriteria: aspect.kriteria,
                        scores: [],
                    };
                }
                if (aspect.total_score) {
                    acc[key].scores.push(aspect.total_score);
                }
                return acc;
            }, {});

            return Object.values(aspectGroups).map((group) => ({
                aspek: group.aspek,
                kriteria: group.kriteria,
                averageScore:
                    group.scores.length > 0
                        ? (
                              group.scores.reduce(
                                  (sum, score) => sum + score,
                                  0
                              ) / group.scores.length
                          ).toFixed(2)
                        : "0.00",
            }));
        },
        calculateAnalysisScores(userData) {
            if (!userData.self_assessment || !userData.evaluated_by_peers)
                return [];

            // Gunakan groupPeerEvaluations untuk mendapatkan total score yang sudah dihitung ulang
            const groupedPeerEvaluations = this.groupPeerEvaluations(
                userData.evaluated_by_peers
            );

            return userData.self_assessment.map((selfAspect) => {
                // Filter peer evaluations for matching aspect AND criteria
                const matchingPeerEvaluations = groupedPeerEvaluations.filter(
                    (peer) =>
                        peer.aspek === selfAspect.aspek &&
                        peer.kriteria === selfAspect.kriteria
                );

                // Calculate average peer score only from matching evaluations
                const averagePeerScore =
                    matchingPeerEvaluations.length > 0
                        ? matchingPeerEvaluations.reduce(
                              (sum, peer) => sum + (peer.total_score || 0),
                              0
                          ) / matchingPeerEvaluations.length
                        : 0;

                const selfScore = selfAspect.total_score || 0;
                const scoreDifference = selfScore - averagePeerScore;

                let status;
                if (scoreDifference > 0) status = "Over";
                else if (scoreDifference < 0) status = "Under";
                else status = "Match";

                return {
                    aspek: selfAspect.aspek,
                    kriteria: selfAspect.kriteria,
                    selfScore: selfScore.toFixed(2),
                    averagePeerScore: averagePeerScore.toFixed(2),
                    scoreDifference: scoreDifference.toFixed(2),
                    status: status,
                };
            });
        },
        preparePeerComparisonChartData(userData) {
            const analysisScores = this.calculateAnalysisScores(userData);

            return {
                series: [
                    {
                        name: "Skor Sendiri",
                        data: analysisScores.map((score) =>
                            parseFloat(score.selfScore)
                        ),
                    },
                    {
                        name: "Rata-rata Peer",
                        data: analysisScores.map((score) =>
                            parseFloat(score.averagePeerScore)
                        ),
                    },
                ],
                options: {
                    chart: {
                        type: "radar",
                        height: 400,
                        toolbar: {
                            show: false,
                        },
                        dropShadow: {
                            enabled: true,
                            blur: 1,
                            left: 1,
                            top: 1,
                        },
                    },
                    labels: analysisScores.map((score) => score.kriteria),
                    plotOptions: {
                        radar: {
                            size: 140,
                            polygons: {
                                strokeColors: "#e8e8e8",
                                fill: {
                                    colors: ["#f8fafc", "#ffffff"],
                                },
                                connectorColors: "#e8e8e8",
                            },
                        },
                    },
                    colors: ["#FF4560", "#00E396"],
                    markers: {
                        size: 5,
                        colors: ["#FF4560", "#00E396"],
                        strokeColors: "#fff",
                        strokeWidth: 2,
                        hover: {
                            size: 8,
                        },
                    },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return val.toFixed(2);
                            },
                        },
                        marker: {
                            show: true,
                        },
                        custom: function ({
                            series,
                            seriesIndex,
                            dataPointIndex,
                            w,
                        }) {
                            const score = analysisScores[dataPointIndex];
                            return (
                                '<div class="apexcharts-tooltip-title p-2 font-semibold" style="background-color: #f8fafc; border-bottom: 1px solid #e8e8e8;">' +
                                score.aspek +
                                ": " +
                                score.kriteria +
                                "</div>" +
                                '<div class="p-2">' +
                                '<div class="flex items-center mb-1">' +
                                '<span class="w-3 h-3 inline-block mr-2" style="background-color: #FF4560; border-radius: 50%;"></span>' +
                                "<span>Skor Sendiri: <strong>" +
                                score.selfScore +
                                "</strong></span>" +
                                "</div>" +
                                '<div class="flex items-center">' +
                                '<span class="w-3 h-3 inline-block mr-2" style="background-color: #00E396; border-radius: 50%;"></span>' +
                                "<span>Rata-rata Peer: <strong>" +
                                score.averagePeerScore +
                                "</strong></span>" +
                                "</div>" +
                                '<div class="mt-1 pt-1 border-t border-gray-200">' +
                                "<span>Selisih: <strong>" +
                                score.scoreDifference +
                                "</strong></span>" +
                                "</div>" +
                                "</div>"
                            );
                        },
                    },
                    yaxis: {
                        show: true,
                        min: 0,
                        max: 5,
                        tickAmount: 5,
                        labels: {
                            formatter: function (val) {
                                return val.toFixed(1);
                            },
                            style: {
                                colors: "#64748b",
                                fontSize: "12px",
                            },
                        },
                    },
                    xaxis: {
                        labels: {
                            style: {
                                colors: "#334155",
                                fontSize: "13px",
                                fontWeight: "500",
                            },
                        },
                    },
                    fill: {
                        opacity: 0.5,
                    },
                    stroke: {
                        width: 2,
                    },
                    title: {
                        text: "Perbandingan Skor",
                        align: "center",
                        style: {
                            fontSize: "18px",
                            fontWeight: "600",
                            color: "#334155",
                        },
                        margin: 15,
                    },
                    legend: {
                        position: "bottom",
                        horizontalAlign: "center",
                        fontSize: "14px",
                        markers: {
                            width: 12,
                            height: 12,
                            radius: 6,
                        },
                        itemMargin: {
                            horizontal: 15,
                            vertical: 8,
                        },
                    },
                    dataLabels: {
                        enabled: false,
                    },
                    grid: {
                        show: true,
                        padding: {
                            top: 10,
                            bottom: 10,
                        },
                        strokeDashArray: 0,
                    },
                    responsive: [
                        {
                            breakpoint: 640,
                            options: {
                                chart: {
                                    height: 350,
                                },
                                legend: {
                                    position: "bottom",
                                    fontSize: "12px",
                                },
                            },
                        },
                    ],
                },
            };
        },
        prepareSelfComparisonChartData(userData) {
            const selfScores = userData.self_assessment
                ? userData.self_assessment.map((aspect) =>
                      parseFloat(aspect.total_score || 0).toFixed(2)
                  )
                : [];

            const averageSelfScores = this.calculateAverageSelfScores().map(
                (score) => parseFloat(score.averageScore)
            );

            // Only use criteria for labels
            const labels = userData.self_assessment
                ? userData.self_assessment.map((aspect) => aspect.kriteria)
                : [];

            // Store full aspect data for tooltip
            const aspectData = userData.self_assessment || [];

            return {
                series: [
                    {
                        name: "Skor Sendiri",
                        data: selfScores,
                    },
                    {
                        name: "Rata-rata Self Kelompok",
                        data: averageSelfScores,
                    },
                ],
                options: {
                    chart: {
                        type: "radar",
                        height: 400,
                        toolbar: {
                            show: false, // Hide toolbar for cleaner look
                        },
                        dropShadow: {
                            enabled: true,
                            blur: 1,
                            left: 1,
                            top: 1,
                        },
                    },
                    labels: labels,
                    plotOptions: {
                        radar: {
                            size: 140,
                            polygons: {
                                strokeColors: "#e8e8e8",
                                fill: {
                                    colors: ["#f8fafc", "#ffffff"],
                                },
                                connectorColors: "#e8e8e8",
                            },
                        },
                    },
                    colors: ["#FF4560", "#3B82F6"],
                    markers: {
                        size: 5,
                        colors: ["#FF4560", "#3B82F6"],
                        strokeColors: "#fff",
                        strokeWidth: 2,
                        hover: {
                            size: 8,
                        },
                    },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return val.toFixed(2);
                            },
                        },
                        marker: {
                            show: true,
                        },
                        custom: function ({
                            series,
                            seriesIndex,
                            dataPointIndex,
                            w,
                        }) {
                            const aspect = aspectData[dataPointIndex];
                            return (
                                '<div class="apexcharts-tooltip-title p-2 font-semibold" style="background-color: #f8fafc; border-bottom: 1px solid #e8e8e8;">' +
                                aspect.aspek +
                                ": " +
                                aspect.kriteria +
                                "</div>" +
                                '<div class="p-2">' +
                                '<div class="flex items-center mb-1">' +
                                '<span class="w-3 h-3 inline-block mr-2" style="background-color: #FF4560; border-radius: 50%;"></span>' +
                                "<span>Skor Sendiri: <strong>" +
                                parseFloat(aspect.total_score).toFixed(2) +
                                "</strong></span>" +
                                "</div>" +
                                '<div class="flex items-center">' +
                                '<span class="w-3 h-3 inline-block mr-2" style="background-color: #3B82F6; border-radius: 50%;"></span>' +
                                "<span>Rata-rata Self Kelompok: <strong>" +
                                averageSelfScores[dataPointIndex] +
                                "</strong></span>" +
                                "</div>" +
                                "</div>"
                            );
                        },
                    },
                    yaxis: {
                        show: true,
                        min: 0,
                        max: 5,
                        tickAmount: 5,
                        labels: {
                            formatter: function (val) {
                                return val.toFixed(1);
                            },
                            style: {
                                colors: "#64748b",
                                fontSize: "12px",
                            },
                        },
                    },
                    xaxis: {
                        labels: {
                            style: {
                                colors: "#334155",
                                fontSize: "13px",
                                fontWeight: "500",
                            },
                        },
                    },
                    fill: {
                        opacity: 0.5,
                    },
                    stroke: {
                        width: 2,
                    },
                    title: {
                        text: "Perbandingan Skor Self Assessment",
                        align: "center",
                        style: {
                            fontSize: "18px",
                            fontWeight: "600",
                            color: "#334155",
                        },
                        margin: 15,
                    },
                    legend: {
                        position: "bottom",
                        horizontalAlign: "center",
                        fontSize: "14px",
                        markers: {
                            width: 12,
                            height: 12,
                            radius: 6,
                        },
                        itemMargin: {
                            horizontal: 15,
                            vertical: 8,
                        },
                    },
                    dataLabels: {
                        enabled: false, // Disable data labels for cleaner look
                    },
                    grid: {
                        show: true,
                        padding: {
                            top: 10,
                            bottom: 10,
                        },
                        strokeDashArray: 0,
                    },
                    responsive: [
                        {
                            breakpoint: 640,
                            options: {
                                chart: {
                                    height: 350,
                                },
                                legend: {
                                    position: "bottom",
                                    fontSize: "12px",
                                },
                            },
                        },
                    ],
                },
            };
        },
        prepareReportData(score) {
            return {
                project_id: this.project_id, // Assuming you have this from your route or props
                group_id: this.group_id, // Assuming you have this from your route or props
                mahasiswa_id: this.selectedUserData.mahasiswa_id,
                batch_year: this.batch_year,
                project_name: this.project_name,
                kelompok: this.kelompok,
                typeCriteria_id: score.typeCriteriaId,
                skor_self: parseFloat(score.selfScore),
                skor_peer: parseFloat(score.averagePeerScore),
                selisih: parseFloat(score.scoreDifference),
                nilai_total:
                    (parseFloat(score.selfScore) +
                        parseFloat(score.averagePeerScore)) /
                    2,
                status: score.status,
            };
        },

        // Check if score and score_SLA are the same
        scoresAreEqual(pertanyaan) {
            return (
                pertanyaan.score == pertanyaan.score_SLA &&
                pertanyaan.score !== null &&
                pertanyaan.score_SLA !== null
            );
        },

        // Check if should highlight score radio
        shouldHighlightScore(pertanyaan) {
            // Priority: use _userSelected if available
            if (pertanyaan._userSelected) {
                return pertanyaan._userSelected === "score";
            }

            // Fallback: check if final_score matches score
            return pertanyaan.final_score == pertanyaan.score;
        },

        // Check if should highlight score_SLA radio
        shouldHighlightScoreSLA(pertanyaan) {
            // Priority: use _userSelected if available
            if (pertanyaan._userSelected) {
                return pertanyaan._userSelected === "score_SLA";
            }

            // Fallback: check if final_score matches score_SLA
            return pertanyaan.final_score == pertanyaan.score_SLA;
        },

        // Helper method to check if user explicitly selected score_SLA
        userExplicitlySelectedScoreSLA(pertanyaan) {
            return pertanyaan._userSelected === "score_SLA";
        },

        // Add method to track user selection
        onScoreRadioChange(pertanyaan, scoreType, aspek) {
            // Mark that user explicitly selected this option
            pertanyaan._userSelected = scoreType;
            // Add a flag to indicate this was explicitly set by user
            pertanyaan._userExplicitlySelected = true;

            // Update final_score based on selection
            if (scoreType === "score") {
                pertanyaan.final_score = pertanyaan.score;
            } else if (scoreType === "score_SLA") {
                pertanyaan.final_score = pertanyaan.score_SLA;
            }

            // Get the actual selected value based on scoreType
            const selectedValue =
                scoreType === "score" ? pertanyaan.score : pertanyaan.score_SLA;

            console.log(
                `Score Selection - Question ID: ${pertanyaan.question_id}, Selected: ${scoreType}, Value: ${selectedValue}, SKOR: ${pertanyaan.score}, SKOR SLA: ${pertanyaan.score_SLA}, final_score: ${pertanyaan.final_score}`
            );

            // Recalculate total score for this aspek
            this.recalculateAspekTotalScore(aspek);
        },

        recalculateAspekTotalScore(aspek) {
            if (!aspek.questions || !Array.isArray(aspek.questions)) {
                return;
            }

            // Hitung total score berdasarkan final_score dari setiap pertanyaan
            let totalScore = 0;
            let questionCount = 0;

            aspek.questions.forEach((pertanyaan) => {
                if (
                    pertanyaan.final_score !== null &&
                    pertanyaan.final_score !== undefined
                ) {
                    totalScore += pertanyaan.final_score;
                    questionCount++;
                }
            });

            // Hitung rata-rata
            if (questionCount > 0) {
                aspek.total_score = totalScore / questionCount;
            } else {
                aspek.total_score = 0;
            }

            console.log(
                `Recalculated total score for aspek ${aspek.aspek}: ${aspek.total_score} (based on ${questionCount} questions)`
            );
        },
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
                    <Breadcrumb
                        :items="[
                            { text: 'Report', href: '/dosen/report' },
                            { text: `${kelompok}`, href: '#' },
                        ]"
                    />
                </div>

                <!-- Header Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <Card
                        title="Detail Kelompok"
                        class="bg-white shadow-sm border-0"
                    >
                        <div
                            v-if="batch_year && project_name && kelompok"
                            class="space-y-2"
                        >
                            <div class="flex items-center">
                                <div class="w-36 font-medium text-gray-600">
                                    Tahun Ajaran:
                                </div>
                                <div>{{ batch_year }}</div>
                            </div>
                            <div class="flex items-center">
                                <div class="w-36 font-medium text-gray-600">
                                    Nama Proyek:
                                </div>
                                <div>{{ project_name }}</div>
                            </div>
                            <div class="flex items-center">
                                <div class="w-36 font-medium text-gray-600">
                                    Kelompok:
                                </div>
                                <div>{{ kelompok }}</div>
                            </div>
                        </div>
                        <div v-else>
                            <p class="text-gray-500">
                                Tidak ada data yang tersedia
                            </p>
                        </div>
                    </Card>

                    <Card
                        title="Pilih Peserta"
                        class="bg-white shadow-sm border-0"
                    >
                        <div class="space-y-1">
                            <label
                                for="peserta"
                                class="block text-sm font-medium text-gray-600"
                                >Nama Peserta</label
                            >
                            <select
                                id="peserta"
                                v-model="selectedUserId"
                                class="w-full p-2.5 bg-white border border-gray-300 text-gray-700 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                            >
                                <option value="" disabled>Pilih Peserta</option>
                                <option
                                    v-for="(userData, userId) in userAnalysis"
                                    :key="userId"
                                    :value="userId"
                                >
                                    {{ userData.name }}
                                </option>
                            </select>
                        </div>
                    </Card>
                </div>

                <!-- Loading State -->
                <div
                    v-if="loading"
                    class="flex justify-center items-center h-64"
                >
                    <div
                        class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"
                    ></div>
                    <span class="ml-3 text-gray-600">Memuat data...</span>
                </div>

                <!-- Error State -->
                <div
                    v-else-if="error"
                    class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md"
                >
                    {{ error }}
                </div>

                <!-- Charts Section -->
                <div v-else-if="selectedUserData" class="space-y-6">
                    <!-- Peer Comparison Chart -->
                    <Card
                        title=""
                        class="bg-white shadow-sm border-0 p-0 overflow-hidden"
                    >
                        <div
                            class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-100"
                        >
                            <h3 class="text-lg font-semibold text-gray-800">
                                Perbandingan Skor Peer Assessment
                            </h3>
                            <p class="text-sm text-gray-600">
                                Menampilkan perbandingan skor evaluasi diri
                                dengan rata-rata skor dari peer
                            </p>
                        </div>
                        <!-- Chart Container - Flex Layout -->
                        <div class="p-4">
                            <ApexChart
                                type="radar"
                                height="400"
                                :series="
                                    preparePeerComparisonChartData(
                                        selectedUserData
                                    ).series
                                "
                                :options="
                                    preparePeerComparisonChartData(
                                        selectedUserData
                                    ).options
                                "
                            />
                        </div>
                    </Card>

                    <!-- Self Assessment Chart -->
                    <Card
                        title=""
                        class="bg-white shadow-sm border-0 p-0 overflow-hidden"
                    >
                        <div
                            class="p-4 bg-gradient-to-r from-rose-50 to-orange-50 border-b border-gray-100"
                        >
                            <h3 class="text-lg font-semibold text-gray-800">
                                Perbandingan Skor Self Assessment
                            </h3>
                            <p class="text-sm text-gray-600">
                                Menampilkan perbandingan skor evaluasi diri
                                dengan rata-rata skor seluruh kelompok
                            </p>
                        </div>
                        <div class="p-4">
                            <ApexChart
                                type="radar"
                                height="400"
                                :series="
                                    prepareSelfComparisonChartData(
                                        selectedUserData
                                    ).series
                                "
                                :options="
                                    prepareSelfComparisonChartData(
                                        selectedUserData
                                    ).options
                                "
                            />
                        </div>
                    </Card>
                </div>

                <div
                    v-else-if="!loading && !error && userIds.length > 0"
                    class="flex flex-col items-center justify-center h-64 bg-white rounded-lg shadow-sm border-0 p-6"
                >
                    <svg
                        class="w-16 h-16 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                        ></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">
                        Pilih Peserta
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Silakan pilih peserta untuk melihat hasil analisis
                    </p>
                </div>

                <!-- <div class="grid grid-cols-2 gap-4">
                    <Card title="Detail Kelompok">
                        <div v-if="batch_year && project_name && kelompok">
                            <p>
                                <strong>Tahun Ajaran:</strong> {{ batch_year }}
                            </p>
                            <p>
                                <strong>Nama Proyek:</strong> {{ project_name }}
                            </p>
                            <p><strong>Kelompok:</strong> {{ kelompok }}</p>
                        </div>
                        <div v-else>
                            <p>Tidak ada data yang tersedia</p>
                        </div>
                    </Card>

                    <Card title="Pilih Peserta">
                        <select v-model="selectedUserId" class="w-full p-2 border rounded">
                            <option value="" disabled>Pilih Peserta</option>
                            <option v-for="(userData, userId) in userAnalysis" :key="userId" :value="userId">
                                {{ userData.name }}
                            </option>
                        </select>
                    </Card>

                    <Card v-if="selectedUserData" title="Perbandingan Skor Peer" class="col-span-2">
                        <ApexChart type="radar" height="350" :series="preparePeerComparisonChartData(selectedUserData)
                            .series
                            " :options="preparePeerComparisonChartData(selectedUserData)
                                .options
                                " />
                    </Card>

                    <Card v-if="selectedUserData" title="Perbandingan Skor Self Assessment" class="col-span-2">
                        <ApexChart type="radar" height="350" :series="prepareSelfComparisonChartData(selectedUserData)
                            .series
                            " :options="prepareSelfComparisonChartData(selectedUserData)
                                .options
                                " />
                    </Card>
                </div> -->

                <div
                    v-if="loading"
                    class="flex justify-center items-center p-8"
                >
                    <div
                        class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"
                    ></div>
                    <span class="ml-3 text-gray-600">Memuat...</span>
                </div>
                <div
                    v-else-if="error"
                    class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md mt-4"
                >
                    {{ error }}
                </div>
                <div v-else-if="selectedUserData">
                    <Card
                        :title="`Analisis Jawaban - ${selectedUserData.name}`"
                        class="mt-4"
                    >
                        <!-- Self Assessment Section -->
                        <div
                            v-if="
                                selectedUserData.self_assessment &&
                                selectedUserData.self_assessment.length
                            "
                        >
                            <div
                                class="bg-white rounded-lg shadow-sm overflow-hidden"
                            >
                                <div
                                    class="bg-gradient-to-r from-blue-600 to-blue-700 p-4 flex justify-between items-center"
                                >
                                    <h3 class="text-white text-lg font-bold">
                                        Self Assessment
                                    </h3>
                                </div>
                                <!-- Tombol Simpan di Bagian Atas -->
                                <div class="mt-3 mb-4 flex justify-end">
                                    <button
                                        @click="saveAnswerSelf"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        :disabled="isLoading"
                                    >
                                        <span v-if="isLoading"
                                            >Menyimpan...</span
                                        >
                                        <span v-else
                                            >Simpan Semua Penilaian</span
                                        >
                                    </button>
                                </div>

                                <div class="p-2">
                                    <div
                                        v-for="(
                                            aspek, index
                                        ) in selectedUserData.self_assessment"
                                        :key="index"
                                        class="mb-6 last:mb-0"
                                    >
                                        <div class="bg-gray-50 p-4 rounded-lg">
                                            <!-- Aspek Header -->
                                            <div
                                                class="flex justify-between items-center mb-4"
                                            >
                                                <div>
                                                    <h4
                                                        class="text-lg font-semibold text-gray-800"
                                                    >
                                                        {{ aspek.aspek }}
                                                    </h4>
                                                    <p
                                                        class="text-sm text-gray-600"
                                                    >
                                                        {{ aspek.kriteria }}
                                                    </p>
                                                </div>
                                                <div class="text-right">
                                                    <!-- Original Total Score -->
                                                    <div>
                                                        <div
                                                            class="text-sm text-gray-600"
                                                        >
                                                            Total Skor
                                                        </div>
                                                        <div
                                                            class="text-2xl font-bold"
                                                            :class="{
                                                                'text-green-600':
                                                                    aspek.total_score >=
                                                                    4,
                                                                'text-yellow-600':
                                                                    aspek.total_score >=
                                                                        3 &&
                                                                    aspek.total_score <
                                                                        4,
                                                                'text-red-600':
                                                                    aspek.total_score <
                                                                    2.5,
                                                            }"
                                                        >
                                                            {{
                                                                aspek.total_score
                                                                    ? aspek.total_score.toFixed(
                                                                          2
                                                                      )
                                                                    : "N/A"
                                                            }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Questions Table -->
                                            <div
                                                class="bg-white rounded-lg border border-gray-200 overflow-hidden"
                                            >
                                                <table class="w-full">
                                                    <thead>
                                                        <tr
                                                            class="bg-gray-50 border-b border-gray-200"
                                                        >
                                                            <th
                                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/2"
                                                            >
                                                                Pertanyaan
                                                            </th>
                                                            <th
                                                                class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24"
                                                            >
                                                                Skor
                                                            </th>
                                                            <th
                                                                class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24"
                                                            >
                                                                Skor SLA
                                                            </th>
                                                            <th
                                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                            >
                                                                Jawaban
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody
                                                        class="divide-y divide-gray-200"
                                                    >
                                                        <tr
                                                            v-for="(
                                                                pertanyaan,
                                                                qIndex
                                                            ) in aspek.questions"
                                                            :key="qIndex"
                                                            class="hover:bg-gray-50 transition-colors"
                                                        >
                                                            <td
                                                                class="px-4 py-3 text-sm text-gray-900"
                                                            >
                                                                {{
                                                                    pertanyaan.pertanyaan
                                                                }}
                                                            </td>
                                                            <td
                                                                class="px-4 py-3 text-center"
                                                            >
                                                                <div
                                                                    :class="{
                                                                        'bg-blue-100 p-2 rounded-md':
                                                                            shouldHighlightScore(
                                                                                pertanyaan
                                                                            ),
                                                                    }"
                                                                >
                                                                    <input
                                                                        type="radio"
                                                                        :name="
                                                                            'score_' +
                                                                            pertanyaan.question_id
                                                                        "
                                                                        :value="
                                                                            pertanyaan.score
                                                                        "
                                                                        :checked="
                                                                            shouldHighlightScore(
                                                                                pertanyaan
                                                                            )
                                                                        "
                                                                        class="border-2 border-gray-300 rounded-md hover:border-blue-500 mr-2"
                                                                        @change="
                                                                            onScoreRadioChange(
                                                                                pertanyaan,
                                                                                'score',
                                                                                aspek
                                                                            )
                                                                        "
                                                                    />
                                                                    <span
                                                                        :class="{
                                                                            'font-medium':
                                                                                shouldHighlightScore(
                                                                                    pertanyaan
                                                                                ),
                                                                        }"
                                                                    >
                                                                        {{
                                                                            pertanyaan.score ||
                                                                            "N/A"
                                                                        }}
                                                                    </span>
                                                                </div>
                                                            </td>
                                                            <td
                                                                class="px-4 py-3 text-center"
                                                            >
                                                                <div
                                                                    :class="{
                                                                        'bg-blue-100 p-2 rounded-md':
                                                                            shouldHighlightScoreSLA(
                                                                                pertanyaan
                                                                            ),
                                                                    }"
                                                                >
                                                                    <input
                                                                        type="radio"
                                                                        :name="
                                                                            'score_' +
                                                                            pertanyaan.question_id
                                                                        "
                                                                        :value="
                                                                            pertanyaan.score_SLA
                                                                        "
                                                                        :checked="
                                                                            shouldHighlightScoreSLA(
                                                                                pertanyaan
                                                                            )
                                                                        "
                                                                        class="border-2 border-gray-300 rounded-md hover:border-blue-500 mr-2"
                                                                        @change="
                                                                            onScoreRadioChange(
                                                                                pertanyaan,
                                                                                'score_SLA',
                                                                                aspek
                                                                            )
                                                                        "
                                                                    />
                                                                    <span
                                                                        :class="{
                                                                            'font-medium':
                                                                                shouldHighlightScoreSLA(
                                                                                    pertanyaan
                                                                                ),
                                                                        }"
                                                                    >
                                                                        {{
                                                                            pertanyaan.score_SLA ||
                                                                            "N/A"
                                                                        }}
                                                                    </span>
                                                                </div>
                                                            </td>
                                                            <td
                                                                class="px-4 py-3 text-sm text-gray-500"
                                                            >
                                                                <div
                                                                    class="max-w-xl"
                                                                >
                                                                    {{
                                                                        pertanyaan.answer ||
                                                                        "-"
                                                                    }}
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-center text-gray-500 p-4">
                            Tidak ada data self assessment
                        </p>

                        <!-- Peer Evaluation Section -->
                        <div class="mt-6">
                            <div
                                class="bg-white rounded-lg shadow-sm overflow-hidden"
                            >
                                <div
                                    class="bg-gradient-to-r from-purple-600 to-purple-700 p-4 flex justify-between items-center"
                                >
                                    <h3 class="text-white text-lg font-bold">
                                        Evaluasi dari Peer
                                    </h3>
                                    <div
                                        class="bg-white bg-opacity-20 rounded-lg px-4 py-2"
                                    >
                                        <span class="text-white text-sm"
                                            >Total Average:
                                        </span>
                                        <span class="text-white font-bold">{{
                                            calculateTotalAverage(
                                                selectedUserId
                                            )
                                        }}</span>
                                    </div>
                                </div>
                                <!-- Tombol Simpan di Bagian Atas -->
                                <div class="mt-3 mb-4 flex justify-end">
                                    <button
                                        @click="saveAnswerPeer"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        :disabled="isLoading"
                                    >
                                        <span v-if="isLoading"
                                            >Menyimpan...</span
                                        >
                                        <span v-else
                                            >Simpan Semua Penilaian</span
                                        >
                                    </button>
                                </div>

                                <div class="p-4">
                                    <div
                                        v-if="
                                            selectedUserData.evaluated_by_peers &&
                                            selectedUserData.evaluated_by_peers
                                                .length
                                        "
                                    >
                                        <div
                                            v-for="(
                                                peerGroup, index
                                            ) in groupPeerEvaluations(
                                                selectedUserData.evaluated_by_peers
                                            )"
                                            :key="index"
                                            class="mb-6 last:mb-0"
                                        >
                                            <div
                                                class="bg-gray-50 p-4 rounded-lg"
                                            >
                                                <!-- Peer Group Header -->
                                                <div
                                                    class="flex flex-wrap md:flex-nowrap justify-between items-start gap-4 mb-4"
                                                >
                                                    <div>
                                                        <h4
                                                            class="text-lg font-semibold text-gray-800"
                                                        >
                                                            {{
                                                                peerGroup.aspek
                                                            }}
                                                        </h4>
                                                        <p
                                                            class="text-sm text-gray-600"
                                                        >
                                                            {{
                                                                peerGroup.kriteria
                                                            }}
                                                        </p>
                                                        <div
                                                            class="mt-2 flex flex-wrap gap-2"
                                                        >
                                                            <span
                                                                v-for="(
                                                                    name,
                                                                    nameIdx
                                                                ) in peerGroup.names"
                                                                :key="nameIdx"
                                                                class="inline-flex items-center px-3py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
                                                            >
                                                                {{ name }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="text-right flex-shrink-0"
                                                    >
                                                        <div
                                                            class="text-sm text-gray-600"
                                                        >
                                                            Total Skor
                                                        </div>
                                                        <div
                                                            class="text-2xl font-bold"
                                                            :class="{
                                                                'text-green-600':
                                                                    peerGroup.total_score >=
                                                                    4,
                                                                'text-yellow-600':
                                                                    peerGroup.total_score >=
                                                                        3 &&
                                                                    peerGroup.total_score <
                                                                        4,
                                                                'text-red-600':
                                                                    peerGroup.total_score <
                                                                    2.5,
                                                            }"
                                                        >
                                                            {{
                                                                peerGroup.total_score
                                                                    ? peerGroup.total_score.toFixed(
                                                                          2
                                                                      )
                                                                    : "N/A"
                                                            }}
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Answers Table -->
                                                <div
                                                    class="bg-white rounded-lg border border-gray-200 overflow-hidden"
                                                >
                                                    <table
                                                        class="w-full table-fixed"
                                                    >
                                                        <thead>
                                                            <tr
                                                                class="bg-gray-50 border-b border-gray-200"
                                                            >
                                                                <th
                                                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48"
                                                                >
                                                                    Penilai
                                                                </th>
                                                                <th
                                                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-64"
                                                                >
                                                                    Pertanyaan
                                                                </th>
                                                                <th
                                                                    class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20"
                                                                >
                                                                    Skor
                                                                </th>
                                                                <th
                                                                    class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20"
                                                                >
                                                                    Skor SLA
                                                                </th>
                                                                <th
                                                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                                >
                                                                    Jawaban
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody
                                                            class="divide-y divide-gray-200"
                                                        >
                                                            <tr
                                                                v-for="(
                                                                    answer, idx
                                                                ) in peerGroup.answers"
                                                                :key="idx"
                                                                class="hover:bg-gray-50 transition-colors"
                                                            >
                                                                <td
                                                                    class="px-4 py-3 w-48"
                                                                >
                                                                    <div
                                                                        class="text-sm font-medium text-gray-900 break-words leading-relaxed"
                                                                    >
                                                                        {{
                                                                            answer.evaluator_name
                                                                        }}
                                                                    </div>
                                                                </td>
                                                                <td
                                                                    class="px-4 py-3 w-64"
                                                                >
                                                                    <div
                                                                        class="text-sm text-gray-900 break-words leading-relaxed"
                                                                    >
                                                                        {{
                                                                            answer.pertanyaan
                                                                        }}
                                                                    </div>
                                                                </td>
                                                                <td
                                                                    class="px-4 py-3 text-center w-20"
                                                                >
                                                                    <div
                                                                        :class="{
                                                                            'bg-blue-100 p-2 rounded-md':
                                                                                shouldHighlightPeerScore(
                                                                                    answer
                                                                                ),
                                                                        }"
                                                                    >
                                                                        <input
                                                                            type="radio"
                                                                            :name="
                                                                                'score_' +
                                                                                answer.question_id +
                                                                                '_' +
                                                                                answer.evaluator_name
                                                                            "
                                                                            :value="
                                                                                answer.score
                                                                            "
                                                                            :checked="
                                                                                shouldHighlightPeerScore(
                                                                                    answer
                                                                                )
                                                                            "
                                                                            class="border-2 border-gray-300 rounded-md hover:border-blue-500 mr-2"
                                                                            @change="
                                                                                onPeerScoreRadioChange(
                                                                                    answer,
                                                                                    'score',
                                                                                    peerGroup
                                                                                )
                                                                            "
                                                                        />
                                                                        <span
                                                                            :class="{
                                                                                'font-medium':
                                                                                    shouldHighlightPeerScore(
                                                                                        answer
                                                                                    ),
                                                                            }"
                                                                        >
                                                                            {{
                                                                                answer.score ||
                                                                                "N/A"
                                                                            }}
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                                <td
                                                                    class="px-4 py-3 text-center w-20"
                                                                >
                                                                    <div
                                                                        :class="{
                                                                            'bg-blue-100 p-2 rounded-md':
                                                                                shouldHighlightPeerScoreSLA(
                                                                                    answer
                                                                                ),
                                                                        }"
                                                                    >
                                                                        <input
                                                                            type="radio"
                                                                            :name="
                                                                                'score_' +
                                                                                answer.question_id +
                                                                                '_' +
                                                                                answer.evaluator_name
                                                                            "
                                                                            :value="
                                                                                answer.score_SLA
                                                                            "
                                                                            :checked="
                                                                                shouldHighlightPeerScoreSLA(
                                                                                    answer
                                                                                )
                                                                            "
                                                                            class="border-2 border-gray-300 rounded-md hover:border-blue-500 mr-2"
                                                                            @change="
                                                                                onPeerScoreRadioChange(
                                                                                    answer,
                                                                                    'score_SLA',
                                                                                    peerGroup
                                                                                )
                                                                            "
                                                                        />
                                                                        <span
                                                                            :class="{
                                                                                'font-medium':
                                                                                    shouldHighlightPeerScoreSLA(
                                                                                        answer
                                                                                    ),
                                                                            }"
                                                                        >
                                                                            {{
                                                                                answer.score_SLA ||
                                                                                "N/A"
                                                                            }}
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                                <td
                                                                    class="px-4 py-3"
                                                                >
                                                                    <div
                                                                        class="text-sm text-gray-500 break-words leading-relaxed max-w-md"
                                                                    >
                                                                        {{
                                                                            answer.answer ||
                                                                            "-"
                                                                        }}
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <p
                                        v-else
                                        class="text-center text-gray-500 p-4"
                                    >
                                        Tidak ada data evaluasi peer
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Analysis Score Section -->
                        <div class="mt-6">
                            <div
                                class="bg-white rounded-lg shadow-sm overflow-hidden"
                            >
                                <div
                                    class="bg-gradient-to-r from-indigo-600 to-indigo-700 p-4"
                                >
                                    <h3 class="text-white text-lg font-bold">
                                        Analysis Score
                                    </h3>
                                </div>

                                <div class="p-4">
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <!-- Score Summary Cards -->
                                        <div
                                            class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6"
                                        >
                                            <div
                                                class="bg-green-50 rounded-lg p-4 border border-green-200"
                                            >
                                                <div
                                                    class="text-sm font-medium text-green-600 mb-1"
                                                >
                                                    Over Estimation
                                                </div>
                                                <div
                                                    class="text-2xl font-bold text-green-700"
                                                >
                                                    {{
                                                        calculateAnalysisScores(
                                                            selectedUserData
                                                        ).filter(
                                                            (s) =>
                                                                s.status ===
                                                                "Over"
                                                        ).length
                                                    }}
                                                </div>
                                                <div
                                                    class="text-xs text-green-600 mt-1"
                                                >
                                                    Aspects where self-score
                                                    More Then peer-score
                                                </div>
                                            </div>

                                            <div
                                                class="bg-yellow-50 rounded-lg p-4 border border-yellow-200"
                                            >
                                                <div
                                                    class="text-sm font-medium text-yellow-600 mb-1"
                                                >
                                                    Matched Estimation
                                                </div>
                                                <div
                                                    class="text-2xl font-bold text-yellow-700"
                                                >
                                                    {{
                                                        calculateAnalysisScores(
                                                            selectedUserData
                                                        ).filter(
                                                            (s) =>
                                                                s.status ===
                                                                "Match"
                                                        ).length
                                                    }}
                                                </div>
                                                <div
                                                    class="text-xs text-yellow-600 mt-1"
                                                >
                                                    Aspects where self-score
                                                    Same peer-score
                                                </div>
                                            </div>

                                            <div
                                                class="bg-red-50 rounded-lg p-4 border border-red-200"
                                            >
                                                <div
                                                    class="text-sm font-medium text-red-600 mb-1"
                                                >
                                                    Under Estimation
                                                </div>
                                                <div
                                                    class="text-2xl font-bold text-red-700"
                                                >
                                                    {{
                                                        calculateAnalysisScores(
                                                            selectedUserData
                                                        ).filter(
                                                            (s) =>
                                                                s.status ===
                                                                "Under"
                                                        ).length
                                                    }}
                                                </div>
                                                <div
                                                    class="text-xs text-red-600 mt-1"
                                                >
                                                    Aspects where self-score
                                                    Less Then peer-score
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Detailed Analysis Table -->
                                        <div
                                            class="bg-white rounded-lg border border-gray-200 overflow-hidden"
                                        >
                                            <table class="w-full">
                                                <thead>
                                                    <tr
                                                        class="bg-gray-50 border-b border-gray-200"
                                                    >
                                                        <th
                                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                        >
                                                            Aspek
                                                        </th>
                                                        <th
                                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                        >
                                                            Kriteria
                                                        </th>
                                                        <th
                                                            class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                        >
                                                            Self Score
                                                        </th>
                                                        <th
                                                            class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                        >
                                                            Peer Score
                                                        </th>
                                                        <th
                                                            class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                        >
                                                            Selisih
                                                        </th>
                                                        <th
                                                            class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                        >
                                                            Status
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody
                                                    class="divide-y divide-gray-200"
                                                >
                                                    <tr
                                                        v-for="(
                                                            score, index
                                                        ) in calculateAnalysisScores(
                                                            selectedUserData
                                                        )"
                                                        :key="index"
                                                        class="hover:bg-gray-50 transition-colors"
                                                    >
                                                        <td class="px-4 py-3">
                                                            <div
                                                                class="text-sm font-medium text-gray-900"
                                                            >
                                                                {{
                                                                    score.aspek
                                                                }}
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <div
                                                                class="text-sm text-gray-500"
                                                            >
                                                                {{
                                                                    score.kriteria
                                                                }}
                                                            </div>
                                                        </td>
                                                        <td
                                                            class="px-4 py-3 text-center"
                                                        >
                                                            <div
                                                                class="text-sm font-semibold text-gray-900"
                                                            >
                                                                {{
                                                                    score.selfScore
                                                                }}
                                                            </div>
                                                        </td>
                                                        <td
                                                            class="px-4 py-3 text-center"
                                                        >
                                                            <div
                                                                class="text-sm font-semibold text-gray-900"
                                                            >
                                                                {{
                                                                    score.averagePeerScore
                                                                }}
                                                            </div>
                                                        </td>
                                                        <td
                                                            class="px-4 py-3 text-center"
                                                        >
                                                            <span
                                                                class="inline-flex items-center px-3py-1 rounded-full text-xs font-medium"
                                                                :class="{
                                                                    'bg-green-100 text-green-800':
                                                                        parseFloat(
                                                                            score.scoreDifference
                                                                        ) > 0,
                                                                    'bg-yellow-100 text-yellow-800':
                                                                        parseFloat(
                                                                            score.scoreDifference
                                                                        ) === 0,
                                                                    'bg-red-100 text-red-800':
                                                                        parseFloat(
                                                                            score.scoreDifference
                                                                        ) < 0,
                                                                }"
                                                            >
                                                                {{
                                                                    score.scoreDifference >=
                                                                    0
                                                                        ? "+" +
                                                                          score.scoreDifference
                                                                        : score.scoreDifference
                                                                }}
                                                            </span>
                                                        </td>
                                                        <td
                                                            class="px-4 py-3 text-center"
                                                        >
                                                            <span
                                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                                                                :class="{
                                                                    'bg-green-100 text-green-800':
                                                                        score.status ===
                                                                        'Over',
                                                                    'bg-yellow-100 text-yellow-800':
                                                                        score.status ===
                                                                        'Match',
                                                                    'bg-red-100 text-red-800':
                                                                        score.status ===
                                                                        'Under',
                                                                }"
                                                            >
                                                                {{
                                                                    score.status
                                                                }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Legend -->
                                        <div
                                            class="mt-4 flex flex-wrap gap-4 text-xs text-gray-500"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="w-3 h-3 inline-block bg-green-100 rounded-full border border-green-200"
                                                ></span>
                                                <span
                                                    >Over: Self score lebih
                                                    tinggi dari peer score</span
                                                >
                                            </div>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="w-3 h-3 inline-block bg-yellow-100 rounded-full border border-yellow-200"
                                                ></span>
                                                <span
                                                    >Match: Self score sama
                                                    dengan peer score</span
                                                >
                                            </div>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="w-3 h-3 inline-block bg-red-100 rounded-full border border-red-200"
                                                ></span>
                                                <span
                                                    >Under: Self score lebih
                                                    rendah dari peer score</span
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>
                <div v-else class="text-center text-gray-500 mt-4">
                    Pilih peserta untuk melihat detail
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
.text-center {
    text-align: center;
}

.text-red-500 {
    color: #f56565;
}

.bg-green-100 {
    background-color: #f0fff4;
}

.bg-yellow-100 {
    background-color: #fffff0;
}

.bg-red-100 {
    background-color: #fff5f5;
}

.border {
    border: 1px solid #e2e8f0;
}

.hover\:bg-gray-50:hover {
    background-color: #f9fafb;
}

/* Improved table readability */
.break-words {
    word-wrap: break-word;
    word-break: break-word;
}

.leading-relaxed {
    line-height: 1.625;
}

.max-w-md {
    max-width: 28rem;
}

/* Table cell padding adjustment for better text display */
table td {
    vertical-align: top;
    padding: 12px 16px;
}

/* Better table row spacing */
table tr {
    transition: background-color 0.15s ease-in-out;
}
</style>
