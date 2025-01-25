<script>
import axios from "axios";
import { router } from "@inertiajs/vue3";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import SidebarMahasiswa from "../../Components/SidebarMahasiswa.vue";

export default {
  name: "Dashboard",
  components: {
    SidebarMahasiswa,
    Navbar,
    Card,
  },
  data() {
    return {
      projects: [],
      selectedProject: null,
      selfAssessmentStatus: null,
      peerGroupSize: 0,
      peerCompletedCount: 0,
    };
  },
  mounted() {
    this.fetchProjectData();
    this.fetchSelfAssessmentStatus();
    this.fetchPeerAssessmentDetails();
  },
  methods: {
    fetchProjectData() {
      axios.get('/api/projects')
        .then(response => {
          this.projects = response.data.projects;
        })
        .catch(error => {
          console.error('Error fetching project data:', error);
        });
    },
    fetchSelfAssessmentStatus() {
      axios.get('/api/assessment-status')
        .then(response => {
          this.selfAssessmentStatus = response.data.selfAssessmentStatus;
        })
        .catch(error => {
          console.error('Error fetching self assessment status:', error);
        });
    },
    fetchPeerAssessmentDetails() {
      axios.get('/api/count-peer')
        .then(response => {
          this.peerGroupSize = response.data.group_size;
          this.peerCompletedCount = response.data.group_peers.filter(peer => response.data.completed_peer_assessments[peer.id].is_completed).length;
        })
        .catch(error => {
          console.error('Error fetching peer assessment details:', error);
        });
    },
    goToDashboardSelf(path) {
      router.visit(path);
    },
    goToDashboardPeer(path) {
      router.visit(path);
    },
    goToKelolaProyek(path) {
      router.visit(path);
    },
  },
};
</script>

<template>
  <div class="flex min-h-screen">
    <SidebarMahasiswa role="mahasiswa" />

    <div class="flex-1">
      <Navbar userName="Mahasiswa" />
      <main class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
          <Card title="Self Assessment" class="cursor-pointer hover:shadow-lg transition-shadow">
            <div><font-awesome-icon :icon="['fas', 'user']" /> {{ selfAssessmentStatus }}</div>
          </Card>
          <Card title="Peer Assessment" class="cursor-pointer hover:shadow-lg transition-shadow">
            <div><font-awesome-icon :icon="['fas', 'user-group']" /> {{ peerCompletedCount }} / {{ peerGroupSize }} Completed</div>
          </Card>
          <Card title="Project" class="cursor-pointer hover:shadow-lg transition-shadow">
            <div>
              <select class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" v-model="selectedProject">
                <option v-for="project in projects" :value="project.nama_proyek">{{ project.nama_proyek }}</option>
              </select>
            </div>
          </Card>
        </div>
        <Card title="Dashboard" class="w-full mt-8">
          <!-- Dashboard content -->
        </Card>
      </main>
    </div>
  </div>
</template>

