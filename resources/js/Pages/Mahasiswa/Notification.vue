<script>
import axios from "axios";
import Sidebar from "@/Components/SidebarMahasiswa.vue";
import Navbar from "@/Components/Navbar.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import { Link, router } from '@inertiajs/vue3';

export default {
    name: "NotificationPage",
    components: {
        Sidebar,
        Navbar,
        Breadcrumb,
        Link,
    },
    props: {
        notifications: {
            type: Array,
            default: () => []
        },
        unreadCount: {
            type: Number,
            default: 0
        }
    },
    
    data() {
        return {
            loading: false,
            socket: null,
            reconnectAttempts: 0,
            maxReconnectAttempts: 5,
            reconnectInterval: 5000,
            breadcrumbs: [
                { text: 'Home', href: '/mahasiswa' },
                { text: 'Notifications', href: '#' }
            ],
            localNotifications: [],
            localUnreadCount: 0,
        };
    },
    
    created() {
        this.localNotifications = this.notifications;
        this.localUnreadCount = this.unreadCount;
    },
    
    mounted() {
        this.initializeWebSocket();
        // Initial fetch of notifications
        this.refreshNotifications();
    },
    
    beforeUnmount() {
        this.disconnectWebSocket();
    },
    
    methods: {
        initializeWebSocket() {
            try {
                // Replace with your actual WebSocket server URL
                const wsProtocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
                const wsHost = window.location.host;
                this.socket = new WebSocket(`${wsProtocol}//${wsHost}/ws/notifications`);
                
                this.socket.onopen = () => {
                    console.log('WebSocket connected');
                    this.reconnectAttempts = 0; // Reset reconnect attempts on successful connection
                };

                this.socket.onmessage = (event) => {
                    const data = JSON.parse(event.data);
                    
                    if (data.type === 'new_notification') {
                        // Add new notification to the list
                        const processedNotification = this.processNotifications([data.notification])[0];
                        this.localNotifications = [processedNotification, ...this.localNotifications];
                        this.localUnreadCount++;
                        
                        // Show browser notification if supported
                        this.showBrowserNotification(processedNotification);
                    } else if (data.type === 'notification_update') {
                        // Update unread count if it changed
                        this.localUnreadCount = data.unread_count;
                    }
                };

                this.socket.onclose = () => {
                    console.log('WebSocket disconnected');
                    this.handleReconnect();
                };

                this.socket.onerror = (error) => {
                    console.error('WebSocket error:', error);
                };

            } catch (error) {
                console.error('Error initializing WebSocket:', error);
                this.handleReconnect();
            }
        },

        handleReconnect() {
            if (this.reconnectAttempts < this.maxReconnectAttempts) {
                this.reconnectAttempts++;
                console.log(`Attempting to reconnect (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
                setTimeout(() => {
                    this.initializeWebSocket();
                }, this.reconnectInterval);
            } else {
                console.log('Max reconnection attempts reached');
            }
        },

        async showBrowserNotification(notification) {
            if (!("Notification" in window)) {
                return;
            }

            if (Notification.permission === "granted") {
                new Notification("New Notification", {
                    body: notification.message,
                    icon: "/path/to/your/icon.png" // Replace with your notification icon
                });
            } else if (Notification.permission !== "denied") {
                const permission = await Notification.requestPermission();
                if (permission === "granted") {
                    this.showBrowserNotification(notification);
                }
            }
        },

        disconnectWebSocket() {
            if (this.socket && this.socket.readyState === WebSocket.OPEN) {
                this.socket.close();
                this.socket = null;
            }
        },

        // Rest of your existing methods remain the same
        processNotifications(notifications) {
            return notifications.map(notification => {
                if (notification.message !== undefined && notification.project_name !== undefined) {
                    return notification;
                }
                
                let data = notification.data || {};
                
                return {
                    id: notification.id,
                    type: data.type || 'notification',
                    message: data.message || 'New notification',
                    project_name: data.project_name || '',
                    assessment_id: data.assessment_id,
                    read_at: notification.read_at,
                    created_at: notification.created_at
                };
            });
        },
        
        async markAsRead(notification) {
            try {
                const response = await axios.post(`/sispa/api/notifications/${notification.id}/read`);
                if (response.data.success) {
                    notification.read_at = new Date();
                    this.localUnreadCount = Math.max(0, this.localUnreadCount - 1);
                    
                    const type = notification.type || (response.data.type || '');
                    
                    let route = '/mahasiswa/assessment/';
                    if (type.toLowerCase().includes('self')) {
                        route += 'self';
                    } else if (type.toLowerCase().includes('peer')) {
                        route += 'peer';
                    } else {
                        console.warn('Unknown notification type:', type);
                        return;
                    }
                    
                    router.visit(route);
                }
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        },

        async markAllAsRead() {
            try {
                const response = await axios.post('/sispa/api/notifications/read-all');
                if (response.data.success) {
                    this.localNotifications.forEach(notification => {
                        notification.read_at = new Date();
                    });
                    this.localUnreadCount = 0;
                }
            } catch (error) {
                console.error('Error marking all notifications as read:', error);
            }
        },

        async refreshNotifications() {
            if (this.loading) return;
            
            try {
                this.loading = true;
                const response = await axios.get('/sispa/api/notifications/get');
                
                if (response.data.success) {
                    this.localNotifications = this.processNotifications(response.data.data.notifications);
                    this.localUnreadCount = response.data.data.unread_count;
                }
            } catch (error) {
                console.error('Error refreshing notifications:', error);
            } finally {
                this.loading = false;
            }
        }
    },
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <Sidebar role="mahasiswa" />
        <div class="flex-1">
            <Navbar userName="Mahasiswa" />
            <main class="p-6">
                <div class="mb-4">
                    <Breadcrumb :items="breadcrumbs" />
                </div>
                
                <!-- Main Content -->
                <div class="bg-white rounded-lg shadow-md">
                    <!-- Header with unread count and actions -->
                    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                        <div class="flex items-center gap-4">
                            <h2 class="text-lg font-semibold text-gray-900">Notifications</h2>
                            <span v-if="localUnreadCount > 0"
                                class="bg-red-500 text-white px-2 py-1 rounded-full text-sm">
                                {{ localUnreadCount }} new
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <button 
                                @click="refreshNotifications"
                                :disabled="loading"
                                class="px-3 py-1 text-sm text-gray-600 hover:text-gray-900 disabled:opacity-50">
                                {{ loading ? 'Refreshing...' : 'Refresh' }}
                            </button>
                            <button 
                                v-if="localUnreadCount > 0"
                                @click="markAllAsRead"
                                class="px-3 py-1 text-sm text-blue-600 hover:text-blue-800">
                                Mark all as read
                            </button>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div v-if="loading" class="p-8 text-center text-gray-500">
                        Loading notifications...
                    </div>

                    <!-- Notifications List -->
                    <div v-else>
                        <template v-if="localNotifications && localNotifications.length > 0">
                            <div v-for="notification in localNotifications" 
                                :key="notification.id" 
                                class="p-4 border-b border-gray-100 hover:bg-gray-50 transition-colors duration-150 cursor-pointer"
                                :class="{ 'bg-blue-50': !notification.read_at }"
                                @click="markAsRead(notification)">
                                <div class="flex items-start space-x-4">
                                    <div class="flex-shrink-0">
                                        <span class="inline-block px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">
                                            {{ notification.type }}
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ notification.message || 'New notification' }}
                                        </p>
                                        <p v-if="notification.project_name" class="text-sm text-gray-600 mt-1">
                                            {{ notification.project_name }}
                                        </p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <p class="text-xs text-gray-500">
                                                {{ notification.created_at }}
                                            </p>
                                            <span v-if="!notification.read_at" 
                                                class="text-xs text-blue-600">
                                                • New
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Empty State -->
                        <div v-else class="p-8 text-center text-gray-500">
                            No notifications available
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>