<script setup>
import axios from "axios";
import SidebarMahasiswa from "@/Components/SidebarMahasiswa.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import { ref, onMounted } from 'vue';

const props = defineProps({
  batchYear: String,
  projectId: String,
  projectName: String,
  kelompok: String,
  userName: String,
});

const breadcrumbs = ref([
  { text: "Dashboard", href: "/mahasiswa/dashboard" },
  { text: "Feedback", href: null },
]);

const loading = ref(false);
const error = ref(null);
const groupMembers = ref([]);
const selectedMember = ref(null);
const feedbackMessage = ref('');
const submitting = ref(false);
const submitSuccess = ref(false);

const fetchGroupMembers = async () => {
  try {
    loading.value = true;
    error.value = null;
    const response = await axios.get(`/api/project/${props.projectId}/group-members`, {
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    });
    if (response.data && Array.isArray(response.data)) {
      groupMembers.value = response.data;
      const currentUser = groupMembers.value.find(member => member.name === props.userName);
      if (currentUser) selectedMember.value = currentUser;
    } else {
      throw new Error('Invalid response format');
    }
  } catch (err) {
    console.error("Error fetching group members:", err);
    error.value = err.response?.data?.message || 'Failed to fetch group members';
    groupMembers.value = [];
  } finally {
    loading.value = false;
  }
};

const submitFeedback = async () => {
  if (!selectedMember.value || !feedbackMessage.value.trim()) {
    error.value = 'Please select a member and enter feedback message';
    return;
  }
  try {
    submitting.value = true;
    error.value = null;
    await axios.post(`/api/project/${props.projectId}/feedback`, {
      recipientId: selectedMember.value.id,
      message: feedbackMessage.value
    });
    submitSuccess.value = true;
    feedbackMessage.value = '';
  } catch (err) {
    console.error("Error submitting feedback:", err);
    error.value = err.response?.data?.message || 'Failed to submit feedback';
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  if (props.projectId) fetchGroupMembers();
});
</script>

<template>
  <div class="flex min-h-screen bg-gray-50">
    <SidebarMahasiswa role="mahasiswa" />
    <div class="flex-1">
      <Navbar :userName="userName" />
      <main class="p-6">
        <div class="mb-6">
          <Breadcrumb :items="breadcrumbs" />
          <h1 class="text-2xl font-bold text-gray-800 mt-4">Give Feedback</h1>
        </div>

        <div class="grid gap-6">
          <!-- Project Info Card -->
          <Card>
            <div class="p-6">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Project Details</h2>
                <span class="px-3 py-1 text-sm bg-indigo-100 text-indigo-800 rounded-full">
                  {{ batchYear }}
                </span>
              </div>
              <div class="grid md:grid-cols-2 gap-6">
                <div>
                  <h3 class="text-sm font-medium text-gray-500">Name</h3>
                  <p class="mt-1 text-gray-900">{{ userName }}</p>
                </div>
                <div>
                  <h3 class="text-sm font-medium text-gray-500">Project</h3>
                  <p class="mt-1 text-gray-900">{{ projectName }}</p>
                </div>
                <div>
                  <h3 class="text-sm font-medium text-gray-500">Group</h3>
                  <p class="mt-1 text-gray-900">{{ kelompok }}</p>
                </div>
              </div>
            </div>
          </Card>

          <!-- Feedback Form Card -->
          <Card>
            <div class="p-6">
              <h2 class="text-lg font-semibold text-gray-900 mb-6">Feedback Form</h2>
              
              <div v-if="loading" class="text-center py-4">
                <div class="animate-pulse text-gray-600">Loading members...</div>
              </div>
              
              <div v-else-if="error" class="rounded-md bg-red-50 p-4 mb-4">
                <div class="flex">
                  <div class="text-sm text-red-700">{{ error }}</div>
                </div>
              </div>
              
              <div v-else class="space-y-6">
                <!-- Member Selection -->
                <div>
                  <label class="block text-sm font-medium text-gray-700">Select Team Member</label>
                  <select 
                    v-model="selectedMember"
                    class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  >
                    <option value="">Choose a member</option>
                    <option 
                      v-for="member in groupMembers" 
                      :key="member.id" 
                      :value="member"
                    >
                      {{ member.name }} ({{ member.nim }})
                    </option>
                  </select>
                </div>

                <!-- Selected Member Info -->
                <div v-if="selectedMember" class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                  <div class="flex items-center space-x-4">
                    <div class="h-12 w-12 rounded-full bg-indigo-100 flex items-center justify-center">
                      <span class="text-indigo-600 font-medium">
                        {{ selectedMember.name.charAt(0) }}
                      </span>
                    </div>
                    <div>
                      <h3 class="text-sm font-medium text-gray-900">{{ selectedMember.name }}</h3>
                      <p class="text-sm text-gray-500">{{ selectedMember.nim }}</p>
                    </div>
                  </div>
                </div>

                <!-- Feedback Message -->
                <div v-if="selectedMember">
                  <label class="block text-sm font-medium text-gray-700">Feedback Message</label>
                  <textarea
                    v-model="feedbackMessage"
                    rows="4"
                    class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    placeholder="Write your feedback here..."
                  ></textarea>
                </div>

                <!-- Submit Button -->
                <div v-if="selectedMember" class="flex justify-end">
                  <button
                    @click="submitFeedback"
                    :disabled="submitting || !feedbackMessage.trim()"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <span v-if="submitting" class="mr-2">
                      <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                    </span>
                    {{ submitting ? 'Submitting...' : 'Submit Feedback' }}
                  </button>
                </div>

                <!-- Success Message -->
                <div v-if="submitSuccess" class="rounded-md bg-green-50 p-4">
                  <div class="flex">
                    <div class="text-sm text-green-700">
                      Feedback submitted successfully!
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </Card>
        </div>
      </main>
    </div>
  </div>
</template>