<script>
import axios from "axios";
import SidebarMahasiswa from "@/Components/SidebarMahasiswa.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import DataTable from "@/Components/DataTable.vue";

export default {
    props: {
        batchYear: {
            type: String,
            required: true
        },
        projectName: {
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
        DataTable,
    },

    data() {
        return {
            breadcrumbs: [
                { text: "Peer Assessment", href: '/mahasiswa/assessment/peer' },
                { text: "Detail", href: null },
            ],
            headers: [
                { key: 'no', label: 'No' },
                { key: 'aspek', label: 'Aspek' },
                { key: 'pertanyaan', label: 'Pertanyaan' },
                { key: 'peer', label: 'Peer' },
                { key: 'skala', label: 'Skala' },
                { key: 'alasan', label: 'Alasan' },
            ],
            studentInfo: {
                nim: "",
                name: "",
                class: "",
                group: "",
                project: "",
                date: "",
            },
            items: [],
        };
    },
    methods: {
        async fetchUserInfo() {
            try {
                const response = await axios.get("/sispa/api/user-detail-answer", {
                    params: {
                        batch_year: this.batchYear,
                        project_name: this.projectName,
                    }
                });
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
                    }
                });

                this.items = response.data.answers.map((answer, index) => ({
                    no: index + 1,
                    aspek: answer.aspect || 'N/A',
                    pertanyaan: answer.question || 'N/A',
                    peer: answer.peer_name || 'N/A',
                    skala: answer.scale,
                    alasan: answer.reason || 'N/A'
                }));
            } catch (error) {
                console.error("Error fetching peer assessment:", error);
            }
        }
    },
    created() {
        this.fetchUserInfo();
        this.fetchPeerAssessment();
    }
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
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
                            <h1 class="text-2xl font-bold">HASIL PENGISIAN PEER ASSESSMENT</h1>
                        </div>
                    </template>

                    <div class="bg-white rounded-lg p-6 mb-6">
                        <h2 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">
                            Informasi Mahasiswa
                        </h2>
                        <div class="grid grid-cols-2 gap-8">
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <span class="text-gray-600 font-medium w-32">NIM</span>
                                    <span class="text-gray-800">: {{ studentInfo.nim }}</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-gray-600 font-medium w-32">Nama Lengkap</span>
                                    <span class="text-gray-800">: {{ studentInfo.name }}</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-gray-600 font-medium w-32">Kelas</span>
                                    <span class="text-gray-800">: {{ studentInfo.class }}</span>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <span class="text-gray-600 font-medium w-32">Kelompok</span>
                                    <span class="text-gray-800">: {{ studentInfo.group }}</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-gray-600 font-medium w-32">Proyek</span>
                                    <span class="text-gray-800">: {{ studentInfo.project }}</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-gray-600 font-medium w-32">Tanggal</span>
                                    <span class="text-gray-800">: {{ studentInfo.date }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm">
                        <DataTable 
                            :headers="headers" 
                            :items="items"
                            class="w-full"
                        >
                            <template #cell-skala="{ item }">
                                <span class="px-2 py-1 rounded-full text-sm font-medium">
                                    {{ item.skala }}
                                </span>
                            </template>
                        </DataTable>
                    </div>
                </Card>
            </main>
        </div>
    </div>
</template>