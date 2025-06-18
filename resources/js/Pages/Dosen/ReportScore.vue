<script>
import axios from "axios";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import ApexChart from "apexcharts";
import VueApexCharts from "vue3-apexcharts";

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
        // Add new method to calculate total_score_final
        calculateTotalScoreFinal(aspek) {
            if (
                !aspek.questions ||
                !Array.isArray(aspek.questions) ||
                aspek.questions.length === 0
            ) {
                return 0;
            }

            // Filter questions that have a final_score and calculate the average
            const questionsWithFinalScore = aspek.questions.filter(
                (q) => q.final_score !== undefined && q.final_score !== null
            );

            if (questionsWithFinalScore.length === 0) {
                return 0;
            }

            const sum = questionsWithFinalScore.reduce(
                (total, question) => total + parseFloat(question.final_score),
                0
            );

            return sum / questionsWithFinalScore.length;
        },

        async saveAnswerSelf() {
            if (this.isLoading) {
                alert("Permintaan sedang diproses, mohon tunggu...");
                return;
            }

            this.isLoading = true;

            try {
                const mahasiswaId = this.selectedUserData.mahasiswa_id;
                console.log("Selected User Data:", this.selectedUserData);
                const answers = [];

                this.selectedUserData.self_assessment.forEach((aspek) => {
                    if (!aspek.questions || !Array.isArray(aspek.questions)) {
                        return;
                    }

                    aspek.questions.forEach((pertanyaan) => {
                        const finalScore =
                            pertanyaan.final_score || pertanyaan.score;

                        if (finalScore) {
                            answers.push({
                                mahasiswa_id: mahasiswaId,
                                typeCriteria_id: aspek.kriteria,
                                question_id: pertanyaan.question_id,
                                final_score_self: parseInt(finalScore),
                            });

                            // Update the final_score_self value for this question directly
                            pertanyaan.final_score_self = finalScore;
                            // If there's a report object, update it too
                            if (pertanyaan.report) {
                                pertanyaan.report.final_score_self = finalScore;
                            }
                        }
                    });
                    // Calculate and update total_score_final for this aspect
                    aspek.total_score_final =
                        this.calculateTotalScoreFinal(aspek);
                });

                if (answers.length === 0) {
                    alert("Mohon pilih minimal satu skor untuk disimpan");
                    this.isLoading = false;
                    return;
                }

                console.log("Answers Data:", answers);

                const response = await axios.post(
                    "/api/report/save-final-scores-self",
                    { answers }
                );

                if (response.data.success) {
                    alert(response.data.message || "Jawaban berhasil disimpan");
                } else {
                    alert(
                        response.data.message ||
                        "Terjadi kesalahan saat menyimpan jawaban"
                    );
                }
            } catch (error) {
                console.error("Error saving answers:", error);

                if (error.response) {
                    alert(
                        "Terjadi kesalahan pada server: " +
                        (error.response.data.message ||
                            error.response.statusText)
                    );
                } else if (error.request) {
                    alert(
                        "Tidak dapat terhubung ke server. Periksa koneksi internet Anda."
                    );
                } else {
                    alert("Terjadi kesalahan: " + error.message);
                }
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

            this.selectedUserData.self_assessment.forEach((aspek) => {
                if (!aspek.questions || !Array.isArray(aspek.questions)) {
                    return;
                }

                aspek.questions.forEach((pertanyaan) => {
                    // Check for the value in report object first (more reliable source)
                    if (
                        pertanyaan.report &&
                        typeof pertanyaan.report.final_score_self !==
                        "undefined"
                    ) {
                        pertanyaan.final_score =
                            pertanyaan.report.final_score_self;
                        console.log(
                            `Setting final_score from report: ${pertanyaan.question_id} = ${pertanyaan.final_score}`
                        );
                    }
                    // Fallback to final_score_self if available directly on the question
                    else if (
                        typeof pertanyaan.final_score_self !== "undefined" &&
                        pertanyaan.final_score_self !== null
                    ) {
                        pertanyaan.final_score = pertanyaan.final_score_self;
                        console.log(
                            `Setting final_score from final_score_self: ${pertanyaan.question_id} = ${pertanyaan.final_score}`
                        );
                    }
                    // Default to score as last resort
                    else if (!pertanyaan.final_score) {
                        pertanyaan.final_score = pertanyaan.score;
                        console.log(
                            `Setting default final_score: ${pertanyaan.question_id} = ${pertanyaan.final_score}`
                        );
                    }
                });
                aspek.total_score_final = this.calculateTotalScoreFinal(aspek);
            });
        },

        updateFinalPeer(answer, value) {
            if (this.$set) {
                this.$set(answer, "final_peer", Number(value));
            } else {
                answer.final_peer = Number(value);
            }

            // Tambahkan flag untuk menandai bahwa jawaban ini sudah diubah
            answer._modified = true;

            console.log(
                "Nilai final_peer diperbarui:",
                answer.final_peer,
                "untuk pertanyaan:",
                answer.question_id
            );

            // Simpan referensi ke jawaban yang telah diubah
            if (!this._modifiedAnswers) {
                this._modifiedAnswers = new Map();
            }

            // Gunakan kombinasi question_id dan evaluator_name sebagai kunci unik
            const key = answer.question_id + "_" + answer.evaluator_name;
            this._modifiedAnswers.set(key, answer);
        },

        async saveAnswerPeer() {
            if (this.isLoading) {
                alert("Permintaan sedang diproses, mohon tunggu...");
                return;
            }

            this.isLoading = true;

            try {
                const mahasiswaId = this.selectedUserData.mahasiswa_id;
                console.log("Selected User Data:", this.selectedUserData);
                const answersPeer = [];

                // Log penting untuk debugging
                console.log(
                    "Peer assessment structure:",
                    this.selectedUserData.peer_assessment
                );

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
                                                    // Debug log untuk melihat nilai yang ingin kita cari

                                                    let finalPeer = null;

                                                    // Cek di map jawaban yang sudah dimodifikasi
                                                    if (this._modifiedAnswers) {
                                                        const key =
                                                            answer.question_id +
                                                            "_" +
                                                            actualEvaluator.name;
                                                        const modifiedAnswer =
                                                            this._modifiedAnswers.get(
                                                                key
                                                            );

                                                        if (
                                                            modifiedAnswer &&
                                                            modifiedAnswer.final_peer !==
                                                            undefined
                                                        ) {
                                                            finalPeer =
                                                                modifiedAnswer.final_peer;
                                                            console.log(
                                                                "Menggunakan nilai yang dipilih user:",
                                                                finalPeer,
                                                                "untuk pertanyaan:",
                                                                answer.question_id
                                                            );
                                                        }
                                                    }

                                                    // Jika tidak ditemukan di jawaban yang dimodifikasi, coba periksa jawaban saat ini
                                                    if (
                                                        finalPeer === null &&
                                                        answer.final_peer !==
                                                        undefined
                                                    ) {
                                                        finalPeer =
                                                            answer.final_peer;
                                                        console.log(
                                                            "Menggunakan nilai final_peer yang ada:",
                                                            finalPeer
                                                        );
                                                    }
                                                    // Gunakan score sebagai fallback
                                                    else if (
                                                        finalPeer === null &&
                                                        answer.score
                                                    ) {
                                                        finalPeer =
                                                            answer.score;
                                                        console.log(
                                                            "Menggunakan nilai default (score):",
                                                            finalPeer
                                                        );
                                                    }

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
                                                                        console.log(
                                                                            "Found matching question in peer_assessment:",
                                                                            peerAspek
                                                                        );
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
                                                            console.log(
                                                                "Adding answer with criteriaId:",
                                                                criteriaId,
                                                                "and peerId:",
                                                                actualPeerId,
                                                                "final_score_peer:",
                                                                finalPeer
                                                            );

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
                    alert("Mohon pilih minimal satu skor untuk disimpan");
                    this.isLoading = false;
                    return;
                }

                console.log("Answers Data Peer to save:", answersPeer);

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

                const response = await axios.post(
                    "/api/report/save-final-scores-peer",
                    { answersPeer }
                );

                if (response.data.success) {
                    this.successMessage =
                        response.data.message || "Jawaban berhasil disimpan";
                    alert(this.successMessage);
                } else {
                    alert(
                        response.data.message ||
                        "Terjadi kesalahan saat menyimpan jawaban"
                    );
                }
            } catch (error) {
                console.error("Error saving answers:", error);

                // Penanganan error sederhana
                if (error.response) {
                    alert(
                        "Terjadi kesalahan pada server: " +
                        (error.response.data.message ||
                            error.response.statusText)
                    );
                } else if (error.request) {
                    alert(
                        "Tidak dapat terhubung ke server. Periksa koneksi internet Anda."
                    );
                } else {
                    alert("Terjadi kesalahan: " + error.message);
                }
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

            this.selectedUserData.self_assessment.forEach((aspek) => {
                if (!aspek.questions || !Array.isArray(aspek.questions)) {
                    return;
                }

                aspek.questions.forEach((pertanyaan) => {
                    // Cek jika pertanyaan memiliki nilai final_score_self dari server
                    if (pertanyaan.final_score_self) {
                        pertanyaan.final_score = pertanyaan.final_score_self;
                    } else if (
                        pertanyaan.report &&
                        pertanyaan.report.final_score_self
                    ) {
                        pertanyaan.final_score =
                            pertanyaan.report.final_score_self;
                    }
                    // Jika tidak ada nilai yang disimpan, gunakan skor default
                    else if (!pertanyaan.final_score) {
                        pertanyaan.final_score = pertanyaan.score;
                    }
                });
            });
        },

        setupFinalScoresPeer() {
            if (
                !this.selectedUserData ||
                !this.selectedUserData.peer_assessment
            ) {
                return;
            }

            this.selectedUserData.peer_assessment.forEach((peerGroup) => {
                if (
                    !peerGroup.questions ||
                    !Array.isArray(peerGroup.questions)
                ) {
                    return;
                }

                peerGroup.questions.forEach((answer) => {
                    // Cek jika pertanyaan memiliki nilai final_score_self dari server
                    if (answer.final_score_peer) {
                        answer.final_peer = answer.final_score_peer;
                    } else if (
                        answer.report &&
                        answer.report.final_score_peer
                    ) {
                        answer.final_peer = answer.report.final_score_peer;
                    }
                    // Jika tidak ada nilai yang disimpan, gunakan skor default
                    else if (!answer.final_peer) {
                        answer.final_peer = answer.score;
                    }
                });
            });
        },

        async fetchKelompokAnalysis() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get("/api/report/kelompok/answers", {
                    params: {
                        batch_year: this.batch_year,
                        project_name: this.project_name,
                        kelompok: this.kelompok,
                    },
                });
                console.log("Data dari API kelompok/answers:", response.data);
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
                    evaluator.answers.map((answer) => ({
                        ...answer,
                        evaluator_name: evaluator.name,
                        pertanyaan: this.getPeerQuestionText(
                            answer.question_id
                        ),
                    }))
                );

                return {
                    aspek: group.aspek,
                    kriteria: group.kriteria,
                    names: names,
                    total_score: group.total_score,
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

            const totalScores = userData.evaluated_by_peers.map(
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

            return userData.self_assessment.map((selfAspect) => {
                // Filter peer evaluations for matching aspect AND criteria
                const matchingPeerEvaluations =
                    userData.evaluated_by_peers.filter(
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
                    <Breadcrumb :items="[
                        { text: 'Report', href: '/dosen/report' },
                        { text: `${kelompok}`, href: '#' },
                    ]" />
                </div>

                <!-- Header Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <Card title="Detail Kelompok" class="bg-white shadow-sm border-0">
                        <div v-if="batch_year && project_name && kelompok" class="space-y-2">
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

                    <Card title="Pilih Peserta" class="bg-white shadow-sm border-0">
                        <div class="space-y-1">
                            <label for="peserta" class="block text-sm font-medium text-gray-600">Nama Peserta</label>
                            <select id="peserta" v-model="selectedUserId"
                                class="w-full p-2.5 bg-white border border-gray-300 text-gray-700 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                                <option value="" disabled>Pilih Peserta</option>
                                <option v-for="(userData, userId) in userAnalysis" :key="userId" :value="userId">
                                    {{ userData.name }}
                                </option>
                            </select>
                        </div>
                    </Card>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="flex justify-center items-center h-64">
                    <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md">
                    {{ error }}
                </div>

                <!-- Charts Section -->
                <div v-else-if="selectedUserData" class="space-y-6">
                    <!-- Peer Comparison Chart -->
                    <Card title="" class="bg-white shadow-sm border-0 p-0 overflow-hidden">
                        <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-100">
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
                            <ApexChart type="radar" height="400" :series="preparePeerComparisonChartData(
                                selectedUserData
                            ).series
                                " :options="preparePeerComparisonChartData(
                                    selectedUserData
                                ).options
                                    " />
                        </div>
                    </Card>

                    <!-- Self Assessment Chart -->
                    <Card title="" class="bg-white shadow-sm border-0 p-0 overflow-hidden">
                        <div class="p-4 bg-gradient-to-r from-rose-50 to-orange-50 border-b border-gray-100">
                            <h3 class="text-lg font-semibold text-gray-800">
                                Perbandingan Skor Self Assessment
                            </h3>
                            <p class="text-sm text-gray-600">
                                Menampilkan perbandingan skor evaluasi diri
                                dengan rata-rata skor seluruh kelompok
                            </p>
                        </div>
                        <div class="p-4">
                            <ApexChart type="radar" height="400" :series="prepareSelfComparisonChartData(
                                selectedUserData
                            ).series
                                " :options="prepareSelfComparisonChartData(
                                    selectedUserData
                                ).options
                                    " />
                        </div>
                    </Card>
                </div>

                <div v-else-if="!loading && !error && userIds.length > 0"
                    class="flex flex-col items-center justify-center h-64 bg-white rounded-lg shadow-sm border-0 p-6">
                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
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

                <div v-if="loading" class="flex justify-center items-center p-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                    <span class="ml-3 text-gray-600">Memuat...</span>
                </div>
                <div v-else-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md mt-4">
                    {{ error }}
                </div>
                <div v-else-if="selectedUserData">
                    <Card :title="`Analisis Jawaban - ${selectedUserData.name}`" class="mt-4">
                        <!-- Self Assessment Section -->
                        <div v-if="
                            selectedUserData.self_assessment &&
                            selectedUserData.self_assessment.length
                        ">
                            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                                <div
                                    class="bg-gradient-to-r from-blue-600 to-blue-700 p-4 flex justify-between items-center">
                                    <h3 class="text-white text-lg font-bold">
                                        Self Assessment
                                    </h3>
                                </div>
                                <!-- Tombol Simpan di Bagian Atas -->
                                <div class="mt-3 mb-4 flex justify-end">
                                    <button @click="saveAnswerSelf"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        :disabled="isLoading">
                                        <span v-if="isLoading">Menyimpan...</span>
                                        <span v-else>Simpan Semua Penilaian</span>
                                    </button>
                                </div>

                                <div class="p-2">
                                    <div v-for="(
aspek, index
                                        ) in selectedUserData.self_assessment" :key="index" class="mb-6 last:mb-0">
                                        <div class="bg-gray-50 p-4 rounded-lg">
                                            <!-- Aspek Header -->
                                            <div class="flex justify-between items-center mb-4">
                                                <div>
                                                    <h4 class="text-lg font-semibold text-gray-800">
                                                        {{ aspek.aspek }}
                                                    </h4>
                                                    <p class="text-sm text-gray-600">
                                                        {{ aspek.kriteria }}
                                                    </p>
                                                </div>
                                                <div class="text-right flex space-x-6">
                                                    <!-- Original Total Score -->
                                                    <div>
                                                        <div class="text-sm text-gray-600">
                                                            Total Skor Asli
                                                        </div>
                                                        <div class="text-2xl font-bold" :class="{
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
                                                        }">
                                                            {{
                                                                aspek.total_score
                                                                    ? aspek.total_score.toFixed(
                                                                        2
                                                                    )
                                                                    : "N/A"
                                                            }}
                                                        </div>
                                                    </div>

                                                    <!-- Final Total Score -->
                                                    <div>
                                                        <div class="text-sm text-gray-600">
                                                            Total Skor Final
                                                        </div>
                                                        <div class="text-2xl font-bold" :class="{
                                                            'text-green-600':
                                                                aspek.total_score_final >=
                                                                4,
                                                            'text-yellow-600':
                                                                aspek.total_score_final >=
                                                                3 &&
                                                                aspek.total_score_final <
                                                                4,
                                                            'text-red-600':
                                                                aspek.total_score_final <
                                                                2.5,
                                                        }">
                                                            {{ (aspek.total_score_final || aspek.total_score || 0).toFixed(2) }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Questions Table -->
                                            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                                <table class="w-full">
                                                    <thead>
                                                        <tr class="bg-gray-50 border-b border-gray-200">
                                                            <th
                                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/2">
                                                                Pertanyaan
                                                            </th>
                                                            <th
                                                                class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                                                                Skor
                                                            </th>
                                                            <th
                                                                class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                                                                Skor SLA
                                                            </th>
                                                            <th
                                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                                Jawaban
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-200">
                                                        <tr v-for="(
pertanyaan,
                                                                    qIndex
                                                            ) in aspek.questions" :key="qIndex"
                                                            class="hover:bg-gray-50 transition-colors">
                                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                                {{
                                                                    pertanyaan.pertanyaan
                                                                }}
                                                            </td>
                                                            <td class="px-4 py-3 text-center">
                                                                <div :class="{
                                                                    'bg-blue-100 p-2 rounded-md':
                                                                        pertanyaan.final_score ==
                                                                        pertanyaan.score,
                                                                }">
                                                                    <input type="radio" :name="'score_' +
                                                                        pertanyaan.question_id
                                                                        " :value="pertanyaan.score
                                                                            " v-model="pertanyaan.final_score
                                                                            "
                                                                        class="border-2 border-gray-300 rounded-md hover:border-blue-500"
                                                                        @change="
                                                                            aspek.total_score_final =
                                                                            calculateTotalScoreFinal(
                                                                                aspek
                                                                            )
                                                                            " />
                                                                    <span :class="{
                                                                        'font-medium':
                                                                            pertanyaan.final_score ==
                                                                            pertanyaan.score,
                                                                    }">
                                                                        {{
                                                                            pertanyaan.score ||
                                                                            "N/A"
                                                                        }}
                                                                    </span>
                                                                </div>
                                                            </td>
                                                            <td class="px-4 py-3 text-center">
                                                                <div :class="{
                                                                    'bg-blue-100 p-2 rounded-md':
                                                                        pertanyaan.final_score ==
                                                                        pertanyaan.score_SLA,
                                                                }">
                                                                    <input type="radio" :name="'score_' +
                                                                        pertanyaan.question_id
                                                                        " :value="pertanyaan.score_SLA
                                                                            " v-model="pertanyaan.final_score
                                                                            "
                                                                        class="border-2 border-gray-300 rounded-md hover:border-blue-500"
                                                                        @change="
                                                                            aspek.total_score_final =
                                                                            calculateTotalScoreFinal(
                                                                                aspek
                                                                            )
                                                                            " />
                                                                    <span :class="{
                                                                        'font-medium':
                                                                            pertanyaan.final_score ==
                                                                            pertanyaan.score_SLA,
                                                                    }">
                                                                        {{
                                                                            pertanyaan.score_SLA ||
                                                                            "N/A"
                                                                        }}
                                                                    </span>
                                                                </div>
                                                            </td>
                                                            <td class="px-4 py-3 text-sm text-gray-500">
                                                                <div class="max-w-xl">
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
                            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                                <div
                                    class="bg-gradient-to-r from-purple-600 to-purple-700 p-4 flex justify-between items-center">
                                    <h3 class="text-white text-lg font-bold">
                                        Evaluasi dari Peer
                                    </h3>
                                    <div class="bg-white bg-opacity-20 rounded-lg px-4 py-2">
                                        <span class="text-white text-sm">Total Average:
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
                                    <button @click="saveAnswerPeer"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        :disabled="isLoading">
                                        <span v-if="isLoading">Menyimpan...</span>
                                        <span v-else>Simpan Semua Penilaian</span>
                                    </button>
                                </div>

                                <div class="p-4">
                                    <div v-if="
                                        selectedUserData.evaluated_by_peers &&
                                        selectedUserData.evaluated_by_peers
                                            .length
                                    ">
                                        <div v-for="(
peerGroup, index
                                            ) in groupPeerEvaluations(
                                                    selectedUserData.evaluated_by_peers
                                                )" :key="index" class="mb-6 last:mb-0">
                                            <div class="bg-gray-50 p-4 rounded-lg">
                                                <!-- Peer Group Header -->
                                                <div
                                                    class="flex flex-wrap md:flex-nowrap justify-between items-start gap-4 mb-4">
                                                    <div>
                                                        <h4 class="text-lg font-semibold text-gray-800">
                                                            {{
                                                                peerGroup.aspek
                                                            }}
                                                        </h4>
                                                        <p class="text-sm text-gray-600">
                                                            {{
                                                                peerGroup.kriteria
                                                            }}
                                                        </p>
                                                        <div class="mt-2 flex flex-wrap gap-2">
                                                            <span v-for="(
name,
                                                                        nameIdx
                                                                ) in peerGroup.names" :key="nameIdx"
                                                                class="inline-flex items-center px-3py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                                {{ name }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="text-right flex-shrink-0">
                                                        <div class="text-sm text-gray-600">
                                                            Total Skor
                                                        </div>
                                                        <div class="text-2xl font-bold" :class="{
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
                                                        }">
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
                                                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                                    <table class="w-full">
                                                        <thead>
                                                            <tr class="bg-gray-50 border-b border-gray-200">
                                                                <th
                                                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                                    Penilai
                                                                </th>
                                                                <th
                                                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                                    Pertanyaan
                                                                </th>
                                                                <th
                                                                    class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                                                                    Skor
                                                                </th>
                                                                <th
                                                                    class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                                                                    Skor SLA
                                                                </th>
                                                                <th
                                                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                                    Jawaban
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-200">
                                                            <tr v-for="(
answer, idx
                                                                ) in peerGroup.answers" :key="idx"
                                                                class="hover:bg-gray-50 transition-colors">
                                                                <td class="px-4 py-3">
                                                                    <div class="text-sm font-medium text-gray-900">
                                                                        {{
                                                                            answer.evaluator_name
                                                                        }}
                                                                    </div>
                                                                </td>
                                                                <td class="px-4 py-3">
                                                                    <div class="text-sm text-gray-900">
                                                                        {{
                                                                            answer.pertanyaan
                                                                        }}
                                                                    </div>
                                                                </td>
                                                                <td class="px-4 py-3 text-center">
                                                                    <div :class="{
                                                                        'p-2 rounded-md':
                                                                            answer.final_peer ==
                                                                            answer.score,
                                                                    }">
                                                                        <input type="radio" :name="'score_' +
                                                                            answer.question_id +
                                                                            '_' +
                                                                            answer.evaluator_name
                                                                            " :value="answer.score
                                                                                " v-model="answer.final_peer
                                                                                "
                                                                            class="border-2 border-gray-300 rounded-md hover:border-blue-500"
                                                                            @change="
                                                                                updateFinalPeer(
                                                                                    answer,
                                                                                    answer.score
                                                                                )
                                                                                " />
                                                                        <span :class="{
                                                                            'font-medium':
                                                                                answer.final_peer ==
                                                                                answer.score,
                                                                        }">
                                                                            {{
                                                                                answer.score ||
                                                                                "N/A"
                                                                            }}
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                                <td class="px-4 py-3 text-center">
                                                                    <div :class="{
                                                                        'p-2 rounded-md':
                                                                            answer.final_peer ==
                                                                            answer.score_SLA,
                                                                    }">
                                                                        <input type="radio" :name="'score_' +
                                                                            answer.question_id +
                                                                            '_' +
                                                                            answer.evaluator_name
                                                                            " :value="answer.score_SLA
                                                                                " v-model="answer.final_peer
                                                                                "
                                                                            class="border-2 border-gray-300 rounded-md hover:border-blue-500"
                                                                            @change="
                                                                                updateFinalPeer(
                                                                                    answer,
                                                                                    answer.score_SLA
                                                                                )
                                                                                " />
                                                                        <span :class="{
                                                                            'font-medium':
                                                                                answer.final_peer ==
                                                                                answer.score_SLA,
                                                                        }">
                                                                            {{
                                                                                answer.score_SLA ||
                                                                                "N/A"
                                                                            }}
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                                <td class="px-4 py-3">
                                                                    <div class="text-sm text-gray-500 max-w-xl">
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
                                    <p v-else class="text-center text-gray-500 p-4">
                                        Tidak ada data evaluasi peer
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Analysis Score Section -->
                        <div class="mt-6">
                            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                                <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 p-4">
                                    <h3 class="text-white text-lg font-bold">
                                        Analysis Score
                                    </h3>
                                </div>

                                <div class="p-4">
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <!-- Score Summary Cards -->
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                            <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                                                <div class="text-sm font-medium text-green-600 mb-1">
                                                    Over Estimation
                                                </div>
                                                <div class="text-2xl font-bold text-green-700">
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
                                                <div class="text-xs text-green-600 mt-1">
                                                    Aspects where self-score
                                                    More Then peer-score
                                                </div>
                                            </div>

                                            <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                                                <div class="text-sm font-medium text-yellow-600 mb-1">
                                                    Matched Estimation
                                                </div>
                                                <div class="text-2xl font-bold text-yellow-700">
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
                                                <div class="text-xs text-yellow-600 mt-1">
                                                    Aspects where self-score
                                                    Same peer-score
                                                </div>
                                            </div>

                                            <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                                                <div class="text-sm font-medium text-red-600 mb-1">
                                                    Under Estimation
                                                </div>
                                                <div class="text-2xl font-bold text-red-700">
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
                                                <div class="text-xs text-red-600 mt-1">
                                                    Aspects where self-score
                                                    Less Then peer-score
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Detailed Analysis Table -->
                                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                            <table class="w-full">
                                                <thead>
                                                    <tr class="bg-gray-50 border-b border-gray-200">
                                                        <th
                                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Aspek
                                                        </th>
                                                        <th
                                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Kriteria
                                                        </th>
                                                        <th
                                                            class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Self Score
                                                        </th>
                                                        <th
                                                            class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Peer Score
                                                        </th>
                                                        <th
                                                            class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Selisih
                                                        </th>
                                                        <th
                                                            class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Status
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200">
                                                    <tr v-for="(
score, index
                                                        ) in calculateAnalysisScores(
                                                                selectedUserData
                                                            )" :key="index" class="hover:bg-gray-50 transition-colors">
                                                        <td class="px-4 py-3">
                                                            <div class="text-sm font-medium text-gray-900">
                                                                {{
                                                                    score.aspek
                                                                }}
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <div class="text-sm text-gray-500">
                                                                {{
                                                                    score.kriteria
                                                                }}
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 text-center">
                                                            <div class="text-sm font-semibold text-gray-900">
                                                                {{
                                                                    score.selfScore
                                                                }}
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 text-center">
                                                            <div class="text-sm font-semibold text-gray-900">
                                                                {{
                                                                    score.averagePeerScore
                                                                }}
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 text-center">
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
                                                                }">
                                                                {{
                                                                    score.scoreDifference >=
                                                                        0
                                                                        ? "+" +
                                                                        score.scoreDifference
                                                                        : score.scoreDifference
                                                                }}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-3 text-center">
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
                                                                }">
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
                                        <div class="mt-4 flex flex-wrap gap-4 text-xs text-gray-500">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="w-3 h-3 inline-block bg-green-100 rounded-full border border-green-200"></span>
                                                <span>Over: Self score lebih
                                                    tinggi dari peer score</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="w-3 h-3 inline-block bg-yellow-100 rounded-full border border-yellow-200"></span>
                                                <span>Match: Self score sama
                                                    dengan peer score</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="w-3 h-3 inline-block bg-red-100 rounded-full border border-red-200"></span>
                                                <span>Under: Self score lebih
                                                    rendah dari peer score</span>
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
</style>
