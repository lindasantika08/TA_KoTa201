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
        <div class="flex gap-4 w-full">
          <!-- Card 1: Profile Mahasiswa -->
          <Card class="flex-[1] text-center p-6 max-h-[320px]" title="Profile">
            <div class="flex flex-col items-center">
              <!-- Foto Profil -->
              <div class="relative">
                <img
                  :src="profileImage"
                  alt="Foto Profil"
                  class="w-32 h-32 rounded-full border-4 border-gray-300"
                />
                <button
                  @click="toggleDropdown"
                  class="absolute bottom-0 right-0 bg-gray-800 text-white p-2 rounded-full"
                  title="Ubah Foto"
                >
                  ✏️
                </button>

                <!-- Dropdown untuk ubah dan hapus foto -->
                <div
                  v-if="isDropdownVisible && profileImage"
                  class="absolute bottom-0 right-0 bg-white shadow-lg rounded-lg w-40 mt-2"
                >
                  <ul class="flex flex-col">
                    <li>
                      <button
                        @click="openFileInput"
                        class="w-full px-4 py-2 text-blue-500 text-left hover:bg-gray-100"
                      >
                        Ubah Foto
                      </button>
                    </li>
                    <li>
                      <button
                        @click="deleteProfilePhoto"
                        class="w-full px-4 py-2 text-red-500 text-left hover:bg-gray-100"
                      >
                        Hapus Foto
                      </button>
                    </li>
                  </ul>
                </div>

                <input
                  ref="fileInput"
                  type="file"
                  @change="handleFileUpload"
                  class="hidden"
                />
              </div>
              <h2 class="text-lg font-semibold mt-4">{{ namaMahasiswa }}</h2>
              <p class="text-gray-600">{{ nimMahasiswa }}</p>
            </div>
          </Card>

          <!-- Card 2: Informasi Akademik -->
          <Card class="flex-[3] p-6 h-full">
            <!-- Header Card dengan Button Edit -->
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-lg font-semibold">Identitas Diri</h2>
              <button
                class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600"
              >
                Edit
              </button>
            </div>

            <!-- Data Identitas Mahasiswa -->
            <div class="grid grid-cols-1 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Nama Lengkap</label
                >
                <input
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                  :value="namaMahasiswa"
                  readonly
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >NIM</label
                >
                <input
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                  :value="nimMahasiswa"
                  readonly
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Prodi</label
                >
                <input
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                  :value="prodiMahasiswa"
                  readonly
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Jurusan</label
                >
                <input
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                  :value="jurusanMahasiswa"
                  readonly
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Email</label
                >
                <input
                  type="email"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                  :value="emailMahasiswa"
                  readonly
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Telepon</label
                >
                <input
                  type="tel"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                  :value="teleponMahasiswa"
                  readonly
                />
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
      isDropdownVisible: false,
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
    this.fetchProfile(); // Pastikan profil diambil dulu
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
          this.teleponMahasiswa = profileData.telepon || ""; // Atur telepon jika ada
          this.profileImage = profileData.photo || ""; // Update foto profil
          console.log("Response dari API:", response.data);
        })
        .catch((error) => {
          console.error("Error fetching profile data:", error);
        });
    },
    toggleDropdown() {
      // Jika tidak ada foto, buka file input untuk memilih foto baru
      if (!this.profileImage) {
        this.openFileInput();
      } else {
        // Jika ada foto, tampilkan dropdown
        this.isDropdownVisible = !this.isDropdownVisible;
      }
    },
    openFileInput() {
      this.$refs.fileInput.click(); 
      this.isDropdownVisible = false;
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
            const baseUrl = "/storage/"; 
            this.profileImage = baseUrl + response.data.path;
            this.isDropdownVisible = false;
          })
          .catch((error) => {
            console.error("Error uploading profile photo:", error);
          });
      }
    },
    deleteProfilePhoto() {
      axios
        .delete("/api/mahasiswa/delete-profile-photo")
        .then(() => {
          this.profileImage = ""; 
          this.isDropdownVisible = false;
        })
        .catch((error) => {
          console.error("Error deleting profile photo:", error);
        });
    },
  },
};
</script>
