<script>
import axios from "axios";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Dropdown from "@/Components/Dropdown.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import Swal from "sweetalert2";
import DataTable from "@/Components/DataTable.vue";


export default {
    name: "KelolaProyek",
    components: {
        Sidebar,
        Navbar,
        Card,
        Dropdown,
        Breadcrumb,
        DataTable
    },
    data() {
        return {
            breadcrumbs: [
                { text: "Manage Project", href: "/dosen/kelola-proyek" },
            ],
            isModalOpen: false,
            isEditModalOpen: false,
            newProject: {
                semester: "",
                batch_year: "",
                project_name: "",
                prodi_id: "",
                start_date: "",
                end_date: "",
                status: "Active",
            },
            projects: [],
            filteredProjects: [],
            years: [],
            selectedYear: "",
            prodis: [],
            editingProject: null,
        };
    },
    mounted() {
        this.getProjects();
        this.getProdis();
    },
    methods: {
        // Add Project Modal Methods
        openModal() {
            this.isModalOpen = true;
        },
        closeModal() {
            this.isModalOpen = false;
            this.resetNewProjectForm();
        },
        resetNewProjectForm() {
            this.newProject = {
                semester: "",
                batch_year: "",
                project_name: "",
                prodi_id: "",
                start_date: "",
                end_date: "",
                status: "Active",
            };
        },

        // Edit Project Modal Methods
        openEditModal(project) {
            // Create a deep copy of the project to avoid direct mutation
            this.editingProject = { ...project };
            this.isEditModalOpen = true;
        },
        closeEditModal() {
            this.isEditModalOpen = false;
            this.editingProject = null;
        },

        // Project CRUD Methods
        async addProject() {
            try {
                const response = await axios.post(
                    "/api/project",
                    this.newProject,
                    {
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
                        },
                    }
                );
                
                
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Proyek berhasil ditambahkan!',
                    confirmButtonColor: '#3085d6'
                });
                
                this.closeModal();
                this.getProjects();
            } catch (error) {
                console.error("Error adding project:", error);
                
                
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Terjadi kesalahan saat menambahkan proyek.',
                    confirmButtonColor: '#3085d6'
                });
            }
        },
        async updateProject() {
            try {
                const response = await axios.put(
                    `/api/projects/${this.editingProject.id}`, 
                    this.editingProject,
                    {
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
                        },
                    }
                );
                
                
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Proyek berhasil diperbarui!',
                    confirmButtonColor: '#3085d6'
                });
                
                this.closeEditModal();
                this.getProjects();
            } catch (error) {
                console.error("Error updating project:", error);
                
                
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Terjadi kesalahan saat memperbarui proyek.',
                    confirmButtonColor: '#3085d6'
                });
            }
        },
        async deleteProject(projectId) {
            try {
                await axios.delete(`/api/projects/${projectId}`, {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
                    },
                    data: {
                        project_id: projectId
                    }
                });
                
                
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Proyek berhasil dihapus!',
                    confirmButtonColor: '#3085d6'
                });
                
                this.getProjects();
            } catch (error) {
                console.error("Error deleting project:", error);
                
                
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Terjadi kesalahan saat menghapus proyek.',
                    confirmButtonColor: '#3085d6'
                });
            }
        },
        confirmDelete(item) {
            // Replace confirm with SweetAlert2
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: `Anda akan menghapus proyek "${item.project_name}"`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.deleteProject(item.id);
                }
            });
        },

        // Fetch and Filter Methods
        async getProjects() {
            try {
                const response = await axios.get("/api/projects", {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
                        'Accept': 'application/json',
                    },
                });

                this.projects = Array.isArray(response.data) ? response.data :
                    (response.data.data ? response.data.data : []);

                this.filteredProjects = this.projects;
                this.years = [
                    ...new Set(this.projects.map((p) => p.batch_year)),
                ];
            } catch (error) {
                console.error("Error fetching projects:", error.response ? error.response : error);
                
                
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Terjadi kesalahan saat mengambil data proyek: ' + 
                        (error.response?.data?.message || error.message),
                    confirmButtonColor: '#3085d6'
                });
            }
        },
        async getProdis() {
            try {
                const response = await axios.get("/api/prodis-by-major", {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
                    },
                });
                this.prodis = response.data;
            } catch (error) {
                console.error("Error fetching prodis:", error);
                
                
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Terjadi kesalahan saat mengambil data prodi.',
                    confirmButtonColor: '#3085d6'
                });
            }
        },
        filterProjects() {
            this.filteredProjects = this.selectedYear
                ? this.projects.filter((project) => project.batch_year === this.selectedYear)
                : this.projects;
        },

        // Status Change Method
        async changeProjectStatus(project) {
            try {
                const newStatus = project.status === "Active" ? "NonActive" : "Active";
                const response = await axios.post(
                    "/api/changeStatus",
                    {
                        tahun_ajaran: project.batch_year,
                        nama_proyek: project.project_name,
                        status: newStatus,
                    },
                    {
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
                        },
                    }
                );
                project.status = newStatus;
                
                
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Status proyek berhasil diperbarui!',
                    confirmButtonColor: '#3085d6'
                });
                
            } catch (error) {
                console.error("Error changing project status:", error);
                
                
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Terjadi kesalahan saat mengubah status proyek.',
                    confirmButtonColor: '#3085d6'
                });
            }
        },
        confirmStatusChange(project) {
            // Replace confirm with SweetAlert2
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: `Anda akan mengubah status proyek "${project.project_name}" menjadi ${project.status === "Active" ? "NonActive" : "Active"}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, ubah!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.changeProjectStatus(project);
                }
            });
        },
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
                <Card title="Kelola Proyek">
                    <template #actions>
                        <Dropdown 
                            title="Daftar Proyek" 
                            :options="years.map((year) => ({
                                label: year,
                                value: year,
                            }))" 
                            v-model="selectedYear" 
                            @update:modelValue="filterProjects" 
                            :defaultOption="{
                                label: 'Semua Tahun Ajaran',
                                value: '',
                            }" 
                            class="flex justify-between items-center mb-4" 
                        />
                        <div>
                            <table class="min-w-full border-collapse table-auto">
                                <thead>
                                    <tr class="bg-white-100">
                                        <th class="px-4 py-2 border">Nama Proyek</th>
                                        <th class="px-4 py-2 border">Semester</th>
                                        <th class="px-4 py-2 border">Tahun Ajaran</th>
                                        <th class="px-4 py-2 border">Program Studi</th>
                                        <th class="px-4 py-2 border">Status</th>
                                        <th class="px-4 py-2 border">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(project, index) in filteredProjects" :key="index">
                                        <td class="px-4 py-2 border">{{ project.project_name }}</td>
                                        <td class="px-4 py-2 border">{{ project.semester }}</td>
                                        <td class="px-4 py-2 border">{{ project.batch_year }}</td>
                                        <td class="px-4 py-2 border">{{ project.prodi.prodi_name }}</td>
                                        <td class="px-4 py-2 border">
                                            <button 
                                                @click="confirmStatusChange(project)" 
                                                class="text-sm font-medium" 
                                                :class="{
                                                    'text-blue-500 hover:text-blue-700': project.status === 'Active',
                                                    'text-red-500 hover:text-red-700': project.status === 'NonActive',
                                                }"
                                            >
                                                {{ project.status === "Active" ? "Active" : "NonActive" }}
                                            </button>
                                        </td>
                                        <td class="px-4 py-2 border">
                                            <div class="flex space-x-2">
                                                <button 
                                                    @click="openEditModal(project)" 
                                                    class="flex items-center justify-center px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 w-10 h-10"
                                                >
                                                    <font-awesome-icon icon="fa-solid fa-edit" />
                                                </button>
                                                <button 
                                                    @click="confirmDelete(project)" 
                                                    class="flex items-center justify-center px-3 py-1 bg-red-500 text-white rounded-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 w-10 h-10"
                                                >
                                                    <font-awesome-icon icon="fa-solid fa-trash" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </Card>

                <!-- Modal Tambah Proyek -->
                <div v-if="isModalOpen" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
                    <div class="bg-white p-6 rounded-lg w-1/2">
                        <h2 class="text-lg font-semibold mb-4">Tambah Proyek</h2>
                        <form @submit.prevent="addProject">
                            <div class="mb-4">
                                <label class="block text-sm font-medium">Semester</label>
                                <select v-model="newProject.semester" class="w-full border border-gray-300 rounded p-2" required>
                                    <option value="Ganjil">Ganjil</option>
                                    <option value="Genap">Genap</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium">Tahun Ajaran</label>
                                <input type="text" v-model="newProject.batch_year" class="w-full border border-gray-300 rounded p-2" required />
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium">Nama Proyek</label>
                                <input type="text" v-model="newProject.project_name" class="w-full border border-gray-300 rounded p-2" required />
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium">Program Studi</label>
                                <select v-model="newProject.prodi_id" class="w-full border border-gray-300 rounded p-2" required>
                                    <option v-for="prodi in prodis" :key="prodi.id" :value="prodi.id">
                                        {{ prodi.prodi_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium">Tanggal Mulai</label>
                                <input type="date" v-model="newProject.start_date" class="w-full border border-gray-300 rounded p-2" required />
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium">Tanggal Selesai</label>
                                <input type="date" v-model="newProject.end_date" class="w-full border border-gray-300 rounded p-2" required />
                            </div>
                            <div class="flex justify-end">
                                <button type="button" @click="closeModal" class="px-4 py-2 bg-gray-300 text-black rounded mr-2">
                                    Batal
                                </button>
                                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">
                                    Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Modal Edit Proyek -->
                <div v-if="isEditModalOpen" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
                    <div class="bg-white p-6 rounded-lg w-1/2">
                        <h2 class="text-lg font-semibold mb-4">Edit Proyek</h2>
                        <form @submit.prevent="updateProject">
                            <div class="mb-4">
                                <label class="block text-sm font-medium">Semester</label>
                                <select v-model="editingProject.semester" class="w-full border border-gray-300 rounded p-2" required>
                                    <option value="Ganjil">Ganjil</option>
                                    <option value="Genap">Genap</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium">Tahun Ajaran</label>
                                <input type="text" v-model="editingProject.batch_year" class="w-full border border-gray-300 rounded p-2" required />
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium">Nama Proyek</label>
                                <input type="text" v-model="editingProject.project_name" class="w-full border border-gray-300 rounded p-2" required />
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium">Program Studi</label>
                                <select v-model="editingProject.prodi_id" class="w-full border border-gray-300 rounded p-2" required>
                                    <option v-for="prodi in prodis" :key="prodi.id" :value="prodi.id">
                                        {{ prodi.prodi_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium">Tanggal Mulai</label>
                                <input type="date" v-model="editingProject.start_date" class="w-full border border-gray-300 rounded p-2" required />
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium">Tanggal Selesai</label>
                                <input type="date" v-model="editingProject.end_date" class="w-full border border-gray-300 rounded p-2" required />
                            </div>
                            <div class="flex justify-end">
                                <button type="button" @click="closeEditModal" class="px-4 py-2 bg-gray-300 text-black rounded mr-2">
                                    Batal
                                </button>
                                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
        <button 
            @click="openModal"
            class="fixed bottom-8 right-8 flex items-center justify-center w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105"
        >
            <font-awesome-icon :icon="['fas', 'plus']" />
        </button>
    </div>
</template>