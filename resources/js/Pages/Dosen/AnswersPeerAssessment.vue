<template>
    <div class="flex min-h-screen">
        <Sidebar role="dosen" />

        <div class="flex-1">
            <Navbar userName="dosen" />

            <main class="p-6">
                <div class="mb-4">
                    <Breadcrumb :items="breadcrumbs" />
                </div>

                <div class="mb-6">
                    <h1 class="text-xl font-semibold">
                        Answers Peer Assessment
                    </h1>
                </div>

                <div class="mb-6 text-sm bg-white p-4 rounded-lg shadow">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <p><strong>Tahun Ajaran:</strong> {{ batch_year }}</p>
                        <p><strong>Nama Proyek:</strong> {{ project_name }}</p>
                        <p>
                            <strong>Total Kelompok:</strong> {{ totalGroups }}
                        </p>
                    </div>
                </div>

                <!-- Error Message -->
                <div
                    v-if="error"
                    class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg shadow"
                >
                    {{ error }}
                </div>

                <!-- Loading State -->
                <div
                    v-if="loading"
                    class="text-center py-6 bg-white rounded-lg shadow"
                >
                    <div
                        class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-t-blue-500 border-blue-200"
                    ></div>
                    <p class="mt-2 text-gray-600">Loading data...</p>
                </div>

                <div
                    v-if="!loading && !error"
                    class="bg-white rounded-lg shadow mb-6"
                >
                    <!-- Filter Section - Collapsible -->
                    <div class="border-b border-gray-200">
                        <button
                            @click="showFilters = !showFilters"
                            class="w-full p-4 text-left flex justify-between items-center hover:bg-gray-50 focus:outline-none"
                        >
                            <span class="font-medium">Filter Data</span>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5 transition-transform duration-200"
                                :class="
                                    showFilters ? 'transform rotate-180' : ''
                                "
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>

                        <div
                            v-if="showFilters"
                            class="p-4 border-t border-gray-200 space-y-4"
                        >
                            <!-- Filter content - unchanged -->
                            <!-- Group Filter -->
                            <div class="mb-4">
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Filter Kelompok
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        @click="selectedGroup = null"
                                        :class="[
                                            'px-3 py-1 rounded-full text-sm transition duration-150',
                                            !selectedGroup
                                                ? 'bg-blue-500 text-white shadow-md'
                                                : 'bg-gray-200 text-gray-800 hover:bg-gray-300',
                                        ]"
                                    >
                                        Semua Kelompok
                                    </button>
                                    <button
                                        v-for="group in groups"
                                        :key="group"
                                        @click="selectedGroup = group"
                                        :class="[
                                            'px-3 py-1 rounded-full text-sm transition duration-150',
                                            selectedGroup === group
                                                ? 'bg-blue-500 text-white shadow-md'
                                                : 'bg-gray-200 text-gray-800 hover:bg-gray-300',
                                        ]"
                                    >
                                        {{ group }}
                                    </button>
                                </div>
                            </div>

                            <!-- Additional Filters -->
                            <div
                                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"
                            >
                                <!-- Nama Pengguna Dropdown -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Nama Pengguna
                                    </label>
                                    <div class="relative">
                                        <div
                                            @click="
                                                toggleDropdown('nama_pengguna')
                                            "
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md cursor-pointer bg-white flex justify-between items-center"
                                        >
                                            <span class="truncate">
                                                {{
                                                    filters.nama_pengguna.length
                                                        ? `${filters.nama_pengguna.length} dipilih`
                                                        : "Pilih nama pengguna..."
                                                }}
                                            </span>
                                            <span class="ml-2">▼</span>
                                        </div>
                                        <div
                                            v-show="
                                                activeDropdown ===
                                                'nama_pengguna'
                                            "
                                            class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto"
                                        >
                                            <div
                                                class="p-2 border-b sticky top-0 bg-white"
                                            >
                                                <input
                                                    v-model="
                                                        searchTerms.nama_pengguna
                                                    "
                                                    type="text"
                                                    class="w-full px-2 py-1 border border-gray-300 rounded-md"
                                                    placeholder="Cari..."
                                                    @click.stop
                                                />
                                            </div>
                                            <div
                                                class="p-2 sticky top-10 bg-white"
                                            >
                                                <label
                                                    class="flex items-center px-2 py-1 hover:bg-gray-100 cursor-pointer rounded"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        :checked="
                                                            filters
                                                                .nama_pengguna
                                                                .length ===
                                                            uniqueNamaPengguna.length
                                                        "
                                                        @change="
                                                            toggleAll(
                                                                'nama_pengguna'
                                                            )
                                                        "
                                                        class="mr-2"
                                                    />
                                                    <span>Pilih Semua</span>
                                                </label>
                                            </div>
                                            <div
                                                v-for="name in filteredNamaPengguna"
                                                :key="name"
                                                class="px-2"
                                            >
                                                <label
                                                    class="flex items-center px-2 py-1 hover:bg-gray-100 cursor-pointer rounded"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        :value="name"
                                                        v-model="
                                                            filters.nama_pengguna
                                                        "
                                                        class="mr-2"
                                                        @click.stop
                                                    />
                                                    <span>{{ name }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Nama Rekan Dropdown -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Nama Rekan
                                    </label>
                                    <div class="relative">
                                        <div
                                            @click="toggleDropdown('namaRekan')"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md cursor-pointer bg-white flex justify-between items-center"
                                        >
                                            <span class="truncate">
                                                {{
                                                    filters.namaRekan.length
                                                        ? `${filters.namaRekan.length} dipilih`
                                                        : "Pilih nama rekan..."
                                                }}
                                            </span>
                                            <span class="ml-2">▼</span>
                                        </div>
                                        <div
                                            v-show="
                                                activeDropdown === 'namaRekan'
                                            "
                                            class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto"
                                        >
                                            <div
                                                class="p-2 border-b sticky top-0 bg-white"
                                            >
                                                <input
                                                    v-model="
                                                        searchTerms.namaRekan
                                                    "
                                                    type="text"
                                                    class="w-full px-2 py-1 border border-gray-300 rounded-md"
                                                    placeholder="Cari..."
                                                    @click.stop
                                                />
                                            </div>
                                            <div
                                                class="p-2 sticky top-10 bg-white"
                                            >
                                                <label
                                                    class="flex items-center px-2 py-1 hover:bg-gray-100 cursor-pointer rounded"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        :checked="
                                                            filters.namaRekan
                                                                .length ===
                                                            uniqueNamaRekan.length
                                                        "
                                                        @change="
                                                            toggleAll(
                                                                'namaRekan'
                                                            )
                                                        "
                                                        class="mr-2"
                                                    />
                                                    <span>Pilih Semua</span>
                                                </label>
                                            </div>
                                            <div
                                                v-for="name in filteredNamaRekan"
                                                :key="name"
                                                class="px-2"
                                            >
                                                <label
                                                    class="flex items-center px-2 py-1 hover:bg-gray-100 cursor-pointer rounded"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        :value="name"
                                                        v-model="
                                                            filters.namaRekan
                                                        "
                                                        class="mr-2"
                                                        @click.stop
                                                    />
                                                    <span>{{ name }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Pertanyaan Dropdown -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Pertanyaan
                                    </label>
                                    <div class="relative">
                                        <div
                                            @click="
                                                toggleDropdown('pertanyaan')
                                            "
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md cursor-pointer bg-white flex justify-between items-center"
                                        >
                                            <span class="truncate">
                                                {{
                                                    filters.pertanyaan.length
                                                        ? `${filters.pertanyaan.length} dipilih`
                                                        : "Pilih pertanyaan..."
                                                }}
                                            </span>
                                            <span class="ml-2">▼</span>
                                        </div>
                                        <div
                                            v-show="
                                                activeDropdown === 'pertanyaan'
                                            "
                                            class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto"
                                        >
                                            <div
                                                class="p-2 border-b sticky top-0 bg-white"
                                            >
                                                <input
                                                    v-model="
                                                        searchTerms.pertanyaan
                                                    "
                                                    type="text"
                                                    class="w-full px-2 py-1 border border-gray-300 rounded-md"
                                                    placeholder="Cari..."
                                                    @click.stop
                                                />
                                            </div>
                                            <div
                                                class="p-2 sticky top-10 bg-white"
                                            >
                                                <label
                                                    class="flex items-center px-2 py-1 hover:bg-gray-100 cursor-pointer rounded"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        :checked="
                                                            filters.pertanyaan
                                                                .length ===
                                                            uniquePertanyaan.length
                                                        "
                                                        @change="
                                                            toggleAll(
                                                                'pertanyaan'
                                                            )
                                                        "
                                                        class="mr-2"
                                                    />
                                                    <span>Pilih Semua</span>
                                                </label>
                                            </div>
                                            <div
                                                v-for="question in filteredPertanyaan"
                                                :key="question"
                                                class="px-2"
                                            >
                                                <label
                                                    class="flex items-center px-2 py-1 hover:bg-gray-100 cursor-pointer rounded"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        :value="question"
                                                        v-model="
                                                            filters.pertanyaan
                                                        "
                                                        class="mr-2"
                                                        @click.stop
                                                    />
                                                    <span
                                                        class="line-clamp-2"
                                                        >{{ question }}</span
                                                    >
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Search in Answers -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Cari Jawaban
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model="filters.jawaban"
                                            type="text"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                            placeholder="Cari dalam jawaban..."
                                        />
                                        <button
                                            v-if="filters.jawaban"
                                            @click="filters.jawaban = ''"
                                            class="absolute right-2 top-2 text-gray-400 hover:text-gray-600"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </div>

                                <!-- Status Filter -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Status
                                    </label>
                                    <div class="flex space-x-2">
                                        <button
                                            @click="toggleStatusFilter(null)"
                                            :class="[
                                                'px-3 py-1 rounded-full text-sm transition duration-150',
                                                filters.status === null
                                                    ? 'bg-blue-500 text-white shadow-md'
                                                    : 'bg-gray-200 text-gray-800 hover:bg-gray-300',
                                            ]"
                                        >
                                            Semua
                                        </button>
                                        <button
                                            @click="toggleStatusFilter('aktif')"
                                            :class="[
                                                'px-3 py-1 rounded-full text-sm transition duration-150',
                                                filters.status === 'aktif'
                                                    ? 'bg-red-500 text-white shadow-md'
                                                    : 'bg-red-100 text-red-800 hover:bg-red-200',
                                            ]"
                                        >
                                            Aktif
                                        </button>
                                        <button
                                            @click="
                                                toggleStatusFilter('selesai')
                                            "
                                            :class="[
                                                'px-3 py-1 rounded-full text-sm transition duration-150',
                                                filters.status === 'selesai'
                                                    ? 'bg-green-500 text-white shadow-md'
                                                    : 'bg-green-100 text-green-800 hover:bg-green-200',
                                            ]"
                                        >
                                            Selesai
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Reset Filters Button -->
                            <div class="flex justify-end">
                                <button
                                    @click="resetFilters"
                                    class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition"
                                >
                                    Reset Filters
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Button to Minimize/Expand All Cards -->
                    <div
                        v-if="!loading && !error && filteredGroups.length > 0"
                        class="mb-6 mt-6 text-center"
                    >
                        <button
                            @click="toggleAllCards"
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-150 shadow-md"
                        >
                            {{
                                allCardsMinimized
                                    ? "Tampilkan Semua"
                                    : "Tutup Semua"
                            }}
                        </button>
                    </div>

                    <!-- Results Summary -->
                    <div
                        class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center"
                    >
                        <div class="text-sm text-gray-600">
                            Menampilkan
                            <span class="font-medium">{{
                                filteredGroups.length
                            }}</span>
                            dari
                            <span class="font-medium">{{
                                groupedAnswers.length
                            }}</span>
                            penilaian
                        </div>
                        <div
                            v-if="selectedGroup"
                            class="text-sm font-medium bg-blue-100 text-blue-800 px-3 py-1 rounded-full"
                        >
                            Kelompok: {{ selectedGroup }}
                        </div>
                    </div>

                    <!-- No Data Message -->
                    <div
                        v-if="filteredGroups.length === 0"
                        class="text-center text-gray-500 py-12"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-12 w-12 mx-auto text-gray-400 mb-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <p class="text-lg">
                            {{
                                noDataMessage ||
                                "Tidak ada data yang sesuai dengan filter"
                            }}
                        </p>
                        <button
                            v-if="hasActiveFilters"
                            @click="resetFilters"
                            class="mt-4 text-blue-600 hover:text-blue-800 underline"
                        >
                            Reset semua filter
                        </button>
                    </div>
                    <!-- Pagination Controls -->
                    <div
                        v-if="filteredGroups.length > 0 && totalPages > 1"
                        class="mb-6 flex flex-col sm:flex-row justify-between items-center bg-white p-4 rounded-lg shadow"
                    >
                        <!-- Page Size Selector -->
                        <div class="mb-4 sm:mb-0 flex items-center">
                            <span class="text-sm text-gray-600 mr-2"
                                >Tampilkan:</span
                            >
                            <select
                                v-model="pageSize"
                                class="border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option
                                    v-for="size in pageSizeOptions"
                                    :key="size"
                                    :value="size"
                                >
                                    {{ size }} baris
                                </option>
                            </select>
                        </div>

                        <!-- Pages Navigation -->
                        <div class="flex items-center space-x-1">
                            <button
                                @click="currentPage = 1"
                                :disabled="currentPage === 1"
                                :class="[
                                    'px-3 py-1 rounded-md text-sm',
                                    currentPage === 1
                                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                        : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                                ]"
                            >
                                &laquo; Awal
                            </button>

                            <button
                                @click="
                                    currentPage = Math.max(1, currentPage - 1)
                                "
                                :disabled="currentPage === 1"
                                :class="[
                                    'px-3 py-1 rounded-md text-sm',
                                    currentPage === 1
                                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                        : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                                ]"
                            >
                                &lt;
                            </button>

                            <div class="text-sm px-3 py-1">
                                Halaman
                                <span class="font-medium">{{
                                    currentPage
                                }}</span>
                                dari
                                <span class="font-medium">{{
                                    totalPages
                                }}</span>
                            </div>

                            <button
                                @click="
                                    currentPage = Math.min(
                                        totalPages,
                                        currentPage + 1
                                    )
                                "
                                :disabled="currentPage === totalPages"
                                :class="[
                                    'px-3 py-1 rounded-md text-sm',
                                    currentPage === totalPages
                                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                        : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                                ]"
                            >
                                &gt;
                            </button>

                            <button
                                @click="currentPage = totalPages"
                                :disabled="currentPage === totalPages"
                                :class="[
                                    'px-3 py-1 rounded-md text-sm',
                                    currentPage === totalPages
                                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                        : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                                ]"
                            >
                                Akhir &raquo;
                            </button>
                        </div>

                        <!-- Page Info -->
                        <div class="mt-4 sm:mt-0 text-sm text-gray-600">
                            Menampilkan
                            <span class="font-medium">{{
                                paginationInfo.start
                            }}</span>
                            -
                            <span class="font-medium">{{
                                paginationInfo.end
                            }}</span>
                            dari
                            <span class="font-medium">{{
                                filteredGroups.length
                            }}</span>
                            penilaian
                        </div>
                    </div>
                </div>

                <!-- Assessment Cards - Now Grouped by Assessor/Peer with Minimize Option -->
                <div
                    v-if="!loading && !error && filteredGroups.length > 0"
                    class="space-y-6"
                >
                    <div
                        v-for="(group, index) in paginatedGroups"
                        :key="`${group.assessor}-${group.peer}-${index}`"
                        class="bg-white rounded-lg shadow overflow-hidden"
                    >
                        <!-- Card Header with Assessor and Peer Info -->
                        <div
                            @click="toggleCard(`card-${index}`)"
                            class="bg-gray-50 p-4 flex justify-between items-center border-b border-gray-200 cursor-pointer hover:bg-gray-100 transition-colors duration-150"
                        >
                            <div class="flex items-center">
                                <span class="font-medium text-gray-600 mr-2"
                                    >#{{
                                        (currentPage - 1) * pageSize + index + 1
                                    }}</span
                                >
                                <span class="font-medium mr-2">{{
                                    group.assessor
                                }}</span>
                                <span class="text-gray-600">menilai</span>
                                <span class="ml-2 font-medium">{{
                                    group.peer
                                }}</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span
                                    :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        group.status === 'aktif'
                                            ? 'bg-red-100 text-red-800'
                                            : 'bg-green-100 text-green-800',
                                    ]"
                                >
                                    {{ group.status }}
                                </span>
                                <span
                                    v-if="!selectedGroup"
                                    class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium"
                                >
                                    {{ group.kelompok }}
                                </span>
                                <!-- Toggle Indicator -->
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 transition-transform duration-200"
                                    :class="
                                        minimizedCards[`card-${index}`]
                                            ? ''
                                            : 'transform rotate-180'
                                    "
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </div>
                        </div>

                        <!-- Card Content - will be hidden when minimized -->
                        <div v-show="!minimizedCards[`card-${index}`]">
                            <!-- Question-Answer Pairs -->
                            <div class="p-4">
                                <div class="space-y-6">
                                    <div
                                        v-for="(qa, qaIndex) in group.qaItems"
                                        :key="`qa-${qaIndex}`"
                                        class="border-b border-gray-200 pb-4 last:border-0 last:pb-0"
                                    >
                                        <div class="mb-3">
                                            <h3
                                                class="font-medium text-gray-900 mb-2"
                                            >
                                                Pertanyaan {{ qaIndex + 1 }}:
                                            </h3>
                                            <p class="text-gray-700">
                                                {{ qa.pertanyaan }}
                                            </p>
                                        </div>
                                        <div>
                                            <div
                                                class="flex justify-between items-center mb-2"
                                            >
                                                <h3
                                                    class="font-medium text-gray-900"
                                                >
                                                    Jawaban:
                                                </h3>
                                                <span
                                                    class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm"
                                                >
                                                    Skor: {{ qa.skor }}
                                                </span>
                                                <span
                                                    class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm"
                                                >
                                                    Score_SLA:
                                                    {{ qa.score_SLA }}
                                                </span>
                                                <span
                                                    class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm"
                                                >
                                                    Similarity:
                                                    {{ qa.similarity }}
                                                </span>
                                            </div>
                                            <div
                                                class="bg-gray-50 p-3 rounded border border-gray-200"
                                            >
                                                <p
                                                    class="text-gray-700 whitespace-pre-line"
                                                >
                                                    {{ qa.jawaban }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import Navbar from "@/Components/Navbar.vue";
import Sidebar from "@/Components/Sidebar.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";

export default {
    name: "AnswersPeerAssessment",
    components: {
        Navbar,
        Sidebar,
        Breadcrumb,
    },
    data() {
        return {
            batch_year: "",
            project_name: "",
            groups: [],
            totalGroups: 0,
            answers: [],
            selectedGroup: null,
            noDataMessage: "",
            activeDropdown: null,
            searchTerms: {
                nama_pengguna: "",
                namaRekan: "",
                pertanyaan: "",
            },
            filters: {
                nama_pengguna: [],
                namaRekan: [],
                pertanyaan: [],
                jawaban: "",
                status: null,
            },
            showFilters: true,
            currentPage: 1,
            pageSize: 10,
            pageSizeOptions: [5, 10, 20, 50, 100], // Available page size options
            breadcrumbs: [
                {
                    text: "Peer Assessment",
                    href: "/dosen/assessment/projectsPeer",
                },
                { text: "List Answer", href: null },
            ],
            loading: false,
            error: null,
            minimizedCards: {}, // Track minimized state of cards
            allCardsMinimized: false, // Track if all cards are minimized
        };
    },
    computed: {
        uniqueNamaPengguna() {
            return [
                ...new Set(this.answers.map((a) => a.nama_pengguna)),
            ].sort();
        },
        uniqueNamaRekan() {
            return [...new Set(this.answers.map((a) => a.nama_rekan))].sort();
        },
        uniquePertanyaan() {
            return [...new Set(this.answers.map((a) => a.pertanyaan))].sort();
        },
        filteredNamaPengguna() {
            const searchTerm = this.searchTerms.nama_pengguna.toLowerCase();
            return this.uniqueNamaPengguna.filter((name) =>
                name.toLowerCase().includes(searchTerm)
            );
        },
        filteredNamaRekan() {
            const searchTerm = this.searchTerms.namaRekan.toLowerCase();
            return this.uniqueNamaRekan.filter((name) =>
                name.toLowerCase().includes(searchTerm)
            );
        },
        filteredPertanyaan() {
            const searchTerm = this.searchTerms.pertanyaan.toLowerCase();
            return this.uniquePertanyaan.filter((question) =>
                question.toLowerCase().includes(searchTerm)
            );
        },
        filteredAnswers() {
            let filtered = this.answers;

            if (this.selectedGroup) {
                filtered = filtered.filter(
                    (answer) => answer.kelompok === this.selectedGroup
                );
            }

            if (this.filters.nama_pengguna.length > 0) {
                filtered = filtered.filter((answer) =>
                    this.filters.nama_pengguna.includes(answer.nama_pengguna)
                );
            }

            if (this.filters.namaRekan.length > 0) {
                filtered = filtered.filter((answer) =>
                    this.filters.namaRekan.includes(answer.nama_rekan)
                );
            }

            if (this.filters.pertanyaan.length > 0) {
                filtered = filtered.filter((answer) =>
                    this.filters.pertanyaan.includes(answer.pertanyaan)
                );
            }

            if (this.filters.jawaban) {
                const searchTerm = this.filters.jawaban.toLowerCase();
                filtered = filtered.filter((answer) =>
                    answer.jawaban.toLowerCase().includes(searchTerm)
                );
            }

            if (this.filters.status) {
                filtered = filtered.filter(
                    (answer) => answer.status === this.filters.status
                );
            }

            // Primary sort by nama_pengguna, secondary by nama_rekan
            return filtered.sort((a, b) => {
                if (a.nama_pengguna < b.nama_pengguna) return -1;
                if (a.nama_pengguna > b.nama_pengguna) return 1;
                if (a.nama_rekan < b.nama_rekan) return -1;
                if (a.nama_rekan > b.nama_rekan) return 1;
                return 0;
            });
        },
        // Group answers by assessor (nama_pengguna) and peer (nama_rekan)
        groupedAnswers() {
            const groups = [];
            const groupMap = new Map();

            this.filteredAnswers.forEach((answer) => {
                const key = `${answer.nama_pengguna}-${answer.nama_rekan}`;

                if (!groupMap.has(key)) {
                    groupMap.set(key, {
                        assessor: answer.nama_pengguna,
                        peer: answer.nama_rekan,
                        kelompok: answer.kelompok,
                        status: answer.status,
                        qaItems: [],
                    });
                }

                groupMap.get(key).qaItems.push({
                    pertanyaan: answer.pertanyaan,
                    jawaban: answer.jawaban,
                    skor: answer.skor,
                    score_SLA: answer.score_SLA,
                    similarity: answer.similarity,
                });
            });

            groupMap.forEach((group) => {
                groups.push(group);
            });

            return groups;
        },
        filteredGroups() {
            return this.groupedAnswers;
        },
        paginatedGroups() {
            const start = (this.currentPage - 1) * this.pageSize;
            const end = start + this.pageSize;
            return this.filteredGroups.slice(start, end);
        },
        totalPages() {
            return Math.ceil(this.filteredGroups.length / this.pageSize);
        },
        paginationInfo() {
            if (this.filteredGroups.length === 0) {
                return { start: 0, end: 0 };
            }

            const start = (this.currentPage - 1) * this.pageSize + 1;
            const end = Math.min(
                start + this.pageSize - 1,
                this.filteredGroups.length
            );

            return { start, end };
        },
        hasActiveFilters() {
            return (
                this.selectedGroup !== null ||
                this.filters.nama_pengguna.length > 0 ||
                this.filters.namaRekan.length > 0 ||
                this.filters.pertanyaan.length > 0 ||
                this.filters.jawaban !== "" ||
                this.filters.status !== null
            );
        },
    },
    watch: {
        filteredGroups() {
            // Reset to page 1 when filters change
            this.currentPage = 1;
        },
        pageSize() {
            // Reset to page 1 when page size changes
            this.currentPage = 1;

            // Save user preference in localStorage
            localStorage.setItem("peerAssessmentPageSize", this.pageSize);
        },
        paginatedGroups: {
            handler(newGroups) {
                // Initialize minimized state for new cards
                if (newGroups && newGroups.length > 0) {
                    newGroups.forEach((_, index) => {
                        const cardId = `card-${index}`;
                        if (this.minimizedCards[cardId] === undefined) {
                            this.minimizedCards[cardId] =
                                this.allCardsMinimized;
                        }
                    });
                }
            },
            immediate: true,
        },
    },
    created() {
        const pageProps = this.$page.props;

        const query = new URLSearchParams(window.location.search);
        this.batch_year = query.get("batch_year") || pageProps.batch_year;
        this.project_name = query.get("project_name") || pageProps.project_name;

        // Load saved page size from localStorage if exists
        const savedPageSize = localStorage.getItem("peerAssessmentPageSize");
        if (
            savedPageSize &&
            this.pageSizeOptions.includes(Number(savedPageSize))
        ) {
            this.pageSize = Number(savedPageSize);
        }

        this.totalGroups = pageProps.totalGroups || 0;

        if (this.batch_year && this.project_name) {
            this.fetchAnswers();
        } else {
            this.error = "Parameter tidak lengkap!";
            console.error(this.error);
        }

        document.addEventListener("click", this.handleClickOutside);
    },
    beforeDestroy() {
        document.removeEventListener("click", this.handleClickOutside);
    },
    methods: {
        async fetchAnswers() {
            this.loading = true;
            this.error = null;
            try {
                const response = await axios.get("/api/answersPeer/list", {
                    params: {
                        batch_year: this.batch_year,
                        project_name: this.project_name,
                    },
                });

                if (response.data.success) {
                    this.answers = response.data.data
                        .map((item) => ({
                            ...item,
                            nama_pengguna: item.user?.name || "-",
                            nama_rekan: item.peer?.name || "-",
                            skor: item.score,
                            score_SLA: item.score_SLA,
                            similarity: item.similarity,
                            jawaban: item.answer,
                            pertanyaan: item.pertanyaan || "-",
                            status: item.status,
                            kelompok: item.kelompok,
                        }))
                        .sort((a, b) => {
                            if (a.nama_pengguna < b.nama_pengguna) return -1;
                            if (a.nama_pengguna > b.nama_pengguna) return 1;
                            return 0;
                        });
                    this.groups = response.data.groups || [];
                    this.noDataMessage = "";
                } else {
                    this.answers = [];
                    this.groups = [];
                    this.noDataMessage = "Belum ada jawaban";
                }
            } catch (error) {
                console.error("Error fetching answers:", error);
                this.error = "Terjadi kesalahan saat mengambil data.";
                this.answers = [];
                this.groups = [];
            } finally {
                this.loading = false;
            }
        },

        toggleDropdown(dropdownName) {
            if (this.activeDropdown === dropdownName) {
                this.activeDropdown = null;
            } else {
                this.activeDropdown = dropdownName;
            }
        },

        handleClickOutside(event) {
            const dropdowns = document.querySelectorAll(".relative");
            let clickedOutside = true;

            dropdowns.forEach((dropdown) => {
                if (dropdown.contains(event.target)) {
                    clickedOutside = false;
                }
            });

            if (clickedOutside) {
                this.activeDropdown = null;
            }
        },

        toggleAll(filterName) {
            const allItems = {
                nama_pengguna: this.uniqueNamaPengguna,
                namaRekan: this.uniqueNamaRekan,
                pertanyaan: this.uniquePertanyaan,
            }[filterName];

            if (this.filters[filterName].length === allItems.length) {
                this.filters[filterName] = [];
            } else {
                this.filters[filterName] = [...allItems];
            }
        },

        toggleStatusFilter(status) {
            this.filters.status =
                this.filters.status === status ? null : status;
        },

        shouldShowCell(index, field) {
            if (field !== "nama_pengguna" && field !== "nama_rekan")
                return true;

            if (index === 0) return true;

            const currentItem = this.filteredAnswers[index];
            const previousItem = this.filteredAnswers[index - 1];

            return currentItem[field] !== previousItem[field];
        },

        getRowSpan(index, field) {
            if (field !== "nama_pengguna" && field !== "nama_rekan") return 1;

            let span = 1;
            const currentItem = this.filteredAnswers[index];

            for (let i = index + 1; i < this.filteredAnswers.length; i++) {
                if (currentItem[field] === this.filteredAnswers[i][field]) {
                    span++;
                } else {
                    break;
                }
            }

            return span;
        },

        calculateAverageScore(qaItems) {
            if (!qaItems || qaItems.length === 0) return 0;

            const total = qaItems.reduce((sum, item) => {
                return sum + (parseFloat(item.skor) || 0);
            }, 0);

            return total / qaItems.length;
        },

        resetFilters() {
            this.selectedGroup = null;
            this.filters = {
                nama_pengguna: [],
                namaRekan: [],
                pertanyaan: [],
                jawaban: "",
                status: null,
            };
            this.searchTerms = {
                nama_pengguna: "",
                namaRekan: "",
                pertanyaan: "",
            };
            this.currentPage = 1;
        },

        // Card toggling functionality - Fixed for Vue 3
        toggleCard(cardId) {
            // Vue 3 way - directly mutate the reactive object
            this.minimizedCards[cardId] = !this.minimizedCards[cardId];

            // Update allCardsMinimized status
            this.updateAllCardsMinimizedStatus();
        },

        toggleAllCards() {
            // Toggle the state
            const newState = !this.allCardsMinimized;
            this.allCardsMinimized = newState;

            // Update all cards
            this.paginatedGroups.forEach((_, index) => {
                this.minimizedCards[`card-${index}`] = newState;
            });
        },

        updateAllCardsMinimizedStatus() {
            // Check if all cards are minimized
            const cardIds = Object.keys(this.minimizedCards).filter(
                (key) =>
                    key.startsWith("card-") &&
                    parseInt(key.split("-")[1]) < this.paginatedGroups.length
            );

            if (cardIds.length === 0) {
                this.allCardsMinimized = false;
                return;
            }

            // If all current cards are minimized, set allCardsMinimized to true
            this.allCardsMinimized = cardIds.every(
                (cardId) => this.minimizedCards[cardId]
            );
        },

        // Reset minimized cards state when page changes
        initializeMinimizedCards() {
            // Initialize minimized state for all current cards
            this.paginatedGroups.forEach((_, index) => {
                this.minimizedCards[`card-${index}`] = this.allCardsMinimized;
            });
        },
    },
    mounted() {
        // Initialize cards state when component is mounted
        this.initializeMinimizedCards();
    },
    updated() {
        // Update all cards minimized status
        this.updateAllCardsMinimizedStatus();
    },
};
</script>
