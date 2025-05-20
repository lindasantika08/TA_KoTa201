<template>
    <div class="flex">
        <aside
            class="w-64 bg-white text-black py-6 px-4 sticky top-0 h-screen shadow-2xl"
        >
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
                <!-- Dashboard -->
                <li>
                    <a
                        :href="'/sispa/mahasiswa/dashboard'"
                        :class="{
                            'bg-gray-200': isActive('/sispa/mahasiswa/dashboard'),
                        }"
                        class="flex items-center px-4 py-2 rounded hover:bg-gray-100 text-base font-medium"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-house"
                            class="w-5 text-center mr-3"
                        />
                        <span>Dashboard</span>
                    </a>
                </li>

                <!-- Assessment -->
                <li>
                    <button
                        @click="toggleAssessmentMenu"
                        :class="{
                            'bg-white':
                                isActive('/sispa/mahasiswa/assessment/self') ||
                                isActive('/sispa/mahasiswa/assessment/peer'),
                        }"
                        class="w-full text-left px-4 py-2 rounded flex items-center hover:bg-gray-100"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-clipboard-list"
                            class="w-5 text-center mr-3"
                        />
                        <span class="text-base font-medium flex-1">Assessment</span>
                        <svg
                            :class="{ 'rotate-180': isAssessmentOpen }"
                            class="w-4 h-4 transform transition-all"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 15l7-7 7 7"
                            ></path>
                        </svg>
                    </button>
                    <ul v-if="isAssessmentOpen" class="pl-4 mt-2 space-y-2">
                        <li>
                            <a
                                @click="goToSelfAssessment"
                                :class="{
                                    'bg-gray-200': isActive('/sispa/mahasiswa/self-assessment'),
                                }"
                                class="flex items-center px-4 py-2 rounded cursor-pointer hover:bg-gray-100 text-sm"
                            >
                                <font-awesome-icon
                                    icon="fa-solid fa-user-check"
                                    class="w-5 text-center mr-3"
                                />
                                <span>Self Assessment</span>
                            </a>
                        </li>
                        <li v-if="role === 'mahasiswa'">
                            <a
                                @click="goToPeerAssessment"
                                :class="{
                                    'bg-gray-200': isActive('/sispa/mahasiswa/peer'),
                                }"
                                class="flex items-center px-4 py-2 rounded cursor-pointer hover:bg-gray-100 text-sm"
                            >
                                <font-awesome-icon
                                    icon="fa-solid fa-users"
                                    class="w-5 text-center mr-3"
                                />
                                <span>Peer Assessment</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Report -->
                <li>
                    <a
                        :href="'/sispa/mahasiswa/report'"
                        :class="{
                            'bg-gray-200': isActive('/sispa/mahasiswa/report'),
                        }"
                        class="flex items-center px-4 py-2 rounded hover:bg-gray-100 text-base font-medium"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-chart-line"
                            class="w-5 text-center mr-3"
                        />
                        <span>Report</span>
                    </a>
                </li>

                <!-- Feedback -->
                <li>
                    <a
                        :href="'/sispa/mahasiswa/feedback'"
                        :class="{
                            'bg-gray-200': isActive('/sispa/mahasiswa/feedback'),
                        }"
                        class="flex items-center px-4 py-2 rounded hover:bg-gray-100 text-base font-medium"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-comment-dots"
                            class="w-5 text-center mr-3"
                        />
                        <span>Feedback</span>
                    </a>
                </li>

                <!-- Reflective -->
                <li>
                    <a
                        :href="'/sispa/mahasiswa/reflective-assessment'"
                        :class="{
                            'bg-gray-200': isActive('/sispa/mahasiswa/reflective-assessment'),
                        }"
                        class="flex items-center px-4 py-2 rounded hover:bg-gray-100 text-base font-medium"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-bars-progress"
                            class="w-5 text-center mr-3"
                        />
                        <span>Reflective</span>
                    </a>
                </li>
            </ul>
        </aside>

        <main class="flex-1"></main>
    </div>
</template>

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
                this.isActive("/sispa/mahasiswa/self") ||
                this.isActive("/sispa/mahasiswa/peer") ||
                this.isActive("/sispa/mahasiswa/assessment/create"),
            isKelolaProyekOpen:
                this.isActive("/sispa/mahasiswa/kelola-proyek") ||
                this.isActive("/sispa/mahasiswa/kelola-kelompok"),
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
            router.visit("/sispa/mahasiswa/assessment/create");
        },
        goToSelfAssessment() {
            router.visit("/sispa/mahasiswa/assessment/self");
        },
        goToPeerAssessment() {
            router.visit("/sispa/mahasiswa/assessment/peer");
        },
        isActive(route) {
            return this.$page.url === route;
        },
        goToKelolaProyek() {
            router.visit("/sispa/mahasiswa/kelola-proyek");
        },
        goToKelolaKelompok() {
            router.visit("/sispa/mahasiswa/kelola-kelompok");
        },
    },
};
</script>

<style scoped>
.transition-all {
    transition: transform 0.3s ease;
}
</style>