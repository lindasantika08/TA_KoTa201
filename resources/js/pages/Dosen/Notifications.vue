<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import Card from '@/Components/Card.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const breadcrumbs = [
  { text: 'Home', href: '/dosen' },
  { text: 'Notifications', href: '#' }
];

const loading = ref(false);
const localNotifications = ref([]);
const localUnreadCount = ref(0);

// ---------------------------  API helpers  ---------------------------
async function refreshNotifications() {
  if (loading.value) return;
  loading.value = true;

  try {
    const { data } = await axios.get('/api/notifications/get');
    if (data.success) {
      localNotifications.value = data.data.notifications;
      localUnreadCount.value = data.data.unread_count;
    }
  } catch (e) {
    console.error('Refresh failed', e);
  } finally {
    loading.value = false;
  }
}

async function markAllAsRead() {
  try {
    const { data } = await axios.post('/api/notifications/read-all');
    if (!data.success) return;

    localNotifications.value.forEach(n => (n.read_at = new Date()));
    localUnreadCount.value = 0;
  } catch (e) {
    console.error('Mark‑all error', e);
  }
}

async function markAsRead(n) {
  // jika sudah dibaca langsung buka
  if (n.read_at) return go(n);

  try {
    const { data } = await axios.post(`/api/notifications/${n.id}/read`);
    if (!data.success) return;

    n.read_at = new Date();
    localUnreadCount.value = Math.max(0, localUnreadCount.value - 1);

    go(n);
  } catch (e) {
    console.error('Mark‑read error', e);
  }
}

// ---------------------------  Router logic  ---------------------------
function go(n, backendUrl = null) {
  // khusus reminder‑lecturer
  if (n.type === 'reminder-lecturer') {
    const { assessment_order, batch_year, project_name } = n;
    const isForPeer = n.message?.toLowerCase().includes('peer assessment');
    const route = isForPeer ? 'answers-peer-assessment' : 'answers-self-assessment';

    const url = `/dosen/${route
      }?assessment_order=${encodeURIComponent(assessment_order)
      }&batch_year=${encodeURIComponent(batch_year)
      }&project_name=${encodeURIComponent(project_name)}`;

    return router.visit(url);
  }

  const url = backendUrl || n.url;
  if (!url) return console.warn('No URL in notification');
  router.visit(url);
}

// first load
onMounted(refreshNotifications);
</script>

<template>
  <div class="flex min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <Sidebar role="dosen" />

    <!-- Main Content -->
    <div class="flex-1">
      <Navbar userName="Dosen" :badge="localUnreadCount" />

      <main class="p-6 space-y-4">
        <Breadcrumb :items="breadcrumbs" />

        <!-- Notification Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
          <!-- Header -->
          <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <div class="flex items-center gap-3">
              <h2 class="text-lg font-semibold text-gray-900">Notifications</h2>
              <span v-if="localUnreadCount > 0"
                class="bg-red-500 text-white px-2 py-0.5 rounded-full text-xs font-medium">
                {{ localUnreadCount }} new
              </span>
            </div>

            <div class="flex gap-3">
              <!-- Refresh Button -->
              <button @click="refreshNotifications" :disabled="loading"
                class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                  stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
              </button>

              <!-- Mark All as Read Button -->
              <button v-if="localUnreadCount > 0" @click="markAllAsRead"
                class="px-3 py-1 text-sm bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-md transition flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                  stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Mark all as read
              </button>
            </div>
          </div>

          <!-- Notification List -->
          <div v-if="loading" class="p-6 text-center text-sm text-gray-500">
            Loading notifications...
          </div>

          <ul v-else class="divide-y divide-gray-200">
            <li v-for="notification in localNotifications" :key="notification.id" @click="markAsRead(notification)"
              class="px-4 py-3 hover:bg-gray-50 cursor-pointer transition"
              :class="{ 'bg-blue-50': !notification.read_at }">
              <div class="flex items-start gap-3">
                <!-- Unread Indicator -->
                <div class="flex-shrink-0 pt-1.5">
                  <span class="block h-2 w-2 rounded-full"
                    :class="notification.read_at ? 'bg-transparent' : 'bg-blue-500'"></span>
                </div>

                <!-- Notification Content -->
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 mb-1">
                    {{ notification.message }}
                  </p>
                  <div class="flex items-center justify-between">
                    <p v-if="notification.project_name" class="text-xs text-gray-500">
                      {{ notification.project_name }}
                    </p>
                    <p class="text-xs text-gray-400">
                      {{ notification.created_at }}
                    </p>
                  </div>
                </div>
              </div>
            </li>

            <li v-if="localNotifications.length === 0" class="p-6 text-center text-sm text-gray-500">
              No notifications available
            </li>
          </ul>
        </div>
      </main>
    </div>
  </div>
</template>

<style scoped>
/* Animasi untuk tombol */
button {
  transition: all 0.2s ease;
}

/* Efek hover yang lebih halus */
.hover\:bg-gray-50:hover {
  background-color: rgba(249, 250, 251, 0.8);
}
</style>
