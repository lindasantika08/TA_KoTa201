<script>
import axios from "axios";
import SidebarMahasiswa from "@/Components/SidebarMahasiswa.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import DataTable from "@/Components/DataTable.vue";

export default {
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
                { text: "Self Assessment", href: '/mahasiswa/assessment/self' },
                { text: "Detail", href: null },
            ],
            headers: [
                { key: 'no', label: 'No' },
                { key: 'pertanyaan', label: 'Pertanyaan' },
                { key: 'skala', label: 'Skala' },
                { key: 'alasan', label: 'Alasan' },
            ],
            studentInfo: {
                nim: '',
                name: '',
                class: '',
                group: '',
                project: '',
                date: ''
            },
            groupedAnswers: {}
        }
    },
    methods: {
        async fetchUserInfo() {
            try {
                const response = await axios.get('/api/user-detail-answer');
                this.studentInfo = response.data;
            } catch (error) {
                console.error('Error fetching user info:', error);
            }
        },
        async fetchAnswerSelf() {
            try {
                const response = await axios.get('/api/detail-answer-self');
                this.groupedAnswers = response.data.answers.reduce((acc, aspect) => {
                    acc[aspect.aspect] = aspect;
                    return acc;
                }, {});
            } catch (error) {
                console.error('Error fetching answers:', error);
            }
        }
    },
    created() {
        this.fetchUserInfo();
        this.fetchAnswerSelf();
    }
};
</script>

<template>
    <div class="flex min-h-screen">
        <SidebarMahasiswa role="mahasiswa" />

        <div class="flex-1">
            <Navbar userName="Mahasiswa" />
            <main class="p-6">
                <div class="mb-4">
                    <Breadcrumb :items="breadcrumbs" />
                </div>
                <Card title="HASIL PENGISIAN SELF ASSESSMENT">
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

                <div v-for="(aspectData, aspectName) in groupedAnswers" :key="aspectName" class="mb-6">
                    <Card :title="`Aspek: ${aspectName}`">
                        <DataTable 
                            :headers="headers" 
                            :items="aspectData.answers.map((answer, index) => ({
                                no: index + 1,
                                pertanyaan: answer.question,
                                skala: answer.scale,
                                alasan: answer.reason
                            }))"
                        />
                    </Card>
                </div>
            </Card>
            </main>
        </div>
    </div>
</template>