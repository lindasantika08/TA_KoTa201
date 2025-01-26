<script>
import axios from "axios";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";


export default {
  name: "DetailDosen",
  components: {
    Sidebar,
    Navbar,
    Card,
    Breadcrumb,
  },
  data() {
    return {
      breadcrumbs: [
        { text: "Manage Dosen", href: "/dosen/manage-dosen" },
        { text: "Detail", href: "/dosen/manage-dosen/detail" },
      ],
    };
  },
};
</script>

<template>
  <div class="flex min-h-screen">
    <Sidebar role="dosen" />

    <div class="flex-1 ">
      <Navbar userName="Dosen" />
      <main class="p-6">
        <div class="mb-4">
          <Breadcrumb :items="breadcrumbs" />
        </div>
        <Card title="Detail Dosen">
          <template #actions>

          </template>
        </Card>
      </main>
    </div>
  </div>
</template>


<style scoped></style>