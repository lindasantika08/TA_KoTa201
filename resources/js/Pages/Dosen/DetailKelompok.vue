<script>
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";

export default {
  name: "DetailKelompok",
  components: {
    Sidebar,
    Navbar,
    Card,
  },
  props: {
    kelompok: Object,
  },
};
</script>

<template>
  <div class="flex min-h-screen">
    <Sidebar role="dosen" />
    <div class="flex-1">
      <Navbar userName="Dosen" />
      <main class="p-6">
        <Card title="Detail Kelompok">
          <div>
            <h2 class="text-lg font-bold">Nama Proyek: {{ kelompok.nama_proyek }}</h2>
            <p>Tahun Ajaran: {{ kelompok.tahun_ajaran }}</p>
            <p>Manager Dosen: {{ kelompok.dosen.name }}</p>
            <p>Anggota Kelompok:</p>
            <ul>
              <li v-for="user in kelompok.user" :key="user.id">{{ user.name }}</li>
            </ul>
          </div>
        </Card>
      </main>
    </div>
  </div>
</template>

<style scoped></style>
