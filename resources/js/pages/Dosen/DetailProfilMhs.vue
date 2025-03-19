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
          <Card class="flex-[2] text-center p-6 max-h-[380px]" title="ProfileMhs">
            <div class="flex flex-col items-center">
              <!-- Foto Profil -->
              <div class="relative">
                <img :src="profileImage" alt="Foto Profil" class="w-32 h-32 rounded-full border-4 border-gray-300 shadow-lg object-cover" />
                
                <input ref="fileInput" type="file" @change="handleFileUpload" class="hidden" />
              </div>
              <h2 class="text-lg font-semibold mt-4">{{ namaMahasiswa }}</h2>
              <p class="text-gray-600 mb-2">{{ nimMahasiswa }}</p>
              <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
                    </svg>
                    {{ prodiMahasiswa }} - {{ jurusanMahasiswa }}

              </div>
            </div>
          </Card>

          <!-- Card 2: Informasi Akademik -->
          <Card :title="'Profile Mhs - ' + user_name" class="flex-[3] p-6 h-full">

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
  name: "DetailProfilMhs",
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
        { text: "Manage Group", href: "/sispa/dosen/kelola-kelompok" },
        { text: "Profile Mhs", href: "/sispa/dosen/kelola-kelompok/profile-mhs" },
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
