<script>
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";

export default {
  components: {
    Sidebar,
    Navbar,
    Card,
  },
  setup() {
    const isLoggingOut = ref(false);
    const assessments = ref([]);

    const downloadTemplate = async () => {
      try {
        const token = localStorage.getItem("auth_token");

        const response = await axios.get("/api/export-self-assessment", {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
          responseType: "blob",
        });

        const blob = new Blob([response.data], {
          type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        });

        const url = window.URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.setAttribute("download", "self-assessment.xlsx");
        document.body.appendChild(link);

        link.click();

        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
      } catch (error) {
        console.error("Download error:", error);
        alert("Terjadi kesalahan saat mengunduh file excel");
      }
    };

    const handleFileUpload = async (event) => {
      const formData = new FormData();
      formData.append("file", event.target.files[0]);

      try {
        await axios.post("/dosen/assessment/import", formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        });
        alert("Data berhasil diimpor");
      } catch (error) {
        console.error("Import error:", error);
        alert("Terjadi kesalahan saat mengimpor data");
      }
    };

    onMounted(async () => {
      try {
        const response = await axios.get("/dosen/assessment/data-with-bobot");
        // Filter hanya yang bertipe 'self assessment'
        assessments.value = response.data.filter(
          (assessment) => assessment.type === "peerAssessment"
        );
      } catch (error) {
        console.error("Error fetching assessments or type criteria:", error);
      }
    });

    // Group assessments berdasarkan aspek
    const groupedAssessments = computed(() => {
      const groups = {};
      assessments.value.forEach((assessment) => {
        if (!groups[assessment.aspek]) {
          groups[assessment.aspek] = [];
        }
        groups[assessment.aspek].push(assessment);
      });
      return groups;
    });

    return {
      downloadTemplate,
      handleFileUpload,
      groupedAssessments,
    };
  },
};
</script>

<template>
  <!-- Wrapper with Flexbox Layout -->
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <Sidebar role="dosen" />

    <!-- Main Content Area -->
    <div class="flex-1 ">
      <!-- Navbar -->
      <Navbar userName="Dosen" />
      <main class="p-6">
        <Card title="Create Assessment">
          <template #actions>
            <label for="file-upload" class="block text-sm font-medium text-gray-700">
                Template Assessment
              </label>
            <!-- Tombol untuk download template Excel -->
            <button @click="downloadTemplate" class="px-4 py-2 mt-4 bg-blue-500 text-white rounded">
              <font-awesome-icon :icon="['fas', 'file-excel']" class="mr-2" />
                Download
            </button>

            <!-- Form untuk mengunggah file Excel -->
            <div class="mt-4">
              <label for="file-upload" class="block text-sm font-medium text-gray-700">
                Import Data Excel (File .xlsx/.xls)
              </label>
              <input type="file" id="file-upload" accept=".xlsx, .xls" @change="handleFileUpload"
                class="mt-2 p-2 border border-gray-300 rounded" />
            </div>
          </template>
        </Card>
      </main>
    </div>
  </div>
</template>


<style scoped>
/* Optional: Add custom styles here */
</style>