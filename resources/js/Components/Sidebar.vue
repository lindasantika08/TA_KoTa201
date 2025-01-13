<script>
import { Inertia } from '@inertiajs/inertia';

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
      isAssessmentOpen: false, // Mengontrol apakah menu Assessment terbuka
    };
  },
  methods: {
    toggleAssessmentMenu() {
      this.isAssessmentOpen = !this.isAssessmentOpen;
    },
    goToSelfAssessment() {
      // Menavigasi ke halaman Self Assessment dengan folder Dosen
      Inertia.visit('/dosen/assessment/selfAssessment'); 
    },
    goToPeerAssessment() {
      // Menavigasi ke halaman Peer Assessment dengan folder Dosen
      Inertia.visit('/dosen/assessment/peer'); 
    },
  },
};
</script>

<template>
  <aside class="w-64 bg-gray-800 text-white min-h-screen py-6 px-4">
    <div class="mb-6 text-center text-xl font-bold">Menu</div>
    <ul>
      <li class="mb-4">
        <a 
          href="/dosen/dashboard" 
          class="block px-4 py-2 rounded hover:bg-gray-700"
        >
          Dashboard
        </a>
      </li>

      <!-- Menu Assessment dengan Dropdown -->
      <li class="mb-4">
        <button 
          @click="toggleAssessmentMenu"
          class="w-full text-left px-4 py-2 rounded hover:bg-gray-700 flex items-center justify-between"
        >
          <span class="text-lg font-semibold">Assessment</span>
          <span :class="{'rotate-180': isAssessmentOpen}" class="transform transition-all">▼</span>
        </button>
        <ul v-if="isAssessmentOpen" class="pl-4 mt-2 space-y-2">
          <li>
            <a 
              @click="goToSelfAssessment"
              class="block px-4 py-2 rounded hover:bg-gray-700 cursor-pointer"
            >
              Self Assessment
            </a>
          </li>
          <li v-if="role === 'dosen'">
            <a 
              @click="goToPeerAssessment"
              class="block px-4 py-2 rounded hover:bg-gray-700 cursor-pointer"
            >
              Peer Assessment
            </a>
          </li>
        </ul>
      </li>
    </ul>
  </aside>
</template>

<style scoped>
/* Menambahkan animasi transisi untuk dropdown */
.transition-all {
  transition: transform 0.3s ease;
}
</style>
