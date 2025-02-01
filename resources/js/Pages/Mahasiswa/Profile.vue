<template>
  <div class="flex min-h-screen">
    <SidebarMahasiswa role="mahasiswa" />

    <div class="flex-1">
      <Navbar userName="Mahasiswa" />
      <main class="p-6">
        <div class="mb-4">
          <Breadcrumb :items="breadcrumbs" />
        </div>

        <!-- Wrapper untuk membuat card sejajar -->
        <div class="flex gap-4">
          <!-- Card 1: Profile Mahasiswa -->
          <Card class="w-1/4 text-center p-6" title="Profile">
            <div class="flex flex-col items-center">
              <!-- Foto Profil -->
              <div class="relative">
                <img :src="profileImage" alt="Foto Profil" class="w-32 h-32 rounded-full border-4 border-gray-300" />
                <button @click="openFileInput" class="absolute bottom-0 right-0 bg-gray-800 text-white p-2 rounded-full"
                  title="Ubah Foto">
                  ✏️
                </button>
                <input ref="fileInput" type="file" @change="handleFileUpload" class="hidden" />
              </div>
              <h2 class="text-lg font-semibold mt-4">{{ namaMahasiswa }}</h2>
              <p class="text-gray-600">{{ nimMahasiswa }}</p>
            </div>
          </Card>

          <!-- Card 2: Informasi Akademik -->
          <Card class="w-3/4 p-6">
            <!-- Header Card dengan Button Edit -->
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-lg font-semibold">Identitas Diri</h2>
              <button class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                Edit
              </button>
            </div>

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
import SidebarMahasiswa from "@/Components/SidebarMahasiswa.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import axios from "axios";

export default {
  name: "Profile",
  components: {
    SidebarMahasiswa,
    Navbar,
    Card,
    Breadcrumb,
  },
  data() {
    return {
      breadcrumbs: [{ text: "Profile", href: "/Mahasiswa/profile" }],
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
    // Ambil data profil mahasiswa dari API
    this.fetchProfile();
  },
  methods: {
    fetchProfile() {
      axios
        .get("/api/get-profile") // Endpoint API untuk mendapatkan data profil
        .then((response) => {
          // Set data yang didapat dari API
          const profileData = response.data;
          this.namaMahasiswa = profileData.nama;
          this.nimMahasiswa = profileData.nim;
          this.prodiMahasiswa = profileData.prodi;
          this.jurusanMahasiswa = profileData.jurusan;
          this.emailMahasiswa = profileData.email;
          this.teleponMahasiswa = profileData.telepon || ''; // Atur telepon jika ada
          // Jika ada foto profil
          this.fetchProfilePhoto(); // Menampilkan foto profil
        })
        .catch((error) => {
          console.error("Error fetching profile data:", error);
        });
    },
    openFileInput() {
      this.$refs.fileInput.click(); // Menampilkan file input ketika tombol edit diklik
    },
    fetchProfilePhoto() {
      axios
        .get("/api/mahasiswa/get-profile-photo") // Ambil URL foto profil
        .then((response) => {
          this.profileImage = response.data.photo_url || this.profileImage;
        })
        .catch((error) => {
          console.error("Error fetching profile photo:", error);
        });
    },
    handleFileUpload(event) {
      const file = event.target.files[0];
      if (file) {
        const formData = new FormData();
        formData.append("photo", file);

        axios
          .post("/api/mahasiswa/upload-profile-photo", formData, {
            headers: {
              "Content-Type": "multipart/form-data",
            },
          })
          .then((response) => {
            // Update gambar profil setelah berhasil diupload
            this.profileImage = response.data.photo_url;
            this.fetchProfile();
          })
          .catch((error) => {
            console.error("Error uploading profile photo:", error);
          });
      }
    },
    deleteProfilePhoto() {
      axios
        .post("/api/mahasiswa/delete-profile-photo")
        .then(() => {
          this.profileImage = "https://via.placeholder.com/150"; // Ganti dengan gambar placeholder
        })
        .catch((error) => {
          console.error("Error deleting profile photo:", error);
        });
    },
  },
};
</script>
