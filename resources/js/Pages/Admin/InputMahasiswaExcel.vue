<script>
import { ref, onMounted } from "vue";
import axios from "axios";
import Sidebar from "@/Components/SidebarAdmin.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

export default {
    components: {
        Sidebar,
        Navbar,
        Card,
        Breadcrumb,
    },
    data() {
        return {
            breadcrumbs: [
                { text: "Manage Mahasiswa", href: "/admin/ManageMahasiswa" },
                { text: "Input", href: "/admin/manage-mahasiswa/input" },
            ],
        };
    },
    setup() {
        const angkatanOptions = ref([]);
        const selectedAngkatan = ref("");
        const inputMode = ref("export");

        const jurusanList = ref([]);
        const selectedJurusan = ref("");
        const selectedProdi = ref("");
        const filteredProdi = ref([]);

        const onJurusanChange = async () => {
            if (!selectedJurusan.value) return;

            try {
                const response = await axios.get(
                    `/api/get-prodi/${selectedJurusan.value}`
                );
                if (response.data.status === "success") {
                    filteredProdi.value = response.data.data;
                } else {
                    filteredProdi.value = [];
                }
                selectedProdi.value = ""; // Reset prodi when jurusan changes
            } catch (error) {
                console.error("Error fetching prodi:", error);
                filteredProdi.value = [];
            }
        };

        const generateAngkatanOptions = () => {
            const currentYear = new Date().getFullYear();
            angkatanOptions.value = Array.from(
                { length: 5 },
                (_, i) => currentYear - i
            );
        };

        const downloadTemplate = async () => {
            if (
                !selectedJurusan.value ||
                !selectedProdi.value ||
                !selectedAngkatan.value
            ) {
                alert(
                    "Harap lengkapi semua pilihan: Jurusan, Prodi, dan Angkatan."
                );
                return;
            }

            try {
                const token = localStorage.getItem("auth_token");

                // First try to get the blob
                const response = await axios.get(
                    "/admin/manage-mahasiswa/export",
                    {
                        params: {
                            jurusan: selectedJurusan.value,
                            prodi: selectedProdi.value,
                            angkatan: selectedAngkatan.value,
                        },
                        headers: {
                            Authorization: `Bearer ${token}`,
                            Accept: "application/json",
                        },
                        responseType: "blob",
                    }
                );

                // Check if the response is an error message
                if (
                    response.data instanceof Blob &&
                    response.data.type.includes("json")
                ) {
                    const text = await response.data.text();
                    const error = JSON.parse(text);
                    throw new Error(error.message || "Error downloading file");
                }

                // If we get here, we have a valid Excel file
                const blob = new Blob([response.data], {
                    type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                });

                const url = window.URL.createObjectURL(blob);
                const link = document.createElement("a");
                link.href = url;
                link.setAttribute("download", "Data_Mahasiswa.xlsx");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
            } catch (error) {
                console.error("Download error:", error);
                alert(
                    error.response?.data?.message ||
                        "Terjadi kesalahan saat mengunduh file excel"
                );
            }
        };

        const handleFileUpload = async (event) => {
            const formData = new FormData();
            formData.append("file", event.target.files[0]);

            try {
                await axios.post("/admin/manage-mahasiswa/import", formData, {
                    headers: {
                        "Content-Type": "multipart/form-data",
                    },
                });

                alert("Data Mahasiswa berhasil diimpor");
            } catch (error) {
                console.error("Import error detail:", error.response?.data);
                alert(
                    `Error: ${
                        error.response?.data?.message ||
                        "Terjadi kesalahan saat mengimpor data"
                    }`
                );
            }
        };

        onMounted(async () => {
            generateAngkatanOptions();

            try {
                const response = await axios.get("/api/get-jurusan");
                if (response.data.status === "success") {
                    jurusanList.value = response.data.data;
                } else {
                    console.error(
                        "Error fetching jurusan:",
                        response.data.message
                    );
                }
            } catch (error) {
                console.error("Error fetching jurusan:", error);
            }
        });

        return {
            downloadTemplate,
            handleFileUpload,
            angkatanOptions,
            selectedAngkatan,
            jurusanList,
            selectedJurusan,
            selectedProdi,
            filteredProdi,
            onJurusanChange,
            inputMode,
        };
    },
};
</script>

<template>
    <div class="flex min-h-screen">
        <Sidebar role="admin" />

        <div class="flex-1">
            <Navbar userName="Admin" />
            <main class="p-6">
                <div class="mb-4">
                    <Breadcrumb :items="breadcrumbs" />
                </div>
                <Card title="Input Data Mahasiswa">
                    <template #actions>
                        <!-- Segmented Radio Buttons -->
                        <div class="flex w-full mb-4">
                            <label
                                class="w-1/2 text-center py-2 border cursor-pointer"
                                :class="{
                                    'bg-blue-500 text-white':
                                        inputMode === 'export',
                                    'bg-white text-gray-700 border-gray-300':
                                        inputMode !== 'export',
                                }"
                            >
                                <input
                                    type="radio"
                                    v-model="inputMode"
                                    value="export"
                                    class="hidden"
                                />
                                Export
                            </label>
                            <label
                                class="w-1/2 text-center py-2 border cursor-pointer"
                                :class="{
                                    'bg-blue-500 text-white':
                                        inputMode === 'import',
                                    'bg-white text-gray-700 border-gray-300':
                                        inputMode !== 'import',
                                }"
                            >
                                <input
                                    type="radio"
                                    v-model="inputMode"
                                    value="import"
                                    class="hidden"
                                />
                                Import
                            </label>
                        </div>

                        <!-- Export Section -->
                        <div v-if="inputMode === 'export'">
                            <div class="mt-4">
                                <label
                                    for="angkatan-select"
                                    class="block text-sm font-medium text-gray-700"
                                >
                                    Pilih Angkatan
                                </label>
                                <select
                                    id="angkatan-select"
                                    v-model="selectedAngkatan"
                                    class="mt-2 p-2 border border-gray-300 rounded w-full"
                                    required
                                >
                                    <option value="" disabled selected>
                                        Pilih Angkatan
                                    </option>
                                    <option
                                        v-for="angkatan in angkatanOptions"
                                        :key="angkatan"
                                        :value="angkatan"
                                    >
                                        {{ angkatan }}
                                    </option>
                                </select>
                            </div>

                            <div class="mt-4">
                                <label
                                    for="jurusan-select"
                                    class="block text-sm font-medium text-gray-700"
                                >
                                    Pilih Jurusan
                                </label>
                                <select
                                    id="jurusan-select"
                                    v-model="selectedJurusan"
                                    @change="onJurusanChange"
                                    class="mt-2 p-2 border border-gray-300 rounded w-full"
                                >
                                    <option value="" disabled selected>
                                        Pilih Jurusan
                                    </option>
                                    <option
                                        v-for="jurusan in jurusanList"
                                        :key="jurusan.id"
                                        :value="jurusan.id"
                                    >
                                        {{ jurusan.major_name }}
                                    </option>
                                </select>
                            </div>

                            <div class="mt-4">
                                <label
                                    for="prodi-select"
                                    class="block text-sm font-medium text-gray-700"
                                >
                                    Pilih Prodi
                                </label>
                                <select
                                    id="prodi-select"
                                    v-model="selectedProdi"
                                    class="mt-2 p-2 border border-gray-300 rounded w-full"
                                    :disabled="!filteredProdi.length"
                                >
                                    <option value="" disabled selected>
                                        Pilih Prodi
                                    </option>
                                    <option
                                        v-for="prodi in filteredProdi"
                                        :key="prodi.id"
                                        :value="prodi.id"
                                    >
                                        {{ prodi.prodi_name }}
                                    </option>
                                </select>
                            </div>

                            <div class="mt-4">
                                <button
                                    @click="downloadTemplate"
                                    class="px-4 py-2 mt-2 bg-blue-500 text-white rounded"
                                    :disabled="
                                        !selectedJurusan ||
                                        !selectedProdi ||
                                        !selectedAngkatan
                                    "
                                >
                                    Download Template Kelompok
                                </button>
                            </div>
                        </div>

                        <!-- Import Section -->
                        <div v-if="inputMode === 'import'" class="mt-4">
                            <label
                                for="file-upload"
                                class="block text-sm font-medium text-gray-700"
                            >
                                Import Data Excel (File .xlsx/.xls)
                            </label>
                            <input
                                type="file"
                                id="file-upload"
                                accept=".xlsx, .xls"
                                @change="handleFileUpload"
                                class="mt-2 p-2 border border-gray-300 rounded w-full"
                            />
                        </div>
                    </template>
                </Card>
            </main>
        </div>
    </div>
</template>
