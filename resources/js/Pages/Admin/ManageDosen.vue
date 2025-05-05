<script>
// Script section remains the same
import axios from "axios";
import { router } from "@inertiajs/vue3";
import Sidebar from "@/Components/SidebarAdmin.vue";
import NavbarAdmin from "@/Components/NavbarAdmin.vue";
import Card from "@/Components/Card.vue";
import DataTable from "@/Components/DataTable.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

export default {
    name: "ManageDosen",
    components: {
        Sidebar,
        NavbarAdmin,
        Card,
        DataTable,
        Breadcrumb,
    },
    data() {
        return {
            breadcrumbs: [
                { text: "Manage Dosen", href: "/sispa/admin/manage-dosen" },
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
        users: {
            handler() {
                this.filterUsers();
            },
            immediate: true
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
            if (!this.searchQuery && !this.selectedMajor) {
                this.filteredUsers = [...this.users];
                return;
            }

            const query = this.searchQuery.toLowerCase();

            this.filteredUsers = this.users.filter((user) => {
                const nameMatch = user.user.name.toLowerCase().includes(query);
                const kodeDosenMatch = user.kode_dosen.toLowerCase().includes(query);
                const nipMatch = user.nip.toLowerCase().includes(query);
                const emailMatch = user.user.email.toLowerCase().includes(query);
                const majorMatch = !this.selectedMajor || user.major_name === this.selectedMajor;

                return (nameMatch || kodeDosenMatch || nipMatch || emailMatch) && majorMatch;
            });
        },
        clearSearch() {
            this.searchQuery = "";
        },

        inputDosen() {
            router.visit("/sispa/admin/manage-dosen/input");
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

                // Check if the data was actually deleted despite the error
                if (response.status === 201 || response.status === 200) {
                    alert("Dosen deleted successfully!");
                    await this.fetchUsers();
                } else {
                    alert("Failed to delete dosen");
                }
            } catch (error) {
                // The data might still be deleted despite the error
                // You could check by refreshing the data
                await this.fetchUsers();

                // If the user no longer exists in the refreshed data, it was actually deleted
                const userStillExists = this.users.some(user => user.nip === NIP);

                if (!userStillExists) {
                    alert("Dosen deleted successfully despite some errors.");
                } else {
                    alert("Failed to delete dosen");
                    console.error(error);
                }
            }
        },
    },
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <Sidebar role="admin" />

        <div class="flex-1">
            <NavbarAdmin userName="Admin" />
            <main class="p-6">
                <div class="mb-6">
                    <Breadcrumb :items="breadcrumbs" />
                </div>
                <div class="mb-6">
                    <div class="mb-6">
                        <h1 class="text-3xl font-bold text-gray-800">Dosen Management</h1>
                        <p class="text-gray-600">Manage dosen data and their details</p>
                    </div>
                </div>

                <!-- Dosen Management Card -->
                <Card>
                    <div class="flex flex-col md:flex-row gap-4">
                        <!-- Search Input -->
                        <div class="flex-1">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input type="text" v-model="searchQuery"
                                    class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Search by prodi name or major name..." />
                                <button v-if="searchQuery" @click="clearSearch"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Major Filter Dropdown -->
                        <div class="w-full md:w-64">
                            <select v-model="selectedMajor"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Semua Jurusan</option>
                                <option v-for="major in majors" :key="major" :value="major">
                                    {{ major }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <template v-if="isLoading">
                        <div class="text-center p-8">Loading...</div>
                    </template>
                    <!-- Card Header with Filters -->
                    <template v-else-if="filteredUsers && filteredUsers.length">
                        <div class="flex justify-between items-center mb-4 px-4">
                            <div v-if="searchQuery" class="text-sm text-gray-600">
                                Found {{ filteredUsers.length }} result(s) for "{{ searchQuery }}"
                            </div>
                            <div v-else class="text-sm text-gray-600">
                                Showing all {{ users.length }} Dosen
                            </div>
                        </div>

                        <DataTable :headers="headers" :items="filteredUsers" class="mt-4">
                            <template #column-name="{ item }">
                                <span class="font-medium">{{ item.user.name }}</span>
                            </template>

                            <template #column-email="{ item }">
                                <div class="flex items-center">
                                    <font-awesome-icon :icon="['fas', 'envelope']" class="mr-2 text-gray-400" />
                                    {{ item.user.email }}
                                </div>
                            </template>

                            <template #column-major_name="{ item }">
                                <div class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-sm">
                                    {{ item.major_name }}
                                </div>
                            </template>

                            <template #column-actions="{ item }">
                                <div class="flex justify-center space-x-2">
                                    <button @click="editDosen(item)"
                                        class="p-2 text-yellow-600 hover:text-yellow-800 hover:bg-yellow-100 rounded-full transition-colors"
                                        title="Edit Dosen">
                                        <font-awesome-icon :icon="['fas', 'edit']" />
                                    </button>
                                    <button @click="deleteDosen(item.nip)"
                                        class="p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-full transition-colors"
                                        title="Delete Dosen">
                                        <font-awesome-icon :icon="['fas', 'trash']" />
                                    </button>
                                </div>
                            </template>
                        </DataTable>
                    </template>
                    <template v-else>
                        <div class="flex flex-col items-center justify-center p-8">
                            <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                </path>
                            </svg>
                            <p class="text-xl font-medium text-gray-600">No results found</p>
                            <p class="text-gray-500 mt-1">Try adjusting your search or filter to find what you're
                                looking for.</p>
                            <button v-if="searchQuery" @click="clearSearch"
                                class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                Clear Search
                            </button>
                        </div>
                    </template>
                </Card>

                <button @click="inputDosen"
                    class="fixed bottom-8 right-8 w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 transition-all">
                    <font-awesome-icon :icon="['fas', 'plus']" />
                </button>

                <!-- Modal Edit Dosen -->
                <div v-if="showEditModal"
                    class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 z-50">
                    <div class="bg-white p-6 rounded-lg w-96">
                        <h2 class="text-xl font-bold mb-4">Edit Dosen</h2>

                        <label class="block mb-2">Nama Dosen</label>
                        <input v-model="editedDosen.name" type="text" class="w-full px-3 py-2 border rounded-lg mb-4" />

                        <label class="block mb-2">Email</label>
                        <input v-model="editedDosen.email" type="email"
                            class="w-full px-3 py-2 border rounded-lg mb-4" />

                        <label class="block mb-2">Kode Dosen</label>
                        <input v-model="editedDosen.kode_dosen" type="text"
                            class="w-full px-3 py-2 border rounded-lg mb-4" />

                        <label class="block mb-2">Jurusan</label>
                        <p class="px-3 py-2 border rounded-lg bg-gray-100">
                            {{ editedDosen.major_name }}
                        </p>

                        <div class="mt-4 flex justify-end">
                            <button @click="showEditModal = false" class="px-4 py-2 mr-2 bg-gray-300 rounded-lg">
                                Batal
                            </button>
                            <button @click="updateDosen" class="px-4 py-2 bg-blue-600 text-white rounded-lg">
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
