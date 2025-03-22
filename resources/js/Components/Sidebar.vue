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
        <li>
          <a
            :href="'/dosen/dashboard'"
            :class="{ 'bg-gray-200': isActive('/dosen/dashboard') }"
            class="block px-4 py-2 rounded hover:bg-gray-100 text-base font-medium"
          >
            <font-awesome-icon icon="fa-solid fa-house" class="mr-4" />
            Dashboard
          </a>
        </li>

        <li>
          <button
            @click="toggleAssessmentMenu"
            :class="{
              'bg-white':
                isActive('/dosen/assessment/projectsSelf') ||
                isActive('/dosen/assessment/projectsPeer'),
            }"
            class="w-full text-left px-4 py-2 rounded flex justify-start hover:bg-gray-100"
          >
            <font-awesome-icon icon="fa-solid fa-clipboard-list" class="mr-6" />
            <span class="text-base font-medium">Assessment</span>
            <span
              :class="{ 'rotate-180': isAssessmentOpen }"
              class="transform transition-all ml-2"
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
                class="block px-4 py-2 rounded cursor-pointer hover:bg-gray-100 text-sm"
              >
                <font-awesome-icon
                  :icon="['fas', 'address-card']"
                  class="mr-4"
                />
                Create Assessment
              </a>
            </li>
            <li>
              <a
                @click="goToSelfAssessment"
                :class="{
                  'bg-gray-200': isActive('/dosen/assessment/projects-self'),
                }"
                class="block px-4 py-2 rounded cursor-pointer hover:bg-gray-100 text-sm"
              >
                <font-awesome-icon icon="fa-solid fa-user-check" class="mr-4" />
                Self Assessment
              </a>
            </li>
            <li v-if="role === 'dosen'">
              <a
                @click="goToPeerAssessment"
                :class="{
                  'bg-gray-200': isActive('/dosen/assessment/projects-peer'),
                }"
                class="block px-4 py-2 rounded cursor-pointer hover:bg-gray-100 text-sm"
              >
                <font-awesome-icon icon="fa-solid fa-users" class="mr-4" />
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
            class="w-full text-left px-4 py-2 rounded flex justify-start hover:bg-gray-100"
          >
            <font-awesome-icon icon="fa-solid fa-cogs" class="mr-4" />
            <span class="text-base font-medium">Manage Projects</span>
            <span
              :class="{ 'rotate-180': isKelolaProyekOpen }"
              class="transform transition-all ml-2"
              >▼</span
            >
          </button>
          <ul v-if="isKelolaProyekOpen" class="pl-4 mt-2 space-y-2">
            <li>
              <a
                @click="goToKelolaProyek"
                :class="{
                  'bg-gray-200': isActive('/dosen/kelola-proyek'),
                }"
                class="block px-4 py-2 rounded cursor-pointer hover:bg-gray-100 text-sm"
              >
                <font-awesome-icon
                  icon="fa-solid fa-project-diagram"
                  class="mr-4"
                />
                Manage Projects
              </a>
            </li>
            <li v-if="role === 'dosen'">
              <a
                @click="goToKelolaKelompok"
                :class="{
                  'bg-gray-200': isActive('/dosen/kelola-kelompok'),
                }"
                class="block px-4 py-2 rounded cursor-pointer hover:bg-gray-100 text-sm"
              >
                <font-awesome-icon icon="fa-solid fa-tasks" class="mr-4" />
                Manage Group
              </a>
            </li>
          </ul>
        </li>
        <li>
          <a
            :href="'/dosen/report'"
            :class="{ 'bg-gray-200': isActive('/dosen/report') }"
            class="block px-4 py-2 rounded hover:bg-gray-100 text-base font-medium"
          >
            <font-awesome-icon icon="fa-solid fa-chart-line" class="mr-4" />
            Report
          </a>
        </li>

        <li>
          <a
            :href="'/dosen/feedback'"
            :class="{ 'bg-gray-200': isActive('/dosen/feedback') }"
            class="block px-4 py-2 rounded hover:bg-gray-100 text-base font-medium"
          >
            <font-awesome-icon icon="fa-solid fa-comment-dots" class="mr-4" />
            Feedback
          </a>
        </li>
        <li class="mb-4">
          <button
            @click="toggleKelolaSettingsMenu"
            :class="{
              'bg-white':
                isActive('/dosen/manage-mahasiswa') ||
                isActive('/dosen/manage-dosen'),
            }"
            class="w-full text-left px-4 py-2 rounded flex justify-start hover:bg-gray-100"
          >
            <font-awesome-icon icon="fa-solid fa-cogs" class="mr-4" />
            <span class="text-base font-medium">Manage Users</span>
            <span
              :class="{ 'rotate-180': isKelolaSettingsOpen }"
              class="transform transition-all ml-2"
              >▼</span
            >
          </button>
          <ul v-if="isKelolaSettingsOpen" class="pl-4 mt-2 space-y-2">
            <li>
              <a
                @click="goToKelolaMahasiswa"
                :class="{
                  'bg-gray-200': isActive('/dosen/manage-mahasiswa'),
                }"
                class="block px-4 py-2 rounded cursor-pointer hover:bg-gray-100 text-sm"
              >
                <font-awesome-icon
                  icon="fa-solid fa-project-diagram"
                  class="mr-4"
                />
                Manage Mahasiswa
              </a>
            </li>
           
          </ul>
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
        this.isActive("/dosen/assessment/projectsSelf") ||
        this.isActive("/dosen/assessment/projectsPeer") ||
        this.isActive("/dosen/assessment/create"),
      isKelolaProyekOpen:
        this.isActive("/dosen/kelola-proyek") ||
        this.isActive("/dosen/kelola-kelompok"),
      isKelolaSettingsOpen:
        this.isActive("/dosen/manage-mahasiswa"),
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

<style scoped>
.transition-all {
  transition: transform 0.3s ease;
}
</style>
