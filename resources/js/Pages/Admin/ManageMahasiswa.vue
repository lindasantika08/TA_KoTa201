<script>
import axios from "axios";
import { router } from "@inertiajs/vue3";
import Sidebar from "@/Components/SidebarAdmin.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import DataTable from "@/Components/DataTable.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

export default {
    name: "ManageMahasiswa",
    components: { Sidebar, Navbar, Card, DataTable, Breadcrumb },
    data() {
        return {
            breadcrumbs: [
                { text: "Manage Mahasiswa", href: "/dosen/manage-mahasiswa" },
            ],
            users: [],
            headers: [
                { key: "no", label: "No" },
                { key: "angkatan", label: "Angkatan" },
                { key: "class", label: "Kelas" },
                { key: "name", label: "Nama" },
                { key: "nim", label: "NIM" },
                { key: "email", label: "Email" },
                { key: "actions", label: "Actions" },
            ],
            selectedAngkatan: "",
            selectedClass: "",
            angkatanList: [],
            classList: [],
            showEditModal: false,
            editedMahasiswa: {
                nim: "",
                name: "",
                email: "",
                angkatan: "",
                class: "",
                user_id: null,
            },
        };
    },
    mounted() {
        this.fetchAngkatan();
        this.fetchClassList();
        this.fetchUsers();
    },
    methods: {
        async fetchUsers() {
            try {
                const response = await axios.get("/api/get-mahasiswa", {
                    params: {
                        angkatan: this.selectedAngkatan,
                        class_name: this.selectedClass,
                    },
                });
                this.users = response.data.map((user, index) => ({
                    ...user,
                    no: index + 1,
                }));
            } catch (error) {
                console.error("Error fetching users:", error);
            }
        },
        async fetchAngkatan() {
            try {
                const response = await axios.get("/api/get-angkatan");
                this.angkatanList = response.data;
            } catch (error) {
                console.error("Error fetching angkatan:", error);
            }
        },
        async fetchClassList() {
            try {
                const response = await axios.get("/api/get-class");
                this.classList = response.data;
            } catch (error) {
                console.error("Error fetching class list:", error);
            }
        },
        editMahasiswa(mahasiswa) {
            this.editedMahasiswa = {
                nim: mahasiswa.nim,
                name: mahasiswa.user.name,
                email: mahasiswa.user.email,
                angkatan: mahasiswa.class_room.angkatan,
                class: mahasiswa.class_room.class_name,
                user_id: mahasiswa.user_id,
            };
            this.showEditModal = true;
        },
        closeEditModal() {
            this.showEditModal = false;
            this.editedMahasiswa = {
                nim: "",
                name: "",
                email: "",
                angkatan: "",
                class: "",
                user_id: null,
            };
        },
        async updateMahasiswa() {
            try {
                await axios.post(`/api/update-mahasiswa`, this.editedMahasiswa);
                this.showEditModal = false;
                await this.fetchUsers();
                alert("Data mahasiswa berhasil diperbarui!");
            } catch (error) {
                console.error("Error updating mahasiswa:", error);
                alert("Gagal memperbarui data mahasiswa");
            }
        },
        async deleteMahasiswa(NIM) {
            if (
                !confirm(
                    `Apakah Anda yakin ingin menghapus mahasiswa dengan NIM ${NIM}?`
                )
            )
                return;
            try {
                const response = await axios.post("/api/delete-mahasiswa", {
                    nim: NIM,
                });
                if (response.status === 201) {
                    alert("Mahasiswa berhasil dihapus!");
                    await this.fetchUsers();
                }
            } catch (error) {
                alert("Gagal menghapus mahasiswa");
                console.error(error);
            }
        },
        inputMahasiswa() {
            router.visit("/admin/manage-mahasiswa/input");
        },
    },
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <Sidebar role="admin" />

        <div class="flex-1">
            <Navbar userName="Admin" />
            <main class="p-6">
                <div class="mb-4">
                    <Breadcrumb :items="breadcrumbs" />
                </div>
                <Card title="Kelola Mahasiswa">
                    <template #actions>
                        <!-- Filter Section -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <!-- Dropdown Angkatan -->
                            <div>
                                <label
                                    for="angkatan-select"
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Filter Angkatan
                                </label>
                                <select
                                    id="angkatan-select"
                                    v-model="selectedAngkatan"
                                    @change="fetchUsers"
                                    class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="">Semua Angkatan</option>
                                    <option
                                        v-for="angkatan in angkatanList"
                                        :key="angkatan"
                                        :value="angkatan"
                                    >
                                        {{ angkatan }}
                                    </option>
                                </select>
                            </div>

                            <!-- Dropdown Kelas -->
                            <div>
                                <label
                                    for="class-select"
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Filter Kelas
                                </label>
                                <select
                                    id="class-select"
                                    v-model="selectedClass"
                                    @change="fetchUsers"
                                    class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="">Semua Kelas</option>
                                    <option
                                        v-for="classItem in classList"
                                        :key="classItem"
                                        :value="classItem"
                                    >
                                        {{ classItem }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <DataTable
                            :headers="headers"
                            :items="users"
                            class="mt-4"
                        >
                            <template #column-actions="{ item }">
                                <div class="flex justify-center space-x-2">
                                    <button
                                        @click="editMahasiswa(item)"
                                        class="p-2 text-yellow-600 hover:text-yellow-800 hover:bg-yellow-100 rounded-full transition-colors"
                                        title="Edit Mahasiswa"
                                    >
                                        <font-awesome-icon
                                            :icon="['fas', 'edit']"
                                        />
                                    </button>
                                    <button
                                        @click="deleteMahasiswa(item.nim)"
                                        class="p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-full transition-colors"
                                        title="Hapus Mahasiswa"
                                    >
                                        <font-awesome-icon
                                            :icon="['fas', 'trash']"
                                        />
                                    </button>
                                </div>
                            </template>

                            <template #column-angkatan="{ item }">
                                <div
                                    class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-sm text-center"
                                >
                                    {{ item.class_room.angkatan }}
                                </div>
                            </template>

                            <template #column-class="{ item }">
                                <div
                                    class="px-3 py-1 bg-gray-100 rounded-full text-center"
                                >
                                    {{ item.class_room.class_name }}
                                </div>
                            </template>

                            <template #column-name="{ item }">
                                <div class="flex items-center">
                                    <div class="font-medium">
                                        {{ item.user.name }}
                                    </div>
                                </div>
                            </template>

                            <template #column-nim="{ item }">
                                <div class="font-mono">{{ item.nim }}</div>
                            </template>

                            <template #column-email="{ item }">
                                <div class="flex items-center">
                                    <font-awesome-icon
                                        :icon="['fas', 'envelope']"
                                        class="mr-2 text-gray-400"
                                    />
                                    {{ item.user.email }}
                                </div>
                            </template>
                        </DataTable>
                    </template>
                </Card>

                <!-- Add Button -->
                <button
                    @click="inputMahasiswa"
                    class="fixed bottom-8 right-8 flex items-center justify-center w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105"
                >
                    <font-awesome-icon :icon="['fas', 'plus']" />
                </button>

                <!-- Edit Modal -->
                <div
                    v-if="showEditModal"
                    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
                >
                    <div
                        class="bg-white rounded-lg shadow-xl w-full max-w-md p-6"
                    >
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-semibold text-gray-800">
                                Edit Data Mahasiswa
                            </h2>
                            <button
                                @click="closeEditModal"
                                class="text-gray-500 hover:text-gray-700"
                            >
                                <font-awesome-icon :icon="['fas', 'times']" />
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                >
                                    NIM
                                </label>
                                <input
                                    v-model="editedMahasiswa.nim"
                                    type="text"
                                    disabled
                                    class="w-full p-2 bg-gray-100 border border-gray-300 rounded-lg"
                                />
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                >
                                    Nama Lengkap
                                </label>
                                <input
                                    v-model="editedMahasiswa.name"
                                    type="text"
                                    class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                >
                                    Email
                                </label>
                                <input
                                    v-model="editedMahasiswa.email"
                                    type="email"
                                    class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                >
                                    Angkatan
                                </label>
                                <select
                                    v-model="editedMahasiswa.angkatan"
                                    class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option
                                        v-for="angkatan in angkatanList"
                                        :key="angkatan"
                                        :value="angkatan"
                                    >
                                        {{ angkatan }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                >
                                    Kelas
                                </label>
                                <select
                                    v-model="editedMahasiswa.class"
                                    class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option
                                        v-for="classItem in classList"
                                        :key="classItem"
                                        :value="classItem"
                                    >
                                        {{ classItem }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-6">
                            <button
                                @click="closeEditModal"
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400"
                            >
                                Batal
                            </button>
                            <button
                                @click="updateMahasiswa"
                                class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                Simpan
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped></style>
