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
                { text: "Dashboard", href: "/admin/dashboard" },
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
        };
    },
    mounted() {
        this.fetchUsers();
    },
    watch: {
        searchQuery: {
            handler() {
                this.filterUsers();
            },
        },
        selectedMajor: {
            handler() {
                this.filterUsers();
            },
        },
    },
    methods: {
        async fetchUsers() {
            try {
                const response = await axios.get("/api/get-dosen-admin");
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
            this.filteredUsers = this.users
                .filter((user) => {
                    const nameMatch = user.user.name
                        .toLowerCase()
                        .includes(this.searchQuery.toLowerCase());
                    const majorMatch =
                        !this.selectedMajor ||
                        user.major_name === this.selectedMajor;
                    return nameMatch && majorMatch;
                })
                .map((user, index) => ({
                    ...user,
                    no: index + 1,
                }));
        },
        inputDosen() {
            router.visit("/admin/manage-dosen/input");
        },

        async deleteDosen(NIP) {
            if (!confirm(`Apakah Anda yakin ingin menghapus ${NIP}?`)) {
                return;
            }
            try {
                const response = await axios.post("/api/delete-dosen", {
                    nip: NIP,
                });
                if (response.status === 201) {
                    alert("Dosen delete successfully!");
                    await this.fetchUsers();
                }
            } catch (error) {
                alert("failed to delete");
            }
        },
    },
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Sidebar Navigation -->
        <Sidebar role="admin" />

        <!-- Main Content Area -->
        <div class="flex-1">
            <!-- Top Navigation Bar -->
            <Navbar userName="Admin" />

            <!-- Main Content -->
            <main class="p-6">
                <!-- Breadcrumb Navigation -->
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

                    <!-- Table Content -->
                    <template #actions>
                        <DataTable
                            :headers="headers"
                            :items="filteredUsers"
                            class="mt-4"
                        >
                            <!-- Table Columns -->
                            <template #column-name="{ item }">
                                <div class="flex items-center">
                                    <span class="font-medium">{{
                                        item.user.name
                                    }}</span>
                                </div>
                            </template>

                            <template #column-kode_dosen="{ item }">
                                <div
                                    class="px-3 py-1 bg-gray-100 rounded-full text-center"
                                >
                                    {{ item.kode_dosen }}
                                </div>
                            </template>

                            <template #column-nip="{ item }">
                                <div class="font-mono">{{ item.nip }}</div>
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
                                        @click="EditMajor(item.major_name)"
                                        class="p-2 text-yellow-600 hover:text-yellow-800 hover:bg-yellow-100 rounded-full transition-colors"
                                        title="Edit Major"
                                    >
                                        <font-awesome-icon
                                            :icon="['fas', 'edit']"
                                        />
                                    </button>
                                    <button
                                        @click="deleteDosen(item.nip)"
                                        class="p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-full transition-colors"
                                        title="Delete Major"
                                    >
                                        <font-awesome-icon
                                            :icon="['fas', 'trash']"
                                        />
                                    </button>
                                </div>
                            </template>
                        </DataTable>
                    </template>
                </Card>

                <!-- Floating Action Button -->
                <button
                    @click="inputDosen"
                    class="fixed bottom-8 right-8 flex items-center justify-center w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105"
                >
                    <font-awesome-icon :icon="['fas', 'plus']" />
                </button>
            </main>
        </div>
    </div>
</template>

<style scoped></style>
