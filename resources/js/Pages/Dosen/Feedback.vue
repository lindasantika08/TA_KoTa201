<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from "axios";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

const breadcrumbs = ref([{ text: "Feedback", href: "/sispa/dosen/feedback" }]);
const combinedOptions = ref([]); 
const selectedOption = ref(null);
const kelompokList = ref([]);
const isLoading = ref(false);
const searchQuery = ref('');

const fetchDropdownOptions = async () => {
  try {
    const response = await axios.get("/sispa/api/dropdown-options");
    combinedOptions.value = response.data.options || [];

    const storedOption = localStorage.getItem("selectedOption");
    if (storedOption) {
      try {
        const parsedOption = JSON.parse(storedOption);
        if (parsedOption.batchYear && parsedOption.projectName) {
          const matchedOption = combinedOptions.value.find(
            (option) =>
              option.batchYear === parsedOption.batchYear &&
              option.projectName === parsedOption.projectName
          );

          if (matchedOption) {
            selectedOption.value = matchedOption;
            selectedOption.value.batch_year = matchedOption.batchYear;
            selectedOption.value.project_name = matchedOption.projectName;
            await fetchKelompok();
          }
        }
      } catch (error) {
        console.error("Error parsing localStorage data:", error);
        localStorage.removeItem("selectedOption");
      }
    }
  } catch (error) {
    console.error("Error fetching dropdown options:", error);
  }
};

const fetchKelompok = async () => {
  if (!selectedOption.value) {
    kelompokList.value = [];
    return;
  }

  isLoading.value = true;
  try {
    const response = await axios.get("/sispa/api/kelompok/report", {
      params: {
        batch_year: selectedOption.value.batch_year,
        project_name: selectedOption.value.project_name,
      },
    });

    if (response.data.success) {
      kelompokList.value = response.data.kelompok || [];
    } else {
      kelompokList.value = [];
      console.log(response.data.message);
    }
  } catch (error) {
    console.error("Error fetching kelompok data:", error);
  } finally {
    isLoading.value = false;
  }
};

const handleDropdownChange = (event) => {
  const selectedValue = event.target.value;
  selectedOption.value = combinedOptions.value.find(
    (option) => option.value === selectedValue
  );

  if (selectedOption.value) {
    selectedOption.value.batch_year = selectedOption.value.batchYear;
    selectedOption.value.project_name = selectedOption.value.projectName;
    localStorage.setItem("selectedOption", JSON.stringify(selectedOption.value));
    fetchKelompok();
  }
};

const handleReportKelompokDetail = (kelompok) => {
  if (!selectedOption.value) return;
  window.location.href = `/dosen/feedback-detail?batch_year=${selectedOption.value.batch_year}&project_name=${selectedOption.value.project_name}&kelompok=${kelompok.nama_kelompok}`;
};

const filteredKelompok = computed(() => {
  if (!searchQuery.value) return kelompokList.value;
  
  const query = searchQuery.value.toLowerCase();
  return kelompokList.value.filter(kelompok => 
    kelompok.nama_kelompok.toLowerCase().includes(query) ||
    kelompok.anggota.some(member => 
      member.name.toLowerCase().includes(query)
    )
  );
});

onMounted(() => {
  fetchDropdownOptions();
});
</script>

<template>
  <div class="flex min-h-screen bg-gray-50">
    <Sidebar role="dosen" />
    <div class="flex-1">
      <Navbar userName="Dosen" />
      <main class="p-6">
        <div class="max-w-7xl mx-auto">
          <div class="mb-6">
            <Breadcrumb :items="breadcrumbs" />
          </div>

          <Card title="Feedback Kelompok Mahasiswa">
            <!-- Filter Section -->
            <div class="space-y-4 mb-6">
                <div class="grid grid-cols-1 gap-4 w-full">

                <!-- Dropdown -->
                <div>
                  <label for="combinedDropdown" class="block mb-2 text-sm font-medium text-gray-700">
                    Pilih Batch Year dan Project Name
                  </label>
                  <select
                    id="combinedDropdown"
                    @change="handleDropdownChange"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                    <option value="" disabled selected>
                      Pilih Batch Year - Project Name
                    </option>
                    <option
                      v-for="option in combinedOptions"
                      :key="option.value"
                      :value="option.value"
                      :selected="selectedOption && option.value === selectedOption.value"
                    >
                      {{ option.label }}
                    </option>
                  </select>
                </div>
                

              </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex justify-center items-center py-12">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            </div>

            <!-- Empty State -->
            <div
              v-else-if="kelompokList.length === 0"
              class="text-center py-12 bg-gray-50 rounded-lg"
            >
              <div class="space-y-3">
                <p class="text-gray-500 text-lg">Tidak ada kelompok yang ditemukan</p>
                <p class="text-gray-400">Silakan pilih batch year dan project name yang berbeda</p>
              </div>
            </div>

            <!-- Group Cards -->
            <div
              v-else
              class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
            >
              <Card
                v-for="kelompok in filteredKelompok"
                :key="kelompok.id"
                :title="kelompok.nama_kelompok"
                class="hover:shadow-md transition-shadow duration-200 cursor-pointer"
                @click="handleReportKelompokDetail(kelompok)"
              >
                <div>
                  <div class="flex justify-end mb-4">
                    <span class="px-3 py-1 text-xs font-medium text-blue-600 bg-blue-100 rounded-full">
                      {{ kelompok.anggota.length }} Anggota
                    </span>
                  </div>
                  <p class="text-sm font-medium text-gray-600 mb-2">Anggota Kelompok:</p>
                  <ul class="space-y-2">
                    <li
                      v-for="member in kelompok.anggota"
                      :key="member.mahasiswa_id"
                      class="flex items-center text-sm text-gray-600"
                    >
                      <span class="h-2 w-2 bg-green-400 rounded-full mr-2"></span>
                      {{ member.name }}
                      <span v-if="member.nim" class="ml-1 text-gray-400">({{ member.nim }})</span>
                    </li>
                  </ul>
                </div>
              </Card>
            </div>
          </Card>
        </div>
      </main>
    </div>
  </div>
</template>