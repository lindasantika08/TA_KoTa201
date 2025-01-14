<template>
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <Sidebar role="dosen" />

        <!-- Main Content -->
        <div class="flex-1">
            <!-- Navbar -->
            <Navbar userName="Dosen" />

            <!-- Content -->
            <main class="p-6">
                <Card :title="`Self Assessment - ${namaProyek} (${tahunAjaran})`">
                    <template #actions>
                        <!-- Container untuk assessment yang dikelompokkan berdasarkan aspek -->
                        <div class="mt-6 space-y-8">
                            <div v-for="(group, aspek) in groupedAssessments" :key="aspek">
                                <!-- Card untuk setiap aspek -->
                                <Card :title="aspek" :description="'Total Pertanyaan: ' + group.length">
                                    <template #actions>
                                        <div>
                                            <!-- Daftar Pertanyaan -->
                                            <div v-for="(assessment, index) in group" :key="assessment.id" class="mt-4">
                                                <h3 class="text-lg font-semibold mb-2">
                                                    {{ index + 1 }}.
                                                    {{ assessment.pertanyaan }}
                                                </h3>
                                                <h4 class="text-sm text-gray-600">
                                                    {{ assessment.kriteria }}
                                                </h4>

                                                <!-- Tabel Bobot -->
                                                <div class="overflow-x-auto mt-2">
                                                    <table class="w-full table-auto border-collapse bg-white">
                                                        <thead>
                                                            <tr>
                                                                <th class="px-4 py-2 border">
                                                                    Bobot 1
                                                                </th>
                                                                <th class="px-4 py-2 border">
                                                                    Bobot 2
                                                                </th>
                                                                <th class="px-4 py-2 border">
                                                                    Bobot 3
                                                                </th>
                                                                <th class="px-4 py-2 border">
                                                                    Bobot 4
                                                                </th>
                                                                <th class="px-4 py-2 border">
                                                                    Bobot 5
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td class="px-4 py-2 border">
                                                                    {{ assessment.bobot_1 }}
                                                                </td>
                                                                <td class="px-4 py-2 border">
                                                                    {{ assessment.bobot_2 }}
                                                                </td>
                                                                <td class="px-4 py-2 border">
                                                                    {{ assessment.bobot_3 }}
                                                                </td>
                                                                <td class="px-4 py-2 border">
                                                                    {{ assessment.bobot_4 }}
                                                                </td>
                                                                <td class="px-4 py-2 border">
                                                                    {{ assessment.bobot_5 }}
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </Card>
                            </div>
                        </div>
                    </template>
                </Card>
            </main>
        </div>
    </div>
</template>

<script>
import { ref, computed } from "vue";
import { usePage } from '@inertiajs/vue3';
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";

export default {
    components: {
        Sidebar,
        Navbar,
        Card,
    },
    props: {
        tahunAjaran: String,
        namaProyek: String,
        assessments: {
            type: Array,
            required: true
        }
    },
    setup(props) {
        const showDropdown = ref(false);
        const page = usePage();

        const toggleDropdown = () => {
            showDropdown.value = !showDropdown.value;
        };

        // Group assessments berdasarkan aspek
        const groupedAssessments = computed(() => {
            const groups = {};
            props.assessments.forEach((assessment) => {
                if (!groups[assessment.aspek]) {
                    groups[assessment.aspek] = [];
                }
                groups[assessment.aspek].push(assessment);
            });
            return groups;
        });

        return {
            toggleDropdown,
            showDropdown,
            groupedAssessments,
            tahunAjaran: props.tahunAjaran,
            namaProyek: props.namaProyek
        };
    },
};
</script>