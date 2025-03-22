<script>
import { ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import ForgetPasswordModal from "./ForgetPasswordModal.vue";

export default {
    components: {
        ForgetPasswordModal,
    },
    setup() {
        const email = ref("");
        const password = ref("");
        const error = ref(null);
        const isLoading = ref(false);
        const isRedirecting = ref(false);
        const showForgetPasswordModal = ref(false);
        const showSuccessMessage = ref(false);
        const successMessage = ref("");
        const showPassword = ref(false);
        const rememberMe = ref(false);

        const handleSubmit = async () => {
            if (isLoading.value) return;

            try {
                isLoading.value = true;
                error.value = null;

                const response = await axios.post("/sispa/api/login", {
                    email: email.value,
                    password: password.value,
                    remember_me: rememberMe.value,
                });

                // Tambahkan log untuk memeriksa response
                console.log("Response Login:", response.data);

                if (response.data.token) {
                    // showSuccessMessage.value = true;
                    // successMessage.value = "Login successful! Redirecting...";

                    localStorage.setItem("auth_token", response.data.token);
                    localStorage.setItem(
                        "user_data",
                        JSON.stringify(response.data.user)
                    );
                    // Cek kondisi need_password_change dari response
                    if (response.data.need_password_change) {
                        console.log("Setting need_password_change flag"); // Debug
                        localStorage.setItem("need_password_change", "true");
                    } else {
                        // Pastikan untuk menghapus item jika tidak perlu ganti password
                        localStorage.removeItem("need_password_change");
                    }

                    axios.defaults.headers.common[
                        "Authorization"
                    ] = `Bearer ${response.data.token}`;

                    const role = response.data.user.role;
                    isRedirecting.value = true;

                    setTimeout(() => {
                        if (role === "dosen") {
                            router.visit("/dosen/dashboard");
                        } else if (role === "mahasiswa") {
                            router.visit("/mahasiswa/dashboard");
                        } else if (role === "admin") {
                            router.visit("/admin/dashboard");
                        }
                    }, 1500);
                }
            } catch (err) {
                error.value =
                    err.response?.data?.message ||
                    "Login failed. Please check your credentials and try again.";
                localStorage.removeItem("auth_token");
                localStorage.removeItem("user_data");
                localStorage.removeItem("need_password_change");
            } finally {
                isLoading.value = false;
            }
        };

        const togglePasswordVisibility = () => {
            showPassword.value = !showPassword.value;
        };

        // Rest of the existing functions remain the same
        const checkAuthAndRedirect = async () => {
            // ... (keep existing implementation)
        };

        const openForgetPasswordModal = () => {
            showForgetPasswordModal.value = true;
        };

        const closeForgetPasswordModal = () => {
            showForgetPasswordModal.value = false;
        };

        onMounted(() => {
            if (
                window.location.pathname === "/login" &&
                localStorage.getItem("auth_token")
            ) {
                checkAuthAndRedirect();
            }

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get("reset") === "success") {
                showSuccessMessage.value = true;
                successMessage.value = decodeURIComponent(
                    urlParams.get("message")
                );
            }
        });

        return {
            email,
            password,
            error,
            isLoading,
            handleSubmit,
            showForgetPasswordModal,
            openForgetPasswordModal,
            closeForgetPasswordModal,
            showSuccessMessage,
            successMessage,
            showPassword,
            togglePasswordVisibility,
            rememberMe,
        };
    },
};
</script>

<template>
    <div class="min-h-screen flex bg-gray-50">
        <!-- Left Section -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-sky-100 to-sky-100 p-12 relative">
            <div class="absolute inset-0 bg-black opacity-10"></div>
            <div class="relative z-10 text-black">
                <h1 class="text-3xl font-bold mb-6">
                    Self and Peer Assessment Application
                </h1>
                <div class="space-y-6">
                    <p class="text-sm leading-relaxed text-justify">
                        Welcome to Politeknik Negeri Bandung's advanced
                        assessment platform!
                    </p>
                    <div class="space-y-4">
                        <div class="flex items-start space-x-3 text-sm">
                            <div class="flex-shrink-0 mt-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z">
                                    </path>
                                </svg>
                            </div>
                            <p>Objective self-evaluation tools</p>
                        </div>
                        <div class="flex items-start space-x-3 text-sm">
                            <div class="flex-shrink-0 mt-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z">
                                    </path>
                                </svg>
                            </div>
                            <p>Fair peer assessment system</p>
                        </div>
                        <div class="flex items-start space-x-3 text-sm">
                            <div class="flex-shrink-0 mt-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z">
                                    </path>
                                </svg>
                            </div>
                            <p>Structured feedback mechanisms</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Section -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12">
            <div class="w-full max-w-md space-y-8">
                <!-- Header -->
                <div class="text-center">
                    <h2 class="text-3xl font-bold text-gray-900">
                        Log In Account
                    </h2>
                    <p class="mt-2 text-gray-600">Hi, Welcome!</p>
                </div>

                <!-- Success Message -->
                <div v-if="showSuccessMessage"
                    class="bg-green-50 text-green-700 p-4 rounded-md flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <span>{{ successMessage }}</span>
                </div>

                <!-- Error Message -->
                <div v-if="error" class="bg-red-50 text-red-700 p-4 rounded-md flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    <span>{{ error }}</span>
                </div>

                <!-- Login Form -->
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <!-- Email Field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Email Address
                        </label>
                        <div class="mt-1">
                            <input type="email" v-model="email" required
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter your email" />
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Password
                        </label>
                        <div class="mt-1 relative">
                            <input :type="showPassword ? 'text' : 'password'" v-model="password" required
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter your password" />
                            <button type="button" @click="togglePasswordVisibility"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg class="h-5 w-5 text-gray-400" :class="{ hidden: showPassword }" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg class="h-5 w-5 text-gray-400" :class="{ hidden: !showPassword }" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input type="checkbox" v-model="rememberMe"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" />
                            <label class="ml-2 block text-sm text-gray-700">
                                Remember me
                            </label>
                        </div>
                        <div class="text-sm">
                            <a href="#" @click.prevent="openForgetPasswordModal"
                                class="font-medium text-blue-600 hover:text-blue-500">
                                Forgot password?
                            </a>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" :disabled="isLoading"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg v-if="isLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        {{ isLoading ? "Signing in..." : "Sign in" }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Forget Password Modal -->
        <ForgetPasswordModal :show-modal="showForgetPasswordModal" @close="closeForgetPasswordModal" />

        <!-- Role Information Modal - New Feature -->
        <div v-if="showRoleInfo" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Account Types</h3>
                    <button @click="showRoleInfo = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="space-y-4">
                    <div class="p-4 bg-blue-50 rounded-lg">
                        <h4 class="font-medium text-blue-800 mb-2">
                            Dosen (Lecturer)
                        </h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li>• Create and manage assessments</li>
                            <li>• View student submissions</li>
                            <li>• Generate reports and analytics</li>
                            <li>• Provide feedback and grades</li>
                        </ul>
                    </div>
                    <div class="p-4 bg-green-50 rounded-lg">
                        <h4 class="font-medium text-green-800 mb-2">
                            Mahasiswa (Student)
                        </h4>
                        <ul class="text-sm text-green-700 space-y-1">
                            <li>• Complete self-assessments</li>
                            <li>• Participate in peer reviews</li>
                            <li>• View personal progress</li>
                            <li>• Access feedback and scores</li>
                        </ul>
                    </div>
                </div>
                <button @click="showRoleInfo = false"
                    class="mt-6 w-full bg-gray-100 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-200 transition-colors">
                    Close
                </button>
            </div>
        </div>

        <!-- Toast Notifications -->
        <div v-if="showToast"
            class="fixed bottom-4 right-4 bg-white shadow-lg rounded-lg p-4 max-w-md animate-fade-in-up">
            <div class="flex items-center">
                <div :class="{
                    'bg-green-500': toastType === 'success',
                    'bg-red-500': toastType === 'error',
                    'bg-blue-500': toastType === 'info',
                }" class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path v-if="toastType === 'success'" stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="2" d="M5 13l4 4L19 7" />
                        <path v-if="toastType === 'error'" stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        <path v-if="toastType === 'info'" stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">
                        {{ toastMessage }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.login-field {
    @apply relative rounded-md shadow-sm;
}

.login-field:focus-within {
    @apply ring-2 ring-blue-500;
}

.login-input {
    @apply block w-full px-4 py-3 border-gray-300 rounded-md focus:outline-none;
}

.login-label {
    @apply absolute -top-2 left-2 px-1 bg-white text-sm font-medium text-gray-600;
}
</style>
