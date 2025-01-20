<script>
import axios from "axios";
import SidebarMahasiswa from "@/Components/SidebarMahasiswa.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import DataTable from "@/Components/DataTable.vue";



export default {
    name: "Profile",
    components: {
        SidebarMahasiswa,
        Navbar,
        Card,
        Breadcrumb,
        DataTable,
    },

    data() {
        return {
            breadcrumbs: [
                { text: "Self Assessment", href: '/mahasiswa/assessment/self' },
                { text: "Detail", href: null },
            ],
            headers: [
                { key: 'no', label: 'No' },
                { key: 'aspek', label: 'Aspek' },
                { key: 'pertanyaan', label: 'Pertanyaan' },
                { key: 'skala', label: 'Skala' },
                { key: 'alasan', label: 'Alasan' },
            ],
            item: [],
        }
    }
};
</script>

<template>
    <div class="flex min-h-screen">
        <SidebarMahasiswa role="mahasiswa" />

        <div class="flex-1 ">
            <Navbar userName="Mahasiswa" />
            <main class="p-6">
                <div class="mb-4">
                    <Breadcrumb :items="breadcrumbs" />
                </div>
                <Card title="HASIL PENGISIAN SELF ASSESSMENT">
                    <template #actions>
                        <DataTable :headers="headers" :items="items" class="mt-4" />
                    </template>
                </Card>
            </main>
        </div>
    </div>
</template>


<style scoped></style>