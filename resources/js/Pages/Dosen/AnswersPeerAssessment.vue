<script>
import axios from "axios";
import DataTable from "@/Components/DataTable.vue";
import Navbar from "@/Components/Navbar.vue";
import Sidebar from "@/Components/Sidebar.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

export default {
    components: {
        DataTable,
        Navbar,
        Sidebar,
        Breadcrumb,
    },
    data() {
        return {
            batch_year: "",
            project_name: "",
            groups: [],
            totalGroups: 0,
            answers: [],
            selectedGroup: null,
            noDataMessage: "",
            breadcrumbs: [
                {
                    text: "Peer Assessment",
                    href: "/dosen/assessment/projectsPeer",
                },
                { text: "List Answer", href: null },
            ],
            headers: [
                { key: "no", label: "No" },
                { key: "nama_pengguna", label: "Nama Pengguna" },
                { key: "nama_rekan", label: "Nama Rekan" },
                { key: "pertanyaan", label: "Pertanyaan" },
                { key: "skor", label: "Skor" },
                { key: "jawaban", label: "Jawaban" },
                { key: "status", label: "Status" },
                { key: "kelompok", label: "Kelompok" },
            ],
            loading: false,
            error: null
        };
    },
    created() {
        const pageProps = this.$page.props;
        
        const query = new URLSearchParams(window.location.search);
        this.batch_year = query.get("batch_year") || pageProps.batch_year;
        this.project_name = query.get("project_name") || pageProps.project_name;
        
        this.totalGroups = pageProps.totalGroups || 0;

        if (this.batch_year && this.project_name) {
            this.fetchAnswers();
        } else {
            this.error = "Parameter tidak lengkap!";
            console.error(this.error);
        }
    },
    methods: {
        async fetchAnswers() {
            this.loading = true;
            this.error = null;
            try {
                const response = await axios.get("/api/answersPeer/list", {
                    params: {
                        batch_year: this.batch_year,
                        project_name: this.project_name,
                    },
                });

                if (response.data.success) {
                    this.answers = response.data.data.map(item => ({
                        ...item,
                        nama_pengguna: item.user?.name || '-',
                        nama_rekan: item.peer?.name || '-',
                        skor: item.score,
                        jawaban: item.answer,
                        pertanyaan: item.pertanyaan || '-',
                        status: item.status,
                        kelompok: item.kelompok
                    }));
                    this.groups = response.data.groups || [];
                    this.noDataMessage = "";
                } else {
                    this.answers = [];
                    this.groups = [];
                    this.noDataMessage = "Belum ada jawaban";
                }
            } catch (error) {
                console.error("Error fetching answers:", error);
                this.error = "Terjadi kesalahan saat mengambil data.";
                this.answers = [];
                this.groups = [];
            } finally {
                this.loading = false;
            }
        },
        filterByGroup(group) {
            this.selectedGroup = group;
        },
        shouldShowCell(index, field) {
            if (field !== 'nama_pengguna' && field !== 'nama_rekan') return true;
            
            if (index === 0) return true;
            
            const currentItem = this.filteredAnswers[index];
            const previousItem = this.filteredAnswers[index - 1];
            
            return currentItem.nama_pengguna !== previousItem.nama_pengguna || 
                   currentItem.nama_rekan !== previousItem.nama_rekan;
        },
        getRowSpan(index, field) {
            if (field !== 'nama_pengguna' && field !== 'nama_rekan') return 1;
            
            let span = 1;
            const currentItem = this.filteredAnswers[index];
            
            for (let i = index + 1; i < this.filteredAnswers.length; i++) {
                if (currentItem.nama_pengguna === this.filteredAnswers[i].nama_pengguna &&
                    currentItem.nama_rekan === this.filteredAnswers[i].nama_rekan) {
                    span++;
                } else {
                    break;
                }
            }
            
            return span;
        }
    },
    computed: {
        filteredAnswers() {
            if (!this.selectedGroup) return this.answers;
            return this.answers.filter(answer => answer.kelompok === this.selectedGroup);
        }
    }
};
</script>

<template>
    <div class="flex min-h-screen">
        <Sidebar role="dosen" />

        <div class="flex-1">
            <Navbar userName="dosen" />

            <main class="p-6">
                <div class="mb-4">
                    <Breadcrumb :items="breadcrumbs" />
                </div>

                <div class="mb-6">
                    <h1 class="text-xl font-semibold">
                        Answers Peer Assessment
                    </h1>
                </div>

                <div class="mb-6 text-sm">
                    <p><strong>Tahun Ajaran </strong> : {{ batch_year }}</p>
                    <p><strong>Nama Proyek </strong> : {{ project_name }}</p>
                    <p><strong>Total Kelompok </strong> : {{ totalGroups }}</p>
                </div>

                <!-- Error Message -->
                <div v-if="error" class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                    {{ error }}
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="text-center py-6">
                    <span class="text-gray-600">Loading...</span>
                </div>

                <!-- Group Filter -->
                <div v-if="!loading && !error" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filter Kelompok</label>
                    <div class="flex flex-wrap gap-2">
                        <button 
                            @click="selectedGroup = null"
                            :class="[
                                'px-3 py-1 rounded-full text-sm',
                                !selectedGroup ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800'
                            ]"
                        >
                            Semua Kelompok
                        </button>
                        <button 
                            v-for="group in groups" 
                            :key="group"
                            @click="selectedGroup = group"
                            :class="[
                                'px-3 py-1 rounded-full text-sm',
                                selectedGroup === group ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800'
                            ]"
                        >
                            {{ group }}
                        </button>
                    </div>
                </div>

                <div
                    v-if="!loading && !error && filteredAnswers.length === 0"
                    class="text-center text-gray-500 py-6"
                >
                    {{ noDataMessage || "Belum ada jawaban" }}
                </div>

                <div v-if="!loading && !error && filteredAnswers.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th v-for="header in headers" 
                                    :key="header.key"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    {{ header.label }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="(item, index) in filteredAnswers" :key="item.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td v-if="shouldShowCell(index, 'nama_pengguna')" 
                                    :rowspan="getRowSpan(index, 'nama_pengguna')"
                                    class="px-6 py-4 whitespace-nowrap"
                                >
                                    {{ item.nama_pengguna }}
                                </td>
                                <td v-if="shouldShowCell(index, 'nama_rekan')" 
                                    :rowspan="getRowSpan(index, 'nama_rekan')"
                                    class="px-6 py-4 whitespace-nowrap"
                                >
                                    {{ item.nama_rekan }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ item.pertanyaan }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ item.skor }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ item.jawaban }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'px-2 py-1 rounded-full text-xs font-medium',
                                            item.status === 'aktif'
                                                ? 'bg-red-100 text-red-800'
                                                : 'bg-green-100 text-green-800',
                                        ]"
                                    >
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ item.kelompok }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
</template>