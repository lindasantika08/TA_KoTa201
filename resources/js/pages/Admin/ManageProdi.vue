<script>
import Sidebar from "@/components/SidebarAdmin.vue";
import Navbar from "@/components/Navbar.vue";
import Card from "@/components/Card.vue";
import Breadcrumb from "@/components/Breadcrumb.vue";
import DataTable from "@/components/DataTable.vue";
import axios from "axios";

export default {
    components: {
        Sidebar,
        Navbar,
        Card,
        Breadcrumb,
        DataTable,
    },
    data() {
        return {
            Breadcrumb: [
                { text: "Dashboard", href: "/admin/dashboard" },
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
    methods: {
        async fetchProdi() {
            this.isLoading = true;
            try {
                const response = await axios.get("/api/get-prodi");

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

                this.selectedProdis = [];
            } catch (error) {
                console.error("Error fetching prodi:", error);
            } finally {
                this.isLoading = false;
            }
        },

        async fetchMajor() {
            try {
                const response = await axios.get("/api/get-major-forDropDown");
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
                await axios.post("/api/add-prodi", {
                    major_name: this.formData.selectedMajor,
                    prodi_name: this.formData.prodiName,
                });
                this.showModalInput = false;
                this.fetchProdi();
                alert("Prodi added successfully!");
            } catch (error) {
                console.error("Error adding prodi:", error);
                alert("Failed to add prodi!");
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
                await axios.post(`/api/update-prodi`, {
                    old_major_name: this.editData.majorName,
                    old_prodi_name: this.editData.originalProdiName, // Gunakan nilai awal
                    new_major_name: this.editData.majorName,
                    new_prodi_name: this.editData.prodiName,
                });
                this.showModalEdit = false;
                await this.fetchProdi();
                alert("Prodi updated successfully!");
            } catch (error) {
                console.error("Error updating prodi:", error);
                alert("Failed to update prodi!");
            }
        },

        async deleteSelectedProdis() {
            if (!this.hasSelectedProdis) return;

            if (
                !confirm(
                    `Are you sure you want to delete ${this.selectedProdis.length} selected prodi(s)?`
                )
            ) {
                return;
            }

            try {
                for (const prodiName of this.selectedProdis) {
                    await axios.post("/api/delete-prodi", {
                        prodi_name: prodiName,
                    });
                }
                alert("Selected prodis deleted successfully!");
                this.selectedProdis = [];
                await this.fetchProdi();
            } catch (error) {
                console.error("Error Deleting Prodis: ", error);
                alert("Failed to delete selected prodis!");
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
    },
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <Sidebar role="admin" />
        <div class="flex-1">
            <Navbar userName="admin" />
            <main class="p-6">
                <Breadcrumb :items="Breadcrumb" class="mb-4" />

                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">
                        Prodi Management
                    </h1>
                    <p class="text-gray-600">
                        Manage academic prodi and their details
                    </p>
                </div>

                <card title="Prodi List" class="bg-white">
                    <div v-if="isLoading" class="flex justify-center p-8">
                        <font-awesome-icon
                            :icon="['fas', 'spinner']"
                            class="text-blue-600 text-2xl animate-spin"
                        />
                    </div>

                    <div v-else-if="prodis && prodis.length">
                        <DataTable :headers="headers" :items="prodis">
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

                                        <button
                                            v-if="hasSelectedProdis"
                                            @click="deleteSelectedProdis"
                                            class="p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-full transition-colors"
                                        >
                                            <font-awesome-icon
                                                :icon="['fas', 'trash']"
                                            />
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </DataTable>
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
