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
          <!-- Card 1: Profile Mahasiswa -->
          <Card class="flex-[1] text-center p-6 max-h-[320px]" title="Profile">
            <div class="flex flex-col items-center">
              <!-- Foto Profil -->
              <div class="relative">
                <img :src="profileImage" alt="Foto Profil" class="w-32 h-32 rounded-full border-4 border-gray-300" />
                
                <input ref="fileInput" type="file" @change="handleFileUpload" class="hidden" />
              </div>
              <h2 class="text-lg font-semibold mt-4">{{ namaMahasiswa }}</h2>
              <p class="text-gray-600">{{ nimMahasiswa }}</p>
            </div>
          </Card>

          <!-- Card 2: Informasi Akademik -->
          <Card :title="'Profile - ' + user_name" class="flex-[3] p-6 h-full">

            <!-- Data Identitas Mahasiswa -->
            <div class="grid grid-cols-1 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                  :value="namaMahasiswa" readonly />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">NIM</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                  :value="nimMahasiswa" readonly />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Prodi</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                  :value="prodiMahasiswa" readonly />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Jurusan</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                  :value="jurusanMahasiswa" readonly />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                  :value="emailMahasiswa" readonly />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Telepon</label>
                <input type="tel" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                  :value="teleponMahasiswa" readonly />
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
  name: "DetailMahasiswa",
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
        { text: "Manage Mahasiswa", href: "/dosen/manage-mahasiswa" },
        { text: "Detail", href: "/dosen/manage-mahasiswa/detail" },
      ],
      profileImage: "", // Ganti dengan URL gambar asli jika ada
      namaMahasiswa: "",
      nimMahasiswa: "",
      prodiMahasiswa: "",
      jurusanMahasiswa: "",
      emailMahasiswa: "",
      teleponMahasiswa: "",
    };
  },
  mounted() {
  this.fetchProfile(this.user_id);
  },
  methods: {
    fetchProfile() {
      axios
        .get(`/api/kelola-kelompok/get-profile/${this.user_id}`) // Endpoint API untuk mendapatkan data profil
        .then((response) => {
          // Set data yang didapat dari API
          const profileData = response.data;
          this.namaMahasiswa = profileData.nama;
          this.nimMahasiswa = profileData.nim;
          this.prodiMahasiswa = profileData.prodi;
          this.jurusanMahasiswa = profileData.jurusan;
          this.emailMahasiswa = profileData.email;
          this.teleponMahasiswa = profileData.telepon || ''; // Atur telepon jika ada
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
