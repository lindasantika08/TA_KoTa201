<script>
import axios from 'axios';
import DataTable from "@/Components/DataTable.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import SidebarMahasiswa from '../../Components/SidebarMahasiswa.vue';
import Breadcrumb from "@/Components/Breadcrumb.vue";
import { router } from '@inertiajs/vue3';

export default {
    components: {
        DataTable,
        Navbar,
        Card,
        SidebarMahasiswa,
        Breadcrumb
    },
    data() {
        return {
            breadcrumbs: [
                { text: "Assessment", href: "/self" },
                { text: "Self Assessment", href: null }
            ],
            headers: [
                { key: 'bobot_1', label: 'Bobot 1' },
                { key: 'bobot_2', label: 'Bobot 2' },
                { key: 'bobot_3', label: 'Bobot 3' },
                { key: 'bobot_4', label: 'Bobot 4' },
                { key: 'bobot_5', label: 'Bobot 5' },
            ],
            items: []
        }
    },
    mounted() {
    axios.get('/api/bobot')
    .then(response => {
        this.items = response.data.map((item, index) => ({
            id: item.id,
            bobot_1: item.bobot_1,
            bobot_2: item.bobot_2,
            bobot_3: item.bobot_3,
            bobot_4: item.bobot_4,
            bobot_5: item.bobot_5,
        }));
    })
    .catch(error => {
        console.error('There something when reach data:', error);
    });
  }

}
</script>

<template>
    <div class="flex min-h-screen">
        <SidebarMahasiswa role="mahasiswa" />
        <div class="flex-1">
            <Navbar userName="mahasiswa" />
            <main class="p-6">
                <div class="mb-4">
                    <Breadcrumb :items="breadcrumbs" />
                </div>
                <Card 
                    title="FORMULIR PENGISIAN SELF ASSESSMENT"
                    description=""
                    class="w-full"
                >
                <div class="grid grid-cols-2 gap-6 text-sm leading-6 mb-6">
                    <div>
                    <p><strong>NIM:</strong> 221511034</p>
                    <p><strong>Nama Lengkap:</strong> Adhiya Rahma Anzani</p>
                    <p><strong>Kelas:</strong> 1B</p>
                    </div>
                    <div>
                    <p><strong>Kelompok:</strong> 1 (Satu)</p>
                    <p><strong>Proyek:</strong> Aplikasi Perkantoran</p>
                    <p><strong>Tanggal Pengisian:</strong> 25 Juli 2024</p>
                    </div>
                </div>
                    <DataTable 
                        :headers="headers"
                        :items="items"
                        @view="handleView"
                    />
                </Card>
            </main>
        </div>
    </div>
</template>

<style scoped>
/* Add any custom styles here if needed */
</style>