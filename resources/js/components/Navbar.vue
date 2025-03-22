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
      userName: "",
      unreadNotificationsCount: 0,
      socket: null,
      eventSource: null,
      unreadNotificationsCount: 0
    };
  },
  mounted() {
    this.fetchUserName();
    this.fetchNotificationsCount();
  },
  beforeUnmount() {
    this.disconnectWebSocket();
  },
  methods: {
    initializeSSE() {
        this.eventSource = new EventSource('/notifications/stream');
        
        this.eventSource.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.unreadNotificationsCount = data.count;
            this.showNotification('New notification received!');
        };
    },

    showNotification(message) {
        if (Notification.permission === 'granted') {
            new Notification(message);
        }
    },
    
    beforeUnmount() {
        if (this.eventSource) {
            this.eventSource.close();
        }
    },

    disconnectWebSocket() {
      if (this.socket) {
        this.socket.close();
        this.socket = null;
      }
    },

    toggleProfileMenu() {
      this.showProfileMenu = !this.showProfileMenu;
    },

    async fetchUserName() {
      try {
        const token = localStorage.getItem("auth_token");
        if (token) {
          const response = await axios.get("/sispa/api/user", {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          });
          this.userName = response.data.name;
        }
      } catch (error) {
        console.error("Gagal mendapatkan data pengguna:", error);
      }
    },

    async fetchNotificationsCount() {
      try {
        const token = localStorage.getItem("auth_token");
        const response = await axios.post('/sispa/api/notifications/count', {}, {
          headers: {
            Authorization: `Bearer ${token}`,
          }
        });
        if (response.data.success) {
          this.unreadNotificationsCount = response.data.count;
        }
      } catch (error) {
        console.error("Gagal mendapatkan jumlah notifikasi:", error);
        this.unreadNotificationsCount = 0;
      }
    },

    async logout() {
      if (this.isLoggingOut) return;
      this.isLoggingOut = true;

      try {
        const token = localStorage.getItem("auth_token");
        if (token) {
          await axios.put(
            "/sispa/api/logout",
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
        this.disconnectWebSocket();
        localStorage.removeItem("auth_token");
        localStorage.removeItem("user_data");
        router.visit("/sispa/login");
        this.isLoggingOut = false;
      }
    },

    goToNotifications() {
      axios.get('/sispa/api/user-role')
        .then(response => {
          const role = response.data.role;
          if (role === 'mahasiswa') {
            router.visit('/mahasiswa/notifications-mhs');
          } else if (role === 'dosen') {
            router.visit('/dosen/notifications');
          }
        })
        .catch(error => {
          console.error('Gagal mendapatkan role pengguna:', error);
          alert('Terjadi kesalahan. Silakan coba lagi.');
        });
    },

    goToProfile() {
      axios.get('/sispa/api/user-role')
        .then(response => {
          const role = response.data.role;
          if (role === 'dosen') {
            router.visit('/dosen/profile');
          } else if (role === 'mahasiswa') {
            router.visit('/mahasiswa/profile');
          } else {
            alert('Role tidak dikenali.');
          }
        })
        .catch(error => {
          console.error('Gagal mendapatkan role pengguna:', error);
          alert('Terjadi kesalahan. Silakan coba lagi.');
        });
    },
  },
};
</script>

<template>
  <nav class="bg-white text-black py-4 px-6 flex justify-between items-center sticky top-0 w-full z-10 shadow-md">
    <div class="text-xl font-bold">Assessment App</div>
    <div class="flex items-center space-x-4">
      <button aria-label="Notifications" class="relative group focus:outline-none"
        @click="goToNotifications">
        <font-awesome-icon icon="fa-solid fa-bell" class="w-6 h-6 text-black group-hover:text-gray-200" />
        <span v-if="unreadNotificationsCount > 0"
          class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full min-w-5 h-5 flex items-center justify-center px-1">
          {{ unreadNotificationsCount > 99 ? '99+' : unreadNotificationsCount }}
        </span>
      </button>

      <div class="relative">
        <button @click="toggleProfileMenu" aria-label="Profile Menu" class="relative focus:outline-none group">
          <font-awesome-icon icon="fa-solid fa-user" class="w-6 h-6 text-black group-hover:text-gray-200 mr-2" />
          <span class="text-sm font-medium text-black mr-2">{{ userName }}</span>
        </button>
        <div v-if="showProfileMenu" class="absolute right-0 mt-2 bg-white text-gray-800 rounded shadow-lg w-48">
          <a href="#" class="block px-4 py-2 hover:bg-gray-100" @click="goToProfile">
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

<style scoped></style>