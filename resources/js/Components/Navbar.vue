<template>
  <nav class="bg-white text-black py-4 px-6 flex justify-between items-center sticky top-0 w-full z-10 shadow-md">
    <div class="text-xl font-bold">Assessment App</div>
    <div class="flex items-center space-x-4">
      <!-- Notification Button -->
      <button
        aria-label="Notifications"
        class="relative group focus:outline-none"
        @click="$inertia.visit('/dosen/notifications')"
      >
        <font-awesome-icon
          icon="fa-solid fa-bell"
          class="w-6 h-6 text-black group-hover:text-gray-200"
        />
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
          <font-awesome-icon
            icon="fa-solid fa-user"
            class="w-6 h-6 text-black group-hover:text-gray-200 mr-2"
          />
          <span class="text-sm font-medium text-black mr-2">{{ userName }}</span>
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

<script>
import { router } from "@inertiajs/vue3";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import axios from "axios";

export default {
  name: "Navbar",
  components: {
    FontAwesomeIcon,
  },
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
      await axios.post(
        "/api/logout",  // Pastikan menggunakan POST di sini
        {},
        {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
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
}
,
    goToNotifications() {
      router.visit("/notifications");
    },
    goToProfile() {
      router.visit("/dosen/profile");
    },
  },
};
</script>

<style scoped>
/* Add custom styles here if needed */
</style>
