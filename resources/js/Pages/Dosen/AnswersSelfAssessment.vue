<template>
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <Sidebar role="dosen" />

        <!-- Main Content -->
        <div class="flex-1">
            <!-- Navbar -->
            <Navbar userName="Dosen" />

            <!-- Content -->
            <main class="p-6">
                <!-- Card utama -->
                <Card :title="`Answer Self Assessment - ${namaProyek} (${tahunAjaran})`">
                    <template #actions>
                        <div class="flex justify-end"></div>
                    </template>

                    <!-- Bagian Konten Jawaban -->
                    <div class="mt-6 space-y-8">
                        <div class="bg-white p-6 shadow rounded-md">
                            <h1 class="text-2xl font-semibold mb-4">List Answers Self Assessment</h1>

                            <!-- Tampilkan informasi kosong jika tidak ada jawaban -->
                            <div v-if="answers.length === -1" class="text-center text-gray-500 py-6">
                                Jawaban belum tersedia.
                            </div>
                            <div v-else>
                                <DataTable :headers="headers" :items="answers">
                                    <template #column-actions="{ item }">
                                        <button
                                            @click="handleDetail(item)"
                                            class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                        >
                                            <font-awesome-icon icon="fa-solid fa-eye" class="mr-2" />
                                            Detail
                                        </button>
                                    </template>

                                    <template #column-status="{ item }">
                                        <span
                                            :class="[ 
                                                'px-2 py-1 rounded-full text-xs font-medium', 
                                                item.status === 'aktif' 
                                                  ? 'bg-green-100 text-green-800' 
                                                  : 'bg-red-100 text-red-800'
                                            ]"
                                        >
                                            {{ item.status }}
                                        </span>
                                    </template>
                                </DataTable>
                            </div>
                        </div>
                    </div>
                </Card>
            </main>
        </div>
    </div>
</template>

<script>
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import DataTable from "@/Components/DataTable.vue";

export default {
    components: {
        Sidebar,
        Navbar,
        Card,
        DataTable
    },
    props: {
        tahunAjaran: String,
        namaProyek: String,
        answers: Array,
    },
    data() {
        return {
            headers: [
                { key: 'no', label: 'No' },
                { key: 'question', label: 'Question' },
                { key: 'answer', label: 'Answer' },
                { key: 'status', label: 'Status' },
                { key: 'tanggal', label: 'Date Created' },
                { key: 'actions', label: 'Actions' },
            ]
        };
    },
    methods: {
        handleDetail(item) {
            this.$inertia.visit('/dosen/assessment/data-with-bobot-self', {
                method: 'get',
                data: {
                    tahun_ajaran: item.tahun_ajaran,
                    nama_proyek: item.nama_proyek,
                },
                preserveState: true
            });
        }
    },
    setup(props) {
        const answers = props.answers.map((answer, index) => ({
            no: index + 1,
            question: answer.question ? answer.question.pertanyaan : 'No question',
            answer: answer.answer,
            status: answer.status,
            tanggal: dayjs(answer.created_at).format('DD MMMM YYYY HH:mm'),
        }));

        return { answers };
    },
};
</script>
