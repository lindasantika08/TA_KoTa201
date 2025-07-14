<script>
import Sidebar from "@/Components/SidebarAdmin.vue";
import NavbarAdmin from "@/Components/NavbarAdmin.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import DataTable from "@/Components/DataTable.vue";
import Swal from "sweetalert2";

import axios from "axios";

export default {
    components: {
        DataTable,
        Sidebar,
        NavbarAdmin,
        Card,
        Breadcrumb,
    },
    data() {
        return {
            Breadcrumb: [
                { text: "Dashboard", href: "/admin/dashboard" },
                { text: "Majors Management", href: "#" },
            ],
            headers: [
                { key: "no", label: "No" },
                { key: "major_name", label: "Majors Name" },
                { key: "student_count", label: "Total Students" },
                { key: "last_updated", label: "Last Updated" },
                { key: "action", label: "Actions" },
            ],
            majors: [],
            showModal: false,
            newMajor: "",
            showModalEdit: false,
            editedMajor: "",
            originalMajor: "",
            isLoading: false,
            filteredMajors: [],
            searchQuery: "",
        };
    },
    created() {
        this.fetchMajors();
    },
    computed: {
        totalMajors() {
            return this.majors.length;
        },
    },
    watch: {
        searchQuery() {
            this.applyFilteredMajors();
        },
        majors: {
            handler() {
                this.applyFilteredMajors();
            },
            immediate: true,
        },
    },
    methods: {
        async applyFilteredMajors() {
            if (!this.searchQuery) {
                this.filteredMajors = [...this.majors];
                return;
            }

            const query = this.searchQuery.toLowerCase();
            this.filteredMajors = this.majors.filter((major) =>
                major.major_name.toLowerCase().includes(query)
            );
        },
        async fetchMajors() {
            this.isLoading = true;
            try {
                const response = await axios.get("/api/get-major");
                this.majors = response.data.map((item, index) => ({
                    no: index + 1,
                    major_name: item.major_name,
                    student_count: item.student_count,
                    last_updated: new Date(
                        item.updated_at
                    ).toLocaleDateString(),
                }));
            } catch (error) {
                console.error(error);
            } finally {
                this.isLoading = false;
            }
        },
        async inputMajor() {
            this.showModal = true;
        },
        async saveMajor() {
            if (!this.newMajor.trim()) {
                Swal.fire({
                    icon: "warning",
                    title: "Validation Error",
                    text: "Major Name can't be empty!",
                });
                return;
            }
            this.isLoading = true;
            try {
                const response = await axios.post("/api/add-major", {
                    major_name: this.newMajor,
                });

                if (response.status === 201) {
                    Swal.fire({
                        icon: "success",
                        title: "Success",
                        text: "Major added successfully!",
                    });
                    this.showModal = false;
                    this.newMajor = "";
                    await this.fetchMajors();
                }
            } catch (error) {
                console.error("Error Adding Major: ", error);
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Failed to add Major!",
                });
            } finally {
                this.isLoading = false;
            }
        },
        async deleteMajor(majorName) {
            const result = await Swal.fire({
                title: `Are you sure?`,
                text: `You are about to delete "${majorName}". This action cannot be undone.`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!",
            });

            if (!result.isConfirmed) {
                return;
            }

            this.isLoading = true;
            try {
                const response = await axios.post("/api/delete-major", {
                    major_name: majorName,
                });

                if (response.status === 201) {
                    Swal.fire({
                        icon: "success",
                        title: "Deleted!",
                        text: "Major deleted successfully!",
                    });
                    await this.fetchMajors();
                }
            } catch (error) {
                console.error("Error Deleting Major: ", error);
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Failed to delete major!",
                });
            } finally {
                this.isLoading = false;
            }
        },
        async EditMajor(majorName) {
            this.originalMajor = majorName;
            this.editedMajor = majorName;
            this.showModalEdit = true;
        },
        async updateMajor() {
            if (!this.editedMajor.trim()) {
                Swal.fire({
                    icon: "warning",
                    title: "Validation Error",
                    text: "Major Name can't be empty!",
                });
                return;
            }
            this.isLoading = true;
            try {
                const response = await axios.post("/api/edit-major", {
                    old_major_name: this.originalMajor,
                    new_major_name: this.editedMajor,
                });

                if (response.status === 200) {
                    Swal.fire({
                        icon: "success",
                        title: "Success",
                        text: "Major updated successfully!",
                    });
                    this.showModalEdit = false;
                    await this.fetchMajors();
                }
            } catch (error) {
                console.error("Error Editing Major: ", error);
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Failed to update Major!",
                });
            } finally {
                this.isLoading = false;
            }
        },
        async clearSearch() {
            this.searchQuery = "";
        },
    },
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <Sidebar role="admin" />
        <div class="flex-1">
            <NavbarAdmin userName="admin" />
            <main class="p-6">
                <Breadcrumb :items="Breadcrumb" class="mb-4" />

                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">
                        Majors Management
                    </h1>
                    <p class="text-gray-600">
                        Manage academic majors and their details
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <!-- Total Majors Card -->
                    <div
                        class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500"
                    >
                        <div class="flex items-center">
                            <div
                                class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <!-- Ikon untuk jurusan/fakultas -->
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                    />
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">
                                    Total Majors
                                </p>
                                <p class="text-2xl font-bold text-gray-800">
                                    {{ totalMajors }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Active Students Card -->
                    <div
                        class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500"
                    >
                        <div class="flex items-center">
                            <div
                                class="p-3 rounded-full bg-green-100 text-green-500 mr-4"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <!-- Ikon untuk mahasiswa -->
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8.398 0M9 3v2m1.5-1.5h-3"
                                    />
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">
                                    Active Students
                                </p>
                                <p class="text-2xl font-bold text-gray-800">
                                    {{
                                        majors.reduce(
                                            (sum, major) =>
                                                sum + major.student_count,
                                            0
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Last Update Card -->
                    <div
                        class="bg-white rounded-xl shadow-md p-6 border-l-4 border-red-500"
                    >
                        <div class="flex items-center">
                            <div
                                class="p-3 rounded-full bg-red-100 text-red-500 mr-4"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <!-- Ikon untuk tanggal/update -->
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Last Update</p>
                                <p class="text-2xl font-bold text-gray-800">
                                    {{ new Date().toLocaleDateString() }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <Card title="Major List" class="bg-white">
                    <div class="mb-4 px-4 pt-2">
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"
                            >
                                <svg
                                    class="w-5 h-5 text-gray-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    ></path>
                                </svg>
                            </div>
                            <input
                                type="text"
                                v-model="searchQuery"
                                class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Search by name or nim..."
                            />
                            <button
                                v-if="searchQuery"
                                @click="clearSearch"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700"
                            >
                                <svg
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    ></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="isLoading"
                        class="flex justify-center items-center p-8"
                    >
                        <font-awesome-icon
                            :icon="['fas', 'spinner']"
                            class="text-blue-600 text-2xl animate-spin"
                        />
                    </div>

                    <div v-else-if="filteredMajors && filteredMajors.length">
                        <div
                            class="flex justify-between items-center mb-4 px-4"
                        >
                            <div
                                v-if="searchQuery"
                                class="text-sm text-gray-600"
                            >
                                Found {{ filteredMajors.length }} result(s) for
                                "{{ searchQuery }}"
                            </div>
                            <div v-else class="text-sm text-gray-600">
                                Showing all {{ majors.length }} major(s)
                            </div>
                        </div>
                        <DataTable :headers="headers" :items="filteredMajors">
                            <template #column-no="{ item }">
                                <div class="text-center font-medium">
                                    {{ item.no }}
                                </div>
                            </template>

                            <template #column-major_name="{ item }">
                                <div class="text-left font-medium">
                                    {{ item.major_name }}
                                </div>
                            </template>

                            <template #column-student_count="{ item }">
                                <div class="text-center">
                                    <span
                                        class="px-3 py-1 rounded-full bg-blue-100 text-blue-800"
                                    >
                                        {{ item.student_count }} students
                                    </span>
                                </div>
                            </template>

                            <template #column-last_updated="{ item }">
                                <div class="text-center text-gray-600">
                                    {{ item.last_updated }}
                                </div>
                            </template>

                            <template #column-action="{ item }">
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
                                        @click="deleteMajor(item.major_name)"
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
                    </div>
                    <div
                        v-else-if="!isLoading"
                        class="flex flex-col items-center justify-center p-8"
                    >
                        <svg
                            class="w-16 h-16 text-gray-400 mb-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                        <p class="text-xl font-medium text-gray-600">
                            No results found
                        </p>
                        <p class="text-gray-500 mt-1">
                            Try adjusting your search or filter to find what
                            you're looking for.
                        </p>
                        <button
                            v-if="searchQuery"
                            @click="clearSearch"
                            class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                        >
                            Clear Search
                        </button>
                    </div>
                </Card>

                <!-- Add Major Button -->
                <button
                    @click="inputMajor"
                    class="fixed bottom-8 right-8 flex items-center justify-center w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105"
                    title="Add New Major"
                >
                    <font-awesome-icon :icon="['fas', 'plus']" />
                </button>

                <!-- Add Major Modal -->
                <div
                    v-if="showModal"
                    class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 z-50"
                >
                    <div class="bg-white p-6 rounded-lg shadow-lg w-96">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-semibold">Add New Major</h2>
                            <button
                                @click="showModal = false"
                                class="text-gray-500 hover:text-gray-700"
                            >
                                <font-awesome-icon :icon="['fas', 'times']" />
                            </button>
                        </div>
                        <input
                            v-model="newMajor"
                            type="text"
                            placeholder="Enter Major Name"
                            class="w-full p-2 border rounded-md mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <div class="flex justify-end space-x-2">
                            <button
                                @click="showModal = false"
                                class="px-4 py-2 bg-gray-400 rounded-md text-white hover:bg-gray-500 transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                @click="saveMajor"
                                class="px-4 py-2 bg-blue-600 rounded-md text-white hover:bg-blue-700 transition-colors"
                            >
                                Save Major
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Edit Major Modal -->
                <div
                    v-if="showModalEdit"
                    class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 z-50"
                >
                    <div class="bg-white p-6 rounded-lg shadow-lg w-96">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-semibold">Edit Major</h2>
                            <button
                                @click="showModalEdit = false"
                                class="text-gray-500 hover:text-gray-700"
                            >
                                <font-awesome-icon :icon="['fas', 'times']" />
                            </button>
                        </div>
                        <input
                            v-model="editedMajor"
                            type="text"
                            placeholder="Enter Major Name"
                            class="w-full p-2 border rounded-md mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <div class="flex justify-end space-x-2">
                            <button
                                @click="showModalEdit = false"
                                class="px-4 py-2 bg-gray-400 rounded-md text-white hover:bg-gray-500 transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                @click="updateMajor"
                                class="px-4 py-2 bg-blue-600 rounded-md text-white hover:bg-blue-700 transition-colors"
                            >
                                Update Major
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>
