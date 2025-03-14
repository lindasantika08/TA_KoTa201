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
    mounted() {
        if (this.batch_year && this.project_name && this.kelompok) {
            this.fetchPeerQuestions();
            this.fetchKelompokAnalysis();
        } else {
            this.error = "Tahun Ajaran, Nama Proyek, atau Kelompok tidak valid";
        }
    },
    methods: {
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
                            show: true,
                        },
                    },
                    labels: analysisScores.map(
                        (score) => `${score.aspek} - ${score.kriteria}`
                    ),
                    plotOptions: {
                        radar: {
                            polygons: {
                                strokeColors: "#e8e8e8",
                                fill: {
                                    colors: ["#f7f7f7", "#fff"],
                                },
                            },
                        },
                    },
                    colors: ["#FF4560", "#00E396"],
                    markers: {
                        size: 6,
                        colors: ["#FF4560", "#00E396"],
                        strokeColors: "#fff",
                        strokeWidth: 2,
                        hover: {
                            size: 9,
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
                                '<div class="apexcharts-tooltip-title" style="font-size: 14px;">' +
                                w.config.labels[dataPointIndex] +
                                "</div>" +
                                '<div style="color: #FF4560; font-size: 14px;">Skor Sendiri: ' +
                                score.selfScore +
                                "</div>" +
                                '<div style="color: #00E396; font-size: 14px;">Rata-rata Peer: ' +
                                score.averagePeerScore +
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
                        },
                    },
                    xaxis: {
                        show: true,
                        labels: {
                            style: {
                                fontSize: "12px",
                            },
                        },
                    },
                    fill: {
                        opacity: 0.4,
                    },
                    title: {
                        text: "Perbandingan Skor",
                        align: "center",
                        style: {
                            fontSize: "16px",
                            fontWeight: "bold",
                        },
                    },
                    legend: {
                        position: "bottom",
                        horizontalAlign: "center",
                        markers: {
                            width: 16,
                            height: 16,
                        },
                        itemMargin: {
                            horizontal: 10,
                            vertical: 10,
                        },
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function (val) {
                            return val.toFixed(2);
                        },
                        style: {
                            fontSize: "12px",
                            colors: ["#FF4560", "#00E396"],
                        },
                    },
                    grid: {
                        show: true,
                        strokeDashArray: 2,
                        borderColor: "#e8e8e8",
                    },
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

            const labels = userData.self_assessment
                ? userData.self_assessment.map(
                      (aspect) => `${aspect.aspek} - ${aspect.kriteria}`
                  )
                : [];

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
                            show: true,
                        },
                    },
                    labels: labels,
                    plotOptions: {
                        radar: {
                            polygons: {
                                strokeColors: "#e8e8e8",
                                fill: {
                                    colors: ["#f7f7f7", "#fff"],
                                },
                            },
                        },
                    },
                    colors: ["#FF4560", "#3B82F6"],
                    markers: {
                        size: 6,
                        colors: ["#FF4560", "#3B82F6"],
                        strokeColors: "#fff",
                        strokeWidth: 2,
                        hover: {
                            size: 9,
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
                            const aspect =
                                userData.self_assessment[dataPointIndex];
                            return (
                                '<div class="apexcharts-tooltip-title" style="font-size: 14px;">' +
                                w.config.labels[dataPointIndex] +
                                "</div>" +
                                '<div style="color: #FF4560; font-size: 14px;">Skor Sendiri: ' +
                                aspect.total_score.toFixed(2) +
                                "</div>" +
                                '<div style="color: #3B82F6; font-size: 14px;">Rata-rata Self Kelompok: ' +
                                averageSelfScores[dataPointIndex] +
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
                        },
                    },
                    xaxis: {
                        show: true,
                        labels: {
                            style: {
                                fontSize: "12px",
                            },
                        },
                    },
                    fill: {
                        opacity: 0.4,
                    },
                    title: {
                        text: "Perbandingan Skor Self Assessment",
                        align: "center",
                        style: {
                            fontSize: "16px",
                            fontWeight: "bold",
                        },
                    },
                    legend: {
                        position: "bottom",
                        horizontalAlign: "center",
                        markers: {
                            width: 16,
                            height: 16,
                        },
                        itemMargin: {
                            horizontal: 10,
                            vertical: 10,
                        },
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function (val) {
                            return val.toFixed(2);
                        },
                        style: {
                            fontSize: "12px",
                            colors: ["#FF4560", "#3B82F6"],
                        },
                    },
                    grid: {
                        show: true,
                        strokeDashArray: 2,
                        borderColor: "#e8e8e8",
                    },
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
                    <Breadcrumb
                        :items="[
                            { text: 'Report', href: '/dosen/report' },
                            { text: `${kelompok}`, href: '#' },
                        ]"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
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
                        <select
                            v-model="selectedUserId"
                            class="w-full p-2 border rounded"
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
                    </Card>

                    <Card
                        v-if="selectedUserData"
                        title="Perbandingan Skor Peer"
                        class="col-span-2"
                    >
                        <ApexChart
                            type="radar"
                            height="350"
                            :series="
                                preparePeerComparisonChartData(selectedUserData)
                                    .series
                            "
                            :options="
                                preparePeerComparisonChartData(selectedUserData)
                                    .options
                            "
                        />
                    </Card>

                    <Card
                        v-if="selectedUserData"
                        title="Perbandingan Skor Self Assessment"
                        class="col-span-2"
                    >
                        <ApexChart
                            type="radar"
                            height="350"
                            :series="
                                prepareSelfComparisonChartData(selectedUserData)
                                    .series
                            "
                            :options="
                                prepareSelfComparisonChartData(selectedUserData)
                                    .options
                            "
                        />
                    </Card>
                </div>

                <div v-if="loading" class="text-center mt-4">Memuat...</div>
                <div v-else-if="error" class="text-red-500 mt-4">
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
                                    class="bg-gradient-to-r from-blue-600 to-blue-700 p-4"
                                >
                                    <h3 class="text-white text-lg font-bold">
                                        Self Assessment
                                    </h3>
                                </div>

                                <div class="p-4">
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
                                                                <span
                                                                    class="inline-flex items-center justify-center px-3py-1 rounded-full text-xs font-medium"
                                                                    :class="{
                                                                        'bg-green-100 text-green-800':
                                                                            pertanyaan.score >=
                                                                            4,
                                                                        'bg-yellow-100 text-yellow-800':
                                                                            pertanyaan.score >=
                                                                                3 &&
                                                                            pertanyaan.score <
                                                                                4,
                                                                        'bg-red-100 text-red-800':
                                                                            pertanyaan.score <
                                                                            2.5,
                                                                        'bg-gray-100 text-gray-800':
                                                                            !pertanyaan.score,
                                                                    }"
                                                                >
                                                                    {{
                                                                        pertanyaan.score ||
                                                                        "N/A"
                                                                    }}
                                                                </span>
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
                                                    <table class="w-full">
                                                        <thead>
                                                            <tr
                                                                class="bg-gray-50 border-b border-gray-200"
                                                            >
                                                                <th
                                                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                                >
                                                                    Penilai
                                                                </th>
                                                                <th
                                                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                                >
                                                                    Pertanyaan
                                                                </th>
                                                                <th
                                                                    class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24"
                                                                >
                                                                    Skor
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
                                                                    class="px-4 py-3"
                                                                >
                                                                    <div
                                                                        class="text-sm font-medium text-gray-900"
                                                                    >
                                                                        {{
                                                                            answer.evaluator_name
                                                                        }}
                                                                    </div>
                                                                </td>
                                                                <td
                                                                    class="px-4 py-3"
                                                                >
                                                                    <div
                                                                        class="text-sm text-gray-900"
                                                                    >
                                                                        {{
                                                                            answer.pertanyaan
                                                                        }}
                                                                    </div>
                                                                </td>
                                                                <td
                                                                    class="px-4 py-3 text-center"
                                                                >
                                                                    <span
                                                                        class="inline-flex items-center justify-center px-3py-1 rounded-full text-xs font-medium"
                                                                        :class="{
                                                                            'bg-green-100 text-green-800':
                                                                                answer.score >=
                                                                                4,
                                                                            'bg-yellow-100 text-yellow-800':
                                                                                answer.score >=
                                                                                    3 &&
                                                                                answer.score <
                                                                                    4,
                                                                            'bg-red-100 text-red-800':
                                                                                answer.score <
                                                                                2.5,
                                                                            'bg-gray-100 text-gray-800':
                                                                                !answer.score,
                                                                        }"
                                                                    >
                                                                        {{
                                                                            answer.score ||
                                                                            "N/A"
                                                                        }}
                                                                    </span>
                                                                </td>
                                                                <td
                                                                    class="px-4 py-3"
                                                                >
                                                                    <div
                                                                        class="text-sm text-gray-500 max-w-xl"
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
</style>
