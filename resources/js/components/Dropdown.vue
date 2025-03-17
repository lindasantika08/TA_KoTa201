<template>
    <div class="flex items-center">
        <h2 class="text-lg font-semibold mr-4">{{ title }}</h2>
        <select
            v-model="localValue"
            @change="updateValue"
            class="border border-gray-300 rounded px-4 py-2"
        >
            <option :value="defaultOption.value">{{ defaultOption.label }}</option>
            <option
                v-for="(option, index) in options"
                :key="index"
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>
    </div>
</template>

<script>
export default {
    name: "DropdownFilter",
    props: {
        title: {
            type: String,
            default: "Filter",
        },
        options: {
            type: Array,
            required: true,
        },
        modelValue: {
            type: [String, Number],
            default: "",
        },
        defaultOption: {
            type: Object,
            default: () => ({ label: "Semua", value: "" }),
        },
    },
    data() {
        return {
            localValue: this.modelValue,
        };
    },
    watch: {
        modelValue(newValue) {
            this.localValue = newValue;
        },
    },
    methods: {
        updateValue() {
            this.$emit("update:modelValue", this.localValue);
        },
    },
};
</script>

<style scoped>
</style>
