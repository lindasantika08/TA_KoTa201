<script>
import axios from "axios";
import { router } from "@inertiajs/vue3";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import DataTable from "@/Components/DataTable.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import Dropdown from "@/Components/Dropdown.vue";
import Swal from "sweetalert2";

export default {
    name: "KelolaKelompok",
    components: {
        Sidebar,
        Navbar,
        Card,
        DataTable,
        Breadcrumb,
        Dropdown,
    },
    props: {
        kelompok: Array,
    },
    data() {
        return {
            breadcrumbs: [
                { text: "Manage Group", href: "/sispa/dosen/kelola-kelompok" },
            ],
            headers: [
                { label: "Tahun Ajaran", key: "batch_year" },
                { label: "Nama Proyek", key: "project_name" },
                { label: "Angkatan", key: "angkatan" },
                { label: "Kelompok", key: "group" },
                { label: "Manager Dosen", key: "dosen" },
                { label: "Anggota Kelompok", key: "anggota" },
                { label: "Action", key: "delete" },
            ],
            projects: [],
            selectedProject: "",
            filteredKelompok: [],
        };
    },
    mounted() {
        console.log("Data Kelompok:", this.kelompok);
        this.fetchProjects();
        this.processGroupData();
    },
    methods: {
        async fetchProjects() {
            try {
                const response = await axios.get("/sispa/api/project-dropdown");
                this.projects = response.data;
            } catch (error) {
                console.error("Error fetching projects:", error);
            }
        },
        processGroupData() {
            const flatGroups = this.kelompok.flatMap((dosenGroup) =>
                dosenGroup.projects.map((project) => ({
                    ...project,
                    dosen: dosenGroup.dosen_name,
                    angkatan: project.angkatan || "-",
                    dosen_id: dosenGroup.dosen_id,
                    project_id: project.project_id || project.id || null
                }))
            );

            const groupMap = {};

            flatGroups.forEach((group) => {
                const key = `${group.project_id}_${group.batch_year}_${group.project_name}_${group.group}`;

                if (!groupMap[key]) {
                    groupMap[key] = {
                        id: group.id,
                        batch_year: group.batch_year,
                        project_name: group.project_name,
                        project_id: group.project_id,
                        angkatan: group.angkatan,
                        group: group.group,
                        dosen: group.dosen,
                        dosen_id: group.dosen_id,
                        classes: group.class ? [group.class] : [],
                        anggota: [...(group.anggota || [])],
                    };
                } else {
                    groupMap[key].anggota = [
                        ...groupMap[key].anggota,
                        ...(group.anggota || []),
                    ];

                    if (
                        group.class &&
                        !groupMap[key].classes.includes(group.class)
                    ) {
                        groupMap[key].classes.push(group.class);
                    }
                }
            });

            this.filteredKelompok = Object.values(groupMap);
            console.log("Consolidated Kelompok:", this.filteredKelompok);
        },
        applyFilter() {
            this.processGroupData();

            if (!this.selectedProject) {
                return;
            }

            const [batch_year, project_name] =
                this.selectedProject.split(" - ");
            this.filteredKelompok = this.filteredKelompok.filter(
                (group) =>
                    group.batch_year === batch_year &&
                    group.project_name === project_name
            );
        },
        showDetail(kelompokId) {
            console.log(`Show detail for kelompok with ID: ${kelompokId}`);
            this.$inertia.get(route("DetailKelompok", { id: kelompokId }));
        },
        createKelompok(url) {
            router.visit("/sispa/dosen/kelola-kelompok/create");
        },
        goToProfile(user_id) {
            router.visit(
                `/sispa/dosen/kelola-kelompok/profile-mhs?user_id=${user_id}`
            );
        },
        formatClasses(classes) {
            return classes && classes.length > 0 ? classes.join(", ") : "";
        },

        async confirmDelete(item) {

            const projectId = item.project_id || item.id;

            if (!projectId) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Project',
                    text: 'No project ID found for this group'
                });
                return;
            }

            try {
                const response = await axios.get('/sispa/api/check-group-deletion', {
                    params: {
                        project_id: projectId,
                        group_name: item.group
                    }
                });

                if (response.data.requires_confirmation === false) {
                    Swal.fire({
                        title: 'Delete Group',
                        text: `Are you sure you want to delete the group "${item.group}"?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.performGroupDeletion(item);
                        }
                    });
                } else if (response.data.requires_confirmation === true) {
                    Swal.fire({
                        title: 'Warning! Peer Answer Data Exists',
                        html: response.data.warning || 'This group has related assessment entries.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Force Delete',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.performGroupDeletion(item, true);
                        }
                    });
                }
            } catch (error) {
                console.error('Deletion check error:', {
                    response: error.response?.data,
                    item: item
                });

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.response?.data?.message || 'Failed to check group deletion'
                });
            }
        },

        async performGroupDeletion(item, force = false) {
            try {
                const response = await axios.delete('/sispa/api/delete-group', {
                    data: {
                        project_id: item.project_id,
                        group_name: item.group,
                        force: force
                    }
                });

                Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: response.data.message
                });

                this.filteredKelompok = this.filteredKelompok.filter(
                    group => !(group.project_id === item.project_id && group.group === item.group)
                );
            } catch (error) {
                console.error('Deletion error:', error.response?.data);
                Swal.fire({
                    icon: 'error',
                    title: 'Deletion Error',
                    text: error.response?.data?.message || 'Failed to delete group'
                });
            }
        }
    }
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
                <Card title="Kelola Kelompok">
                    <div class="flex justify-between mb-4 items-center">
                        <div class="ml-4">
                            <span class="text-lg font-semibold text-black">Daftar Kelompok</span>
                        </div>
                        <div class="flex-1 max-w-xs">
                            <select id="projectDropdown" v-model="selectedProject"
                                class="py-2 px-2 border border-gray-300 rounded w-full" @change="applyFilter">
                                <option value="" disabled>
                                    Pilih Tahun Ajaran - Proyek
                                </option>
                                <option v-for="project in projects"
                                    :key="`${project.batch_year}-${project.project_name}`"
                                    :value="`${project.batch_year} - ${project.project_name}`">
                                    {{ project.batch_year }} -
                                    {{ project.project_name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <DataTable :headers="headers" :items="filteredKelompok">
                        <template v-slot:column-angkatan="{ item }">
                            <span>{{ item.angkatan }}</span>
                        </template>

                        <template v-slot:column-anggota="{ item }">
                            <div>
                                <div v-if="
                                    item.classes && item.classes.length > 0
                                " class="text-xs font-medium text-gray-500 mb-1">
                                    Kelas: {{ formatClasses(item.classes) }}
                                </div>
                                <div v-else class="text-xs font-medium text-gray-500 mb-1">
                                    Tidak Ada Kelas
                                </div>

                                <ul>
                                    <li v-for="(anggota, index) in item.anggota" :key="index"
                                        class="flex items-center space-x-2">
                                        <span class="text-gray-400">•</span>
                                        <a href="#" @click.prevent="
                                            goToProfile(anggota.user_id)
                                            "
                                            class="text-blue-600 hover:text-blue-800 hover:underline transition-colors duration-200">
                                            {{ anggota.name }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </template>
                        <template v-slot:column-delete="{ item }">
                            <button @click="confirmDelete(item)" class="flex items-center justify-center px-3 py-1 bg-red-500 text-white rounded-md hover:bg-red-600 
               focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 w-10 h-10">
                                <font-awesome-icon icon="fa-solid fa-trash" />
                            </button>
                        </template>

                    </DataTable>
                </Card>

                <button @click="createKelompok('/sispa/dosen/kelola-kelompok/create')"
                    class="fixed bottom-8 right-8 flex items-center justify-center w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105">
                    <font-awesome-icon :icon="['fas', 'plus']" />
                </button>
            </main>
        </div>
    </div>
</template>