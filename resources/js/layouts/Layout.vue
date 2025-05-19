<script>
import Navbar from '@/Components/Navbar.vue';
import Sidebar from '@/Components/Sidebar.vue';

export default {
  name: "Layout",
  props: {
    role: {
      type: String,
      required: true,
    },
    userName: {
      type: String,
      default: "User",
    },
  },
  methods: {
    handleLogout() {
      this.$emit("logout");
    },
  },
};
</script>

<template>
  <div class="flex">
    <Sidebar :role="role" />
    <div class="flex-1">
      <Navbar :userName="userName" @logout="handleLogout" />
      <main class="p-6">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
/* Add custom styles here if needed */
</style>
