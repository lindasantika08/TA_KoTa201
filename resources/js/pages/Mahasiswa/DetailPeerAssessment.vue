<script>
import axios from "axios";
import SidebarMahasiswa from "@/Components/SidebarMahasiswa.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import DataTable from "@/Components/DataTable.vue";
import { ref, onMounted, computed } from "vue";

export default {
    props: {
        batchYear: {
            type: String,
            required: true,
        },
        projectName: {
            type: String,
            required: true,
        },
    },
    name: "PeerAssessmentDetail",
    components: {
        SidebarMahasiswa,
        Navbar,
        Card,
        Breadcrumb,
        DataTable,
    },

    setup(props) {
        const breadcrumbs = [
            {
                text: "Peer Assessment",
                href: "/sispa/mahasiswa/assessment/peer",
            },
            { text: "Detail", href: null },
        ];

        const headers = [
            { key: "no", label: "No" },
            { key: "aspek", label: "Aspek" },
            { key: "pertanyaan", label: "Pertanyaan" },
            { key: "peer", label: "Peer" },
            { key: "skala", label: "Skala" },
            { key: "alasan", label: "Alasan" },
        ];

        const studentInfo = ref({
            nim: "",
            name: "",
            class: "",
            group: "",
            project: "",
            date: "",
        });

        const items = ref([]);
        const isLoading = ref(true);
        const filteredAspects = ref([]);
        const selectedAspect = ref("all");
        const selectedPeer = ref("all");
        const filteredPeers = ref([]);

        const filteredItems = computed(() => {
            let filtered = items.value;

            if (selectedAspect.value !== "all") {
                filtered = filtered.filter(
                    (item) => item.aspek === selectedAspect.value
                );
            }

            if (selectedPeer.value !== "all") {
                filtered = filtered.filter(
                    (item) => item.peer === selectedPeer.value
                );
            }

            return filtered;
        });

        const completionPercentage = computed(() => {
            if (items.value.length === 0) return 0;

            const completedItems = items.value.filter(
                (item) =>
                    item.alasan &&
                    item.alasan !== "N/A" &&
                    item.alasan.trim() !== ""
            );

            return Math.round(
                (completedItems.length / items.value.length) * 100
            );
        });

        const getScaleColorClass = (scale) => {
            const numScale = parseInt(scale, 10);
            if (numScale >= 8) return "bg-green-100 text-green-800"; // Excellent
            if (numScale >= 6) return "bg-blue-100 text-blue-800"; // Good
            if (numScale >= 4) return "bg-yellow-100 text-yellow-800"; // Average
            return "bg-red-100 text-red-800"; // Poor
        };

        const getCompletionStatus = () => {
            if (completionPercentage.value === 100) return "Selesai";
            if (completionPercentage.value >= 75) return "Hampir Selesai";
            if (completionPercentage.value >= 50) return "Setengah Jalan";
            if (completionPercentage.value > 0) return "Baru Mulai";
            return "Belum Mulai";
        };

        const getCompletionClass = () => {
            if (completionPercentage.value === 100) return "bg-green-500";
            if (completionPercentage.value >= 75) return "bg-blue-500";
            if (completionPercentage.value >= 50) return "bg-yellow-500";
            if (completionPercentage.value > 0) return "bg-orange-500";
            return "bg-red-500";
        };

        const fetchUserInfo = async () => {
            try {
                const response = await axios.get(
                    "/sispa/api/user-detail-answer",
                    {
                        params: {
                            batch_year: props.batchYear,
                            project_name: props.projectName,
                        },
                    }
                );
                studentInfo.value = response.data;
            } catch (error) {
                console.error("Error fetching user info:", error);
            }
        };

        const fetchPeerAssessment = async () => {
            try {
                isLoading.value = true;
                const response = await axios.get(
                    "/sispa/api/peer-assessment-detail",
                    {
                        params: {
                            batch_year: props.batchYear,
                            project_name: props.projectName,
                        },
                    }
                );

                items.value = response.data.answers.map((answer, index) => ({
                    no: index + 1,
                    aspek: answer.aspect || "N/A",
                    pertanyaan: answer.question || "N/A",
                    peer: answer.peer_name || "N/A",
                    skala: answer.scale,
                    alasan: answer.reason || "N/A",
                }));

                // Extract unique aspects and peers for filtering
                const aspects = new Set(items.value.map((item) => item.aspek));
                filteredAspects.value = Array.from(aspects).filter(
                    (aspect) => aspect !== "N/A"
                );

                const peers = new Set(items.value.map((item) => item.peer));
                filteredPeers.value = Array.from(peers).filter(
                    (peer) => peer !== "N/A"
                );

                isLoading.value = false;
            } catch (error) {
                console.error("Error fetching peer assessment:", error);
                isLoading.value = false;
            }
        };

        onMounted(() => {
            fetchUserInfo();
            fetchPeerAssessment();
        });

        return {
            breadcrumbs,
            headers,
            studentInfo,
            items,
            isLoading,
            filteredAspects,
            selectedAspect,
            selectedPeer,
            filteredPeers,
            filteredItems,
            completionPercentage,
            getScaleColorClass,
            getCompletionStatus,
            getCompletionClass,
        };
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

                <div
                    v-if="isLoading"
                    class="flex justify-center items-center h-64"
                >
                    <div class="flex flex-col items-center">
                        <div
                            class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-blue-600"
                        ></div>
                        <p class="mt-4 text-lg text-blue-600 font-medium">
                            Memuat Data Peer Assessment...
                        </p>
                    </div>
                </div>

                <div v-else>
                    <Card class="shadow-lg mb-6">
                        <template #title>
                            <div
                                class="flex flex-col sm:flex-row sm:justify-between sm:items-center p-2"
                            >
                                <div
                                    class="flex items-center space-x-3 mb-4 sm:mb-0"
                                >
                                    <div class="bg-blue-100 p-2 rounded-full">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-8 w-8 text-blue-600"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                                            />
                                        </svg>
                                    </div>
                                    <h1
                                        class="text-2xl font-bold text-blue-800"
                                    >
                                        Peer Assessment
                                    </h1>
                                </div>
                                <div class="flex flex-col">
                                    <div class="flex items-center mb-1">
                                        <span
                                            class="text-sm font-medium text-gray-600 mr-2"
                                            >Status:</span
                                        >
                                        <span
                                            class="px-2 py-1 text-xs font-semibold rounded-full"
                                            :class="
                                                completionPercentage === 100
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-yellow-100 text-yellow-800'
                                            "
                                        >
                                            {{ getCompletionStatus() }}
                                        </span>
                                    </div>
                                    <div
                                        class="w-full bg-gray-200 rounded-full h-2.5"
                                    >
                                        <div
                                            class="h-2.5 rounded-full"
                                            :class="getCompletionClass()"
                                            :style="`width: ${completionPercentage}%`"
                                        ></div>
                                    </div>
                                    <div
                                        class="text-xs text-right mt-1 text-gray-600"
                                    >
                                        {{ completionPercentage }}% selesai
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Student Info Section -->
                        <div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
                            <h2
                                class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2 flex items-center"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 mr-2 text-blue-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                    />
                                </svg>
                                Informasi Mahasiswa
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div
                                    class="bg-blue-50 p-4 rounded-lg shadow-sm"
                                >
                                    <div class="space-y-3">
                                        <div class="flex items-center">
                                            <span
                                                class="text-gray-600 font-medium w-32"
                                                >NIM</span
                                            >
                                            <span
                                                class="text-gray-800 font-semibold"
                                                >: {{ studentInfo.nim }}</span
                                            >
                                        </div>
                                        <div class="flex items-center">
                                            <span
                                                class="text-gray-600 font-medium w-32"
                                                >Nama Lengkap</span
                                            >
                                            <span
                                                class="text-gray-800 font-semibold"
                                                >: {{ studentInfo.name }}</span
                                            >
                                        </div>
                                        <div class="flex items-center">
                                            <span
                                                class="text-gray-600 font-medium w-32"
                                                >Kelas</span
                                            >
                                            <span
                                                class="text-gray-800 font-semibold"
                                                >: {{ studentInfo.class }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="bg-blue-50 p-4 rounded-lg shadow-sm"
                                >
                                    <div class="space-y-3">
                                        <div class="flex items-center">
                                            <span
                                                class="text-gray-600 font-medium w-32"
                                                >Kelompok</span
                                            >
                                            <span
                                                class="text-gray-800 font-semibold"
                                                >: {{ studentInfo.group }}</span
                                            >
                                        </div>
                                        <div class="flex items-center">
                                            <span
                                                class="text-gray-600 font-medium w-32"
                                                >Proyek</span
                                            >
                                            <span
                                                class="text-gray-800 font-semibold"
                                                >:
                                                {{ studentInfo.project }}</span
                                            >
                                        </div>
                                        <div class="flex items-center">
                                            <span
                                                class="text-gray-600 font-medium w-32"
                                                >Tanggal</span
                                            >
                                            <span
                                                class="text-gray-800 font-semibold"
                                                >: {{ studentInfo.date }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Section -->
                        <div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
                            <h2
                                class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2 flex items-center"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 mr-2 text-blue-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                                    />
                                </svg>
                                Filter Penilaian
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label
                                        for="aspectFilter"
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                        >Filter berdasarkan Aspek:</label
                                    >
                                    <select
                                        id="aspectFilter"
                                        v-model="selectedAspect"
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                                    >
                                        <option value="all">Semua Aspek</option>
                                        <option
                                            v-for="aspect in filteredAspects"
                                            :key="aspect"
                                            :value="aspect"
                                        >
                                            {{ aspect }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        for="peerFilter"
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                        >Filter berdasarkan Peer:</label
                                    >
                                    <select
                                        id="peerFilter"
                                        v-model="selectedPeer"
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                                    >
                                        <option value="all">Semua Peer</option>
                                        <option
                                            v-for="peer in filteredPeers"
                                            :key="peer"
                                            :value="peer"
                                        >
                                            {{ peer }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Assessment Results -->
                        <div class="bg-white rounded-lg p-6 shadow-sm">
                            <h2
                                class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2 flex items-center"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 mr-2 text-blue-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                    />
                                </svg>
                                Hasil Penilaian
                                <span
                                    class="ml-2 text-sm font-normal text-gray-500"
                                >
                                    ({{ filteredItems.length }} Penilaian)
                                </span>
                            </h2>

                            <div
                                v-if="filteredItems.length > 0"
                                class="overflow-x-auto"
                            >
                                <table
                                    class="min-w-full border border-gray-200 mb-4 rounded-lg overflow-hidden"
                                >
                                    <thead>
                                        <tr class="bg-gray-100">
                                            <th
                                                class="border px-4 py-3 text-center w-16"
                                            >
                                                No
                                            </th>
                                            <th
                                                class="border px-4 py-3 text-left"
                                            >
                                                Aspek
                                            </th>
                                            <th
                                                class="border px-4 py-3 text-left"
                                            >
                                                Pertanyaan
                                            </th>
                                            <th
                                                class="border px-4 py-3 text-center"
                                            >
                                                Peer
                                            </th>
                                            <th
                                                class="border px-4 py-3 text-center w-24"
                                            >
                                                Skala
                                            </th>
                                            <th
                                                class="border px-4 py-3 text-left"
                                            >
                                                Alasan
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(
                                                item, index
                                            ) in filteredItems"
                                            :key="index"
                                            class="hover:bg-blue-50 transition-colors duration-150"
                                        >
                                            <td
                                                class="border px-4 py-3 text-center"
                                            >
                                                <span
                                                    class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-blue-100 text-blue-800 font-semibold"
                                                >
                                                    {{ index + 1 }}
                                                </span>
                                            </td>
                                            <td class="border px-4 py-3">
                                                <span
                                                    class="font-medium text-blue-700"
                                                    >{{ item.aspek }}</span
                                                >
                                            </td>
                                            <td class="border px-4 py-3">
                                                <div
                                                    class="font-medium text-gray-700"
                                                >
                                                    {{ item.pertanyaan }}
                                                </div>
                                            </td>
                                            <td
                                                class="border px-4 py-3 text-center"
                                            >
                                                <span
                                                    class="inline-block bg-purple-100 text-purple-800 text-sm px-3 py-1 rounded-full"
                                                >
                                                    {{ item.peer }}
                                                </span>
                                            </td>
                                            <td
                                                class="border px-4 py-3 text-center"
                                            >
                                                <span
                                                    class="inline-block px-3 py-1 rounded-full text-sm font-semibold"
                                                    :class="
                                                        getScaleColorClass(
                                                            item.skala
                                                        )
                                                    "
                                                >
                                                    {{ item.skala }}
                                                </span>
                                            </td>
                                            <td class="border px-4 py-3">
                                                <div
                                                    v-if="
                                                        item.alasan &&
                                                        item.alasan !== 'N/A'
                                                    "
                                                    class="bg-gray-50 p-2 rounded text-gray-800"
                                                >
                                                    {{ item.alasan }}
                                                </div>
                                                <div
                                                    v-else
                                                    class="italic text-gray-500"
                                                >
                                                    (Belum ada alasan yang
                                                    diberikan)
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div
                                v-else
                                class="bg-blue-50 p-6 rounded-lg text-center"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-16 w-16 mx-auto text-blue-400 mb-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <p
                                    class="text-blue-600 font-medium text-lg mb-2"
                                >
                                    Tidak ada data yang ditemukan
                                </p>
                                <p class="text-gray-600">
                                    Silakan gunakan filter lain atau kembali ke
                                    tampilan semua data
                                </p>

                                <button
                                    @click="
                                        selectedAspect = 'all';
                                        selectedPeer = 'all';
                                    "
                                    class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center mx-auto"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5 mr-1"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                        />
                                    </svg>
                                    Reset Filter
                                </button>
                            </div>
                        </div>

                        <!-- No data message -->
                        <div
                            v-if="items.length === 0"
                            class="bg-white rounded-lg p-8 shadow-sm flex flex-col items-center justify-center mt-6"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-16 w-16 text-gray-400 mb-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            <p class="text-center text-gray-500 text-lg mb-2">
                                Belum ada data peer assessment yang tersedia.
                            </p>
                            <p class="text-center text-gray-400 text-sm">
                                Silahkan isi peer assessment terlebih dahulu
                                untuk melihat hasilnya.
                            </p>
                        </div>
                    </Card>
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
.animation-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}
</style>
