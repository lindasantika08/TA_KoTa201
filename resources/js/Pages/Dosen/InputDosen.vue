<script>
import { ref, onMounted } from "vue";
import axios from "axios";
import Sidebar from "@/Components/Sidebar.vue";
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
                { text: "Manage Dosen", href: "/sispa/dosen/manage-dosen" },
                { text: "Input", href: "/sispa/dosen/manage-dosen/input" },
            ],
        };
    },
    setup() {
        const inputMode = ref("export");
        const jurusanList = ref([]);
        const selectedJurusan = ref("");
        const isLoading = ref(false);
        const errorMessage = ref("");

        // Fungsi untuk mengambil data jurusan dari API
        const getJurusanList = async () => {
            try {
                isLoading.value = true;
                const token = localStorage.getItem("auth_token");
                const response = await axios.get("/sispa/api/majors", {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        Accept: "application/json",
                    },
                });
                // Langsung assign response.data karena sudah berupa array
                jurusanList.value = response.data;
                console.log('Jurusan list:', jurusanList.value);
            } catch (error) {
                console.error("Error mengambil data jurusan:", error);
                errorMessage.value = "Gagal memuat data jurusan. Silakan coba lagi.";
            } finally {
                isLoading.value = false;
            }
        };

        // Panggil fungsi getJurusanList saat komponen dimount
        onMounted(() => {
            getJurusanList();
        });

        // Fungsi untuk mengunduh template berdasarkan jurusan
        const downloadTemplate = async () => {
            if (!selectedJurusan.value) {
                alert("Harap pilih jurusan terlebih dahulu.");
                return;
            }

            try {
                const token = localStorage.getItem("auth_token");
                const response = await axios.get("/dosen/manage-dosen/export", {
                    params: { jurusan: selectedJurusan.value },
                    headers: {
                        Authorization: `Bearer ${token}`,
                        Accept: "application/json",
                    },
                    responseType: "blob",
                });

                const blob = new Blob([response.data], {
                    type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                });
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement("a");
                link.href = url;
                link.setAttribute("download", "Data_Dosen.xlsx");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
            } catch (error) {
                console.error("Error download:", error);
                alert("Gagal mengunduh template. Silakan coba lagi.");
            }
        };

        // Fungsi untuk mengunggah file Excel
        const handleFileUpload = async (event) => {
            const formData = new FormData();
            formData.append("file", event.target.files[0]);

            try {
                const token = localStorage.getItem("auth_token");
                await axios.post("/dosen/manage-dosen/import", formData, {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        "Content-Type": "multipart/form-data",
                    },
                });
                alert("Data dosen berhasil diimpor.");
            } catch (error) {
                console.error("Error import:", error.response?.data);
                alert("Gagal mengimpor data. Silakan periksa format file Anda.");
            }
        };

        return {
            inputMode,
            jurusanList,
            selectedJurusan,
            isLoading,
            errorMessage,
            downloadTemplate,
            handleFileUpload,
        };
    },
};
</script>

<template>
    <div class="flex min-h-screen">
        <Sidebar role="dosen" />

        <div class="flex-1">
            <Navbar userName="Dosen" />
            <main class="p-6">
                <div class="mb-4">
                    <Breadcrumb :items="breadcrumbs" />
                </div>
                <Card title="Input Data Dosen">
                    <template #actions>
                        <!-- Pilihan Mode -->
                        <div class="flex w-full mb-4">
                            <label class="w-1/2 text-center py-2 border cursor-pointer" :class="{
                                'bg-blue-500 text-white': inputMode === 'export',
                                'bg-white text-gray-700 border-gray-300':
                                    inputMode !== 'export',
                            }">
                                <input type="radio" v-model="inputMode" value="export" class="hidden" />
                                Export
                            </label>
                            <label class="w-1/2 text-center py-2 border cursor-pointer" :class="{
                                'bg-blue-500 text-white': inputMode === 'import',
                                'bg-white text-gray-700 border-gray-300':
                                    inputMode !== 'import',
                            }">
                                <input type="radio" v-model="inputMode" value="import" class="hidden" />
                                Import
                            </label>
                        </div>

                        <!-- Bagian Export -->
                        <div v-if="inputMode === 'export'">
                            <div class="mt-4">
                                <label for="jurusan-select" class="block text-sm font-medium text-gray-700">
                                    Pilih Jurusan
                                </label>
                                <div v-if="isLoading" class="mt-2 text-gray-600">
                                    Memuat data jurusan...
                                </div>
                                <div v-else-if="errorMessage" class="mt-2 text-red-600">
                                    {{ errorMessage }}
                                </div>
                                <select v-else id="jurusan-select" v-model="selectedJurusan"
                                    class="mt-2 p-2 border border-gray-300 rounded w-full">
                                    <option value="" disabled>Pilih Jurusan</option>
                                    <option v-for="jurusan in jurusanList" :key="jurusan.id" :value="jurusan.id">
                                        {{ jurusan.major_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="mt-4">
                                <button @click="downloadTemplate"
                                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:bg-gray-400"
                                    :disabled="!selectedJurusan || isLoading">
                                    Download Template
                                </button>
                            </div>
                        </div>

                        <!-- Bagian Import -->
                        <div v-if="inputMode === 'import'" class="mt-4">
                            <label for="file-upload" class="block text-sm font-medium text-gray-700">
                                Import Data Excel
                            </label>
                            <input type="file" id="file-upload" accept=".xlsx, .xls" @change="handleFileUpload"
                                class="mt-2 p-2 border border-gray-300 rounded w-full" />
                        </div>
                    </template>
                </Card>
            </main>
        </div>
    </div>
</template>