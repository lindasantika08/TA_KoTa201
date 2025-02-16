<script>
import axios from "axios";
import {router} from '@inertiajs/vue3';
import Sidebar from "@/components/SidebarAdmin.vue";
import Navbar from "@/components/Navbar.vue"

export default {
    name : "DashboardAdmin",
    components: {
        Sidebar,
        Navbar,
    }
}
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <Sidebar role="admin" />
        <div class="flex-1">
            <navbar userName="admin"/>
            <main class="p-6">

            </main>
        </div>

    </div>
</template>
