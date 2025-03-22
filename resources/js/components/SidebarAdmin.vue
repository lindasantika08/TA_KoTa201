<template>
    <div class="flex">
        <aside
            class="w-64 bg-white text-black py-6 px-6 sticky top-0 h-screen shadow-2xl"
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
                <li>
                    <a
                        :href="'/admin/dashboard'"
                        :class="{ 'bg-gray-200': isActive('/admin/dashboard') }"
                        class="block px-4 py-2 rounded hover:bg-gray-100 text-base font-medium"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-house"
                            class="mr-4"
                        />
                        Dashboard
                    </a>
                </li>
                <li>
                    <button
                        @click="toggleMajorMenu"
                        :class="{
                            'bg-white':
                                isActive('/admin/ManageMajor') ||
                                isActive('/admin/ManageProdi'),
                        }"
                        class="w-full text-left px-4 py-2 rounded flex justify-start hover:bg-gray-100"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-school"
                            class="mr-6"
                        />
                        <span class="text-base font-medium">Manage Major</span>
                        <span
                            :class="{ 'rotate-180': isMajorOpen }"
                            class="transform transition-all ml-2"
                            >▼</span
                        >
                    </button>
                    <ul v-if="isMajorOpen" class="pl-4 mt-2 space-y-2">
                        <li v-if="role === 'admin'">
                            <a
                                @click="goToCreateMajor"
                                :class="{
                                    'bg-gray-200':
                                        isActive('/admin/ManageMajor'),
                                }"
                                class="block px-4 py-2 rounded cursor-pointer hover:bg-gray-100 text-sm"
                            >
                                <font-awesome-icon
                                    :icon="['fas', 'graduation-cap']"
                                    class="mr-4"
                                />
                                Create Major
                            </a>
                            <a
                                @click="goToCreateProdi"
                                :class="{
                                    'bg-gray-200':
                                        isActive('/admin/ManageProdi'),
                                }"
                                class="block px-4 py-2 rounded cursor-pointer hover:bg-gray-100 text-sm"
                            >
                                <font-awesome-icon
                                    :icon="['fas', 'graduation-cap']"
                                    class="mr-4"
                                />
                                Create Prodi
                            </a>
                        </li>
                    </ul>
                </li>

                <li>
                    <button
                        @click="toggleUserMenu"
                        :class="{
                            'bg-white': isActive('/admin/ManageDosen'),
                        }"
                        class="w-full text-left px-4 py-2 rounded flex justify-start hover:bg-gray-100"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-users"
                            class="mr-6"
                        />
                        <span class="text-base font-medium">Manage user</span>
                        <span
                            :class="{ 'rotate-180': isUserOpen }"
                            class="transform transition-all ml-2"
                            >▼</span
                        >
                    </button>
                    <ul v-if="isUserOpen" class="pl-4 mt-2 space-y-2">
                        <li v-if="role === 'admin'">
                            <a
                                @click="goToCreateDosen"
                                :class="{
                                    'bg-gray-200':
                                        isActive('/admin/ManageDosen'),
                                }"
                                class="block px-4 py-2 rounded cursor-pointer hover:bg-gray-100 text-sm"
                            >
                                <font-awesome-icon
                                    :icon="['fas', 'user']"
                                    class="mr-4"
                                />
                                Manage Dosen
                            </a>
                            <a
                                @click="goToCreateMahasiswa"
                                :class="{
                                    'bg-gray-200': isActive(
                                        '/admin/ManageMahasiswa'
                                    ),
                                }"
                                class="block px-4 py-2 rounded cursor-pointer hover:bg-gray-100 text-sm"
                            >
                                <font-awesome-icon
                                    :icon="['fas', 'user']"
                                    class="mr-4"
                                />
                                Manage Mahasiswa
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </aside>
    </div>
</template>

<script>
import { router } from "@inertiajs/vue3";

export default {
    name: "SidebarAdmin",
    props: {
        role: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            isMajorOpen:
                this.isActive("/admin/ManageMajor") ||
                this.isActive("/admin/ManageProdi"),
            isUserOpen:
                this.isActive("/admin/ManageDosen") ||
                this.isActive("/admin/ManageMahasiswa"),
        };
    },
    methods: {
        isActive(route) {
            return this.$page.url === route;
        },
        toggleMajorMenu() {
            this.isMajorOpen = !this.isMajorOpen;
        },
        toggleUserMenu() {
            this.isUserOpen = !this.isUserOpen;
        },
        goToCreateMajor() {
            router.visit("/admin/ManageMajor");
        },
        goToCreateProdi() {
            router.visit("/admin/ManageProdi");
        },
        goToCreateDosen() {
            router.visit("/admin/ManageDosen");
        },
        goToCreateMahasiswa() {
            router.visit("/admin/ManageMahasiswa");
        },
    },
};
</script>
