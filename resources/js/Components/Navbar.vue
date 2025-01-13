<script>
import { router } from "@inertiajs/vue3";
import axios from "axios";

export default {
  name: "Navbar",
  props: {
    userName: {
      type: String,
      default: "User",
    },
  },
  data() {
    return {
      showProfileMenu: false,
      isLoggingOut: false,
    };
  },
  methods: {
    toggleProfileMenu() {
      this.showProfileMenu = !this.showProfileMenu;
    },
    async logout() {
      if (this.isLoggingOut) return;
      this.isLoggingOut = true;

      try {
        const token = localStorage.getItem("auth_token");
        if (token) {
          console.log("Token:", token);
          await axios.put(
            "/api/logout",
            {},
            {
              headers: {
                Authorization: `Bearer ${token}`,
              },
            }
          );
        }
      } catch (error) {
        console.error("Logout error:", error);
        alert("Logout failed. Please try again.");
      } finally {
        localStorage.removeItem("auth_token");
        localStorage.removeItem("user_data");
        router.visit("/login");
        this.isLoggingOut = false;
      }
    },
    goToNotifications() {
      router.visit("/notifications");
    },
    goToProfile() {
      router.visit("/dosen/profile");
    },
  },
};
</script>

<template>
  <nav
    class="bg-blue-500 text-white py-4 px-6 flex justify-between items-center"
  >
    <div class="text-xl font-bold">Assessment App</div>
    <div class="flex items-center space-x-4">
      <!-- Notification Button -->
      <button
        aria-label="Notifications"
        class="relative group focus:outline-none"
        @click="$inertia.visit('/dosen/notifications')"
      >
        <svg
          class="w-6 h-6 text-white group-hover:text-gray-200"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 17h5l-1.403-1.403A9.003 9.003 0 0013 9V5a3 3 0 00-3-3 3 3 0 00-3 3v4a9.003 9.003 0 00-5.597 10.597L4 17h5m6 0h-6"
          ></path>
        </svg>
        <!-- Notification Badge -->
        <span
          class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center"
        >
          3
        </span>
      </button>

      <!-- Profile Icon with Dropdown -->
      <div class="relative">
        <button
          @click="toggleProfileMenu"
          aria-label="Profile Menu"
          class="relative focus:outline-none group"
        >
          <svg
            class="w-6 h-6 text-white group-hover:text-gray-200"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M5 3h14M12 3v18M3 12h18"
            ></path>
          </svg>
        </button>

        <!-- Dropdown Menu -->
        <div
          v-if="showProfileMenu"
          class="absolute right-0 mt-2 bg-white text-gray-800 rounded shadow-lg w-48"
        >
          <a
            href="#"
            class="block px-4 py-2 hover:bg-gray-100"
            @click="goToProfile"
          >
            Profile
          </a>
          <a href="#" class="block px-4 py-2 hover:bg-gray-100" @click="logout">
            Logout
          </a>
        </div>
      </div>
    </div>
  </nav>
</template>

<style scoped>
/* Add custom styles here if needed */
</style>
