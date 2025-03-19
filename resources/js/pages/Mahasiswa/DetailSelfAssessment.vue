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
                { text: "Self Assessment", href: "/sispa/mahasiswa/assessment/self" },
                { text: "Detail", href: null },
            ],
            headers: [
                { key: "no", label: "No" },
                { key: "pertanyaan", label: "Pertanyaan" },
                { key: "skala", label: "Skala" },
                { key: "alasan", label: "Alasan" },
            ],
            studentInfo: {
                nim: "",
                name: "",
                class: "",
                group: "",
                project: "",
                date: "",
            },
            groupedAnswers: {}
        };
    },
    methods: {
        async fetchUserInfo() {
            try {
                const response = await axios.get("/api/user-detail-answer", {
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
        async fetchAnswerSelf() {
    try {
        const response = await axios.get("/api/detail-answer-self", {
            params: {
                batch_year: this.batchYear,
                project_name: this.projectName
            }
        });
        this.groupedAnswers = response.data.answers.reduce((acc, aspect) => {
            acc[aspect.aspect] = aspect;
            return acc;
        }, {});
    } catch (error) {
        console.error("Error fetching answers:", error);
    }
},
getScaleColor(scale) {
            const scaleNum = parseInt(scale);
            if (scaleNum >= 4) return 'bg-green-100 text-green-800';
            if (scaleNum >= 3) return 'bg-blue-100 text-blue-800';
            if (scaleNum >= 2) return 'bg-yellow-100 text-yellow-800';
            return 'bg-red-100 text-red-800';
        },
    },
    created() {
        this.fetchUserInfo();
        this.fetchAnswerSelf();
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
                
                <!-- Main Card -->
                <Card class="shadow-lg">
                    <template #title>
                        <div class="flex items-center space-x-2 text-blue-700">
                            <h1 class="text-2xl font-bold">HASIL PENGISIAN SELF ASSESSMENT</h1>
                        </div>
                    </template>

                    <!-- Student Info Section -->
                    <div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">Informasi Mahasiswa</h2>
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

                    <!-- Assessment Results Section -->
                    <div v-for="(aspectData, aspectName) in groupedAnswers" 
                         :key="aspectName" 
                         class="mb-6 bg-white rounded-lg shadow-sm">
                        <Card class="border-none shadow-none">
                            <template #title>
                                <div class="flex items-center space-x-2 bg-blue-50 p-3 rounded-t-lg border-b">
                                    <h2 class="text-lg font-semibold text-blue-700">
                                        Aspek: {{ aspectName }}
                                    </h2>
                                </div>
                            </template>
                            
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600 w-16">No</th>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Pertanyaan</th>
                                            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-600 w-24">Skala</th>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Alasan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(answer, index) in aspectData.answers" 
                                            :key="index"
                                            class="border-t hover:bg-gray-50">
                                            <td class="px-4 py-3 text-gray-600">{{ index + 1 }}</td>
                                            <td class="px-4 py-3 text-gray-800">{{ answer.question }}</td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-sm font-medium"
                                                      :class="getScaleColor(answer.scale)">
                                                    {{ answer.scale }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-gray-700">{{ answer.reason }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </Card>
                    </div>
                </Card>
            </main>
        </div>
    </div>
</template>