<script>
import { router } from "@inertiajs/vue3";

export default {
    name: "Sidebar",
    props: {
        role: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            isAssessmentOpen:
                this.isActive("/mahasiswa/self") || this.isActive("/mahasiswa/peer") || this.isActive("/mahasiswa/assessment/create"),
            isKelolaProyekOpen: this.isActive("/mahasiswa/kelola-proyek") || this.isActive("/mahasiswa/kelola-kelompok"),
        };
    },
    methods: {
        toggleAssessmentMenu() {
            this.isAssessmentOpen = !this.isAssessmentOpen;
        },
        toggleKelolaProyekMenu() {
            this.isKelolaProyekOpen = !this.isKelolaProyekOpen;
        },
        goToCreateAssessment() {
            router.visit("/mahasiswa/assessment/create");
        },
        goToSelfAssessment() {
            router.visit("/mahasiswa/assessment/self");
        },
        goToPeerAssessment() {
            router.visit("/mahasiswa/assessment/peer");
        },
        isActive(route) {
            return this.$page.url === route;
        },
        goToKelolaProyek() {
            router.visit("/mahasiswa/kelola-proyek");
        },
        goToKelolaKelompok() {
            router.visit("/mahasiswa/kelola-kelompok");
        },
    },
};
</script>

<template>
    <div class="flex">
        <aside class="w-64 bg-white text-black py-6 px-4 sticky top-0 h-screen shadow-2xl">
            <div class="mb-6 text-center">
                <img src="https://th.bing.com/th/id/OIP.vIvWflPMt3G6kLbeL_uYBQHaKq?rs=1&pid=ImgDetMain"
                    alt="Logo Polban" width="40" height="5" class="mx-auto" />
            </div>
            <ul class="flex flex-col space-y-4">
                <li>
                    <a :href="'/mahasiswa/dashboard'" :class="{ 'bg-gray-200': isActive('/mahasiswa/dashboard') }"
                        class="block px-4 py-2 rounded hover:bg-gray-100 text-base font-medium">
                        <font-awesome-icon icon="fa-solid fa-house" class="mr-4" />
                        Dashboard
                    </a>
                </li>

                <li>
                    <button @click="toggleAssessmentMenu" :class="{
                        'bg-white':
                            isActive('/mahasiswa/assessment/self') ||
                            isActive('/mahasiswa/assessment/peer'),
                    }" class="w-full text-left px-4 py-2 rounded flex justify-start hover:bg-gray-100">
                        <font-awesome-icon icon="fa-solid fa-clipboard-list" class="mr-6" />
                        <span class="text-base font-medium">Assessment</span>
                        <span :class="{ 'rotate-180': isAssessmentOpen }" class="transform transition-all ml-2">▼</span>
                    </button>
                    <ul v-if="isAssessmentOpen" class="pl-4 mt-2 space-y-2">
                        <li>
                            <a @click="goToSelfAssessment" :class="{
                                'bg-gray-200': isActive('/mahasiswa/self-assessment'),
                            }" class="block px-4 py-2 rounded cursor-pointer hover:bg-gray-100 text-sm">
                                <font-awesome-icon icon="fa-solid fa-user-check" class="mr-4" />
                                Self Assessment
                            </a>
                        </li>
                        <li v-if="role === 'mahasiswa'">
                            <a @click="goToPeerAssessment" :class="{
                                'bg-gray-200': isActive('/mahasiswa/peer'),
                            }" class="block px-4 py-2 rounded cursor-pointer hover:bg-gray-100 text-sm">
                                <font-awesome-icon icon="fa-solid fa-users" class="mr-4" />
                                Peer Assessment
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a :href="'/mahasiswa/report'" :class="{ 'bg-gray-200': isActive('/mahasiswa/report') }"
                        class="block px-4 py-2 rounded hover:bg-gray-100 text-base font-medium">
                        <font-awesome-icon icon="fa-solid fa-chart-line" class="mr-4" />
                        Report
                    </a>
                </li>

                <li>
                    <a :href="'/mahasiswa/feedback'" :class="{ 'bg-gray-200': isActive('/mahasiswa/feedback') }"
                        class="block px-4 py-2 rounded hover:bg-gray-100 text-base font-medium">
                        <font-awesome-icon icon="fa-solid fa-comment-dots" class="mr-4" />
                        Feedback
                    </a>
                </li>
            </ul>
        </aside>

        <main class="flex-1">
        </main>
    </div>
</template>

<style scoped>
.transition-all {
    transition: transform 0.3s ease;
}
</style>
