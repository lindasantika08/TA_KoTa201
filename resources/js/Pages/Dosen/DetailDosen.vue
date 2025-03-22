<template>
  <div class="flex min-h-screen">
    <Sidebar role="dosen" />

    <div class="flex-1">
      <Navbar userName="dosen" />
      <main class="p-6">
        <div class="mb-4">
          <Breadcrumb :items="breadcrumbs" />
        </div>

        <!-- Wrapper untuk membuat card sejajar -->
        <div class="flex gap-4 w-full">
          <!-- Card 1: Profile Dosen -->
          <Card class="flex-[1] text-center p-6 max-h-[320px]" title="Profile">
            <div class="flex flex-col items-center">
              <!-- Foto Profil -->
              <div class="relative">
                <img :src="profileImage" alt="Foto Profil" class="w-32 h-32 rounded-full border-4 border-gray-300" />
                
                <input ref="fileInput" type="file" @change="handleFileUpload" class="hidden" />
              </div>
              <h2 class="text-lg font-semibold mt-4">{{ namaDosen }}</h2>
              <p class="text-gray-600">{{ nipDosen }}</p>
            </div>
          </Card>

          <!-- Card 2: Informasi Akademik -->
          <Card :title="'Profile - ' + user_name" class="flex-[3] p-6 h-full">

            <!-- Data Identitas Dosen -->
            <div class="grid grid-cols-1 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                  :value="namaDosen" readonly />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">NIP</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                  :value="nipDosen" readonly />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Jurusan</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                  :value="jurusanDosen" readonly />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                  :value="emailDosen" readonly />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Telepon</label>
                <input type="tel" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                  :value="teleponDosen" readonly />
              </div>
            </div>
          </Card>
        </div>
      </main>
    </div>
  </div>
</template>

<script>
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import axios from "axios";

export default {
  name: "DetailDosen",
  components: {
    Sidebar,
    Navbar,
    Card,
    Breadcrumb,
  },
  props: {
    user_id: Number,
    user_name: String,
  },
  data() {
    return {
      breadcrumbs: [
        { text: "Manage Dosen", href: "/dosen/manage-dosen" },
        { text: "Detail", href: "/dosen/manage-dosen/detail" },
      ],
      profileImage: "", // Ganti dengan URL gambar asli jika ada
      namaDosen: "",
      nipDosen: "",
      jurusanDosen: "",
      emailDosen: "",
      teleponDosen: "",
    };
  },
  mounted() {
  this.fetchProfile(this.user_id);
  },
  methods: {
    fetchProfile() {
      axios
        .get(`/sispa/api/get-dosen/detail/${this.user_id}`) // Endpoint API untuk mendapatkan data profil
        .then((response) => {
          // Set data yang didapat dari API
          const profileData = response.data;
          this.namaDosen = profileData.nama;
          this.nipDosen = profileData.nip;
          this.jurusanDosen = profileData.jurusan;
          this.emailDosen = profileData.email;
          this.teleponDosen = profileData.telepon || ''; // Atur telepon jika ada
          this.profileImage = profileData.photo || ""; // Update foto profil
        })
        .catch((error) => {
          console.error("Error fetching profile data:", error);
        });
    },
    openFileInput() {
      this.$refs.fileInput.click(); 
    },
  },
};
</script>
