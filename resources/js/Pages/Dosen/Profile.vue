<template>
  <div class="flex min-h-screen">
    <Sidebar role="dosen" />

    <div class="flex-1">
      <Navbar userName="Dosen" />
      <main class="p-6">
        <div class="mb-4">
          <Breadcrumb :items="breadcrumbs" />
        </div>

        <div class="flex gap-4 w-full">
          <!-- Card Profile -->
          <Card class="flex-[1] text-center p-6 max-h-[320px]" title="Profile">
            <div class="flex flex-col items-center">
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
                  accept="image/*"
                />
              </div>
              <h2 class="text-lg font-semibold mt-4">{{ formData.nama }}</h2>
              <p class="text-gray-600">{{ formData.nip }}</p>
            </div>
          </Card>

          <!-- Card Informasi -->
          <Card class="flex-[3] p-6 h-full">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-lg font-semibold">Identitas Diri</h2>
              <button
                @click="handleEditSave"
                class="px-4 py-2 rounded-lg"
                :class="isEditMode ? 'bg-green-500 hover:bg-green-600' : 'bg-blue-500 hover:bg-blue-600'"
              >
                <span class="text-white">{{ isEditMode ? 'Simpan' : 'Edit' }}</span>
              </button>
            </div>

            <!-- Form Data Diri -->
            <form @submit.prevent="handleEditSave" class="grid grid-cols-1 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                <input
                  v-model="formData.nama"
                  type="text"
                  :class="inputClass"
                  :readonly="!isEditMode"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">NIP</label>
                <input
                  v-model="formData.nip"
                  type="text"
                  :class="inputClass"
                  :readonly="!isEditMode"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Jurusan</label>
                <input
                  v-model="formData.jurusan"
                  type="text"
                  :class="inputClass"
                  :readonly="!isEditMode"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input
                  v-model="formData.email"
                  type="email"
                  :class="inputClass"
                  :readonly="!isEditMode"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Telepon</label>
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
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import axios from "axios";

export default {
  name: "Profile",
  components: {
    Sidebar,
    Navbar,
    Card,
    Breadcrumb,
  },
  data() {
    return {
      breadcrumbs: [{ text: "Profile", href: "/Dosen/profile" }],
      isDropdownVisible: false,
      profileImage: "",
      isEditMode: false,
      formData: {
        nama: "",
        nip: "",
        jurusan: "",
        email: "",
        telepon: "",
      },
      originalData: null, // Untuk menyimpan data asli sebelum edit
    };
  },
  computed: {
    inputClass() {
      return {
        'w-full px-3 py-2 border rounded-md': true,
        'bg-white border-gray-300': this.isEditMode,
        'bg-gray-100 border-gray-300': !this.isEditMode
      };
    }
  },
  mounted() {
    this.fetchProfile();
  },
  methods: {
    fetchProfile() {
      axios.get("/api/get-profile-dosen")
        .then((response) => {
          const profileData = response.data;
          this.formData = {
            nama: profileData.nama,
            nip: profileData.nip,
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
        axios.put("/api/dosen/update-profile", this.formData)
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

        axios.post("/api/dosen/upload-profile-photo", formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        })
          .then((response) => {
            // this.profileImage = response.data.path;
            this.fetchProfile();
            this.isDropdownVisible = false;
            alert("Foto profil berhasil diupload!");
          })
          .catch((error) => {
            alert("Gagal mengupload foto. Silakan coba lagi.");
            console.error("Error upload foto:", error);
          });
      }
    },

    deleteProfilePhoto() {
      if (confirm("Apakah Anda yakin ingin menghapus foto profil?")) {
        axios.delete("/api/dosen/delete-profile-photo")
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