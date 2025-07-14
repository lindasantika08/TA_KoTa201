<script>
import axios from "axios";
import { router } from "@inertiajs/vue3";
import Sidebar from "@/Components/SidebarAdmin.vue";
import NavbarAdmin from "@/Components/NavbarAdmin.vue";
import Card from "@/Components/Card.vue";
import DataTable from "@/Components/DataTable.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import Swal from "sweetalert2";


export default {
    name: "admin.ManageMahasiswa",
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
                { text: "Manage Mahasiswa", href: "/admin/manage-mahasiswa" },
            ],
            users: [],
            filteredUsers: [],
            searchQuery: "",
            selectedAngkatan: "",
            selectedClass: "",
            selectedMajor: "",
            angkatanList: [],
            classList: [],
            majorList: [],
            headers: [
                { key: "no", label: "No" },
                { key: "name", label: "Nama" },
                { key: "nim", label: "NIM" },
                { key: "email", label: "Email" },
                { key: "angkatan", label: "Angkatan" },
                { key: "class", label: "Kelas" },
                { key: "major", label: "Jurusan" },
                { key: "actions", label: "Aksi" },
            ],
            showEditModal: false,
            editedMahasiswa: {
                nim: "",
                name: "",
                email: "",
                angkatan: "",
                class: "",
                major: "",
                user_id: null,
            },
        };
    },
    mounted() {
        this.fetchAngkatan();
        this.fetchClassList();
        this.fetchMajorList();
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
        selectedAngkatan() {
            this.filterUsers();
        },
        selectedClass() {
            this.filterUsers();
        },
        selectedMajor() { // Add watch for selected major
            this.filterUsers();
        },
    },
    methods: {
        async fetchUsers() {
            try {
                const response = await axios.get("/api/get-mahasiswa");
                this.users = response.data.map((user, index) => ({
                    ...user,
                    no: index + 1,
                }));
                this.filteredUsers = [...this.users];
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
        async fetchMajorList() { // Add method to fetch major list
            try {
                const response = await axios.get("/api/get-majors");
                this.majorList = response.data;
            } catch (error) {
                console.error("Error fetching major list:", error);
            }
        },
        filterUsers() {
            if (
                !this.searchQuery &&
                !this.selectedMajor &&
                !this.selectedAngkatan &&
                !this.selectedClass
            ) {
                this.filteredUsers = [...this.users];
                return;
            }

            const query = this.searchQuery.toLowerCase();
            this.filteredUsers = this.users.filter((user) => {
                const nameMatch = user.user.name.toLowerCase().includes(query);
                const nimMatch = user.nim.toLowerCase().includes(query);
                const emailMatch = user.user.email.toLowerCase().includes(query);
                const angkatanMatch =
                    !this.selectedAngkatan ||
                    user.class_room.angkatan === this.selectedAngkatan;
                const classMatch =
                    !this.selectedClass ||
                    user.class_room.class_name === this.selectedClass;
                const majorMatch =
                    !this.selectedMajor ||
                    user.major_name === this.selectedMajor;
                return (nameMatch || nimMatch || emailMatch) && angkatanMatch && classMatch && majorMatch;
            });
        },
        clearSearch() {
            this.searchQuery = "";
        },

        inputMahasiswa() {
            router.visit("/admin/manage-mahasiswa/input");
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
        async updateMahasiswa() {
            try {
                await axios.post(`/api/update-mahasiswa`, this.editedMahasiswa);
                this.showEditModal = false;
                await this.fetchUsers();
                Swal.fire({
                    icon: "success",
                    title: "Berhasil",
                    text: "Mahasiswa berhasil diperbarui!",
                });
            } catch (error) {
                Swal.fire({
                    icon: "error",
                    title: "Gagal",
                    text: "Gagal memperbarui mahasiswa",
                });
                console.error(error);
            }
        },

        async deleteMahasiswa(NIM) {
            const result = await Swal.fire({
                title: `Apakah Anda yakin ingin menghapus mahasiswa dengan NIM ${NIM}?`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Ya, hapus!",
                cancelButtonText: "Batal",
            });

            if (!result.isConfirmed) return;

            try {
                const response = await axios.post("/api/delete-mahasiswa", {
                    nim: NIM,
                });

                if (response.status === 201) {
                    await this.fetchUsers();
                    Swal.fire({
                        icon: "success",
                        title: "Berhasil",
                        text: "Mahasiswa berhasil dihapus!",
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: "error",
                    title: "Gagal",
                    text: "Gagal menghapus mahasiswa",
                });
                console.error(error);
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
                        <h1 class="text-3xl font-bold text-gray-800">Mahasiswa Management</h1>
                        <p class="text-gray-600">Manage mahasiswa data and their details</p>
                    </div>
                </div>

                <!-- Mahasiswa Management Card -->
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
                                    placeholder="Search by name or nim" />
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

                        <!-- Filter Dropdowns -->
                        <div class="w-full md:w-64">
                            <select v-model="selectedAngkatan"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Semua Angkatan</option>
                                <option v-for="angkatan in angkatanList" :key="angkatan" :value="angkatan">
                                    {{ angkatan }}
                                </option>
                            </select>
                        </div>

                        <div class="w-full md:w-64">
                            <select v-model="selectedClass"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Semua Kelas</option>
                                <option v-for="classItem in classList" :key="classItem" :value="classItem">
                                    {{ classItem }}
                                </option>
                            </select>
                        </div>
                        <!-- Add Major Dropdown -->
                        <div class="w-full md:w-64">
                            <select v-model="selectedMajor"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Semua Jurusan</option>
                                <option v-for="major in majorList" :key="major" :value="major">
                                    {{ major }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <template v-if="isLoading">
                        <div class="text-center p-8">Loading...</div>
                    </template>

                    <template v-else-if="filteredUsers && filteredUsers.length">
                        <div class="flex justify-between items-center mb-4 px-4">
                            <div v-if="searchQuery" class="text-sm text-gray-600">
                                Found {{ filteredUsers.length }} result(s) for "{{ searchQuery }}"
                            </div>
                            <div v-else class="text-sm text-gray-600">
                                Showing all {{ users.length }} Mahasiswa
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

                            <template #column-angkatan="{ item }">
                                <div class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-sm text-center">
                                    {{ item.class_room.angkatan }}
                                </div>
                            </template>

                            <template #column-class="{ item }">
                                <div class="px-3 py-1 bg-gray-100 rounded-full text-center">
                                    {{ item.class_room.class_name }}
                                </div>
                            </template>

                            <template #column-major="{ item }">
                                <div class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-sm text-center">
                                    {{ item.major_name }}
                                </div>
                            </template>

                            <template #column-actions="{ item }">
                                <div class="flex justify-center space-x-2">
                                    <button @click="editMahasiswa(item)"
                                        class="p-2 text-yellow-600 hover:text-yellow-800 hover:bg-yellow-100 rounded-full transition-colors"
                                        title="Edit Mahasiswa">
                                        <font-awesome-icon :icon="['fas', 'edit']" />
                                    </button>
                                    <button @click="deleteMahasiswa(item.nim)"
                                        class="p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-full transition-colors"
                                        title="Delete Mahasiswa">
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

                <button @click="inputMahasiswa"
                    class="fixed bottom-8 right-8 w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 transition-all">
                    <font-awesome-icon :icon="['fas', 'plus']" />
                </button>

                <!-- Modal Edit Mahasiswa -->
                <div v-if="showEditModal"
                    class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 z-50">
                    <div class="bg-white p-6 rounded-lg w-96">
                        <h2 class="text-xl font-bold mb-4">Edit Mahasiswa</h2>

                        <label class="block mb-2">NIM</label>
                        <input v-model="editedMahasiswa.nim" type="text" disabled
                            class="w-full px-3 py-2 border rounded-lg mb-4 bg-gray-100" />

                        <label class="block mb-2">Nama Mahasiswa</label>
                        <input v-model="editedMahasiswa.name" type="text"
                            class="w-full px-3 py-2 border rounded-lg mb-4" />

                        <label class="block mb-2">Email</label>
                        <input v-model="editedMahasiswa.email" type="email"
                            class="w-full px-3 py-2 border rounded-lg mb-4" />

                        <label class="block mb-2">Angkatan</label>
                        <select v-model="editedMahasiswa.angkatan" class="w-full px-3 py-2 border rounded-lg mb-4">
                            <option v-for="angkatan in angkatanList" :key="angkatan" :value="angkatan">
                                {{ angkatan }}
                            </option>
                        </select>

                        <label class="block mb-2">Kelas</label>
                        <select v-model="editedMahasiswa.class" class="w-full px-3 py-2 border rounded-lg mb-4">
                            <option v-for="classItem in classList" :key="classItem" :value="classItem">
                                {{ classItem }}
                            </option>
                        </select>

                        <div class="mt-4 flex justify-end">
                            <button @click="showEditModal = false" class="px-4 py-2 mr-2 bg-gray-300 rounded-lg">
                                Batal
                            </button>
                            <button @click="updateMahasiswa" class="px-4 py-2 bg-blue-600 text-white rounded-lg">
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
