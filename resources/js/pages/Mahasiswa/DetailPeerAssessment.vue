<script>
import axios from "axios";
import SidebarMahasiswa from "@/Components/SidebarMahasiswa.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

export default {
    props: {
        batchYear: {
            type: String,
            required: true,
        },
        projectName: {
            type: String,
            required: true
        },
        assessmentOrder: {
            type: String,
            required: true
        }
    },
    name: "PeerAssessmentDetail",
    components: {
        SidebarMahasiswa,
        Navbar,
        Card,
        Breadcrumb,
    },
// test
    data() {
        return {
            breadcrumbs: [
                {
                    text: "Peer Assessment",
                    href: "/sispa/mahasiswa/assessment/peer",
                },
                { text: "Detail", href: null },
            ],
            studentInfo: {
                nim: "",
                name: "",
                class: "",
                group: "",
                project: "",
                date: "",
            },
            peerAssessments: {},
            collapsedCards: {},
        };
    },
    methods: {
        async fetchUserInfo() {
            try {
                const response = await axios.get(
                    "/sispa/api/user-detail-answer",
                    {
                        params: {
                            batch_year: this.batchYear,
                            project_name: this.projectName,
                        },
                    }
                );
                this.studentInfo = response.data;
            } catch (error) {
                console.error("Error fetching user info:", error);
            }
        },
        async fetchPeerAssessment() {
            try {
                const response = await axios.get("/sispa/api/peer-assessment-detail", {
                    params: {
                        batch_year: this.batchYear,
                        project_name: this.projectName,
                        assessment_order: this.assessmentOrder
                    }
                });
                const response = await axios.get(
                    "/sispa/api/peer-assessment-detail",
                    {
                        params: {
                            batch_year: this.batchYear,
                            project_name: this.projectName,
                        },
                    }
                );

                // Group assessments by peer name
                const groupedByPeer = {};
                response.data.answers.forEach((answer) => {
                    const peerName = answer.peer_name || "Unknown";
                    if (!groupedByPeer[peerName]) {
                        groupedByPeer[peerName] = [];
                    }
                    groupedByPeer[peerName].push(answer);
                });

                this.peerAssessments = groupedByPeer;

                // Initialize all cards as expanded
                Object.keys(groupedByPeer).forEach((peerName) => {
                    this.collapsedCards[peerName] = false;
                });
            } catch (error) {
                console.error("Error fetching peer assessment:", error);
            }
        },
        toggleCard(peerName) {
            this.collapsedCards[peerName] = !this.collapsedCards[peerName];
        },
    },
    created() {
        this.fetchUserInfo();
        this.fetchPeerAssessment();
    },
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <SidebarMahasiswa role="mahasiswa" />

        <div class="flex-1">
            <Navbar userName="Mahasiswa" />
            <main class="p-6">
                <div class="mb-4">
                    <Breadcrumb :items="breadcrumbs" />
                </div>

                <Card class="mb-6">
                    <template #title>
                        <div class="flex items-center space-x-2 text-blue-700">
                            <h1 class="text-2xl font-bold">
                                HASIL PENGISIAN PEER ASSESSMENT
                            </h1>
                        </div>
                    </template>

                    <!-- Student Information Card -->
                    <div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
                        <h2
                            class="text-lg font-semibold text-blue-700 mb-4 border-b pb-2"
                        >
                            Informasi Mahasiswa
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <span class="text-gray-600 font-medium w-32"
                                        >NIM</span
                                    >
                                    <span class="text-gray-800"
                                        >: {{ studentInfo.nim }}</span
                                    >
                                </div>
                                <div class="flex items-center">
                                    <span class="text-gray-600 font-medium w-32"
                                        >Nama Lengkap</span
                                    >
                                    <span class="text-gray-800"
                                        >: {{ studentInfo.name }}</span
                                    >
                                </div>
                                <div class="flex items-center">
                                    <span class="text-gray-600 font-medium w-32"
                                        >Kelas</span
                                    >
                                    <span class="text-gray-800"
                                        >: {{ studentInfo.class }}</span
                                    >
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <span class="text-gray-600 font-medium w-32"
                                        >Kelompok</span
                                    >
                                    <span class="text-gray-800"
                                        >: {{ studentInfo.group }}</span
                                    >
                                </div>
                                <div class="flex items-center">
                                    <span class="text-gray-600 font-medium w-32"
                                        >Proyek</span
                                    >
                                    <span class="text-gray-800"
                                        >: {{ studentInfo.project }}</span
                                    >
                                </div>
                                <div class="flex items-center">
                                    <span class="text-gray-600 font-medium w-32"
                                        >Tanggal</span
                                    >
                                    <span class="text-gray-800"
                                        >: {{ studentInfo.date }}</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Assessment Summary -->
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-blue-700 mb-4">
                            Ringkasan Assessment ({{
                                Object.keys(peerAssessments).length
                            }}
                            Peer)
                        </h2>
                    </div>

                    <!-- Individual Peer Cards -->
                    <div class="space-y-6">
                        <div
                            v-for="(
                                assessments, peerName, index
                            ) in peerAssessments"
                            :key="index"
                            class="bg-white rounded-lg shadow-sm overflow-hidden"
                        >
                            <div
                                @click="toggleCard(peerName)"
                                class="bg-blue-50 p-4 border-b border-blue-100 flex justify-between items-center cursor-pointer hover:bg-blue-100 transition-colors"
                            >
                                <h3 class="font-medium text-lg text-blue-800">
                                    Peer: {{ peerName }} ({{
                                        assessments.length
                                    }}
                                    penilaian)
                                </h3>
                                <button
                                    class="text-blue-700 focus:outline-none"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5 transition-transform duration-200"
                                        :class="{
                                            'transform rotate-180':
                                                !collapsedCards[peerName],
                                        }"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </div>

                            <div
                                v-if="!collapsedCards[peerName]"
                                class="divide-y divide-gray-100"
                            >
                                <div
                                    v-for="(assessment, idx) in assessments"
                                    :key="idx"
                                    class="p-4 hover:bg-gray-50 transition-colors"
                                >
                                    <div
                                        class="grid grid-cols-1 lg:grid-cols-4 gap-4"
                                    >
                                        <div>
                                            <h4
                                                class="text-sm font-semibold text-gray-500"
                                            >
                                                Aspek
                                            </h4>
                                            <p class="text-gray-800">
                                                {{ assessment.aspect || "N/A" }}
                                            </p>
                                        </div>
                                        <div class="lg:col-span-2">
                                            <h4
                                                class="text-sm font-semibold text-gray-500"
                                            >
                                                Pertanyaan
                                            </h4>
                                            <p class="text-gray-800">
                                                {{
                                                    assessment.question || "N/A"
                                                }}
                                            </p>
                                        </div>
                                        <div>
                                            <div class="flex justify-between">
                                                <div>
                                                    <h4
                                                        class="text-sm font-semibold text-gray-500"
                                                    >
                                                        Skala
                                                    </h4>
                                                    <div class="mt-1">
                                                        <span
                                                            class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium"
                                                        >
                                                            {{
                                                                assessment.scale
                                                            }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-3">
                                                <h4
                                                    class="text-sm font-semibold text-gray-500"
                                                >
                                                    Alasan
                                                </h4>
                                                <p
                                                    class="text-gray-800 text-sm mt-1"
                                                >
                                                    {{
                                                        assessment.reason ||
                                                        "N/A"
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                v-else
                                class="px-4 py-3 text-center text-sm text-gray-500 italic"
                            >
                                Klik untuk menampilkan detail penilaian
                            </div>
                        </div>

                        <!-- Empty state if no assessments -->
                        <div
                            v-if="Object.keys(peerAssessments).length === 0"
                            class="bg-white rounded-lg p-8 text-center shadow-sm"
                        >
                            <p class="text-gray-500">
                                Belum ada data peer assessment yang tersedia.
                            </p>
                        </div>
                    </div>
                </Card>
            </main>
        </div>
    </div>
</template>
