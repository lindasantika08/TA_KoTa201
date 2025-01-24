<script>
import axios from "axios";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import { router } from "@inertiajs/vue3";

export default {
    name: "Report",
    components: {
        Sidebar,
        Navbar,
        Card,
        Breadcrumb,
    },
    data() {
        return {
            breadcrumbs: [{ text: "Report", href: "/dosen/report" }],
            combinedOptions: [], // Dropdown options received from controller
            selectedOption: localStorage.getItem("selectedOption") || "", // Retrieve saved value from localStorage
            kelompokList: [], // List of kelompok to display
        };
    },
    methods: {
        // Fetch dropdown options directly from controller
        fetchDropdownOptions() {
            axios
                .get("/api/dropdown-options")
                .then((response) => {
                    this.combinedOptions = response.data.options || []; // Use the provided data
                })
                .catch((error) => {
                    console.error("Error fetching dropdown options:", error);
                });
        },
        // Fetch kelompok data based on selected option
        fetchKelompok() {
            if (!this.selectedOption) {
                this.kelompokList = []; // Clear kelompokList if no selection
                return;
            }

            const selected = this.combinedOptions.find(
                (option) => option.value === this.selectedOption
            );

            if (!selected) return;

            // Log tahun_ajaran dan nama_proyek sebelum mengirim request
            console.log("Tahun Ajaran:", selected.tahunAjaran);
            console.log("Nama Proyek:", selected.namaProyek);

            axios
                .get("/api/kelompok/report", {
                    params: {
                        tahun_ajaran: selected.tahunAjaran,
                        nama_proyek: selected.namaProyek,
                    },
                })
                .then((response) => {
                    if (response.data.success) {
                        this.kelompokList = response.data.kelompok || [];
                    } else {
                        this.kelompokList = []; // Handle case if no kelompok found
                        console.log(response.data.message); // Display error message
                    }
                })
                .catch((error) => {
                    console.error("Error fetching kelompok data:", error);
                });
        },

        // Method to handle dropdown change
        handleDropdownChange() {
            // Save selected option to localStorage
            localStorage.setItem("selectedOption", this.selectedOption);
            this.fetchKelompok(); // Fetch kelompok data based on new selection
        },

        handleReportKelompokDetail(kelompok) {
            const selected = this.combinedOptions.find(
                (option) => option.value === this.selectedOption
            );

            if (!selected) return;

            // Cek log untuk memastikan data sudah benar sebelum dikirim
            console.log('Tahun Ajaran:', selected.tahunAjaran);
            console.log('Nama Proyek:', selected.namaProyek);
            console.log('Nama Kelompok:', kelompok.nama_kelompok);

            // Mengganti URL langsung
            window.location.href = `/dosen/kelompok/report-detail?tahun_ajaran=${selected.tahunAjaran}&nama_proyek=${selected.namaProyek}&kelompok=${kelompok.nama_kelompok}`;
        }
    },
    mounted() {
        this.fetchDropdownOptions();
        handleReportKelompokDetail(kelompok);
    },
};
</script>

<template>
  <div class="flex min-h-screen">
      <Sidebar role="dosen" />
      <div class="flex-1">
          <Navbar userName="Dosen" />
          <main class="p-6">
              <div class="mb-4">
                  <Breadcrumb :items="breadcrumbs" />
              </div>

              <Card title="Report">
                  <!-- Dropdown for Tahun Ajaran - Nama Proyek -->
                  <div class="mb-4">
                      <label
                          for="combinedDropdown"
                          class="block mb-1 font-semibold"
                          >Pilih Tahun Ajaran dan Nama Proyek</label
                      >
                      <select
                          id="combinedDropdown"
                          v-model="selectedOption"
                          @change="handleDropdownChange"
                          class="border rounded px-3 py-2 w-full"
                      >
                          <option value="" disabled selected>
                              Pilih Tahun Ajaran - Nama Proyek
                          </option>
                          <option
                              v-for="option in combinedOptions"
                              :key="option.value"
                              :value="option.value"
                          >
                              {{ option.label }}
                              <!-- Use label for display if provided -->
                          </option>
                      </select>
                  </div>

                  <!-- Display Kelompok Data -->
                  <div
                      v-if="kelompokList.length === 0"
                      class="text-center text-gray-500 py-6"
                  >
                      Tidak ada kelompok yang ditemukan.
                  </div>

                  <div
                      v-else
                      class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
                  >
                      <Card
                          v-for="kelompok in kelompokList"
                          :key="kelompok.id"
                          :title="kelompok.nama_kelompok"
                          @click="handleReportKelompokDetail(kelompok)"
                      >
                          <p><strong>Anggota:</strong></p>
                          <ul>
                              <li
                                  v-for="member in kelompok.anggota"
                                  :key="member.user_id"
                              >
                                  {{ member.name }}
                              </li>
                          </ul>
                      </Card>
                  </div>
              </Card>
          </main>
      </div>
  </div>
</template>
