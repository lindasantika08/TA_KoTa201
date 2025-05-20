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
                this.isActive("/sispa/dosen/assessment/projectsSelf") ||
                this.isActive("/sispa/dosen/assessment/projectsPeer") ||
                this.isActive("/sispa/dosen/assessment/create"),
            isKelolaProyekOpen:
                this.isActive("/sispa/dosen/kelola-proyek") ||
                this.isActive("/sispa/dosen/kelola-kelompok"),
            isKelolaSettingsOpen: this.isActive(
                "/sispa/dosen/manage-mahasiswa"
            ),
        };
    },
    methods: {
        toggleAssessmentMenu() {
            this.isAssessmentOpen = !this.isAssessmentOpen;
        },
        toggleKelolaProyekMenu() {
            this.isKelolaProyekOpen = !this.isKelolaProyekOpen;
        },
        toggleKelolaSettingsMenu() {
            this.isKelolaSettingsOpen = !this.isKelolaSettingsOpen;
        },
        goToCreateAssessment() {
            router.visit("/sispa/dosen/assessment/create");
        },
        goToSelfAssessment() {
            router.visit("/sispa/dosen/assessment/projects-self");
        },
        goToPeerAssessment() {
            router.visit("/sispa/dosen/assessment/projects-peer");
        },
        isActive(route) {
            return this.$page.url === route;
        },
        goToKelolaProyek() {
            router.visit("/sispa/dosen/kelola-proyek");
        },
        goToKelolaKelompok() {
            router.visit("/sispa/dosen/kelola-kelompok");
        },
        goToKelolaMahasiswa() {
            router.visit("/sispa/dosen/manage-mahasiswa");
        },
    },
};
</script>
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
                        :href="'/sispa/dosen/dashboard'"
                        :class="{
                            'bg-gray-200': isActive('/sispa/dosen/dashboard'),
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
                                isActive('/sispa/dosen/assessment/create') ||
                                isActive(
                                    '/sispa/dosen/assessment/projects-self'
                                ) ||
                                isActive(
                                    '/sispa/dosen/assessment/projects-peer'
                                ),
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
                        <li v-if="role === 'dosen'">
                            <a
                                @click="goToCreateAssessment"
                                :class="{
                                    'bg-gray-200': isActive(
                                        '/sispa/dosen/assessment/create'
                                    ),
                                }"
                                class="flex items-center px-4 py-2 rounded cursor-pointer hover:bg-gray-100 text-sm"
                            >
                                <font-awesome-icon
                                    :icon="['fas', 'address-card']"
                                    class="w-5 text-center mr-3"
                                />
                                <span>Create Assessment</span>
                            </a>
                        </li>
                        <li>
                            <a
                                @click="goToSelfAssessment"
                                :class="{
                                    'bg-gray-200': isActive(
                                        '/sispa/dosen/assessment/projects-self'
                                    ),
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
                        <li v-if="role === 'dosen'">
                            <a
                                @click="goToPeerAssessment"
                                :class="{
                                    'bg-gray-200': isActive(
                                        '/sispa/dosen/assessment/projects-peer'
                                    ),
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

                <!-- Manage Projects -->
                <li>
                    <button
                        @click="toggleKelolaProyekMenu"
                        :class="{
                            'bg-white':
                                isActive('/sispa/dosen/kelola-proyek') ||
                                isActive('/sispa/dosen/kelola-kelompok'),
                        }"
                        class="w-full text-left px-4 py-2 rounded flex items-center hover:bg-gray-100"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-cogs"
                            class="w-5 text-center mr-3"
                        />
                        <span class="text-base font-medium flex-1">Manage Projects</span>
                        <svg
                            :class="{ 'rotate-180': isKelolaProyekOpen }"
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
                    <ul v-if="isKelolaProyekOpen" class="pl-4 mt-2 space-y-2">
                        <li>
                            <a
                                @click="goToKelolaProyek"
                                :class="{
                                    'bg-gray-200': isActive(
                                        '/sispa/dosen/kelola-proyek'
                                    ),
                                }"
                                class="flex items-center px-4 py-2 rounded cursor-pointer hover:bg-gray-100 text-sm"
                            >
                                <font-awesome-icon
                                    icon="fa-solid fa-project-diagram"
                                    class="w-5 text-center mr-3"
                                />
                                <span>Manage Projects</span>
                            </a>
                        </li>
                        <li v-if="role === 'dosen'">
                            <a
                                @click="goToKelolaKelompok"
                                :class="{
                                    'bg-gray-200': isActive(
                                        '/sispa/dosen/kelola-kelompok'
                                    ),
                                }"
                                class="flex items-center px-4 py-2 rounded cursor-pointer hover:bg-gray-100 text-sm"
                            >
                                <font-awesome-icon
                                    icon="fa-solid fa-tasks"
                                    class="w-5 text-center mr-3"
                                />
                                <span>Manage Group</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Report -->
                <li>
                    <a
                        :href="'/sispa/dosen/report'"
                        :class="{
                            'bg-gray-200': isActive('/sispa/dosen/report'),
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
                        :href="'/sispa/dosen/feedback'"
                        :class="{
                            'bg-gray-200': isActive('/sispa/dosen/feedback'),
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
                        :href="'/sispa/dosen/assessment/projects-reflective'"
                        :class="{
                            'bg-gray-200': isActive(
                                '/sispa/dosen/assessment/projects-reflective'
                            ),
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

                <!-- Manage Users -->
                <li>
                    <button
                        @click="toggleKelolaSettingsMenu"
                        :class="{
                            'bg-white':
                                isActive('/sispa/dosen/manage-mahasiswa') ||
                                isActive('/sispa/dosen/manage-dosen'),
                        }"
                        class="w-full text-left px-4 py-2 rounded flex items-center hover:bg-gray-100"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-cogs"
                            class="w-5 text-center mr-3"
                        />
                        <span class="text-base font-medium flex-1">Manage Users</span>
                        <svg
                            :class="{ 'rotate-180': isKelolaSettingsOpen }"
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
                    <ul v-if="isKelolaSettingsOpen" class="pl-4 mt-2 space-y-2">
                        <li>
                            <a
                                @click="goToKelolaMahasiswa"
                                :class="{
                                    'bg-gray-200': isActive(
                                        '/sispa/dosen/manage-mahasiswa'
                                    ),
                                }"
                                class="flex items-center px-4 py-2 rounded cursor-pointer hover:bg-gray-100 text-sm"
                            >
                                <font-awesome-icon
                                    icon="fa-solid fa-project-diagram"
                                    class="w-5 text-center mr-3"
                                />
                                <span>Manage Mahasiswa</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </aside>
    </div>
</template>

<style scoped>
.transition-all {
    transition: transform 0.3s ease;
}
</style>