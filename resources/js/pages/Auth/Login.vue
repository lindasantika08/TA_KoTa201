const handleSubmit = async () => {
    if (isLoading.value) return;

    try {
        isLoading.value = true;
        error.value = null;

        const response = await axios.post("/api/login", {
            email: email.value,
            password: password.value,
            remember_me: rememberMe.value,
        });

        console.log("Response Login:", response.data);

        if (response.data.token) {
            localStorage.setItem("auth_token", response.data.token);
            localStorage.setItem(
                "user_data",
                JSON.stringify(response.data.user)
            );

            if (response.data.need_password_change) {
                console.log("Setting need_password_change flag");
                localStorage.setItem("need_password_change", "true");
            } else {
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
