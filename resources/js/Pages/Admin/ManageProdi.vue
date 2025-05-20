<script>
import Sidebar from "@/Components/SidebarAdmin.vue";
import NavbarAdmin from "@/Components/NavbarAdmin.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import DataTable from "@/Components/DataTable.vue";
import axios from "axios";
import Swal from "sweetalert2";


export default {
    components: {
        Sidebar,
        NavbarAdmin,
        Card,
        Breadcrumb,
        DataTable,
    },
    data() {
        return {
            Breadcrumb: [
                { text: "Dashboard", href: "/sispa/admin/dashboard" },
                { text: "Prodi Management", href: "#" },
            ],
            headers: [
                { key: "no", label: "No" },
                { key: "major_name", label: "Major's Name" },
                { key: "prodi_list", label: "Prodi's Name" },
                { key: "total_prodi", label: "Total Prodi" },
                { key: "action", label: "Actions" },
            ],
            isLoading: false,
            prodis: [],
            filteredProdis: [],
            searchQuery: "",
            showModalInput: false,
            showModalEdit: false,
            majorList: [],
            formData: {
                selectedMajor: "",
                prodiName: "",
            },
            editData: {
                majorName: "",
                selectedMajor: "",
                prodiName: "",
                prodiIndex: null,
            },
            selectedProdis: [],
            lastUpdated: new Date().toLocaleDateString(),
        };
    },
    created() {
        this.fetchProdi();
        this.fetchMajor();
    },
    computed: {
        hasSelectedProdis() {
            return this.selectedProdis.length > 0;
        },
    },
    watch: {
        searchQuery() {
            this.filterProdis();
        },
        prodis: {
            handler() {
                this.filterProdis();
            },
            immediate: true
        }
    },
    methods: {
        filterProdis() {
            if (!this.searchQuery) {
                this.filteredProdis = [...this.prodis];
                return;
            }

            const query = this.searchQuery.toLowerCase();
            this.filteredProdis = this.prodis.filter(item => {
                // Search by major name
                if (item.major_name.toLowerCase().includes(query)) {
                    return true;
                }
                
                // Search by prodi name
                return item.prodi_list.some(prodi => 
                    prodi.toLowerCase().includes(query)
                );
            });
        },
        async fetchProdi() {
            this.isLoading = true;
            try {
                const response = await axios.get("/sispa/api/get-prodi");

                const groupedProdis = response.data.reduce((acc, curr) => {
                    if (!acc[curr.major_name]) {
                        acc[curr.major_name] = {
                            prodi_names: [],
                            count: 0,
                        };
                    }
                    acc[curr.major_name].prodi_names.push(curr.prodi_name);
                    acc[curr.major_name].count++;
                    return acc;
                }, {});

                this.prodis = Object.entries(groupedProdis).map(
                    ([major_name, data], index) => ({
                        no: index + 1,
                        major_name: major_name,
                        prodi_list: data.prodi_names,
                        total_prodi: data.count,
                    })
                );

                this.lastUpdated = new Date().toLocaleDateString();
                this.selectedProdis = [];
            } catch (error) {
                console.error("Error fetching prodi:", error);
            } finally {
                this.isLoading = false;
            }
        },

        async fetchMajor() {
            try {
                const response = await axios.get("/sispa/api/get-major-forDropDown");
                this.majorList = response.data;
            } catch (error) {
                console.error("Error fetching majors:", error);
            }
        },

        inputProdi() {
            this.showModalInput = true;
            this.formData = {
                selectedMajor: "",
                prodiName: "",
            };
        },

        async submitProdi() {
            try {
            await axios.post("/sispa/api/add-prodi", {
                major_name: this.formData.selectedMajor,
                prodi_name: this.formData.prodiName,
            });
            this.showModalInput = false;
            this.fetchProdi();
            Swal.fire({
                icon: "success",
                title: "Success",
                text: "Prodi added successfully!",
            });
            } catch (error) {
            console.error("Error adding prodi:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Failed to add prodi!",
            });
            }
        },
        editProdi(item, prodiName, index) {
            this.editData = {
                majorName: item.major_name,
                originalProdiName: prodiName, // Tambah properti untuk menyimpan nilai awal
                prodiName: prodiName,
                prodiIndex: index,
            };
            this.showModalEdit = true;
        },

        async updateProdi() {
            try {
            await axios.post(`/sispa/api/update-prodi`, {
                old_major_name: this.editData.majorName,
                old_prodi_name: this.editData.originalProdiName, // Gunakan nilai awal
                new_major_name: this.editData.majorName,
                new_prodi_name: this.editData.prodiName,
            });
            this.showModalEdit = false;
            await this.fetchProdi();
            Swal.fire({
                icon: "success",
                title: "Success",
                text: "Prodi updated successfully!",
            });
            } catch (error) {
            console.error("Error updating prodi:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Failed to update prodi!",
            });
            }
        },

        async deleteSelectedProdis() {
            if (!this.hasSelectedProdis) return;

            const result = await Swal.fire({
            title: `Are you sure?`,
            text: `You are about to delete ${this.selectedProdis.length} selected prodi(s). This action cannot be undone.`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, delete it!",
            });

            if (!result.isConfirmed) {
            return;
            }

            try {
            for (const prodiName of this.selectedProdis) {
                await axios.post("/sispa/api/delete-prodi", {
                prodi_name: prodiName,
                });
            }
            Swal.fire({
                icon: "success",
                title: "Deleted!",
                text: "Selected prodis have been deleted successfully.",
            });
            this.selectedProdis = [];
            await this.fetchProdi();
            } catch (error) {
            console.error("Error Deleting Prodis: ", error);
            Swal.fire({
                icon: "error",
                title: "Error!",
                text: "Failed to delete selected prodis.",
            });
            }
        },

        toggleProdiSelection(prodiName) {
            const index = this.selectedProdis.indexOf(prodiName);
            if (index === -1) {
                this.selectedProdis.push(prodiName);
            } else {
                this.selectedProdis.splice(index, 1);
            }
        },

        isProdiSelected(prodiName) {
            return this.selectedProdis.includes(prodiName);
        },

        clearSearch() {
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
                        Prodi Management
                    </h1>
                    <p class="text-gray-600">
                        Manage academic prodi and their details
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <!-- Total Prodi Card -->
                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Total Majors</p>
                                <p class="text-2xl font-bold text-gray-800">{{ prodis.length }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Majors Card -->
                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-500 mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Total Prodi</p>
                                <p class="text-2xl font-bold text-gray-800">{{ prodis.reduce((sum, prodi) => sum + prodi.total_prodi, 0) }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Last Updated Prodi -->
                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-red-500">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-100 text-red-500 mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Last Updated</p>
                                <p class="text-2xl font-bold text-gray-800">{{ new Date().toLocaleDateString() }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <card title="Prodi List" class="bg-white">
                    <div class="mb-4 px-4 pt-2">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input
                                type="text"
                                v-model="searchQuery"
                                class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Search by prodi name or major name..."
                            />
                            <button 
                                v-if="searchQuery"
                                @click="clearSearch"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div v-if="isLoading" class="flex justify-center p-8">
                        <font-awesome-icon
                            :icon="['fas', 'spinner']"
                            class="text-blue-600 text-2xl animate-spin"
                        />
                    </div>

                    <div v-else-if="filteredProdis  && filteredProdis .length">
                        <div class="flex justify-between items-center mb-4 px-4">
                            <div v-if="searchQuery" class="text-sm text-gray-600">
                                Found {{ filteredProdis.length }} result(s) for "{{ searchQuery }}"
                            </div>
                            <div v-else class="text-sm text-gray-600">
                                Showing all {{ prodis.length }} major(s)
                            </div>
                            <p v-if="!hasSelectedProdis" class="text-gray-600">Selected prodi to delete</p>
                            <button
                                v-if="hasSelectedProdis"
                                @click="deleteSelectedProdis"
                                class="flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors"
                            >
                            <font-awesome-icon
                                                :icon="['fas', 'trash']"
                                                class="mr-2"
                                            />
                                            Delete Selected ({{ selectedProdis.length }})
                            </button>
                        </div>
                        <DataTable :headers="headers" :items="filteredProdis">
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

                            <template #column-prodi_list="{ item }">
                                <div class="text-left font-medium">
                                    <ul class="list-none p-0">
                                        <li
                                            v-for="(
                                                prodi, index
                                            ) in item.prodi_list"
                                            :key="index"
                                            class="flex items-center space-x-2 py-1"
                                        >
                                            <input
                                                type="checkbox"
                                                :checked="
                                                    isProdiSelected(prodi)
                                                "
                                                @change="
                                                    toggleProdiSelection(prodi)
                                                "
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            />
                                            <span>{{ prodi }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </template>

                            <template #column-total_prodi="{ item }">
                                <div
                                    class="text-center px-3 py-1 rounded-full bg-blue-100 text-blue-800"
                                >
                                    {{ item.total_prodi }} Prodi
                                </div>
                            </template>

                            <template #column-action="{ item }">
                                <div class="flex flex-col space-y-2">
                                    <div
                                        v-for="(
                                            prodi, index
                                        ) in item.prodi_list"
                                        :key="index"
                                        class="flex justify-center space-x-2"
                                    >
                                        <button
                                            @click="
                                                editProdi(item, prodi, index)
                                            "
                                            class="p-2 text-yellow-600 hover:text-yellow-800 hover:bg-yellow-100 rounded-full transition-colors"
                                        >
                                            <font-awesome-icon
                                                :icon="['fas', 'edit']"
                                            />
                                        </button>

                                       
                                    </div>
                                </div>
                            </template>
                        </DataTable>
                    </div>
                    <div v-else-if="!isLoading" class="flex flex-col items-center justify-center p-8">
                        <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-xl font-medium text-gray-600">No results found</p>
                        <p class="text-gray-500 mt-1">Try adjusting your search or filter to find what you're looking for.</p>
                        <button 
                            v-if="searchQuery"
                            @click="clearSearch" 
                            class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                        >
                            Clear Search
                        </button>
                    </div>
                </card>

                <!-- Add Prodi Button -->
                <button
                    @click="inputProdi"
                    class="fixed bottom-8 right-8 flex items-center justify-center w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105"
                    title="Add New Prodi"
                >
                    <font-awesome-icon :icon="['fas', 'plus']" />
                </button>

                <!-- Modal Input Prodi-->
                <div
                    v-if="showModalInput"
                    class="fixed inset-0 z-50 overflow-y-auto"
                >
                    <div
                        class="flex items-center justify-center min-h-screen p-4"
                    >
                        <div class="fixed inset-0 bg-black bg-opacity-50"></div>

                        <div
                            class="relative bg-white rounded-lg shadow-xl w-full max-w-md p-6"
                        >
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Add New Prodi
                            </h3>
                            <form @submit.prevent="submitProdi">
                                <div class="mb-4">
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Select Major
                                    </label>
                                    <select
                                        v-model="formData.selectedMajor"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                    >
                                        <option value="" disabled>
                                            Choose a major
                                        </option>
                                        <option
                                            v-for="major in majorList"
                                            :key="major.id"
                                            :value="major.major_name"
                                        >
                                            {{ major.major_name }}
                                        </option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Prodi Name
                                    </label>
                                    <input
                                        type="text"
                                        v-model="formData.prodiName"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                        placeholder="Enter prodi name"
                                    />
                                </div>

                                <div class="flex justify-end space-x-3">
                                    <button
                                        type="button"
                                        @click="showModalInput = false"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        Save
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Edit Prodi -->
                <div
                    v-if="showModalEdit"
                    class="fixed inset-0 z-50 overflow-y-auto"
                >
                    <div
                        class="flex items-center justify-center min-h-screen p-4"
                    >
                        <div class="fixed inset-0 bg-black bg-opacity-50"></div>

                        <div
                            class="relative bg-white rounded-lg shadow-xl w-full max-w-md p-6"
                        >
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Edit Prodi
                            </h3>
                            <form @submit.prevent="updateProdi">
                                <div class="mb-4">
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Major
                                    </label>
                                    <input
                                        type="text"
                                        :value="editData.majorName"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 cursor-not-allowed"
                                        disabled
                                    />
                                </div>

                                <div class="mb-4">
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Prodi Name
                                    </label>
                                    <input
                                        type="text"
                                        v-model="editData.prodiName"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                        placeholder="Enter prodi name"
                                    />
                                </div>

                                <div class="flex justify-end space-x-3">
                                    <button
                                        type="button"
                                        @click="showModalEdit = false"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        Save
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>