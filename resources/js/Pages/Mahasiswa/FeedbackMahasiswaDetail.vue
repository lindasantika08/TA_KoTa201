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
const validationErrors = ref(null);
const submittedFeedbacks = ref([]); // Menyimpan daftar feedback yang telah diberikan

// Fungsi untuk mengambil daftar feedback yang telah diberikan
const fetchSubmittedFeedbacks = async () => {
  try {
    const response = await axios.get('/api/feedback/given');
    submittedFeedbacks.value = response.data;
  } catch (err) {
    console.error('Failed to fetch submitted feedbacks:', err);
  }
};

// Fungsi untuk mendapatkan anggota kelompok yang belum mendapat feedback
const fetchGroupMembers = async () => {
  try {
    loading.value = true;
    error.value = null;
    
    // Ambil daftar anggota kelompok dan feedback yang sudah diberikan
    const [membersResponse, feedbacksResponse] = await Promise.all([
      axios.get(`/api/project/${props.projectId}/group-members`),
      axios.get(`/api/feedback/given`)
    ]);

    if (membersResponse.data && Array.isArray(membersResponse.data)) {
      const submittedFeedbackRecipients = feedbacksResponse.data.map(f => f.peer_id);
      
      // Filter anggota yang belum mendapat feedback
      groupMembers.value = membersResponse.data.filter(member => 
        member.name !== props.userName && 
        !submittedFeedbackRecipients.includes(member.id)
      );
    } else {
      throw new Error('Invalid response format');
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to fetch group members';
    groupMembers.value = [];
  } finally {
    loading.value = false;
  }
};

// Fungsi untuk mengirim feedback
const submitFeedback = async () => {
  if (!selectedMember.value || !feedbackMessage.value.trim()) {
    error.value = 'Please select a member and enter feedback message';
    return;
  }

  try {
    submitting.value = true;
    error.value = null;
    validationErrors.value = null;

    const response = await axios.post('/api/feedback/store', {
      recipientId: selectedMember.value.id,
      message: feedbackMessage.value,
      projectId: props.projectId,
      batchYear: props.batchYear,
      projectName: props.projectName
    });

    if (response.data.status === 'success') {
      submitSuccess.value = true;
      feedbackMessage.value = '';
      
      // Hapus anggota yang baru saja mendapat feedback dari daftar
      groupMembers.value = groupMembers.value.filter(
        member => member.id !== selectedMember.value.id
      );
      
      selectedMember.value = null;
      setTimeout(() => {
        submitSuccess.value = false;
      }, 3000);

      // Refresh daftar feedback yang telah diberikan
      fetchSubmittedFeedbacks();
    }

  } catch (err) {
    if (err.response?.status === 422) {
      validationErrors.value = err.response.data.errors;
      error.value = 'Please check the form for errors';
    } else {
      error.value = err.response?.data?.message || 'Failed to submit feedback';
    }
  } finally {
    submitting.value = false;
  }
};

// Ambil data saat komponen dimuat
onMounted(() => {
  fetchGroupMembers();
  fetchSubmittedFeedbacks();
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
        <Card>
            <div class="p-6">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Project Details</h2>
                <span class="px-3 py-1 text-sm bg-indigo-100 text-indigo-800 rounded-full">
                  {{ batchYear }}
                </span>
              </div>
              <div class="grid md:grid-cols-3 gap-6">
                <div>
                  <h3 class="text-sm font-medium text-gray-500">Your Name</h3>
                  <p class="mt-1 font-semibold text-gray-900">{{ userName }}</p>
                </div>
                <div>
                  <h3 class="text-sm font-medium text-gray-500">Project</h3>
                  <p class="mt-1 font-semibold text-gray-900">
                    {{ projectName }}
                  </p>
                </div>
                <div>
                  <h3 class="text-sm font-medium text-gray-500">Group</h3>
                  <span class="mt-1 inline-block px-3 py-1 text-sm font-medium bg-green-100 text-green-700 rounded-full">
                    {{ kelompok }}
                  </span>
                </div>
              </div>
            </div>
          </Card>

        
          <!-- Feedback Form Card -->
          <Card>
            <div class="p-6">
              <h2 class="text-lg font-semibold text-gray-900 mb-4">Feedback Form</h2>

              <div v-if="loading" class="text-center py-4">
                <span class="animate-pulse text-gray-600">Loading members...</span>
              </div>

              <div v-if="error" class="rounded-md bg-red-50 p-4 mb-4">
                <p class="text-sm text-red-700 font-semibold">{{ error }}</p>
              </div>

              <div v-if="!loading && groupMembers.length === 0" class="text-center py-4 text-gray-600">
                <p class="text-lg font-semibold text-gray-700">You have provided feedback to all team members!</p>
              </div>

              <div v-if="!loading && groupMembers.length > 0" class="space-y-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Select Team Member</label>
                  <select 
                    v-model="selectedMember"
                    class="mt-2 block w-full rounded-md border-gray-300 shadow-sm"
                  >
                    <option value="">Choose a member</option>
                    <option v-for="member in groupMembers" :key="member.id" :value="member">
                      {{ member.name }} ({{ member.nim }})
                    </option>
                  </select>
                </div>

                <div v-if="selectedMember">
                  <label class="block text-sm font-medium text-gray-700">Feedback Message</label>
                  <textarea v-model="feedbackMessage" rows="4" 
                    class="mt-2 block w-full rounded-md border-gray-300 shadow-sm"
                    placeholder="Write your feedback here..."></textarea>
                </div>

                <div v-if="selectedMember" class="flex justify-end">
                  <button @click="submitFeedback" :disabled="submitting || !feedbackMessage.trim()"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50">
                    {{ submitting ? 'Submitting...' : 'Submit Feedback' }}
                  </button>
                </div>

                <div v-if="submitSuccess" class="bg-green-50 p-4 rounded-md text-green-700">
                  ✅ Feedback submitted successfully!
                </div>
              </div>
            </div>
          </Card>

          <!-- List of Given Feedbacks -->
          <Card>
            <div class="p-6">
              <h2 class="text-lg font-semibold text-gray-900 mb-6">Feedback Sent</h2>

              <div v-if="submittedFeedbacks.length === 0" class="text-gray-600 text-center">
                <p>You have not given any feedback yet.</p>
              </div>

              <ul v-else class="space-y-4">
                <li v-for="feedback in submittedFeedbacks" :key="feedback.peer_id" 
                    class="p-4 bg-white rounded-lg shadow border flex justify-between items-center">
                  <div>
                    <h3 class="text-sm font-medium text-gray-900">{{ feedback.peer_name }}</h3>
                    <p class="text-gray-700">{{ feedback.feedback }}</p>
                  </div>
                  <span class="text-sm text-gray-500">
                    {{ new Date(feedback.created_at).toLocaleDateString() }}
                  </span>
                </li>
              </ul>
            </div>
          </Card>

        </div>
      </main>
    </div>
  </div>
</template>
