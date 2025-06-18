<script>
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import Card from "@/Components/Card.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import axios from "axios";

export default {
    components: {
        Sidebar,
        Navbar,
        Card,
        Breadcrumb,
    },
    props: {
        batchYear: String,
        projectName: String,
        currentOrder: {
            type: Number,
            required: true,
        },
        totalOrders: {
            type: Number,
            required: true,
        },
        assessments: {
            type: Array,
            required: true,
        },
    },
    setup(props) {
        const showDropdown = ref(false);
        const page = usePage();
        const loading = ref(false);
        const infoModalOpen = ref(false);
        // Add a new ref to track expanded cards
        const expandedCards = ref({});

        const breadcrumbs = [
            {
                text: "Reflective Writing",
                href: "/dosen/reflectiveWriting",
            },
            { text: "Detail", href: null },
        ];

        const toggleDropdown = () => {
            showDropdown.value = !showDropdown.value;
        };

        const toggleInfoModal = () => {
            infoModalOpen.value = !infoModalOpen.value;
        };

        // Add a function to toggle card expansion
        const toggleCard = (cardId) => {
            expandedCards.value[cardId] = !expandedCards.value[cardId];
        };

        // Function to check if a card is expanded
        const isCardExpanded = (cardId) => {
            return !!expandedCards.value[cardId];
        };

        const groupedByType = computed(() => {
            const groups = {};
            props.assessments.forEach((assessment) => {
                const type = assessment.type || "Uncategorized";
                if (!groups[type]) {
                    groups[type] = [];
                }
                groups[type].push(assessment);
            });
            return groups;
        });

        const totalAssignments = computed(() => {
            return props.assessments.length;
        });

        const typeCount = computed(() => {
            return Object.keys(groupedByType.value).length;
        });

        const formatDate = (dateString) => {
            if (!dateString) return "No deadline set";
            const date = new Date(dateString);
            return date.toLocaleDateString("en-US", {
                year: "numeric",
                month: "long",
                day: "numeric",
            });
        };

        const daysUntilDeadline = (dateString) => {
            if (!dateString) return null;

            const deadline = new Date(dateString);
            const today = new Date();

            const diffTime = deadline - today;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            return diffDays;
        };

        const changeOrder = (newOrder) => {
            loading.value = true;
            router.get(
                "/dosen/reflectiveWriting/detail",
                {
                    batch_year: props.batchYear,
                    project_name: props.projectName,
                    reflective_writing_order: newOrder,
                },
                {
                    preserveState: true,
                    onSuccess: () => {
                        loading.value = false;
                    },
                }
            );
        };

        const handleSubmissions = async () => {
            loading.value = true;

            router.visit("/dosen/ReflectiveWritingSubmissions", {
                method: "get",
                data: {
                    batch_year: props.batchYear,
                    project_name: props.projectName,
                    reflective_writing_order: props.currentOrder,
                },
                onSuccess: () => {
                    loading.value = false;
                },
                onError: () => {
                    loading.value = false;
                    alert(
                        "Failed to navigate to submissions. Please try again."
                    );
                },
            });
        };

        return {
            breadcrumbs,
            toggleDropdown,
            showDropdown,
            groupedByType,
            handleSubmissions,
            changeOrder,
            loading,
            totalAssignments,
            typeCount,
            toggleInfoModal,
            infoModalOpen,
            formatDate,
            daysUntilDeadline,
            // Export the new functions
            toggleCard,
            isCardExpanded,
        };
    },
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <Sidebar role="dosen" />
        <div class="flex-1 flex flex-col">
            <Navbar userName="Dosen" />
            <main class="p-6 flex-1">
                <!-- Header Section -->
                <div class="mb-6">
                    <Breadcrumb :items="breadcrumbs" />
                    <div
                        class="mt-4 flex flex-col md:flex-row md:justify-between md:items-center gap-4"
                    >
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                Reflective Writing Assignments
                            </h1>
                            <div class="mt-2 text-sm text-gray-600">
                                <div class="flex flex-wrap gap-2 items-center">
                                    <div
                                        class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full flex items-center gap-2"
                                    >
                                        <font-awesome-icon
                                            icon="fa-solid fa-folder-open"
                                        />
                                        <span class="font-medium">{{
                                            projectName
                                        }}</span>
                                    </div>
                                    <div
                                        class="px-3 py-1 bg-green-50 text-green-700 rounded-full flex items-center gap-2"
                                    >
                                        <font-awesome-icon
                                            icon="fa-solid fa-calendar"
                                        />
                                        <span class="font-medium">{{
                                            batchYear
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assignment Overview Card -->
                <div
                    class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden"
                >
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2
                                class="text-lg font-semibold text-gray-900 flex items-center"
                            >
                                <font-awesome-icon
                                    icon="fa-solid fa-edit"
                                    class="mr-2 text-indigo-600"
                                />
                                Reflective Writing Overview
                            </h2>
                            <button
                                @click="toggleInfoModal"
                                class="text-indigo-600 hover:text-indigo-800 focus:outline-none"
                                title="Assignment Information"
                            >
                                <font-awesome-icon
                                    icon="fa-solid fa-circle-info"
                                    class="text-lg"
                                />
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div
                                class="bg-green-50 p-4 rounded-lg flex items-center"
                            >
                                <div class="rounded-full bg-green-100 p-3 mr-3">
                                    <font-awesome-icon
                                        icon="fa-solid fa-pen-fancy"
                                        class="text-green-600"
                                    />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Assignment Types
                                    </p>
                                    <p class="text-lg font-semibold">
                                        {{ typeCount }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="bg-purple-50 p-4 rounded-lg flex items-center"
                            >
                                <div
                                    class="rounded-full bg-purple-100 p-3 mr-3"
                                >
                                    <font-awesome-icon
                                        icon="fa-solid fa-tasks"
                                        class="text-purple-600"
                                    />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Total Assignments
                                    </p>
                                    <p class="text-lg font-semibold">
                                        {{ totalAssignments }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="assessments.length > 0"
                                class="bg-amber-50 p-4 rounded-lg flex items-center"
                            >
                                <div class="rounded-full bg-amber-100 p-3 mr-3">
                                    <font-awesome-icon
                                        icon="fa-solid fa-clock"
                                        class="text-amber-600"
                                    />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Status</p>
                                    <p
                                        v-if="
                                            assessments.length > 0 &&
                                            assessments[0].is_published
                                        "
                                        class="text-sm font-medium text-green-600 flex items-center gap-1"
                                    >
                                        <font-awesome-icon
                                            icon="fa-solid fa-check-circle"
                                        />
                                        Published
                                    </p>
                                    <p
                                        v-else
                                        class="text-sm font-medium text-amber-600 flex items-center gap-1"
                                    >
                                        <font-awesome-icon
                                            icon="fa-solid fa-clock"
                                        />
                                        Draft
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assignment Groups by Type -->
                <div class="space-y-6">
                    <div
                        v-for="(group, type) in groupedByType"
                        :key="type"
                        class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden transition-all duration-300 hover:shadow-md"
                    >
                        <div
                            class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-white border-b border-gray-200"
                        >
                            <div class="flex justify-between items-center">
                                <h2
                                    class="text-lg font-semibold text-gray-900 flex items-center"
                                >
                                    <font-awesome-icon
                                        icon="fa-solid fa-bookmark"
                                        class="mr-2 text-indigo-600"
                                    />
                                    {{ type || "Uncategorized" }}
                                </h2>
                                <span
                                    class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm flex items-center"
                                >
                                    <font-awesome-icon
                                        icon="fa-solid fa-list"
                                        class="mr-1"
                                    />
                                    {{ group.length }} Assignments
                                </span>
                            </div>
                        </div>

                        <div class="divide-y divide-gray-200">
                            <div
                                v-for="(assignment, index) in group"
                                :key="assignment.id"
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <!-- Card Header (Always visible) -->
                                <div
                                    class="p-6 cursor-pointer"
                                    @click="toggleCard(assignment.id)"
                                >
                                    <div class="flex items-start space-x-4">
                                        <div
                                            class="flex-shrink-0 w-12 h-12 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-semibold shadow-sm"
                                        >
                                            {{ index + 1 }}
                                        </div>
                                        <div class="flex-1">
                                            <div
                                                class="flex justify-between items-start mb-2"
                                            >
                                                <h3
                                                    class="text-lg font-medium text-gray-900 flex items-center"
                                                >
                                                    {{ assignment.type }} -
                                                    Reflection #{{
                                                        assignment.reflective_writing_order
                                                    }}
                                                    <font-awesome-icon
                                                        :icon="
                                                            isCardExpanded(
                                                                assignment.id
                                                            )
                                                                ? 'fa-solid fa-chevron-down'
                                                                : 'fa-solid fa-chevron-right'
                                                        "
                                                        class="ml-2 text-gray-500 transition-transform"
                                                        :class="{
                                                            'transform rotate-0':
                                                                !isCardExpanded(
                                                                    assignment.id
                                                                ),
                                                            'transform rotate-90':
                                                                isCardExpanded(
                                                                    assignment.id
                                                                ),
                                                        }"
                                                    />
                                                </h3>
                                                <div
                                                    class="flex items-center space-x-2"
                                                >
                                                    <span
                                                        v-if="
                                                            assignment.is_published
                                                        "
                                                        class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full flex items-center gap-1"
                                                    >
                                                        <font-awesome-icon
                                                            icon="fa-solid fa-check-circle"
                                                        />
                                                        Published
                                                    </span>
                                                    <span
                                                        v-else
                                                        class="px-2 py-1 bg-amber-100 text-amber-700 text-xs rounded-full flex items-center gap-1"
                                                    >
                                                        <font-awesome-icon
                                                            icon="fa-solid fa-clock"
                                                        />
                                                        Draft
                                                    </span>
                                                    <span
                                                        v-if="
                                                            assignment.end_date
                                                        "
                                                        :class="{
                                                            'bg-red-100 text-red-700':
                                                                daysUntilDeadline(
                                                                    assignment.end_date
                                                                ) < 3,
                                                            'bg-amber-100 text-amber-700':
                                                                daysUntilDeadline(
                                                                    assignment.end_date
                                                                ) >= 3 &&
                                                                daysUntilDeadline(
                                                                    assignment.end_date
                                                                ) <= 7,
                                                            'bg-green-100 text-green-700':
                                                                daysUntilDeadline(
                                                                    assignment.end_date
                                                                ) > 7,
                                                        }"
                                                        class="px-2 py-1 text-xs rounded-full flex items-center gap-1"
                                                    >
                                                        <font-awesome-icon
                                                            icon="fa-solid fa-calendar-day"
                                                        />
                                                        Due:
                                                        {{
                                                            formatDate(
                                                                assignment.end_date
                                                            )
                                                        }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Collapsible Content (Reflection Points) -->
                                <div
                                    v-if="isCardExpanded(assignment.id)"
                                    class="px-6 pb-6 pt-0"
                                >
                                    <div class="grid grid-cols-1 gap-4">
                                        <!-- Point 1 -->
                                        <div
                                            v-if="assignment.point_1"
                                            class="bg-white rounded-lg p-4 border border-indigo-100 hover:border-indigo-300 transition-colors"
                                        >
                                            <h4
                                                class="text-md font-medium text-indigo-700 mb-2 flex items-center"
                                            >
                                                <div
                                                    class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center mr-2 text-xs"
                                                >
                                                    1
                                                </div>
                                                Reflection Point 1
                                            </h4>
                                            <p class="text-gray-700">
                                                {{ assignment.point_1 }}
                                            </p>
                                        </div>

                                        <!-- Point 2 -->
                                        <div
                                            v-if="assignment.point_2"
                                            class="bg-white rounded-lg p-4 border border-blue-100 hover:border-blue-300 transition-colors"
                                        >
                                            <h4
                                                class="text-md font-medium text-blue-700 mb-2 flex items-center"
                                            >
                                                <div
                                                    class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center mr-2 text-xs"
                                                >
                                                    2
                                                </div>
                                                Reflection Point 2
                                            </h4>
                                            <p class="text-gray-700">
                                                {{ assignment.point_2 }}
                                            </p>
                                        </div>

                                        <!-- Point 3 -->
                                        <div
                                            v-if="assignment.point_3"
                                            class="bg-white rounded-lg p-4 border border-purple-100 hover:border-purple-300 transition-colors"
                                        >
                                            <h4
                                                class="text-md font-medium text-purple-700 mb-2 flex items-center"
                                            >
                                                <div
                                                    class="w-6 h-6 rounded-full bg-purple-600 text-white flex items-center justify-center mr-2 text-xs"
                                                >
                                                    3
                                                </div>
                                                Reflection Point 3
                                            </h4>
                                            <p class="text-gray-700">
                                                {{ assignment.point_3 }}
                                            </p>
                                        </div>

                                        <!-- Point 4 -->
                                        <div
                                            v-if="assignment.point_4"
                                            class="bg-white rounded-lg p-4 border border-green-100 hover:border-green-300 transition-colors"
                                        >
                                            <h4
                                                class="text-md font-medium text-green-700 mb-2 flex items-center"
                                            >
                                                <div
                                                    class="w-6 h-6 rounded-full bg-green-600 text-white flex items-center justify-center mr-2 text-xs"
                                                >
                                                    4
                                                </div>
                                                Reflection Point 4
                                            </h4>
                                            <p class="text-gray-700">
                                                {{ assignment.point_4 }}
                                            </p>
                                        </div>

                                        <!-- Point 5 -->
                                        <div
                                            v-if="assignment.point_5"
                                            class="bg-white rounded-lg p-4 border border-amber-100 hover:border-amber-300 transition-colors"
                                        >
                                            <h4
                                                class="text-md font-medium text-amber-700 mb-2 flex items-center"
                                            >
                                                <div
                                                    class="w-6 h-6 rounded-full bg-amber-600 text-white flex items-center justify-center mr-2 text-xs"
                                                >
                                                    5
                                                </div>
                                                Reflection Point 5
                                            </h4>
                                            <p class="text-gray-700">
                                                {{ assignment.point_5 }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No Data State -->
                <div
                    v-if="Object.keys(groupedByType).length === 0"
                    class="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200"
                >
                    <div class="text-gray-500">
                        <font-awesome-icon
                            icon="fa-solid fa-edit"
                            class="text-5xl mb-4 text-gray-400"
                        />
                        <p class="text-lg mb-2">
                            No reflective writing assignments found
                        </p>
                        <p class="text-sm text-gray-400">
                            There are no writing assignments available for this
                            order.
                        </p>
                    </div>
                </div>

                <!-- Information Modal -->
                <div
                    v-if="infoModalOpen"
                    class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
                >
                    <div
                        class="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto"
                    >
                        <div
                            class="flex justify-between items-center p-6 border-b border-gray-200"
                        >
                            <h2 class="text-xl font-semibold text-gray-900">
                                About Reflective Writing
                            </h2>
                            <button
                                @click="toggleInfoModal"
                                class="text-gray-400 hover:text-gray-600"
                            >
                                <font-awesome-icon
                                    icon="fa-solid fa-times"
                                    class="text-xl"
                                />
                            </button>
                        </div>
                        <div class="p-6">
                            <div class="mb-4">
                                <h3
                                    class="text-md font-medium text-gray-800 mb-2"
                                >
                                    What is Reflective Writing?
                                </h3>
                                <p class="text-gray-600 mb-3">
                                    Reflective writing encourages students to
                                    deeply analyze their learning experiences,
                                    connecting theory with practice and
                                    examining their personal growth throughout
                                    the project.
                                </p>
                            </div>
                            <div class="mb-4">
                                <h3
                                    class="text-md font-medium text-gray-800 mb-2"
                                >
                                    Structure
                                </h3>
                                <p class="text-gray-600 mb-3">
                                    Each reflective writing assignment includes
                                    up to 5 key points that guide students in
                                    their reflection process. Students are
                                    expected to address each point thoroughly in
                                    their submissions.
                                </p>
                            </div>
                            <div class="mb-4">
                                <h3
                                    class="text-md font-medium text-gray-800 mb-2"
                                >
                                    Assessment Process
                                </h3>
                                <ul
                                    class="list-disc list-inside text-gray-600 space-y-1"
                                >
                                    <li>
                                        Review each student's submission for
                                        depth of reflection
                                    </li>
                                    <li>
                                        Evaluate how well they addressed each
                                        point
                                    </li>
                                    <li>
                                        Provide constructive feedback on their
                                        insights
                                    </li>
                                    <li>
                                        Consider growth from previous
                                        reflections
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div
                            class="p-4 bg-gray-50 border-t border-gray-200 flex justify-end"
                        >
                            <button
                                @click="toggleInfoModal"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <footer
                class="bg-white border-t border-gray-200 p-4 text-center text-gray-500 text-sm"
            >
                <p>
                    © {{ new Date().getFullYear() }} SAPA - Reflective Writing
                    System
                </p>
            </footer>
        </div>
    </div>
</template>
