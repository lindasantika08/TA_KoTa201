<script setup>
import { ref, computed, onMounted } from "vue";
import axios from "axios";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

const breadcrumbs = ref([{ text: "Report", href: "/sispa/dosen/report" }]);
const combinedOptions = ref([]);
const selectedOption = ref(null);
const kelompokList = ref([]);
const isLoading = ref(false);
const searchQuery = ref("");
const showModal = ref(false);
const modalLoading = ref(false);
const modalData = ref({
    students: [],
    ranges: [],
});
const expandedStudents = ref({});

const fetchDropdownOptions = async () => {
    try {
        const response = await axios.get("/sispa/api/dropdown-options");
        combinedOptions.value = response.data.options || [];

        const storedOption = localStorage.getItem("selectedOption");
        if (storedOption) {
            try {
                const parsedOption = JSON.parse(storedOption);
                if (parsedOption.batchYear && parsedOption.projectName) {
                    const matchedOption = combinedOptions.value.find(
                        (option) =>
                            option.batchYear === parsedOption.batchYear &&
                            option.projectName === parsedOption.projectName
                    );

                    if (matchedOption) {
                        selectedOption.value = matchedOption;
                        selectedOption.value.batch_year =
                            matchedOption.batchYear;
                        selectedOption.value.project_name =
                            matchedOption.projectName;
                        await fetchKelompok();
                    }
                }
            } catch (error) {
                console.error("Error parsing localStorage data:", error);
                localStorage.removeItem("selectedOption");
            }
        }
    } catch (error) {
        console.error("Error fetching dropdown options:", error);
    }
};

const fetchKelompok = async () => {
    if (!selectedOption.value) {
        kelompokList.value = [];
        return;
    }

    isLoading.value = true;
    try {
        const response = await axios.get("/sispa/api/kelompok/report", {
            params: {
                batch_year: selectedOption.value.batch_year,
                project_name: selectedOption.value.project_name,
            },
        });

        if (response.data.success) {
            kelompokList.value = response.data.kelompok || [];
        } else {
            kelompokList.value = [];
            console.log(response.data.message);
        }
    } catch (error) {
        console.error("Error fetching kelompok data:", error);
        kelompokList.value = [];
    } finally {
        isLoading.value = false;
    }
};

const handleDropdownChange = (event) => {
    const selectedValue = event.target.value;
    selectedOption.value = combinedOptions.value.find(
        (option) => option.value === selectedValue
    );

    if (selectedOption.value) {
        selectedOption.value.batch_year = selectedOption.value.batchYear;
        selectedOption.value.project_name = selectedOption.value.projectName;
        localStorage.setItem(
            "selectedOption",
            JSON.stringify(selectedOption.value)
        );
        fetchKelompok();
    }
};

const handleReportKelompokDetail = (kelompok) => {
    if (!selectedOption.value) return;

    // Get the first member's class information since all members in the same group and class
    const firstMember = kelompok.anggota[0];
    const classId = firstMember?.class_id;

    console.log("firstmember :", kelompok.anggota[0]);
    window.location.href = `/dosen/kelompok/report-detail?batch_year=${selectedOption.value.batch_year}&project_name=${selectedOption.value.project_name}&kelompok=${kelompok.nama_kelompok}&class_id=${classId}`;
};

const fetchStudentData = async () => {
    if (!selectedOption.value) return;

    modalLoading.value = true;
    showModal.value = true;

    try {
        const response = await axios.get("/sispa/api/student-peer-data", {
            params: {
                batch_year: selectedOption.value.batch_year,
                project_name: selectedOption.value.project_name,
            },
        });

        if (response.data.success) {
            modalData.value = response.data;
        } else {
            modalData.value = { students: [], ranges: [] };
        }
    } catch (error) {
        console.error("Error fetching student data:", error);
        modalData.value = { students: [], ranges: [] };
    } finally {
        modalLoading.value = false;
    }
};

const filteredKelompok = computed(() => {
    if (!searchQuery.value) return kelompokList.value;

    const query = searchQuery.value.toLowerCase();
    return kelompokList.value.filter(
        (kelompok) =>
            kelompok.nama_kelompok.toLowerCase().includes(query) ||
            kelompok.anggota.some((member) =>
                member.name.toLowerCase().includes(query)
            )
    );
});

const getSelisihClass = (selisih) => {
    if (selisih <= 1.2) return "text-green-600";
    if (selisih <= 2.4) return "text-blue-600";
    if (selisih <= 3.6) return "text-yellow-600";
    if (selisih <= 4.8) return "text-orange-600";
    return "text-red-600";
};

const getNilaiClass = (nilai) => {
    switch (nilai) {
        case 100:
            return "text-green-600";
        case 90:
            return "text-blue-600";
        case 80:
            return "text-yellow-600";
        case 70:
            return "text-orange-600";
        default:
            return "text-red-600";
    }
};

const closeModal = () => {
    showModal.value = false;
    modalData.value = { students: [], ranges: [] };
    expandedStudents.value = {};
};

const toggleDetails = (studentId) => {
    expandedStudents.value[studentId] = !expandedStudents.value[studentId];
};

onMounted(() => {
    fetchDropdownOptions();
});
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <Sidebar role="dosen" />
        <div class="flex-1">
            <Navbar userName="Dosen" />
            <main class="p-6">
                <div class="max-w-7xl mx-auto">
                    <div class="mb-6">
                        <Breadcrumb :items="breadcrumbs" />
                    </div>

                    <Card title="Laporan Kelompok Mahasiswa">
                        <div class="mb-6 flex justify-between items-start">
                            <!-- Filter Section -->
                            <div class="space-y-4 flex-1 max-w-xl">
                                <div>
                                    <label
                                        for="combinedDropdown"
                                        class="block mb-2 text-sm font-medium text-gray-700"
                                    >
                                        Pilih Batch Year dan Project Name
                                    </label>
                                    <select
                                        id="combinedDropdown"
                                        @change="handleDropdownChange"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                        <option value="" disabled selected>
                                            Pilih Batch Year - Project Name
                                        </option>
                                        <option
                                            v-for="option in combinedOptions"
                                            :key="option.value"
                                            :value="option.value"
                                            :selected="
                                                selectedOption &&
                                                option.value ===
                                                    selectedOption.value
                                            "
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Report Summary Button -->
                            <button
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                                @click="fetchStudentData"
                                :disabled="!selectedOption"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 mr-2"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                                Lihat Ringkasan Penilaian
                            </button>
                        </div>

                        <!-- Loading State -->
                        <div
                            v-if="isLoading"
                            class="flex justify-center items-center py-12"
                        >
                            <div
                                class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"
                            ></div>
                        </div>

                        <!-- Empty State -->
                        <div
                            v-else-if="kelompokList.length === 0"
                            class="text-center py-12 bg-gray-50 rounded-lg"
                        >
                            <div class="space-y-3">
                                <p class="text-gray-500 text-lg">
                                    Tidak ada kelompok yang ditemukan
                                </p>
                                <p class="text-gray-400">
                                    Silakan pilih batch year dan project name
                                    yang berbeda
                                </p>
                            </div>
                        </div>

                        <!-- Group Cards -->
                        <div
                            v-else
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                        >
                            <Card
                                v-for="kelompok in filteredKelompok"
                                :key="kelompok.id"
                                :title="kelompok.nama_kelompok"
                                class="hover:shadow-md transition-shadow duration-200 cursor-pointer"
                                @click="handleReportKelompokDetail(kelompok)"
                            >
                                <div>
                                    <div class="flex justify-end mb-4">
                                        <span
                                            class="px-3 py-1 text-xs font-medium text-blue-600 bg-blue-100 rounded-full"
                                        >
                                            {{ kelompok.anggota.length }}
                                            Anggota
                                        </span>
                                    </div>
                                    <p
                                        class="text-sm font-medium text-gray-600 mb-2"
                                    >
                                        Anggota Kelompok:
                                    </p>
                                    <ul class="space-y-2">
                                        <li
                                            v-for="member in kelompok.anggota"
                                            :key="member.mahasiswa_id"
                                            class="flex items-center text-sm text-gray-600"
                                        >
                                            <span
                                                class="h-2 w-2 bg-green-400 rounded-full mr-2"
                                            ></span>
                                            {{ member.name }}
                                            <span
                                                v-if="member.nim"
                                                class="ml-1 text-gray-400"
                                            >
                                                ({{ member.nim }})
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </Card>
                        </div>
                    </Card>
                </div>
            </main>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <!-- Modal Backdrop -->
                <div class="fixed inset-0 bg-black opacity-30"></div>

                <!-- Modal Content -->
                <div
                    class="relative bg-white rounded-lg max-w-4xl w-full mx-4 p-6"
                >
                    <!-- Modal Header -->
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-semibold text-gray-900">
                            Data Mahasiswa dan Selisih Penilaian
                        </h3>
                        <button
                            @click="closeModal"
                            class="text-gray-400 hover:text-gray-500"
                        >
                            <span class="sr-only">Close</span>
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="mt-4">
                        <!-- Loading State -->
                        <div
                            v-if="modalLoading"
                            class="flex justify-center items-center py-12"
                        >
                            <div
                                class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"
                            ></div>
                        </div>

                        <!-- Data Table -->
                        <div v-else>
                            <div class="overflow-x-auto">
                                <table
                                    class="min-w-full divide-y divide-gray-200"
                                >
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                No
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                Nama Mahasiswa
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                NIM
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                Kelompok
                                            </th>
                                            <th
                                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                Total Selisih
                                            </th>
                                            <th
                                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                Nilai Akhir
                                            </th>
                                            <th
                                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
                                            >
                                                Detail
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="bg-white divide-y divide-gray-200"
                                    >
                                        <template
                                            v-for="(
                                                student, index
                                            ) in modalData.students"
                                            :key="student.id"
                                        >
                                            <!-- Main Student Row -->
                                            <tr>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900"
                                                >
                                                    {{ index + 1 }}
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                                >
                                                    {{ student.name }}
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                                >
                                                    {{ student.nim }}
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                                >
                                                    {{ student.kelompok }}
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-sm text-center"
                                                    :class="
                                                        getSelisihClass(
                                                            student.selisih
                                                        )
                                                    "
                                                >
                                                    {{ student.selisih }}
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center"
                                                    :class="
                                                        getNilaiClass(
                                                            student.nilai_total
                                                        )
                                                    "
                                                >
                                                    {{ student.nilai_total }}
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-sm text-center"
                                                >
                                                    <button
                                                        @click="
                                                            toggleDetails(
                                                                student.id
                                                            )
                                                        "
                                                        class="text-blue-600 hover:text-blue-800"
                                                    >
                                                        {{
                                                            expandedStudents[
                                                                student.id
                                                            ]
                                                                ? "Tutup"
                                                                : "Lihat"
                                                        }}
                                                    </button>
                                                </td>
                                            </tr>
                                            <!-- Detailed Scores Row -->
                                            <tr
                                                v-if="
                                                    expandedStudents[student.id]
                                                "
                                            >
                                                <td
                                                    colspan="7"
                                                    class="px-6 py-4 bg-gray-50"
                                                >
                                                    <div
                                                        class="border rounded-lg overflow-hidden"
                                                    >
                                                        <table
                                                            class="min-w-full divide-y divide-gray-200"
                                                        >
                                                            <thead
                                                                class="bg-gray-100"
                                                            >
                                                                <tr>
                                                                    <th
                                                                        class="px-4 py-2 text-left text-xs font-medium text-gray-500"
                                                                    >
                                                                        Aspek
                                                                    </th>
                                                                    <th
                                                                        class="px-4 py-2 text-left text-xs font-medium text-gray-500"
                                                                    >
                                                                        Kriteria
                                                                    </th>
                                                                    <th
                                                                        class="px-4 py-2 text-center text-xs font-medium text-gray-500"
                                                                    >
                                                                        Self
                                                                        Score
                                                                    </th>
                                                                    <th
                                                                        class="px-4 py-2 text-center text-xs font-medium text-gray-500"
                                                                    >
                                                                        Peer
                                                                        Score
                                                                    </th>
                                                                    <th
                                                                        class="px-4 py-2 text-center text-xs font-medium text-gray-500"
                                                                    >
                                                                        Selisih
                                                                    </th>
                                                                </tr>
                                                            </thead>
                                                            <tbody
                                                                class="bg-white divide-y divide-gray-200"
                                                            >
                                                                <tr
                                                                    v-for="detail in student.aspect_details"
                                                                    :key="`${detail.aspek}_${detail.kriteria}`"
                                                                >
                                                                    <td
                                                                        class="px-4 py-2 text-sm text-gray-900"
                                                                    >
                                                                        {{
                                                                            detail.aspek
                                                                        }}
                                                                    </td>
                                                                    <td
                                                                        class="px-4 py-2 text-sm text-gray-900"
                                                                    >
                                                                        {{
                                                                            detail.kriteria
                                                                        }}
                                                                    </td>
                                                                    <td
                                                                        class="px-4 py-2 text-sm text-center text-gray-900"
                                                                    >
                                                                        {{
                                                                            detail.self_score
                                                                        }}
                                                                    </td>
                                                                    <td
                                                                        class="px-4 py-2 text-sm text-center text-gray-900"
                                                                    >
                                                                        {{
                                                                            detail.peer_score
                                                                        }}
                                                                    </td>
                                                                    <td
                                                                        class="px-4 py-2 text-sm text-center"
                                                                        :class="
                                                                            getSelisihClass(
                                                                                detail.selisih
                                                                            )
                                                                        "
                                                                    >
                                                                        {{
                                                                            detail.selisih
                                                                        }}
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Score Ranges -->
                            <div class="mt-6 bg-gray-50 p-4 rounded-lg">
                                <h4
                                    class="text-sm font-medium text-gray-700 mb-3"
                                >
                                    Rentang Nilai Berdasarkan Selisih:
                                </h4>
                                <div
                                    class="grid grid-cols-1 md:grid-cols-5 gap-4"
                                >
                                    <div
                                        v-for="(
                                            range, index
                                        ) in modalData.ranges"
                                        :key="index"
                                        class="bg-white p-3 rounded-lg shadow-sm"
                                    >
                                        <div
                                            class="text-sm font-medium text-gray-700"
                                        >
                                            Nilai {{ range.score }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            Selisih:
                                            {{ range.min.toFixed(2) }} -
                                            {{ range.max.toFixed(2) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
