<script setup>
import { ref, computed, onMounted } from "vue";
import axios from "axios";
import Swal from "sweetalert2";
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
            // console.log(response.data.message);
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

    // console.log("firstmember :", kelompok.anggota[0]);
    window.location.href = `/sispa/dosen/kelompok/report-detail?batch_year=${selectedOption.value.batch_year}&project_name=${selectedOption.value.project_name}&kelompok=${kelompok.nama_kelompok}&class_id=${classId}`;
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

const exportGroupReport = async () => {
    if (!selectedOption.value) {
        await Swal.fire({
            icon: "warning",
            title: "Project Not Selected",
            text: "Please select a project first",
            confirmButtonColor: "#3B82F6",
        });
        return;
    }

    // Show loading confirmation
    const result = await Swal.fire({
        title: "Export Group Report",
        text: `Export group report for ${selectedOption.value.project_name} (${selectedOption.value.batch_year})?`,
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#3B82F6",
        cancelButtonColor: "#6B7280",
        confirmButtonText: "Yes, Export",
        cancelButtonText: "Cancel",
    });

    if (!result.isConfirmed) {
        return;
    }

    // Show loading
    Swal.fire({
        title: "Exporting...",
        text: "Please wait while we prepare your Excel file",
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        },
    });

    try {
        isLoading.value = true;
        const url = `/sispa/dosen/report/export-groups?batch_year=${encodeURIComponent(
            selectedOption.value.batch_year
        )}&project_name=${encodeURIComponent(
            selectedOption.value.project_name
        )}`;

        const response = await fetch(url, {
            method: "GET",
            headers: {
                Accept: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
        });

        if (!response.ok) {
            let errorMessage = "Export failed";
            try {
                const errorData = await response.json();
                errorMessage = errorData.message || errorMessage;
            } catch (e) {
                // If response is not JSON, use default message
                errorMessage = `HTTP ${response.status}: ${response.statusText}`;
            }
            throw new Error(errorMessage);
        }

        // Get the blob from response
        const blob = await response.blob();

        // Create download link
        const downloadUrl = window.URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = downloadUrl;
        link.download = `Group_Report_${selectedOption.value.batch_year.replace(
            "/",
            "_"
        )}_${selectedOption.value.project_name.replace(" ", "_")}_${new Date()
            .toISOString()
            .slice(0, 19)
            .replace(/:/g, "-")}.xlsx`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Clean up
        window.URL.revokeObjectURL(downloadUrl);

        console.log("Export completed successfully");

        // Show success message
        await Swal.fire({
            icon: "success",
            title: "Export Successful!",
            text: "Your group report has been downloaded successfully",
            confirmButtonColor: "#10B981",
            timer: 3000,
            timerProgressBar: true,
        });
    } catch (error) {
        console.error("Error exporting group report:", error);

        // Show error message
        await Swal.fire({
            icon: "error",
            title: "Export Failed",
            text: `Failed to export group report: ${error.message}`,
            confirmButtonColor: "#EF4444",
        });
    } finally {
        isLoading.value = false;
    }
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

                            <!-- Action Buttons -->
                            <div class="flex space-x-3">
                                <!-- Export Excel Button -->
                                <button
                                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                                    @click="exportGroupReport"
                                    :disabled="!selectedOption || isLoading"
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
                                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        />
                                    </svg>
                                    <span v-if="isLoading">Exporting...</span>
                                    <span v-else>Export Excel</span>
                                </button>

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
                    class="relative bg-white rounded-lg max-w-3xl w-full mx-4 p-6"
                >
                    <!-- Modal Header -->
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-2xl font-semibold text-gray-900">
                            Ringkasan Penilaian Mahasiswa
                        </h3>
                        <button
                            @click="closeModal"
                            class="text-gray-400 hover:text-gray-500"
                        >
                            <span class="sr-only">Close</span>
                            <svg
                                class="h-7 w-7"
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
                    <div>
                        <!-- Loading State -->
                        <div
                            v-if="modalLoading"
                            class="flex justify-center items-center py-12"
                        >
                            <div
                                class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-500"
                            ></div>
                        </div>

                        <!-- Data Table -->
                        <div v-else>
                            <!-- Score Ranges -->
                            <div class="mt-6 bg-gray-50 p-4 rounded-lg">
                                <div class="flex flex-wrap gap-3">
                                    <div
                                        v-for="(
                                            range, index
                                        ) in modalData.ranges"
                                        :key="index"
                                        class="bg-white px-4 py-2 rounded shadow text-sm"
                                    >
                                        <span class="font-semibold"
                                            >Nilai {{ range.score }}</span
                                        >
                                        <span class="ml-2 text-gray-500"
                                            >({{ range.min.toFixed(2) }} -
                                            {{ range.max.toFixed(2) }})</span
                                        >
                                    </div>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th
                                                class="px-4 py-3 text-center font-medium text-gray-500"
                                            >
                                                No
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left font-medium text-gray-500"
                                            >
                                                Nama
                                            </th>
                                            <th
                                                class="px-4 py-3 text-center font-medium text-gray-500"
                                            >
                                                NIM
                                            </th>
                                            <th
                                                class="px-4 py-3 text-center font-medium text-gray-500"
                                            >
                                                Kelompok
                                            </th>
                                            <th
                                                class="px-4 py-3 text-center font-medium text-gray-500"
                                            >
                                                Selisih
                                            </th>
                                            <th
                                                class="px-4 py-3 text-center font-medium text-gray-500"
                                            >
                                                Nilai
                                            </th>
                                            <th
                                                class="px-4 py-3 text-center font-medium text-gray-500"
                                            >
                                                Detail
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template
                                            v-for="(
                                                student, index
                                            ) in modalData.students"
                                            :key="student.id"
                                        >
                                            <tr>
                                                <td
                                                    class="px-4 py-3 text-center"
                                                >
                                                    {{ index + 1 }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    {{ student.name }}
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-center"
                                                >
                                                    {{ student.nim }}
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-center"
                                                >
                                                    {{ student.kelompok }}
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-center"
                                                    :class="
                                                        getSelisihClass(
                                                            student.selisih
                                                        )
                                                    "
                                                >
                                                    {{ student.selisih }}
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-center font-semibold"
                                                    :class="
                                                        getNilaiClass(
                                                            student.nilai_total
                                                        )
                                                    "
                                                >
                                                    {{ student.nilai_total }}
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-center"
                                                >
                                                    <button
                                                        @click="
                                                            toggleDetails(
                                                                student.id
                                                            )
                                                        "
                                                        class="text-blue-600 hover:underline"
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
                                            <tr
                                                v-if="
                                                    expandedStudents[student.id]
                                                "
                                            >
                                                <td
                                                    colspan="7"
                                                    class="bg-gray-50 px-4 py-3"
                                                >
                                                    <table
                                                        class="w-full text-sm"
                                                    >
                                                        <thead>
                                                            <tr
                                                                class="bg-gray-100"
                                                            >
                                                                <th
                                                                    class="px-3 py-2 text-left"
                                                                >
                                                                    Aspek
                                                                </th>
                                                                <th
                                                                    class="px-3 py-2 text-left"
                                                                >
                                                                    Kriteria
                                                                </th>
                                                                <th
                                                                    class="px-3 py-2 text-center"
                                                                >
                                                                    Self
                                                                </th>
                                                                <th
                                                                    class="px-3 py-2 text-center"
                                                                >
                                                                    Peer
                                                                </th>
                                                                <th
                                                                    class="px-3 py-2 text-center"
                                                                >
                                                                    Selisih
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr
                                                                v-for="detail in student.aspect_details"
                                                                :key="`${detail.aspek}_${detail.kriteria}`"
                                                            >
                                                                <td
                                                                    class="px-3 py-2"
                                                                >
                                                                    {{
                                                                        detail.aspek
                                                                    }}
                                                                </td>
                                                                <td
                                                                    class="px-3 py-2"
                                                                >
                                                                    {{
                                                                        detail.kriteria
                                                                    }}
                                                                </td>
                                                                <td
                                                                    class="px-3 py-2 text-center"
                                                                >
                                                                    {{
                                                                        detail.self_score
                                                                    }}
                                                                </td>
                                                                <td
                                                                    class="px-3 py-2 text-center"
                                                                >
                                                                    {{
                                                                        detail.peer_score
                                                                    }}
                                                                </td>
                                                                <td
                                                                    class="px-3 py-2 text-center"
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
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
