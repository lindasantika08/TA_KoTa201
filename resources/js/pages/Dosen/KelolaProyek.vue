<script>
import axios from "axios";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Dropdown from "@/Components/Dropdown.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

export default {
    name: "KelolaProyek",
    components: {
        Sidebar,
        Navbar,
        Card,
        Dropdown,
        Breadcrumb,
    },
    data() {
        return {
            breadcrumbs: [
                { text: "Manage Project", href: "/sispa/dosen/kelola-proyek" },
            ],
            isModalOpen: false,
            newProject: {
                semester: "",
                batch_year: "",
                project_name: "",
                prodi_id: "", // Changed from major_id
                start_date: "",
                end_date: "",
                status: "Active",
            },
            projects: [],
            filteredProjects: [],
            years: [],
            selectedYear: "",
            prodis: [], // Replaced majors with prodis
        };
    },
    mounted() {
        this.getProjects();
        this.getProdis();
    },
    methods: {
        openModal() {
            this.isModalOpen = true;
        },
        closeModal() {
            this.isModalOpen = false;
            // Reset form
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
        async addProject() {
            try {
                const response = await axios.post(
                    "/sispa/api/project",
                    this.newProject,
                    {
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem(
                                "auth_token"
                            )}`,
                        },
                    }
                );
                alert("Proyek berhasil ditambahkan!");
                this.closeModal();
                this.getProjects();
            } catch (error) {
                console.error("Error adding project:", error);
                alert("Terjadi kesalahan saat menambahkan proyek.");
            }
        },
        async getProjects() {
            try {
                const response = await axios.get("/sispa/api/projects", {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem(
                            "auth_token"
                        )}`,
                    },
                });
                this.projects = response.data;
                this.filteredProjects = this.projects;
                this.years = [
                    ...new Set(this.projects.map((p) => p.batch_year)),
                ];
            } catch (error) {
                console.error("Error fetching projects:", error);
                alert("Terjadi kesalahan saat mengambil data proyek.");
            }
        },
        filterProjects() {
            if (this.selectedYear) {
                this.filteredProjects = this.projects.filter(
                    (project) => project.batch_year === this.selectedYear
                );
            } else {
                this.filteredProjects = this.projects;
            }
        },
        async changeProjectStatus(project) {
            try {
                const newStatus =
                    project.status === "Active" ? "NonActive" : "Active";
                const response = await axios.post(
                    "/sispa/api/changeStatus",
                    {
                        tahun_ajaran: project.batch_year,
                        nama_proyek: project.project_name,
                        status: newStatus,
                    },
                    {
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem(
                                "auth_token"
                            )}`,
                        },
                    }
                );
                project.status = newStatus;
                alert("Status proyek berhasil diperbarui!");
            } catch (error) {
                console.error("Error changing project status:", error);
                alert("Terjadi kesalahan saat mengubah status proyek.");
            }
        },
        confirmStatusChange(project) {
            const confirmChange = window.confirm(
                `Apakah Anda yakin ingin mengubah status proyek "${
                    project.project_name
                }" menjadi ${
                    project.status === "Active" ? "NonActive" : "Active"
                }?`
            );

            if (confirmChange) {
                this.changeProjectStatus(project);
            }
        },
        async getProdis() {
            try {
                const response = await axios.get("/sispa/api/prodis-by-major", {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem(
                            "auth_token"
                        )}`,
                    },
                });
                this.prodis = response.data;
            } catch (error) {
                console.error("Error fetching prodis:", error);
                alert("Terjadi kesalahan saat mengambil data prodi.");
            }
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
                            :options="
                                years.map((year) => ({
                                    label: year,
                                    value: year,
                                }))
                            "
                            v-model="selectedYear"
                            @update:modelValue="filterProjects"
                            :defaultOption="{
                                label: 'Semua Tahun Ajaran',
                                value: '',
                            }"
                            class="flex justify-between items-center mb-4"
                        />
                        <div>
                            <table
                                class="min-w-full border-collapse table-auto"
                            >
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="px-4 py-2 border">
                                            Nama Proyek
                                        </th>
                                        <th class="px-4 py-2 border">
                                            Semester
                                        </th>
                                        <th class="px-4 py-2 border">
                                            Tahun Ajaran
                                        </th>
                                        <th class="px-4 py-2 border">
                                            Program Studi
                                        </th>
                                        <th class="px-4 py-2 border">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(
                                            project, index
                                        ) in filteredProjects"
                                        :key="index"
                                    >
                                        <td class="px-4 py-2 border">
                                            {{ project.project_name }}
                                        </td>
                                        <td class="px-4 py-2 border">
                                            {{ project.semester }}
                                        </td>
                                        <td class="px-4 py-2 border">
                                            {{ project.batch_year }}
                                        </td>
                                        <td class="px-4 py-2 border">
                                            {{ project.prodi.prodi_name }}
                                        </td>
                                        <td class="px-4 py-2 border">
                                            <button
                                                @click="
                                                    confirmStatusChange(project)
                                                "
                                                class="text-sm font-medium"
                                                :class="{
                                                    'text-blue-500 hover:text-blue-700':
                                                        project.status ===
                                                        'Active',
                                                    'text-red-500 hover:text-red-700':
                                                        project.status ===
                                                        'NonActive',
                                                }"
                                            >
                                                {{
                                                    project.status === "Active"
                                                        ? "Active"
                                                        : "NonActive"
                                                }}
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </Card>

                <!-- Modal Tambah Proyek -->
                <div
                    v-if="isModalOpen"
                    class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
                >
                    <div class="bg-white p-6 rounded-lg w-1/2">
                        <h2 class="text-lg font-semibold mb-4">
                            Tambah Proyek
                        </h2>
                        <form @submit.prevent="addProject">
                            <div class="mb-4">
                                <label class="block text-sm font-medium"
                                    >Semester</label
                                >
                                <select
                                    v-model="newProject.semester"
                                    class="w-full border border-gray-300 rounded p-2"
                                    required
                                >
                                    <option value="Ganjil">Ganjil</option>
                                    <option value="Genap">Genap</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium"
                                    >Tahun Ajaran</label
                                >
                                <input
                                    type="text"
                                    v-model="newProject.batch_year"
                                    class="w-full border border-gray-300 rounded p-2"
                                    required
                                />
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium"
                                    >Nama Proyek</label
                                >
                                <input
                                    type="text"
                                    v-model="newProject.project_name"
                                    class="w-full border border-gray-300 rounded p-2"
                                    required
                                />
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium"
                                    >Program Studi</label
                                >
                                <select
                                    v-model="newProject.prodi_id"
                                    class="w-full border border-gray-300 rounded p-2"
                                    required
                                >
                                    <option
                                        v-for="prodi in prodis"
                                        :key="prodi.id"
                                        :value="prodi.id"
                                    >
                                        {{ prodi.prodi_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium"
                                    >Tanggal Mulai</label
                                >
                                <input
                                    type="date"
                                    v-model="newProject.start_date"
                                    class="w-full border border-gray-300 rounded p-2"
                                    required
                                />
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium"
                                    >Tanggal Selesai</label
                                >
                                <input
                                    type="date"
                                    v-model="newProject.end_date"
                                    class="w-full border border-gray-300 rounded p-2"
                                    required
                                />
                            </div>
                            <div class="flex justify-end">
                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="px-4 py-2 bg-gray-300 text-black rounded mr-2"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    class="px-4 py-2 bg-blue-500 text-white rounded"
                                >
                                    Simpan
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
