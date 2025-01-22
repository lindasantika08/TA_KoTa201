<script>
export default {
  name: 'DataTable',
  props: {
    headers: {
      type: Array,
      required: true
    },
    items: {
      type: Array,
      required: true
    }
  },
  mounted() {
    console.log('DataTable mounted')
    console.log('Headers:', this.headers)
    console.log('Items:', this.items)
  },
  methods: {
    // Mengambil nilai dari kolom yang memiliki struktur lebih dalam (misal: dosen.nama)
    getNestedValue(item, key) {
      return key.split('.').reduce((obj, k) => (obj ? obj[k] : ''), item);
    },
  },
}
</script>

<template>
  <div class="overflow-x-auto">
    <table class="min-w-full bg-white">
      <thead>
        <tr>
          <th v-for="header in headers" :key="header.key" class="px-4 py-2 border">
            {{ header.label }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in items" :key="index">
          <td v-for="header in headers" :key="header.key" class="px-4 py-2 border">
            <!-- Menggunakan slot untuk custom rendering -->
            <slot
              :name="'column-' + header.key"
              v-bind="{ item, index }"
              v-if="$slots['column-' + header.key]"
            />
            <!-- Default rendering jika tidak ada slot -->
            <template v-else>
              <!-- Cek apakah kolom memiliki key yang lebih dalam (misal: dosen.nama) -->
              <template v-if="header.key.includes('.')">
                {{ getNestedValue(item, header.key) }}
              </template>
              <template v-else>
                {{ item[header.key] }}
              </template>
            </template>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>