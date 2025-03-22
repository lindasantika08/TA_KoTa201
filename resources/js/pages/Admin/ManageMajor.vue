<script>
import Sidebar from "@/Components/SidebarAdmin.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import DataTable from "@/Components/DataTable.vue";

import axios from "axios";

export default {
  components: {
    DataTable,
    Sidebar,
    Navbar,
    Card,
    Breadcrumb,
  },
  data() {
    return {
      Breadcrumb: [
        { text: "Dashboard", href: "/admin/dashboard" },
        { text: "Majors Management", href: "#" },
      ],
      headers: [
        { key: "no", label: "No" },
        { key: "major_name", label: "Majors Name" },
        { key: "student_count", label: "Total Students" },
        { key: "last_updated", label: "Last Updated" },
        { key: "action", label: "Actions" },
      ],
      majors: [],
      showModal: false,
      newMajor: "",
      showModalEdit: false,
      editedMajor: "",
      originalMajor: "",
      isLoading: false,
      searchQuery: "",
    };
  },
  created() {
    this.fetchMajors();
  },
  computed: {
    filteredMajors() {
      return this.majors.filter((major) =>
        major.major_name.toLowerCase().includes(this.searchQuery.toLowerCase())
      );
    },
    totalMajors() {
      return this.majors.length;
    },
  },
  methods: {
    async fetchMajors() {
      this.isLoading = true;
      try {
        const response = await axios.get("/api/get-major");
        this.majors = response.data.map((item, index) => ({
          no: index + 1,
          major_name: item.major_name,
          student_count: item.student_count,
          last_updated: new Date(item.updated_at).toLocaleDateString(),
        }));
      } catch (error) {
        console.error(error);
      } finally {
        this.isLoading = false;
      }
    },
    async inputMajor() {
      this.showModal = true;
    },
    async saveMajor() {
      if (!this.newMajor.trim()) {
        alert("Major Name can't be empty!");
        return;
      }
      this.isLoading = true;
      try {
        const response = await axios.post("/api/add-major", {
          major_name: this.newMajor,
        });

        if (response.status === 201) {
          alert("Major added successfully!");
          this.showModal = false;
          this.newMajor = "";
          await this.fetchMajors();
        }
      } catch (error) {
        console.error("Error Adding Major: ", error);
        alert("Failed to add Major!");
      } finally {
        this.isLoading = false;
      }
    },
    async deleteMajor(majorName) {
      if (
        !confirm(
          `Are you sure you want to delete "${majorName}"? This action cannot be undone.`
        )
      ) {
        return;
      }
      this.isLoading = true;
      try {
        const response = await axios.post("/api/delete-major", {
          major_name: majorName,
        });

        if (response.status === 201) {
          alert("Major deleted successfully!");
          await this.fetchMajors();
        }
      } catch (error) {
        console.error("Error Deleting Major: ", error);
        alert("Failed to delete major!");
      } finally {
        this.isLoading = false;
      }
    },
    async EditMajor(majorName) {
      this.originalMajor = majorName;
      this.editedMajor = majorName;
      this.showModalEdit = true;
    },
    async updateMajor() {
      if (!this.editedMajor.trim()) {
        alert("Major Name can't be empty!");
        return;
      }
      this.isLoading = true;
      try {
        const response = await axios.post("/api/edit-major", {
          old_major_name: this.originalMajor,
          new_major_name: this.editedMajor,
        });

        if (response.status === 200) {
          alert("Major updated successfully!");
          this.showModalEdit = false;
          await this.fetchMajors();
        }
      } catch (error) {
        console.error("Error Editing Major: ", error);
        alert("Failed to update Major!");
      } finally {
        this.isLoading = false;
      }
    },
  },
};
</script>

<template>
  <div class="flex min-h-screen bg-gray-100">
    <Sidebar role="admin" />
    <div class="flex-1">
      <Navbar userName="admin" />
      <main class="p-6">
        <Breadcrumb :items="Breadcrumb" class="mb-4" />

        <div class="mb-6">
          <h1 class="text-2xl font-bold text-gray-800">Majors Management</h1>
          <p class="text-gray-600">Manage academic majors and their details</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <Card class="bg-white p-4">
            <div class="text-center">
              <h3 class="text-lg font-semibold text-gray-700">Total Majors</h3>
              <p class="text-3xl font-bold text-blue-600">{{ totalMajors }}</p>
            </div>
          </Card>

          <Card class="bg-white p-4">
            <div class="text-center">
              <h3 class="text-lg font-semibold text-gray-700">
                Active Students
              </h3>
              <p class="text-3xl font-bold text-green-600">
                {{
                  majors.reduce((sum, major) => sum + major.student_count, 0)
                }}
              </p>
            </div>
          </Card>

          <Card class="bg-white p-4">
            <div class="text-center">
              <h3 class="text-lg font-semibold text-gray-700">Last Update</h3>
              <p class="text-xl font-medium text-gray-600">
                {{ new Date().toLocaleDateString() }}
              </p>
            </div>
          </Card>
        </div>

        <Card title="Major List" class="bg-white">
          <div class="mb-4 flex justify-between items-center">
            <div class="relative">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search majors..."
                class="pl-8 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <font-awesome-icon
                :icon="['fas', 'search']"
                class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
              />
            </div>
          </div>

          <div v-if="isLoading" class="flex justify-center items-center p-8">
            <font-awesome-icon
              :icon="['fas', 'spinner']"
              class="text-blue-600 text-2xl animate-spin"
            />
          </div>

          <div v-else-if="majors && majors.length">
            <DataTable :headers="headers" :items="filteredMajors">
              <template #column-no="{ item }">
                <div class="text-center font-medium">{{ item.no }}</div>
              </template>

              <template #column-major_name="{ item }">
                <div class="text-left font-medium">
                  {{ item.major_name }}
                </div>
              </template>

              <template #column-student_count="{ item }">
                <div class="text-center">
                  <span
                    class="px-3 py-1 rounded-full bg-blue-100 text-blue-800"
                  >
                    {{ item.student_count }} students
                  </span>
                </div>
              </template>

              <template #column-last_updated="{ item }">
                <div class="text-center text-gray-600">
                  {{ item.last_updated }}
                </div>
              </template>

              <template #column-action="{ item }">
                <div class="flex justify-center space-x-2">
                  <button
                    @click="EditMajor(item.major_name)"
                    class="p-2 text-yellow-600 hover:text-yellow-800 hover:bg-yellow-100 rounded-full transition-colors"
                    title="Edit Major"
                  >
                    <font-awesome-icon :icon="['fas', 'edit']" />
                  </button>
                  <button
                    @click="deleteMajor(item.major_name)"
                    class="p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-full transition-colors"
                    title="Delete Major"
                  >
                    <font-awesome-icon :icon="['fas', 'trash']" />
                  </button>
                </div>
              </template>
            </DataTable>
          </div>
          <div v-else class="text-center py-8">
            <font-awesome-icon
              :icon="['fas', 'folder-open']"
              class="text-4xl text-gray-400 mb-2"
            />
            <p class="text-gray-500">
              No majors available. Add your first major!
            </p>
          </div>
        </Card>

        <!-- Add Major Button -->
        <button
          @click="inputMajor"
          class="fixed bottom-8 right-8 flex items-center justify-center w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105"
          title="Add New Major"
        >
          <font-awesome-icon :icon="['fas', 'plus']" />
        </button>

        <!-- Add Major Modal -->
        <div
          v-if="showModal"
          class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 z-50"
        >
          <div class="bg-white p-6 rounded-lg shadow-lg w-96">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-semibold">Add New Major</h2>
              <button
                @click="showModal = false"
                class="text-gray-500 hover:text-gray-700"
              >
                <font-awesome-icon :icon="['fas', 'times']" />
              </button>
            </div>
            <input
              v-model="newMajor"
              type="text"
              placeholder="Enter Major Name"
              class="w-full p-2 border rounded-md mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <div class="flex justify-end space-x-2">
              <button
                @click="showModal = false"
                class="px-4 py-2 bg-gray-400 rounded-md text-white hover:bg-gray-500 transition-colors"
              >
                Cancel
              </button>
              <button
                @click="saveMajor"
                class="px-4 py-2 bg-blue-600 rounded-md text-white hover:bg-blue-700 transition-colors"
              >
                Save Major
              </button>
            </div>
          </div>
        </div>

        <!-- Edit Major Modal -->
        <div
          v-if="showModalEdit"
          class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 z-50"
        >
          <div class="bg-white p-6 rounded-lg shadow-lg w-96">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-semibold">Edit Major</h2>
              <button
                @click="showModalEdit = false"
                class="text-gray-500 hover:text-gray-700"
              >
                <font-awesome-icon :icon="['fas', 'times']" />
              </button>
            </div>
            <input
              v-model="editedMajor"
              type="text"
              placeholder="Enter Major Name"
              class="w-full p-2 border rounded-md mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <div class="flex justify-end space-x-2">
              <button
                @click="showModalEdit = false"
                class="px-4 py-2 bg-gray-400 rounded-md text-white hover:bg-gray-500 transition-colors"
              >
                Cancel
              </button>
              <button
                @click="updateMajor"
                class="px-4 py-2 bg-blue-600 rounded-md text-white hover:bg-blue-700 transition-colors"
              >
                Update Major
              </button>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>