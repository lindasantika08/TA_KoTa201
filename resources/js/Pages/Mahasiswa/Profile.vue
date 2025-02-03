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
                @click="handleEditSave"
                class="px-4 py-2 rounded-lg"
                :class="
                  isEditMode
                    ? 'bg-green-500 hover:bg-green-600'
                    : 'bg-blue-500 hover:bg-blue-600'
                "
              >
                <span class="text-white">{{
                  isEditMode ? "Simpan" : "Edit"
                }}</span>
              </button>
            </div>

            <!-- Data Identitas Mahasiswa -->
            <form
              @submit.prevent="handleEditSave"
              class="grid grid-cols-1 gap-4"
            >
              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Nama Lengkap</label
                >
                <input
                  v-model="formData.nama"
                  type="text"
                  :class="inputClass"
                  :readonly="!isEditMode"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >NIM</label
                >
                <input
                  v-model="formData.nim"
                  type="text"
                  :class="inputClass"
                  :readonly="!isEditMode"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Prodi</label
                >
                <input
                  v-model="formData.prodi"
                  type="text"
                  :class="inputClass"
                  :readonly="!isEditMode"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Jurusan</label
                >
                <input
                  v-model="formData.jurusan"
                  type="text"
                  :class="inputClass"
                  :readonly="!isEditMode"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Email</label
                >
                <input
                  v-model="formData.email"
                  type="email"
                  :class="inputClass"
                  :readonly="!isEditMode"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Telepon</label
                >
                <input
                  v-model="formData.telepon"
                  type="tel"
                  :class="inputClass"
                  :readonly="!isEditMode"
                />
              </div>
            </form>
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
      isEditMode: false,
      profileImage: "", // Ganti dengan URL gambar asli jika ada
      formData: {
        nama: "",
        nim: "",
        prodi: "",
        jurusan: "",
        email: "",
        telepon: "",
      },
      originalData: null,
    };
  },
  computed: {
    inputClass() {
      return {
        "w-full px-3 py-2 border rounded-md": true,
        "bg-white border-gray-300": this.isEditMode,
        "bg-gray-100 border-gray-300": !this.isEditMode,
      };
    },
  },
  mounted() {
    this.fetchProfile(); // Pastikan profil diambil dulu
  },
  methods: {
    fetchProfile() {
      axios
        .get("/api/get-profile") // Endpoint API untuk mendapatkan data profil
        .then((response) => {
          const profileData = response.data;
          this.formData = {
            nama: profileData.nama,
            nim: profileData.nim,
            prodi: profileData.prodi,
            jurusan: profileData.jurusan,
            email: profileData.email,
            telepon: profileData.telepon || "",
          };
          this.originalData = { ...this.formData }; // Simpan salinan data asli
          this.profileImage = profileData.photo || "";
        })
        .catch((error) => {
          console.error("Error mengambil data profil:", error);
        });
    },
    handleEditSave() {
      if (this.isEditMode) {
        // Mode Simpan
        axios
          .put("/api/mahasiswa/update-profile", this.formData)
          .then((response) => {
            alert("Data berhasil disimpan!");
            this.originalData = { ...this.formData }; // Update data asli
            this.isEditMode = false;
          })
          .catch((error) => {
            alert("Gagal menyimpan data. Silakan coba lagi.");
            console.error("Error menyimpan data:", error);
          });
      } else {
        // Mode Edit
        this.isEditMode = true;
      }
    },
    // Jika user membatalkan edit
    cancelEdit() {
      this.formData = { ...this.originalData }; // Kembalikan ke data asli
      this.isEditMode = false;
    },
    toggleDropdown() {
      if (!this.profileImage) {
        this.openFileInput();
      } else {
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
      if (confirm("Apakah Anda yakin ingin menghapus foto profil?")) {
        axios
          .delete("/api/mahasiswa/delete-profile-photo")
          .then(() => {
            this.profileImage = "";
            this.isDropdownVisible = false;
            alert("Foto profil berhasil dihapus!");
          })
          .catch((error) => {
            alert("Gagal menghapus foto. Silakan coba lagi.");
            console.error("Error menghapus foto:", error);
          });
      }
    },
  },
};
</script>
