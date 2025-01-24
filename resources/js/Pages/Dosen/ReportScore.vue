<template>
    <div class="flex min-h-screen">
      <!-- Sidebar -->
      <Sidebar role="dosen" />
      
      <div class="flex-1">
        <!-- Navbar -->
        <Navbar userName="Dosen" />
        
        <main class="p-6">
          <div class="mb-4">
            <!-- Breadcrumb -->
            <Breadcrumb :items="breadcrumbs" />
          </div>
          
          <!-- Card displaying group details -->
          <Card title="Kelompok Detail">
            <div v-if="tahunAjaran && namaProyek && kelompok">
              <p><strong>Tahun Ajaran:</strong> {{ tahunAjaran }}</p>
              <p><strong>Nama Proyek:</strong> {{ namaProyek }}</p>
              <p><strong>Kelompok:</strong> {{ kelompok }}</p>
            </div>
            <div v-else>
              <p>No data available or the requested group was not found.</p>
            </div>
          </Card>
        </main>
      </div>
    </div>
  </template>
  
  <script>
  import Sidebar from "@/Components/Sidebar.vue";
  import Navbar from "@/Components/Navbar.vue";
  import Card from "@/Components/Card.vue";
  import Breadcrumb from "@/Components/Breadcrumb.vue";
  
  export default {
    props: {
      tahunAjaran: {
        type: String,
        required: true,
      },
      namaProyek: {
        type: String,
        required: true,
      },
      kelompok: {
        type: String,
        required: true,
      },
    },
    components: {
      Sidebar,
      Navbar,
      Card,
      Breadcrumb,
    },
    data() {
      return {
        breadcrumbs: [{ text: "Report", href: "/dosen/report" }],
      };
    },
    mounted() {
      console.log("Received data:", this.tahunAjaran, this.namaProyek, this.kelompok);
    }
  };
  </script>
  