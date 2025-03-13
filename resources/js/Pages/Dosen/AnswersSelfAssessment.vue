<script>
import Navbar from "@/Components/Navbar.vue";
import Sidebar from "@/Components/Sidebar.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import DataTable from "@/Components/DataTable.vue";
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import axios from "axios";

export default {
    components: {
        DataTable,
        Navbar,
        Sidebar,
        Breadcrumb,
    },
    data() {
        return {
            totalKeseluruhan: 0,
            totalSudahMengisi: 0,
            submissionStatus: [],
            nimFilter: "",
            namaFilter: "",
            isFiltering: false,
            breadcrumbs: [
                {
                    text: "Self Assessment",
                    href: "/dosen/assessment/projectsSelf",
                },
            ],
            headers: [
                { key: "no", label: "No", class: "text-center" },
                { key: "nim", label: "NIM", class: "text-center" },
                {
                    key: "nama_mahasiswa",
                    label: "Nama Mahasiswa",
                    class: "w-1/4",
                },
                { key: "kelas", label: "Kelas", class: "text-center" },
                { key: "status", label: "Status", class: "text-center" },
                { key: "detail", label: "Actions", class: "text-center" },
            ],
            isLoading: true,
            error: null,
        };
    },
    setup() {
        const page = usePage();
        return {
            batch_year: computed(() => page.props.batch_year),
            project_id: computed(() => page.props.projectId),
            project_name: computed(() => page.props.project_name),
            assessment_order: computed(() => page.props.assessment_order),
        };
    },
    mounted() {
        if (this.batch_year && this.project_id) {
            this.fetchStatistics();
        } else {
            this.error = "Batch Year atau Project ID tidak ditemukan!";
            this.isLoading = false;
        }
    },
    watch: {
        nimFilter(newVal) {
            this.applyFilters();
        },
        namaFilter(newVal) {
            this.applyFilters();
        },
    },
    methods: {
        fetchStatistics() {
            this.isLoading = true;
            axios
                .get("/api/answers/statistics", {
                    params: {
                        batch_year: this.batch_year,
                        project_id: this.project_id,
                        nim: this.nimFilter,
                        nama: this.namaFilter,
                    },
                })
                .then((response) => {
                    this.totalKeseluruhan = response.data.totalKeseluruhan;
                    this.totalSudahMengisi = response.data.totalSudahMengisi;
                    this.submissionStatus = response.data.submissionStatus;
                    this.isLoading = false;
                    this.isFiltering = false;
                })
                .catch((error) => {
                    console.error("Error fetching statistics:", error);
                    this.error = "Gagal memuat statistik";
                    this.isLoading = false;
                    this.isFiltering = false;
                });
        },
        applyFilters() {
            // Tambahkan debounce agar tidak terlalu sering melakukan request
            if (this.filterTimeout) {
                clearTimeout(this.filterTimeout);
            }

            this.filterTimeout = setTimeout(() => {
                this.isFiltering = true;
                this.fetchStatistics();
            }, 300); // Debounce 300ms
        },
        resetFilters() {
            this.nimFilter = "";
            this.namaFilter = "";
            this.fetchStatistics();
        },
        showDetails(mahasiswaId) {
            // Pastikan semua parameter tersedia sebelum melakukan router.visit
            if (
                this.batch_year &&
                this.project_name &&
                this.project_id &&
                this.assessment_order
            ) {
                // Encode parameter URL untuk menghindari masalah karakter khusus
                const url = `/dosen/answers/details?mahasiswaId=${encodeURIComponent(
                    mahasiswaId
                )}&batch_year=${encodeURIComponent(
                    this.batch_year
                )}&project_name=${encodeURIComponent(
                    this.project_name
                )}&project_id=${encodeURIComponent(
                    this.project_id
                )}&assessment_order=${encodeURIComponent(
                    this.assessment_order
                )}`;

                // Gunakan router.visit dengan parameter kedua untuk memberikan opsi tambahan jika diperlukan
                router.visit(url, {
                    preserveScroll: true, // Opsional: mempertahankan posisi scroll
                    only: [], // Opsional: untuk partial reloads
                });
            } else {
                // Tampilkan pesan error jika ada parameter yang hilang
                console.error("Missing required parameters for navigation:", {
                    batch_year: this.batch_year,
                    project_name: this.project_name,
                    project_id: this.project_id,
                    assessment_order: this.assessment_order,
                });
                // Opsional: tampilkan pesan error ke pengguna
                // alert('Terjadi kesalahan saat memuat detail. Mohon coba lagi.');
            }
        },
        getStatusClass(status) {
            const statusClasses = {
                submitted: "bg-green-100 text-green-800",
                unsubmitted: "bg-red-100 text-red-800",
                "on progress": "bg-yellow-100 text-yellow-800",
            };
            return statusClasses[status] || "bg-gray-100 text-gray-800";
        },
    },
    computed: {
        filteredAnswers() {
            return this.submissionStatus.filter((item) => {
                let matchesNim = true;
                let matchesNama = true;

                if (this.nimFilter) {
                    matchesNim = item.nim
                        .toLowerCase()
                        .includes(this.nimFilter.toLowerCase());
                }

                if (this.namaFilter) {
                    matchesNama = item.mahasiswaName
                        .toLowerCase()
                        .includes(this.namaFilter.toLowerCase());
                }

                return matchesNim && matchesNama;
            });
        },
        groupedAnswers() {
            return this.filteredAnswers
                .map((item, index) => ({
                    index: index + 1,
                    id: item.id,
                    nim: item.nim,
                    mahasiswaName: item.mahasiswaName,
                    kelas: item.kelas,
                    status: item.status,
                }))
                .sort((a, b) => {
                    // Urutkan berdasarkan NIM
                    if (a.nim !== b.nim) {
                        return a.nim.localeCompare(b.nim);
                    }
                    return a.mahasiswaName.localeCompare(b.mahasiswaName);
                })
                .sort((a, b) => {
                    const statusOrder = [
                        "submitted",
                        "on progress",
                        "unsubmitted",
                    ];
                    return (
                        statusOrder.indexOf(a.status) -
                        statusOrder.indexOf(b.status)
                    );
                });
        },
        submissionPercentage() {
            return this.totalKeseluruhan > 0
                ? Math.round(
                      (this.totalSudahMengisi / this.totalKeseluruhan) * 100
                  )
                : 0;
        },
    },
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

                <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                    <h1 class="text-2xl font-bold text-gray-800 mb-4">
                        Answers Self Assessment
                    </h1>

                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Batch Year</p>
                            <p class="font-semibold text-lg">
                                {{ batch_year }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Project Name</p>
                            <p class="font-semibold text-lg">
                                {{ project_name }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">
                                Submission Progress
                            </p>
                            <div class="flex items-center">
                                <div
                                    class="w-full bg-gray-200 rounded-full h-2.5 mr-2"
                                >
                                    <div
                                        class="bg-blue-600 h-2.5 rounded-full"
                                        :style="`width: ${submissionPercentage}%`"
                                    ></div>
                                </div>
                                <span class="text-sm font-medium text-gray-500">
                                    {{ submissionPercentage }}%
                                </span>
                            </div>
                            <p class="text-lg font-semibold mt-2">
                                Total Mengisi: {{ totalSudahMengisi }} /
                                {{ totalKeseluruhan }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Filter section -->
                <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">
                        Filter
                    </h2>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                for="nimFilter"
                            >
                                NIM
                            </label>
                            <input
                                type="text"
                                id="nimFilter"
                                v-model="nimFilter"
                                placeholder="Masukkan NIM"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                for="namaFilter"
                            >
                                Nama Mahasiswa
                            </label>
                            <input
                                type="text"
                                id="namaFilter"
                                v-model="namaFilter"
                                placeholder="Masukkan Nama Mahasiswa"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                    </div>
                    <div class="flex justify-end space-x-2 mt-4">
                        <button
                            @click="resetFilters"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <div
                    v-if="error"
                    class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"
                    role="alert"
                >
                    {{ error }}
                </div>

                <div
                    v-else-if="isLoading"
                    class="bg-gray-100 text-center text-gray-500 py-6 rounded-lg"
                >
                    Loading...
                </div>

                <div
                    v-else-if="groupedAnswers.length === 0"
                    class="bg-gray-100 text-center text-gray-500 py-6 rounded-lg"
                >
                    Belum ada jawaban
                </div>

                <div v-else>
                    <DataTable
                        :headers="headers"
                        :items="groupedAnswers"
                        class="mt-6 shadow-md rounded-lg overflow-hidden"
                    >
                        <template #column-no="{ item }">
                            <div class="text-center">{{ item.index }}</div>
                        </template>

                        <template #column-nim="{ item }">
                            <div class="text-center">{{ item.nim }}</div>
                        </template>

                        <template #column-nama_mahasiswa="{ item }">
                            {{ item.mahasiswaName }}
                        </template>

                        <template #column-kelas="{ item }">
                            <div class="text-center">{{ item.kelas }}</div>
                        </template>

                        <template #column-status="{ item }">
                            <div class="flex justify-center">
                                <span
                                    :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        getStatusClass(item.status),
                                    ]"
                                >
                                    {{ item.status }}
                                </span>
                            </div>
                        </template>

                        <template #column-detail="{ item }">
                            <div class="flex justify-center">
                                <button
                                    v-if="item.status === 'submitted'"
                                    @click="showDetails(item.id)"
                                    class="px-4 py-2 text-white bg-blue-500 rounded-lg hover:bg-blue-700 transition-colors"
                                >
                                    Detail
                                </button>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </main>
        </div>
    </div>
</template>
