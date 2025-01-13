<template>
    <div class="flex">
        <!-- Sidebar -->
        <aside
            class="w-64 bg-white text-black py-6 px-4 sticky top-0 h-screen shadow-2xl"
        >
            <!-- Logo Institusi -->
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
                            icon="fa-solid fa-pencil-alt"
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
                this.isActive("/dosen/self") || this.isActive("/dosen/peer"), // Mengontrol apakah menu Assessment terbuka
        };
    },
    methods: {
        toggleAssessmentMenu() {
            this.isAssessmentOpen = !this.isAssessmentOpen;
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
    },
};
</script>

<style scoped>
/* Menambahkan animasi transisi untuk dropdown */
.transition-all {
    transition: transform 0.3s ease;
}
</style>
