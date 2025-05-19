<script>
import axios from "axios";
import { router } from "@inertiajs/vue3";
import Sidebar from "@/Components/SidebarAdmin.vue";
import NavbarAdmin from "@/Components/NavbarAdmin.vue";
import { ref, onMounted, computed } from "vue";

export default {
    name: "DashboardAdmin",
    components: {
        Sidebar,
        NavbarAdmin,
    },
    props: {
        majorsData: {
            type: Array,
            required: true,
        },
    },
    setup(props) {
        const totalActiveProjects = computed(() => {
            return props.majorsData.reduce((total, major) => total + major.activeProjects, 0);
        });
        
        const totalInactiveProjects = computed(() => {
            return props.majorsData.reduce((total, major) => total + major.inactiveProjects, 0);
        });
        
        const totalProjects = computed(() => {
            return totalActiveProjects.value + totalInactiveProjects.value;
        });
        
        const projectPercentage = computed(() => {
            return (totalActiveProjects.value / totalProjects.value * 100).toFixed(1);
        });

        return {
            totalActiveProjects,
            totalInactiveProjects,
            totalProjects,
            projectPercentage
        };
    }
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <Sidebar role="admin" />
        <div class="flex-1">
            <NavbarAdmin userName="admin" />
            <main class="p-6">
                <!-- Header Section -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Dashboard Admin</h1>
                        <p class="text-gray-500">Pantau semua jurusan dan proyek dalam satu tempat</p>
                    </div>
                </div>

                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Total Proyek</p>
                                <p class="text-2xl font-bold text-gray-800">{{ totalProjects }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-500 mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Proyek Aktif</p>
                                <p class="text-2xl font-bold text-gray-800">{{ totalActiveProjects }}</p>
                                <p class="text-sm text-green-500">{{ projectPercentage }}% dari total</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-red-500">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-100 text-red-500 mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Proyek Non-Aktif</p>
                                <p class="text-2xl font-bold text-gray-800">{{ totalInactiveProjects }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jurusan Cards -->
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Detail Jurusan</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="major in majorsData"
                        :key="major.id"
                        class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow duration-300"
                    >
                        <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                            <span class="w-3 h-3 rounded-full mr-2" :class="major.activeProjects > major.inactiveProjects ? 'bg-green-500' : 'bg-red-500'"></span>
                            {{ major.name }}
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Proyek Aktif:</span>
                                <span class="text-green-600 font-semibold px-3 py-1 bg-green-100 rounded-full">
                                    {{ major.activeProjects }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Proyek Non-Aktif:</span>
                                <span class="text-red-600 font-semibold px-3 py-1 bg-red-100 rounded-full">
                                    {{ major.inactiveProjects }}
                                </span>
                            </div>
                            <div class="pt-2 border-t">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Total Proyek:</span>
                                    <span class="text-blue-600 font-semibold">
                                        {{ major.totalProjects }}
                                    </span>
                                </div>
                            </div>
                            <div class="w-full bg-blue-600 rounded-full h-2 mt-2">
                                <div class="bg-gray-200 h-2 rounded-full" :style="{ width: (major.activeProjects / major.totalProjects * 100) + '%' }"></div>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
.bg-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
</style>