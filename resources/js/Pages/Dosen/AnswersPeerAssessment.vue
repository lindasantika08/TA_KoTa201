vue

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

                <!-- Filter Section -->
                <div v-if="!loading && !error" class="mb-6 space-y-4">
                    <!-- Group Filter -->
                    <div class="mb-4">
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

                    <!-- Additional Filters -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Nama Pengguna Dropdown -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Pengguna</label>
                            <div class="relative">
                                <div
                                    @click="toggleDropdown('nama_pengguna')"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md cursor-pointer bg-white flex justify-between items-center"
                                >
                                    <span class="truncate">
                                        {{ filters.nama_pengguna.length ? `${filters.nama_pengguna.length} dipilih` : 'Pilih nama pengguna...' }}
                                    </span>
                                    <span class="ml-2">▼</span>
                                </div>
                                <div
                                    v-show="activeDropdown === 'nama_pengguna'"
                                    class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto"
                                >
                                    <div class="p-2 border-b">
                                        <input
                                            v-model="searchTerms.nama_pengguna"
                                            type="text"
                                            class="w-full px-2 py-1 border border-gray-300 rounded-md"
                                            placeholder="Cari..."
                                            @click.stop
                                        />
                                    </div>
                                    <div class="p-2">
                                        <label class="flex items-center px-2 py-1 hover:bg-gray-100 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                :checked="filters.nama_pengguna.length === uniqueNamaPengguna.length"
                                                @change="toggleAll('nama_pengguna')"
                                                class="mr-2"
                                            />
                                            <span>Pilih Semua</span>
                                        </label>
                                    </div>
                                    <div
                                        v-for="name in filteredNamaPengguna"
                                        :key="name"
                                        class="px-2"
                                    >
                                        <label class="flex items-center px-2 py-1 hover:bg-gray-100 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                :value="name"
                                                v-model="filters.nama_pengguna"
                                                class="mr-2"
                                                @click.stop
                                            />
                                            <span>{{ name }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Nama Rekan Dropdown -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Rekan</label>
                            <div class="relative">
                                <div
                                    @click="toggleDropdown('namaRekan')"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md cursor-pointer bg-white flex justify-between items-center"
                                >
                                    <span class="truncate">
                                        {{ filters.namaRekan.length ? `${filters.namaRekan.length} dipilih` : 'Pilih nama rekan...' }}
                                    </span>
                                    <span class="ml-2">▼</span>
                                </div>
                                <div
                                    v-show="activeDropdown === 'namaRekan'"
                                    class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto"
                                >
                                    <div class="p-2 border-b">
                                        <input
                                            v-model="searchTerms.namaRekan"
                                            type="text"
                                            class="w-full px-2 py-1 border border-gray-300 rounded-md"
                                            placeholder="Cari..."
                                            @click.stop
                                        />
                                    </div>
                                    <div class="p-2">
                                        <label class="flex items-center px-2 py-1 hover:bg-gray-100 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                :checked="filters.namaRekan.length === uniqueNamaRekan.length"
                                                @change="toggleAll('namaRekan')"
                                                class="mr-2"
                                            />
                                            <span>Pilih Semua</span>
                                        </label>
                                    </div>
                                    <div
                                        v-for="name in filteredNamaRekan"
                                        :key="name"
                                        class="px-2"
                                    >
                                        <label class="flex items-center px-2 py-1 hover:bg-gray-100 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                :value="name"
                                                v-model="filters.namaRekan"
                                                class="mr-2"
                                                @click.stop
                                            />
                                            <span>{{ name }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pertanyaan Dropdown -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pertanyaan</label>
                            <div class="relative">
                                <div
                                    @click="toggleDropdown('pertanyaan')"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md cursor-pointer bg-white flex justify-between items-center"
                                >
                                    <span class="truncate">
                                        {{ filters.pertanyaan.length ? `${filters.pertanyaan.length} dipilih` : 'Pilih pertanyaan...' }}
                                    </span>
                                    <span class="ml-2">▼</span>
                                </div>
                                <div
                                    v-show="activeDropdown === 'pertanyaan'"
                                    class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto"
                                >
                                    <div class="p-2 border-b">
                                        <input
                                            v-model="searchTerms.pertanyaan"
                                            type="text"
                                            class="w-full px-2 py-1 border border-gray-300 rounded-md"
                                            placeholder="Cari..."
                                            @click.stop
                                        />
                                    </div>
                                    <div class="p-2">
                                        <label class="flex items-center px-2 py-1 hover:bg-gray-100 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                :checked="filters.pertanyaan.length === uniquePertanyaan.length"
                                                @change="toggleAll('pertanyaan')"
                                                class="mr-2"
                                            />
                                            <span>Pilih Semua</span>
                                        </label>
                                    </div>
                                    <div
                                        v-for="question in filteredPertanyaan"
                                        :key="question"
                                        class="px-2"
                                    >
                                        <label class="flex items-center px-2 py-1 hover:bg-gray-100 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                :value="question"
                                                v-model="filters.pertanyaan"
                                                class="mr-2"
                                                @click.stop
                                            />
                                            <span>{{ question }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Jawaban Search -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cari Jawaban</label>
                            <input
                                v-model="filters.jawaban"
                                type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                placeholder="Cari dalam jawaban..."
                            />
                        </div>
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
                                <!-- Hide the Kelompok column if a group is selected -->
                                <th v-if="!selectedGroup" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Kelompok
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
                                                : 'bg-green-100 text-green-800'
                                        ]"
                                    >
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td v-if="!selectedGroup" class="px-6 py-4 whitespace-nowrap">
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

<script>
import axios from "axios";
import DataTable from "@/Components/DataTable.vue";
import Navbar from "@/Components/Navbar.vue";
import Sidebar from "@/Components/Sidebar.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

export default {
    name: 'AnswersPeerAssessment',
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
            activeDropdown: null,
            searchTerms: {
                nama_pengguna: '',
                namaRekan: '',
                pertanyaan: ''
            },
            filters: {
                nama_pengguna: [], // Array for multi-select
                namaRekan: [], // Array for multi-select
                pertanyaan: [], // Array for multi-select
                jawaban: '' // String for text search
            },
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
            ],
            loading: false,
            error: null
        };
    },
    computed: {
        // Get unique values for dropdown options
        uniqueNamaPengguna() {
            return [...new Set(this.answers.map(a => a.nama_pengguna))].sort();
        },
        uniqueNamaRekan() {
            return [...new Set(this.answers.map(a => a.nama_rekan))].sort();
        },
        uniquePertanyaan() {
            return [...new Set(this.answers.map(a => a.pertanyaan))].sort();
        },

        // Filter dropdown options based on search terms
        filteredNamaPengguna() {
            const searchTerm = this.searchTerms.nama_pengguna.toLowerCase();
            return this.uniqueNamaPengguna.filter(name => 
                name.toLowerCase().includes(searchTerm)
            );
        },
        filteredNamaRekan() {
            const searchTerm = this.searchTerms.namaRekan.toLowerCase();
            return this.uniqueNamaRekan.filter(name => 
                name.toLowerCase().includes(searchTerm)
            );
        },
        filteredPertanyaan() {
            const searchTerm = this.searchTerms.pertanyaan.toLowerCase();
            return this.uniquePertanyaan.filter(question => 
                question.toLowerCase().includes(searchTerm)
            );
        },

        // Main filtering logic for the answers
        filteredAnswers() {
            let filtered = this.answers;

            // Apply group filter
            if (this.selectedGroup) {
                filtered = filtered.filter(answer => answer.kelompok === this.selectedGroup);
            }

            // Apply multi-select filters
            if (this.filters.nama_pengguna.length > 0) {
                filtered = filtered.filter(answer => 
                    this.filters.nama_pengguna.includes(answer.nama_pengguna)
                );
            }

            if (this.filters.namaRekan.length > 0) {
                filtered = filtered.filter(answer => 
                    this.filters.namaRekan.includes(answer.nama_rekan)
                );
            }

            if (this.filters.pertanyaan.length > 0) {
                filtered = filtered.filter(answer => 
                    this.filters.pertanyaan.includes(answer.pertanyaan)
                );
            }

            // Apply text search filter for jawaban
            if (this.filters.jawaban) {
                const searchTerm = this.filters.jawaban.toLowerCase();
                filtered = filtered.filter(answer => 
                    answer.jawaban.toLowerCase().includes(searchTerm)
                );
            }

            return filtered;
        }
    },
    created() {
        const pageProps = this.$page.props;
        
        // Get URL parameters
        const query = new URLSearchParams(window.location.search);
        this.batch_year = query.get("batch_year") || pageProps.batch_year;
        this.project_name = query.get("project_name") || pageProps.project_name;
        
        this.totalGroups = pageProps.totalGroups || 0;

        // Fetch data if parameters are present
        if (this.batch_year && this.project_name) {
            this.fetchAnswers();
        } else {
            this.error = "Parameter tidak lengkap!";
            console.error(this.error);
        }

        // Add click event listener to close dropdowns when clicking outside
        document.addEventListener('click', this.handleClickOutside);
    },
    beforeDestroy() {
        // Remove event listener when component is destroyed
        document.removeEventListener('click', this.handleClickOutside);
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

        // Dropdown management
        toggleDropdown(dropdownName) {
            if (this.activeDropdown === dropdownName) {
                this.activeDropdown = null;
            } else {
                this.activeDropdown = dropdownName;
            }
        },
        handleClickOutside(event) {
            const dropdowns = document.querySelectorAll('.relative');
            let clickedOutside = true;
            
            dropdowns.forEach(dropdown => {
                if (dropdown.contains(event.target)) {
                    clickedOutside = false;
                }
            });

            if (clickedOutside) {
                this.activeDropdown = null;
            }
        },

        // Filter management
        toggleAll(filterName) {
            const allItems = {
                'nama_pengguna': this.uniqueNamaPengguna,
                'namaRekan': this.uniqueNamaRekan,
                'pertanyaan': this.uniquePertanyaan
            }[filterName];
            
            if (this.filters[filterName].length === allItems.length) {
                this.filters[filterName] = [];
            } else {
                this.filters[filterName] = [...allItems];
            }
        },

        // Table cell management for rowspan
        shouldShowCell(index, field) {
            if (field !== 'nama_pengguna' && field !== 'nama_rekan') return true;
            
            if (index === 0) return true;
            
            const currentItem = this.filteredAnswers[index];
            const previousItem = this.filteredAnswers[index - 1];
            
            return currentItem[field] !== previousItem[field];
        },
        getRowSpan(index, field) {
            if (field !== 'nama_pengguna' && field !== 'nama_rekan') return 1;
            
            let span = 1;
            const currentItem = this.filteredAnswers[index];
            
            for (let i = index + 1; i < this.filteredAnswers.length; i++) {
                if (currentItem[field] === this.filteredAnswers[i][field]) {
                    span++;
                } else {
                    break;
                }
            }
            
            return span;
        }
    }
};
</script>