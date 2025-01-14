<template>
    <div class="flex min-h-screen">
        <Sidebar role="dosen" />
        <div class="flex-1">
            <Navbar userName="Dosen" />
            <main class="p-6">
                <Card title="Kelola Proyek">
                    <template #actions>
                        <!-- Filter Tahun Ajaran -->
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold">Daftar Proyek</h2>
                            <select
                                v-model="selectedYear"
                                @change="filterProjects"
                                class="border border-gray-300 rounded px-4 py-2"
                            >
                                <option value="">Semua Tahun Ajaran</option>
                                <option
                                    v-for="(year, index) in years"
                                    :key="index"
                                    :value="year"
                                >
                                    {{ year }}
                                </option>
                            </select>
                        </div>

                        <!-- Tabel Daftar Proyek -->
                        <div>
                            <table class="min-w-full border-collapse table-auto">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="px-4 py-2 border">Nama Proyek</th>
                                        <th class="px-4 py-2 border">Semester</th>
                                        <th class="px-4 py-2 border">Tahun Ajaran</th>
                                        <th class="px-4 py-2 border">Jurusan</th>
                                        <th class="px-4 py-2 border">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(project, index) in filteredProjects" :key="index">
                                        <td class="px-4 py-2 border">{{ project.nama_proyek }}</td>
                                        <td class="px-4 py-2 border">{{ project.semester }}</td>
                                        <td class="px-4 py-2 border">{{ project.tahun_ajaran }}</td>
                                        <td class="px-4 py-2 border">{{ project.jurusan }}</td>
                                        <td class="px-4 py-2 border">{{ project.status }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </Card>

                <!-- Modal -->
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
                                    v-model="newProject.tahun_ajaran"
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
                                    v-model="newProject.nama_proyek"
                                    class="w-full border border-gray-300 rounded p-2"
                                    required
                                />
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium"
                                    >Jurusan</label
                                >
                                <select
                                    v-model="newProject.jurusan"
                                    class="w-full border border-gray-300 rounded p-2"
                                    required
                                >
                                    <option value="Teknik Sipil">Teknik Sipil</option>
                                    <option value="Teknik Mesin">Teknik Mesin</option>
                                    <option value="Teknik Elektro">Teknik Elektro</option>
                                    <option value="Teknik Komputer dan Informatika">Teknik Komputer dan Informatika</option>
                                    <option value="Teknik Refrigerasi dan Tata Udara">Teknik Refrigerasi dan Tata Udara</option>
                                    <option value="Teknik Konversi Energi">Teknik Konversi Energi</option>
                                    <option value="Teknik Kimia">Teknik Kimia</option>
                                    <option value="Akuntansi">Akuntansi</option>
                                    <option value="Administrasi Niaga">Administrasi Niaga</option>
                                    <option value="Bahasa Inggris">Bahasa Inggris</option>

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
                            <div class="mb-4">
                                <label class="block text-sm font-medium"
                                    >Status</label
                                >
                                <select
                                    v-model="newProject.status"
                                    class="w-full border border-gray-300 rounded p-2"
                                    required
                                >
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </select>
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
            class="fixed bottom-10 right-10 bg-blue-500 text-white rounded-full p-6 shadow-lg hover:bg-blue-600 focus:outline-none"
        >
            <span class="text-2xl">+</span>
        </button>
    </div>
</template>

<script>
import axios from "axios";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";

export default {
    name: "KelolaProyek",
    components: {
        Sidebar,
        Navbar,
        Card,
    },
    data() {
        return {
            isModalOpen: false,
            newProject: {
                semester: "",
                tahun_ajaran: "",
                nama_proyek: "",
                jurusan: "",
                start_date: "",
                end_date: "",
                status: "",
            },
            projects: [],
            filteredProjects: [],
            years: [],
            selectedYear: "",
        };
    },
    mounted() {
        this.getProjects();
    },
    methods: {
        openModal() {
            this.isModalOpen = true;
        },
        closeModal() {
            this.isModalOpen = false;
        },
        async addProject() {
            try {
                const response = await axios.post(
                    "/api/project",
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
                const response = await axios.get("/api/projects", {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem(
                            "auth_token"
                        )}`,
                    },
                });
                this.projects = response.data;
                this.filteredProjects = this.projects;

                // Extract unique tahun ajaran
                this.years = [...new Set(this.projects.map((p) => p.tahun_ajaran))];
            } catch (error) {
                console.error("Error fetching projects:", error);
                alert("Terjadi kesalahan saat mengambil data proyek.");
            }
        },
        filterProjects() {
            if (this.selectedYear) {
                this.filteredProjects = this.projects.filter(
                    (project) => project.tahun_ajaran === this.selectedYear
                );
            } else {
                this.filteredProjects = this.projects;
            }
        },
    },
};
</script>

