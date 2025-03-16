<script>
import axios from "axios";
import { router } from "@inertiajs/vue3";
import Sidebar from "../../components/SidebarAdmin.vue";
import Navbar from "../../components/Navbar.vue";

export default {
    name: "DashboardAdmin",
    components: {
        Sidebar,
        Navbar,
    },
    props: {
        majorsData: {
            type: Array,
            required: true,
        },
    },
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <Sidebar role="admin" />
        <div class="flex-1">
            <navbar userName="admin" />
            <main class="p-6">
                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                >
                    <div
                        v-for="major in majorsData"
                        :key="major.id"
                        class="bg-white rounded-lg shadow-md p-6"
                    >
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">
                            {{ major.name }}
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600"
                                    >Project Aktif:</span
                                >
                                <span class="text-green-600 font-semibold">
                                    {{ major.activeProjects }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600"
                                    >Project Non-Aktif:</span
                                >
                                <span class="text-red-600 font-semibold">
                                    {{ major.inactiveProjects }}
                                </span>
                            </div>
                            <div class="pt-2 border-t">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600"
                                        >Total Project:</span
                                    >
                                    <span class="text-blue-600 font-semibold">
                                        {{ major.totalProjects }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>
