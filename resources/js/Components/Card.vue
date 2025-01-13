<template>
    
      <div class="w-full max-w-6xl bg-white shadow-2xl rounded-lg overflow-hidden">
        <img 
          :src="image" 
          alt="Card Image" 
          v-if="image" 
          class="w-full h-full object-cover"
        />
        <div class="p-6">
          <h2 class="text-2xl font-semibold text-gray-800">{{ title }}</h2>
          <p class="text-gray-600 mt-4">{{ description }}</p>
          <slot name="actions" />
        </div>
      </div>

  </template>
  
  <script>
  export default {
    name: "Card",
    props: {
      title: {
        type: String,
        required: true,
      },
      description: {
        type: String,
        required: true,
      },
      image: {
        type: String,
        default: null,
      },
    },
  };
  </script>
  
  <style scoped>
  /* Tambahan gaya untuk mempertegas shadow jika diperlukan */
  .shadow-2xl {
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.25), 
                0 6px 6px rgba(0, 0, 0, 0.22);
  }
  </style>
  