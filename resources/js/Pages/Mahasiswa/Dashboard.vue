<script>
import axios from "axios";
import { router } from "@inertiajs/vue3";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import SidebarMahasiswa from "../../Components/SidebarMahasiswa.vue";
import ApexChart from 'vue3-apexcharts';

export default {
  name: "Dashboard",
  components: {
    SidebarMahasiswa,
    Navbar,
    Card,
    ApexChart,
  },
  data() {
    return {
      // Existing bar and pie chart options
      barChartOptions: {
        chart: { type: 'bar', height: 350 },
        plotOptions: { bar: { horizontal: false, columnWidth: '55%' } },
        dataLabels: { enabled: false },
        stroke: { show: true, width: 2, colors: ['transparent'] },
        xaxis: { categories: ['Self Assessment', 'Peer Assessment', 'Proyek'] },
        yaxis: { title: { text: 'Progress (%)' } },
        fill: { opacity: 1 },
        tooltip: { y: { formatter: function (val) { return val + "%" } } },
        series: [{ name: 'Progress', data: [65, 59, 80] }]
      },
      pieChartOptions: {
        labels: ['Completed', 'In Progress', 'Not Started'],
        series: [44, 55, 13],
        chart: { type: 'pie', height: 350 },
        responsive: [{
          breakpoint: 480,
          options: {
            chart: { width: 200 },
            legend: { position: 'bottom' }
          }
        }]
      },
      // New spider chart options
      spiderChartOptions: {
        chart: {
          type: 'radar',
          height: 350
        },
        title: {
          text: 'Kompetensi Mahasiswa'
        },
        xaxis: {
          categories: ['Komunikasi', 'Kolaborasi', 'Kreativitas', 'Kepemimpinan', 'Analitis']
        },
        series: [
          {
            name: 'Skor',
            data: [80, 70, 65, 75, 85]
          }
        ],
        plotOptions: {
          radar: {
            size: 140,
            polygons: {
              strokeColors: '#e9e9e9',
              fill: {
                colors: ['#f8f8f8', '#fff']
              }
            }
          }
        },
        colors: ['#FF4560'],
        markers: {
          size: 4,
          colors: ['#FF4560'],
          strokeColor: '#FF4560',
          strokeWidth: 2
        }
      }
    }
  },
  methods: {
    goToDashboardSelf(route) {
      router.visit(route);
    },
    goToDashboardPeer(route) {
      router.visit(route);
    },
    goToKelolaProyek(route) {
      router.visit(route);
    }
  }
}
</script>

<template>
  <div class="flex min-h-screen">
    <SidebarMahasiswa role="mahasiswa" />

    <div class="flex-1 ">
      <Navbar userName="Mahasiswa" />
      <main class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
          <Card title="Status Self Assessment" class="cursor-pointer hover:shadow-lg transition-shadow"
            @click="goToDashboardSelf('/dosen/dashboard/self')">
            <template #title>
              <h3 class="text-sm font-bold">{{ title }}</h3>
            </template>
            <p>Content for Card 1</p>
          </Card>
          <Card title="Peer Assessment" class="cursor-pointer hover:shadow-lg transition-shadow"
            @click="goToDashboardPeer('/dosen/dashboard/peer')">
            <template #title>
              <h3 class="text-sm font-bold">{{ title }}</h3>
            </template>
            <p>Content for Card 2</p>
          </Card>
          <Card title="Proyek" class="cursor-pointer hover:shadow-lg transition-shadow"
            @click="goToKelolaProyek('/dosen/kelola-proyek')">
            <template #title>
              <h3 class="text-sm font-bold">{{ title }}</h3>
            </template>
            <p>Content for Card 3</p>
          </Card>
        </div>

        <Card title="Dashboard Analytics" class="w-full mt-8">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <h4 class="text-lg font-semibold mb-4">Progress Overview</h4>
              <ApexChart 
                type="bar" 
                :options="barChartOptions" 
                :series="barChartOptions.series"
                height="350"
              />
            </div>
            <div>
              <h4 class="text-lg font-semibold mb-4">Project Status</h4>
              <ApexChart 
                type="pie" 
                :options="pieChartOptions" 
                :series="pieChartOptions.series"
                height="350"
              />
            </div>
          </div>
        </Card>

        <Card title="Kompetensi Mahasiswa" class="w-full mt-8">
          <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
            <div>
              <ApexChart 
                type="radar" 
                :options="spiderChartOptions" 
                :series="spiderChartOptions.series"
                height="350"
              />
            </div>
          </div>
        </Card>
      </main>
    </div>
  </div>
</template>

<style scoped></style>