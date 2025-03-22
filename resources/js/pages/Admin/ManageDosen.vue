<script>
// Script section remains the same
import axios from "axios";
import { router } from "@inertiajs/vue3";
import Sidebar from "@/Components/SidebarAdmin.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import DataTable from "@/Components/DataTable.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

export default {
    name: "ManageDosen",
    components: {
        Sidebar,
        Navbar,
        Card,
        DataTable,
        Breadcrumb,
    },
    data() {
        return {
            breadcrumbs: [
                { text: "Manage Dosen", href: "/admin/manage-dosen" },
            ],
            users: [],
            filteredUsers: [],
            searchQuery: "",
            selectedMajor: "",
            majors: [],
            headers: [
                { key: "no", label: "No" },
                { key: "name", label: "Nama Dosen" },
                { key: "kode_dosen", label: "Kode Dosen" },
                { key: "nip", label: "NIP" },
                { key: "email", label: "Email" },
                { key: "major_name", label: "Jurusan" },
                { key: "actions", label: "Aksi" },
            ],
            showEditModal: false,
            editedDosen: {
                nip: "",
                name: "",
                email: "",
                kode_dosen: "",
                major_name: "",
            },
        };
    },
    mounted() {
        this.fetchUsers();
    },
    watch: {
        searchQuery() {
            this.filterUsers();
        },
        selectedMajor() {
            this.filterUsers();
        },
    },
    methods: {
        async fetchUsers() {
            try {
                const response = await axios.get("/sispa/api/get-dosen-admin");
                this.users = response.data.map((user, index) => ({
                    ...user,
                    no: index + 1,
                }));
                this.filteredUsers = [...this.users];
                this.majors = [
                    ...new Set(this.users.map((user) => user.major_name)),
                ];
            } catch (error) {
                console.error("Error fetching users:", error);
            }
        },
        filterUsers() {
            this.filteredUsers = this.users.filter((user) => {
                const nameMatch = user.user.name
                    .toLowerCase()
                    .includes(this.searchQuery.toLowerCase());
                const majorMatch =
                    !this.selectedMajor ||
                    user.major_name === this.selectedMajor;
                return nameMatch && majorMatch;
            });
        },
        inputDosen() {
            router.visit("/admin/manage-dosen/input");
        },
        editDosen(dosen) {
            this.editedDosen = {
                nip: dosen.nip,
                name: dosen.user.name,
                email: dosen.user.email,
                kode_dosen: dosen.kode_dosen,
                major_name: dosen.major_name, // Tidak bisa diedit
            };
            this.showEditModal = true;
        },
        async updateDosen() {
            try {
                await axios.post(`/sispa/api/update-dosen`, {
                    nip: this.editedDosen.nip,
                    name: this.editedDosen.name,
                    email: this.editedDosen.email,
                    kode_dosen: this.editedDosen.kode_dosen,
                });

                alert("Dosen updated successfully!");
                this.showEditModal = false;
                await this.fetchUsers();
            } catch (error) {
                alert("Failed to update dosen");
                console.error(error);
            }
        },
        async deleteDosen(NIP) {
            if (!confirm(`Apakah Anda yakin ingin menghapus ${NIP}?`)) {
                return;
            }
            try {
                const response = await axios.post("/sispa/api/delete-dosen", {
                    nip: NIP,
                });
                if (response.status === 201) {
                    alert("Dosen deleted successfully!");
                    await this.fetchUsers();
                }
            } catch (error) {
                alert("Failed to delete dosen");
            }
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
                <div class="mb-6">
                    <Breadcrumb :items="breadcrumbs" />
                </div>

                <!-- Dosen Management Card -->
                <Card>
                    <div class="flex flex-col md:flex-row gap-4">
                        <!-- Search Input -->
                        <div class="flex-1">
                            <div class="relative">
                                <span
                                    class="absolute inset-y-0 left-0 pl-3 flex items-center"
                                >
                                    <font-awesome-icon
                                        :icon="['fas', 'search']"
                                        class="text-gray-400"
                                    />
                                </span>
                                <input
                                    type="text"
                                    v-model="searchQuery"
                                    placeholder="Cari nama dosen..."
                                    class="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                        </div>

                        <!-- Major Filter Dropdown -->
                        <div class="w-full md:w-64">
                            <select
                                v-model="selectedMajor"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Semua Jurusan</option>
                                <option
                                    v-for="major in majors"
                                    :key="major"
                                    :value="major"
                                >
                                    {{ major }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <!-- Card Header with Filters -->

                    <div class="p-4 space-y-4">
                        <h2 class="text-xl font-bold text-gray-800">
                            Daftar Dosen
                        </h2>
                    </div>

                    <DataTable
                        :headers="headers"
                        :items="filteredUsers"
                        class="mt-4"
                    >
                        <template #column-name="{ item }">
                            <span class="font-medium">{{
                                item.user.name
                            }}</span>
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

                        <template #column-major_name="{ item }">
                            <div
                                class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-sm"
                            >
                                {{ item.major_name }}
                            </div>
                        </template>

                        <template #column-actions="{ item }">
                            <div class="flex justify-center space-x-2">
                                <button
                                    @click="editDosen(item)"
                                    class="p-2 text-yellow-600 hover:text-yellow-800 hover:bg-yellow-100 rounded-full transition-colors"
                                    title="Edit Dosen"
                                >
                                    <font-awesome-icon
                                        :icon="['fas', 'edit']"
                                    />
                                </button>
                                <button
                                    @click="deleteDosen(item.nip)"
                                    class="p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-full transition-colors"
                                    title="Delete Dosen"
                                >
                                    <font-awesome-icon
                                        :icon="['fas', 'trash']"
                                    />
                                </button>
                            </div>
                        </template>
                    </DataTable>
                </Card>

                <button
                    @click="inputDosen"
                    class="fixed bottom-8 right-8 w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 transition-all"
                >
                    <font-awesome-icon :icon="['fas', 'plus']" />
                </button>

                <!-- Modal Edit Dosen -->
                <div
                    v-if="showEditModal"
                    class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 z-50"
                >
                    <div class="bg-white p-6 rounded-lg w-96">
                        <h2 class="text-xl font-bold mb-4">Edit Dosen</h2>

                        <label class="block mb-2">Nama Dosen</label>
                        <input
                            v-model="editedDosen.name"
                            type="text"
                            class="w-full px-3 py-2 border rounded-lg mb-4"
                        />

                        <label class="block mb-2">Email</label>
                        <input
                            v-model="editedDosen.email"
                            type="email"
                            class="w-full px-3 py-2 border rounded-lg mb-4"
                        />

                        <label class="block mb-2">Kode Dosen</label>
                        <input
                            v-model="editedDosen.kode_dosen"
                            type="text"
                            class="w-full px-3 py-2 border rounded-lg mb-4"
                        />

                        <label class="block mb-2">Jurusan</label>
                        <p class="px-3 py-2 border rounded-lg bg-gray-100">
                            {{ editedDosen.major_name }}
                        </p>

                        <div class="mt-4 flex justify-end">
                            <button
                                @click="showEditModal = false"
                                class="px-4 py-2 mr-2 bg-gray-300 rounded-lg"
                            >
                                Batal
                            </button>
                            <button
                                @click="updateDosen"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg"
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
