<script>
import axios from "axios";
import { router } from "@inertiajs/vue3";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import DataTable from "@/Components/DataTable.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import { debounce } from "lodash";
import Swal from "sweetalert2";

export default {
    name: "ManageMahasiswa",
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
                { text: "Manage Mahasiswa", href: "/sispa/dosen/manage-mahasiswa" },
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
            searchQuery: "",
            angkatanList: [],
            classList: [],
            allUsers: [], // Store all users for client-side filtering
        };
    },
    watch: {
        selectedAngkatan() {
            this.applyFilters();
        },
        selectedClass() {
            this.applyFilters();
        },
        searchQuery() {
            this.debouncedSearch();
        },
    },
    created() {
        this.debouncedSearch = debounce(this.applyFilters, 300);
    },
    mounted() {
        this.initialize();
    },
    computed: {
        filteredUsers() {
            return this.users.map((user, index) => ({
                ...user,
                no: index + 1,
            }));
        },
    },
    methods: {
        async initialize() {
            await Promise.all([
                this.fetchAngkatan(),
                this.fetchClassList(),
                this.fetchUsers(),
            ]);
        },

        async fetchUsers() {
            try {
                const response = await axios.get("/sispa/api/get-mahasiswa");
                this.allUsers = response.data;
                this.applyFilters();
            } catch (error) {
                console.error("Error fetching users:", error);
            }
        },

        async fetchAngkatan() {
            try {
                const response = await axios.get("/sispa/api/get-angkatan");
                this.angkatanList = response.data;
            } catch (error) {
                console.error("Error fetching angkatan:", error);
            }
        },

        async fetchClassList() {
            try {
                const response = await axios.get("/sispa/api/get-class");
                this.classList = response.data;
            } catch (error) {
                console.error("Error fetching class list:", error);
            }
        },

        applyFilters() {
            let filteredData = [...this.allUsers];

            // Apply angkatan filter
            if (this.selectedAngkatan) {
                filteredData = filteredData.filter(
                    (user) => user.class_room.angkatan === this.selectedAngkatan
                );
            }

            // Apply class filter
            if (this.selectedClass) {
                filteredData = filteredData.filter(
                    (user) => user.class_room.class_name === this.selectedClass
                );
            }

            // Apply search filter
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filteredData = filteredData.filter((user) => {
                    const userName = user.user.name.toLowerCase();
                    const userNim = user.nim.toLowerCase();
                    return userName.includes(query) || userNim.includes(query);
                });
            }

            this.users = filteredData;
        },

        inputMahasiswa() {
            router.visit("/sispa/dosen/manage-mahasiswa/input");
        },

        detailUser(user_id) {
            router.visit(`/sispa/dosen/manage-mahasiswa/detail?user_id=${user_id}`);
        },

        async deleteUser(userId) {
            try {
            const response = await axios.delete(`/sispa/api/delete-mhs/${userId}`);
            Swal.fire({
                icon: 'success',
                title: 'User Deleted',
                text: 'The user has been successfully deleted.'
            });
            this.fetchUsers();
            } catch (error) {
            if (error.response.data.requires_action) {
                Swal.fire({
                icon: 'warning',
                title: 'Action Required',
                text: 'Tidak dapat menghapus mahasiswa yang memiliki group.'
                });
            } else {
                Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error deleting user: ' + (error.response?.data?.message || error.message)
                });
            }
            }
        },

        confirmDelete(user) {
            if (confirm(`Apakah Anda yakin ingin menghapus mahasiswa ${user.user.name}?`)) {
                this.deleteUser(user.user_id);
            }
        },

        detailUser(user_id) {
            router.visit(`/sispa/dosen/manage-mahasiswa/detail?user_id=${user_id}`);
        },

        async confirmDeleteMahasiswa(item) {
            // Ensure we're passing the correct user ID
            const userId = item.user_id || (item.user && item.user.id) || item;

            try {
                // First, check if the user belongs to any groups
                const response = await axios.get(`/sispa/api/check-mahasiswa-groups/${userId}`);

                if (response.data.has_groups) {
                    // If user belongs to groups, show a warning
                    Swal.fire({
                        title: 'Warning: User Belongs to Groups',
                        html: `
                <p>This user is a member of the following groups:</p>
                <ul class="list-disc list-inside text-left mt-2">
                    ${response.data.groups.map(group =>
                            `<li>${group.project_name} - Group ${group.group_name}</li>`
                        ).join('')}
                </ul>
                <p class="mt-3 text-red-600">Please remove the user from the groups first.</p>
                `,
                        icon: 'warning',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    });
                } else {
                    // If no groups, directly confirm deletion
                    Swal.fire({
                        title: 'Delete User',
                        text: 'Are you sure you want to delete this user?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete user',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.performMahasiswaDeletion(userId);
                        }
                    });
                }
            } catch (error) {
                // Handle specific error cases
                if (error.response?.data?.error === "Mahasiswa tidak ditemukan") {
                    Swal.fire({
                        icon: 'error',
                        title: 'User Not Found',
                        text: 'The specified user could not be found in the system.'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to check user groups: ' + (error.response?.data?.message || error.message)
                    });
                }
            }
        },

        async performMahasiswaDeletion(userId) {
            try {
                const response = await axios.delete(`/sispa/api/delete-mhs/${userId}`);

                Swal.fire({
                    icon: 'success',
                    title: 'User Deleted',
                    text: response.data.message
                });

                // Refresh the user list
                this.fetchUsers();
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Deletion Error',
                    text: error.response?.data?.message || 'Failed to delete user'
                });
            }
        }
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
                <Card title="Kelola Mahasiswa">
                    <template #actions>
                        <!-- Filter and Search in a row -->
                        <div class="grid grid-cols-5 gap-4 mb-6">
                            <!-- Dropdown Angkatan -->
                            <div>
                                <label for="angkatan-select" class="block text-sm font-medium text-gray-700">
                                    Filter Angkatan
                                </label>
                                <select id="angkatan-select" v-model="selectedAngkatan"
                                    class="mt-2 p-2 border border-gray-300 rounded w-full">
                                    <option value="">Semua Angkatan</option>
                                    <option v-for="angkatan in angkatanList" :key="angkatan" :value="angkatan">
                                        {{ angkatan }}
                                    </option>
                                </select>
                            </div>

                            <!-- Dropdown Kelas -->
                            <div>
                                <label for="class-select" class="block text-sm font-medium text-gray-700">
                                    Filter Kelas
                                </label>
                                <select id="class-select" v-model="selectedClass"
                                    class="mt-2 p-2 border border-gray-300 rounded w-full">
                                    <option value="">Semua Kelas</option>
                                    <option v-for="classItem in classList" :key="classItem" :value="classItem">
                                        {{ classItem }}
                                    </option>
                                </select>
                            </div>

                            <!-- Search Input -->
                            <div class="col-span-2">
                                <label for="search" class="block text-sm font-medium text-gray-700">
                                    Cari (Nama/NIM)
                                </label>
                                <input type="text" id="search" v-model="searchQuery"
                                    placeholder="Cari berdasarkan nama atau NIM..."
                                    class="mt-2 p-2 border border-gray-300 rounded w-full" />
                            </div>
                        </div>

                        <!-- Data Table -->
                        <DataTable :headers="headers" :items="filteredUsers" class="mt-10">
                            <template #column-actions="{ item }">
                                <div class="flex space-x-2">
                                    <!-- Tombol Detail -->
                                    <button @click="detailUser(item.user_id)"
                                        class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                        <font-awesome-icon icon="fa-solid fa-eye" />
                                    </button>

                                    <!-- Tombol Delete -->
                                    <button @click="confirmDeleteMahasiswa(item)"
                                        class="px-3 py-1 bg-red-500 text-white rounded-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                        <font-awesome-icon icon="fa-solid fa-trash" />
                                    </button>
                                </div>
                            </template>


                            <template #column-angkatan="{ item }">
                                {{ item.class_room.angkatan }}
                            </template>

                            <template #column-class="{ item }">
                                {{ item.class_room.class_name }}
                            </template>

                            <template #column-name="{ item }">
                                {{ item.user.name }}
                            </template>

                            <template #column-nim="{ item }">
                                {{ item.nim }}
                            </template>

                            <template #column-email="{ item }">
                                {{ item.user.email }}
                            </template>

                        </DataTable>
                    </template>
                </Card>

                <button @click="inputMahasiswa"
                    class="fixed bottom-8 right-8 flex items-center justify-center w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105">
                    <font-awesome-icon :icon="['fas', 'plus']" />
                </button>
            </main>
        </div>
    </div>
</template>
