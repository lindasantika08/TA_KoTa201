<template>
    <div class="flex">
        <!-- Sidebar -->
        <aside
            class="w-64 bg-white text-black py-6 px-4 sticky top-0 h-screen shadow-2xl"
        >
            <!-- Logo Polban -->
            <div class="mb-6 text-center">
                <img
                    src="https://th.bing.com/th/id/OIP.vIvWflPMt3G6kLbeL_uYBQHaKq?rs=1&pid=ImgDetMain"
                    alt="Logo Polban"
                    width="40"
                    height="5"
                    class="mx-auto"
                />
            </div>
            <ul class="flex flex-col space-y-4">
                <li>
                    <a
                        :href="'/dosen/dashboard'"
                        :class="{ 'bg-gray-200': isActive('/dosen/dashboard') }"
                        class="block px-4 py-2 rounded hover:bg-gray-100 text-lg font-semibold"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-house"
                            class="mr-2"
                        />
                        Dashboard
                    </a>
                </li>

                <!-- Menu Assessment dengan Dropdown -->
                <li>
                    <button
                        @click="toggleAssessmentMenu"
                        :class="{
                            'bg-white':
                                isActive('/dosen/self') ||
                                isActive('/dosen/peer'),
                        }"
                        class="w-full text-left px-4 py-2 rounded flex justify-between hover:bg-gray-100"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-clipboard-list"
                            class="mr-2"
                        />
                        <span class="text-lg font-semibold">Assessment</span>
                        <span
                            :class="{ 'rotate-180': isAssessmentOpen }"
                            class="transform transition-all"
                            >▼</span
                        >
                    </button>
                    <ul v-if="isAssessmentOpen" class="pl-4 mt-2 space-y-2">
                        <li v-if="role === 'dosen'">
                            <a
                                @click="goToCreateAssessment"
                                :class="{
                                    'bg-gray-200': isActive('/dosen/assessment/create'),
                                }"
                                class="block px-4 py-2 rounded cursor-pointer hover:bg-gray-100"
                            >
                            <font-awesome-icon :icon="['fas', 'address-card']" class="mr-2"/>
                                Create Assessment
                            </a>
                        </li>
                        <li>
                            <a
                                @click="goToSelfAssessment"
                                :class="{
                                    'bg-gray-200': isActive('/dosen/self'),
                                }"
                                class="block px-4 py-2 rounded cursor-pointer hover:bg-gray-100"
                            >
                                <font-awesome-icon
                                    icon="fa-solid fa-user-check"
                                    class="mr-2"
                                />
                                Self Assessment
                            </a>
                        </li>
                        <li v-if="role === 'dosen'">
                            <a
                                @click="goToPeerAssessment"
                                :class="{
                                    'bg-gray-200': isActive('/dosen/peer'),
                                }"
                                class="block px-4 py-2 rounded cursor-pointer hover:bg-gray-100"
                            >
                                <font-awesome-icon
                                    icon="fa-solid fa-users"
                                    class="mr-2"
                                />
                                Peer Assessment
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="mb-4">
                    <button
                        @click="toggleKelolaProyekMenu"
                        :class="{
                            'bg-white':
                                isActive('/dosen/kelola-proyek') ||
                                isActive('/dosen/kelola-kelompok'),
                        }"
                        class="w-full text-left px-4 py-2 rounded flex justify-between hover:bg-gray-100"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-cogs"
                            class="mr-2"
                        />
                        <span class="text-lg font-semibold">Kelola Proyek</span>
                        <span
                            :class="{ 'rotate-180': isKelolaProyekOpen }"
                            class="transform transition-all"
                            >▼</span
                        >
                    </button>
                    <ul v-if="isKelolaProyekOpen" class="pl-4 mt-2 space-y-2">
                        <li>
                            <a
                                @click="goToKelolaProyek"
                                :class="{
                                    'bg-gray-200': isActive(
                                        '/dosen/kelola-proyek'
                                    ),
                                }"
                                class="block px-4 py-2 rounded cursor-pointer hover:bg-gray-100"
                            >
                                <font-awesome-icon
                                    icon="fa-solid fa-project-diagram"
                                    class="mr-2"
                                />
                                Kelola Proyek
                            </a>
                        </li>
                        <li v-if="role === 'dosen'">
                            <a
                                @click="goToKelolaKelompok"
                                :class="{
                                    'bg-gray-200': isActive(
                                        '/dosen/kelola-kelompok'
                                    ),
                                }"
                                class="block px-4 py-2 rounded cursor-pointer hover:bg-gray-100"
                            >
                                <font-awesome-icon
                                    icon="fa-solid fa-tasks"
                                    class="mr-2"
                                />
                                Kelola Kelompok
                            </a>
                        </li>
                    </ul>
                </li>

                <li>
                    <a
                        :href="'/dosen/report'"
                        :class="{ 'bg-gray-200': isActive('/dosen/report') }"
                        class="block px-4 py-2 rounded hover:bg-gray-100 text-lg font-semibold"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-chart-line"
                            class="mr-2"
                        />
                        Report
                    </a>
                </li>

                <li>
                    <a
                        :href="'/dosen/feedback'"
                        :class="{ 'bg-gray-200': isActive('/dosen/feedback') }"
                        class="block px-4 py-2 rounded hover:bg-gray-100 text-lg font-semibold"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-comment-dots"
                            class="mr-2"
                        />
                        Feedback
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Konten Utama -->
        <main class="flex-1">
            <!-- Konten Anda di sini -->
        </main>
    </div>
</template>

<script>
import { router } from "@inertiajs/vue3";

export default {
    name: "Sidebar",
    props: {
        role: {
            type: String,
            required: true, // "dosen" atau "mahasiswa"
        },
    },
    data() {
        return {
            isAssessmentOpen:
                this.isActive("/dosen/self") || this.isActive("/dosen/peer") || this.isActive("/dosen/assessment/create"), 
            isKelolaProyekOpen: this.isActive("/dosen/kelola-proyek") || this.isActive("/dosen/kelola-kelompok"),
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
            router.visit("/dosen/assessment/create");
        },
        goToSelfAssessment() {
            router.visit("/dosen/self");
        },
        goToPeerAssessment() {
            router.visit("/dosen/peer");
        },
        isActive(route) {
            return this.$page.url === route;
        },
        goToKelolaProyek() {
            router.visit("/dosen/kelola-proyek");
        },
        goToKelolaKelompok() {
            router.visit("/dosen/kelola-kelompok");
        },
    },
};
</script>

<style scoped>
/* Menambahkan animasi transisi untuk dropdown */
.transition-all {
    transition: transform 0.3s ease;
}
</style>
