<template>
  <div class="w-full bg-white shadow-2xl rounded-lg overflow-hidden">
    <img :src="image" alt="Card Image" v-if="image" class="w-full h-full object-cover" />
    <div class="p-6">
      <h2 class="text-2xl font-semibold text-gray-800">{{ title }}</h2>
      <p v-if="description" class="text-gray-600 mt-4">{{ description }}</p>
      <div class="mt-4">
        <slot></slot>
      </div>
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
      default: '',
    },
    image: {
      type: String,
      default: null,
    },
  },
};
</script>