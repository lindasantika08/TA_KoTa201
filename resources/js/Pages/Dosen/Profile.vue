<template>
  <div class="flex min-h-screen">
    <Sidebar role="dosen" />

    <div class="flex-1">
      <Navbar userName="Dosen" />
      <main class="p-6">
        <div class="mb-4">
          <Breadcrumb :items="breadcrumbs" />
        </div>

        <div class="grid grid-cols-12 gap-4">
          <!-- Left Column -->
          <div class="col-span-4 space-y-4">
            <!-- Card Profile -->
            <Card class="text-center p-6" title="Profile">
              <div class="flex flex-col items-center">
                <!-- Foto Profil -->
                <div class="relative">
                  <img :src="profileImage" alt="Foto Profil"
                    class="w-32 h-32 rounded-full border-4 border-gray-300 shadow-lg object-cover" />
                  <button @click="toggleDropdown"
                    class="absolute bottom-0 right-0 bg-white text-gray-700 p-2 rounded-full shadow-lg hover:bg-gray-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path
                        d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                    </svg>
                  </button>

                  <div v-if="isDropdownVisible && profileImage"
                    class="absolute bottom-0 right-0 bg-white shadow-lg rounded-lg w-40 mt-2">
                    <ul class="flex flex-col">
                      <li>
                        <button @click="openFileInput"
                          class="w-full px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path
                              d="M4 5a2 2 0 012-2h4.586A2 2 0 0112 3.586L15.414 7A2 2 0 0116 8.414V15a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" />
                          </svg>
                          Ubah Foto
                        </button>
                      </li>
                      <li>
                        <button @click="deleteProfilePhoto"
                          class="w-full px-4 py-2 text-red-600 hover:bg-red-50 flex items-center gap-2">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                              d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                              clip-rule="evenodd" />
                          </svg>
                          Hapus Foto
                        </button>
                      </li>
                    </ul>
                  </div>

                  <input ref="fileInput" type="file" @change="handleFileUpload" class="hidden" accept="image/*" />
                </div>
                <h2 class="text-xl font-semibold mt-4">{{ formData.nama }}</h2>
                <p class="text-gray-600 mb-2">{{ formData.nip }}</p>
                <div class="flex text-gray-500 text-sm">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path
                      d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
                  </svg>
                  {{ formData.jurusan }}
                </div>
              </div>
            </Card>

            <!-- Change Password Button -->
            <button @click="showModal = true"
              class="w-full bg-blue-500 hover:bg-blue-600 text-white p-4 rounded-lg transition-colors flex items-center justify-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path
                  d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2V7a5 5 0 00-5-5zM4 10a6 6 0 0112 0v2a4 4 0 01-4 4H8a4 4 0 01-4-4v-2z" />
              </svg>
              Change Password
            </button>
          </div>

          <!-- Right Column - Card Informasi -->
          <div class="col-span-8">
            <Card class="p-6">
              <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">Identitas Diri</h2>
                <button @click="handleEditSave" class="px-4 py-2 rounded-lg" :class="isEditMode
                    ? 'bg-green-500 hover:bg-green-600'
                    : 'bg-blue-500 hover:bg-blue-600'
                  ">
                  <span class="text-white">{{
                    isEditMode ? "Simpan" : "Edit"
                    }}</span>
                </button>
              </div>

              <!-- Form Data Diri -->
              <form @submit.prevent="handleEditSave" class="grid grid-cols-1 gap-4">
                <!-- ... rest of the form inputs remain the same ... -->
                <div>
                  <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                  <input v-model="formData.nama" type="text" :class="inputClass" :readonly="!isEditMode" />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">NIP</label>
                  <input v-model="formData.nip" type="text" :class="inputClass" :readonly="!isEditMode" />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Kode Dosen</label>
                  <input v-model="formData.kode_dosen" type="text" :class="inputClass" :readonly="!isEditMode" />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Jurusan</label>
                  <input v-model="formData.jurusan" type="text" :class="inputClass" :readonly="!isEditMode" />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Email</label>
                  <input v-model="formData.email" type="email" :class="inputClass" :readonly="!isEditMode" />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Telepon</label>
                  <input v-model="formData.telepon" type="tel" :class="inputClass" :readonly="!isEditMode" />
                </div>
              </form>
            </Card>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- Modal with validation -->
  <div v-if="showModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg w-full max-w-md">
      <h2 class="text-2xl font-semibold mb-4">Change Password</h2>
      <form @submit.prevent="changePassword">
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700">Old Password</label>
          <input v-model="passwordForm.oldPassword" type="password" class="w-full px-3 py-2 border rounded-md bg-white"
            :class="errors.oldPassword ? 'border-red-500' : 'border-gray-300'" required />
          <p v-if="errors.oldPassword" class="mt-1 text-sm text-red-500">{{ errors.oldPassword }}</p>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700">New Password</label>
          <input v-model="passwordForm.newPassword" type="password" class="w-full px-3 py-2 border rounded-md bg-white"
            :class="errors.newPassword ? 'border-red-500' : 'border-gray-300'" required />
          <p v-if="errors.newPassword" class="mt-1 text-sm text-red-500">{{ errors.newPassword }}</p>
          <p class="mt-1 text-sm text-gray-500">Password minimal 8 karakter</p>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
          <input v-model="passwordForm.confirmPassword" type="password"
            class="w-full px-3 py-2 border rounded-md bg-white"
            :class="errors.confirmPassword ? 'border-red-500' : 'border-gray-300'" required />
          <p v-if="errors.confirmPassword" class="mt-1 text-sm text-red-500">{{ errors.confirmPassword }}</p>
        </div>
        <div class="flex justify-end">
          <button type="button" @click="closeModal" class="px-4 py-2 bg-gray-500 text-white rounded-lg mr-2">
            Cancel
          </button>
          <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg">
            Change Password
          </button>
        </div>
      </form>
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
        kode_dosen: "",
        jurusan: "",
        email: "",
        telepon: "",
      },
      originalData: null, // Untuk menyimpan data asli sebelum edit
      showModal: false,
      passwordForm: {
        oldPassword: '',
        newPassword: '',
        confirmPassword: ''
      },
      errors: {
        oldPassword: '',
        newPassword: '',
        confirmPassword: ''
      }
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
    this.fetchProfile();
  },
  methods: {
    fetchProfile() {
      axios
        .get("/api/get-profile-dosen")
        .then((response) => {
          const profileData = response.data;
          this.formData = {
            nama: profileData.nama,
            nip: profileData.nip,
            kode_dosen: profileData.kode_dosen,
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
          .put("/api/dosen/update-profile", this.formData)
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
          .post("/api/dosen/upload-profile-photo", formData, {
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
        axios
          .delete("/api/dosen/delete-profile-photo")
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

    resetErrors() {
      this.errors = {
        oldPassword: '',
        newPassword: '',
        confirmPassword: ''
      };
    },

    closeModal() {
      this.showModal = false;
      this.resetErrors();
      this.passwordForm = {
        oldPassword: '',
        newPassword: '',
        confirmPassword: ''
      };
    },

    validatePasswordForm() {
      this.resetErrors();
      let isValid = true;

      if (!this.passwordForm.oldPassword) {
        this.errors.oldPassword = 'Password lama harus diisi';
        isValid = false;
      }

      if (!this.passwordForm.newPassword) {
        this.errors.newPassword = 'Password baru harus diisi';
        isValid = false;
      } else if (this.passwordForm.newPassword.length < 8) {
        this.errors.newPassword = 'Password minimal 8 karakter';
        isValid = false;
      }

      if (!this.passwordForm.confirmPassword) {
        this.errors.confirmPassword = 'Konfirmasi password harus diisi';
        isValid = false;
      } else if (this.passwordForm.newPassword !== this.passwordForm.confirmPassword) {
        this.errors.confirmPassword = 'Password tidak sama';
        isValid = false;
      }

      return isValid;
    },

    changePassword() {
      if (!this.validatePasswordForm()) {
        return;
      }

      axios.post('/api/change-password', this.passwordForm)
        .then(response => {
          // Update token baru jika ada
          if (response.data.token) {
            // Simpan token baru ke localStorage
            localStorage.setItem('token', response.data.token);

            // Update header Authorization untuk axios
            axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
          }

          alert(response.data.message || 'Password berhasil diubah');
          this.closeModal();
        })
        .catch(error => {
          if (error.response && error.response.data) {
            if (error.response.status === 401) {
              this.errors.oldPassword = error.response.data.message;
            } else {
              alert(error.response.data.message || 'Gagal mengubah password. Silakan coba lagi.');
            }
          } else {
            alert('Terjadi kesalahan. Silakan coba lagi.');
          }
          console.error('Error changing password:', error);
        });
    },
  },
};
</script>
