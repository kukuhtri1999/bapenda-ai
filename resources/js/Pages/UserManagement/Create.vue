<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    roles: Array,
});

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    role: "",
    is_active: true,
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = () => {
    form.post(route("users.store"), {
        onSuccess: () => form.reset(),
    });
};

const nameRules = [
    (v) => !!v || "Name is required",
    (v) => v.length >= 2 || "Name must be at least 2 characters",
];

const emailRules = [
    (v) => !!v || "Email is required",
    (v) => /.+@.+\..+/.test(v) || "Email must be valid",
];

const passwordRules = [
    (v) => !!v || "Password is required",
    (v) => v.length >= 8 || "Password must be at least 8 characters",
];

const passwordConfirmationRules = [
    (v) => !!v || "Password confirmation is required",
    (v) => v === form.password || "Passwords do not match",
];

const roleRules = [(v) => !!v || "Role is required"];
</script>

<template>
    <AppLayout title="Create User">
        <div class="pa-0">
            <!-- Header -->
            <v-row class="mb-6">
                <v-col cols="12">
                    <v-card elevation="2">
                        <v-card-text class="pa-6">
                            <div class="d-flex align-center">
                                <v-btn
                                    icon="mdi-arrow-left"
                                    variant="text"
                                    @click="
                                        $inertia.visit(route('users.index'))
                                    "
                                    class="mr-4"
                                ></v-btn>
                                <div>
                                    <h1
                                        class="text-h4 font-weight-bold text-primary mb-2"
                                    >
                                        <v-icon class="mr-3" size="36"
                                            >mdi-account-plus</v-icon
                                        >
                                        Create New User
                                    </h1>
                                    <p class="text-body-1 text-medium-emphasis">
                                        Add a new user to the system with
                                        appropriate role and permissions
                                    </p>
                                </div>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

            <!-- Form -->
            <v-row>
                <v-col cols="12" lg="8">
                    <v-card elevation="2">
                        <v-card-title class="pa-6 pb-0">
                            <h2 class="text-h5">User Information</h2>
                        </v-card-title>
                        <v-card-text class="pa-6">
                            <v-form @submit.prevent="submit" class="space-y-6">
                                <v-row>
                                    <!-- Name Field -->
                                    <v-col cols="12" md="6">
                                        <v-text-field
                                            v-model="form.name"
                                            label="Full Name *"
                                            variant="outlined"
                                            :rules="nameRules"
                                            :error-messages="form.errors.name"
                                            prepend-inner-icon="mdi-account"
                                            placeholder="Enter full name"
                                            required
                                        ></v-text-field>
                                    </v-col>

                                    <!-- Email Field -->
                                    <v-col cols="12" md="6">
                                        <v-text-field
                                            v-model="form.email"
                                            label="Email Address *"
                                            variant="outlined"
                                            type="email"
                                            :rules="emailRules"
                                            :error-messages="form.errors.email"
                                            prepend-inner-icon="mdi-email"
                                            placeholder="Enter email address"
                                            required
                                        ></v-text-field>
                                    </v-col>

                                    <!-- Password Field -->
                                    <v-col cols="12" md="6">
                                        <v-text-field
                                            v-model="form.password"
                                            label="Password *"
                                            variant="outlined"
                                            :type="
                                                showPassword
                                                    ? 'text'
                                                    : 'password'
                                            "
                                            :rules="passwordRules"
                                            :error-messages="
                                                form.errors.password
                                            "
                                            prepend-inner-icon="mdi-lock"
                                            :append-inner-icon="
                                                showPassword
                                                    ? 'mdi-eye'
                                                    : 'mdi-eye-off'
                                            "
                                            @click:append-inner="
                                                showPassword = !showPassword
                                            "
                                            placeholder="Enter password"
                                            required
                                        ></v-text-field>
                                    </v-col>

                                    <!-- Password Confirmation Field -->
                                    <v-col cols="12" md="6">
                                        <v-text-field
                                            v-model="form.password_confirmation"
                                            label="Confirm Password *"
                                            variant="outlined"
                                            :type="
                                                showPasswordConfirmation
                                                    ? 'text'
                                                    : 'password'
                                            "
                                            :rules="passwordConfirmationRules"
                                            :error-messages="
                                                form.errors
                                                    .password_confirmation
                                            "
                                            prepend-inner-icon="mdi-lock-check"
                                            :append-inner-icon="
                                                showPasswordConfirmation
                                                    ? 'mdi-eye'
                                                    : 'mdi-eye-off'
                                            "
                                            @click:append-inner="
                                                showPasswordConfirmation =
                                                    !showPasswordConfirmation
                                            "
                                            placeholder="Confirm password"
                                            required
                                        ></v-text-field>
                                    </v-col>

                                    <!-- Role Field -->
                                    <v-col cols="12" md="6">
                                        <v-select
                                            v-model="form.role"
                                            :items="
                                                roles.map((role) => ({
                                                    title: role.name,
                                                    value: role.name,
                                                }))
                                            "
                                            label="User Role *"
                                            variant="outlined"
                                            :rules="roleRules"
                                            :error-messages="form.errors.role"
                                            prepend-inner-icon="mdi-shield-account"
                                            placeholder="Select user role"
                                            required
                                        ></v-select>
                                    </v-col>

                                    <!-- Status Field -->
                                    <v-col cols="12" md="6">
                                        <div class="d-flex align-center">
                                            <v-switch
                                                v-model="form.is_active"
                                                color="success"
                                                label="Active User"
                                                :true-value="true"
                                                :false-value="false"
                                            ></v-switch>
                                            <v-tooltip
                                                activator="parent"
                                                location="top"
                                            >
                                                Enable or disable user account
                                            </v-tooltip>
                                        </div>
                                    </v-col>
                                </v-row>

                                <!-- Action Buttons -->
                                <v-row class="mt-6">
                                    <v-col cols="12">
                                        <v-divider class="mb-6"></v-divider>
                                        <div class="d-flex gap-4">
                                            <v-btn
                                                type="submit"
                                                color="primary"
                                                size="large"
                                                :loading="form.processing"
                                                prepend-icon="mdi-content-save"
                                            >
                                                Create User
                                            </v-btn>
                                            <v-btn
                                                variant="outlined"
                                                size="large"
                                                @click="
                                                    $inertia.visit(
                                                        route('users.index'),
                                                    )
                                                "
                                                prepend-icon="mdi-cancel"
                                            >
                                                Cancel
                                            </v-btn>
                                        </div>
                                    </v-col>
                                </v-row>
                            </v-form>
                        </v-card-text>
                    </v-card>
                </v-col>

                <!-- Info Panel -->
                <v-col cols="12" lg="4">
                    <v-card elevation="2" class="mb-4">
                        <v-card-title class="pa-4 pb-2">
                            <v-icon class="mr-2" color="info"
                                >mdi-information</v-icon
                            >
                            User Creation Guidelines
                        </v-card-title>
                        <v-card-text class="pa-4">
                            <v-list density="compact">
                                <v-list-item>
                                    <template #prepend>
                                        <v-icon color="success" size="small"
                                            >mdi-check</v-icon
                                        >
                                    </template>
                                    <v-list-item-title class="text-body-2">
                                        Use a strong password (min 8 characters)
                                    </v-list-item-title>
                                </v-list-item>
                                <v-list-item>
                                    <template #prepend>
                                        <v-icon color="success" size="small"
                                            >mdi-check</v-icon
                                        >
                                    </template>
                                    <v-list-item-title class="text-body-2">
                                        Assign appropriate role based on
                                        responsibilities
                                    </v-list-item-title>
                                </v-list-item>
                                <v-list-item>
                                    <template #prepend>
                                        <v-icon color="success" size="small"
                                            >mdi-check</v-icon
                                        >
                                    </template>
                                    <v-list-item-title class="text-body-2">
                                        Verify email address is accessible
                                    </v-list-item-title>
                                </v-list-item>
                                <v-list-item>
                                    <template #prepend>
                                        <v-icon color="success" size="small"
                                            >mdi-check</v-icon
                                        >
                                    </template>
                                    <v-list-item-title class="text-body-2">
                                        User will receive welcome email
                                        notification
                                    </v-list-item-title>
                                </v-list-item>
                            </v-list>
                        </v-card-text>
                    </v-card>

                    <!-- Role Information -->
                    <v-card elevation="2">
                        <v-card-title class="pa-4 pb-2">
                            <v-icon class="mr-2" color="primary"
                                >mdi-shield-account</v-icon
                            >
                            Role Descriptions
                        </v-card-title>
                        <v-card-text class="pa-4">
                            <div
                                v-for="role in roles"
                                :key="role.id"
                                class="mb-3"
                            >
                                <v-chip
                                    :color="
                                        role.name === 'Super Admin'
                                            ? 'purple'
                                            : role.name === 'Admin'
                                              ? 'primary'
                                              : role.name === 'Manager'
                                                ? 'orange'
                                                : 'grey'
                                    "
                                    variant="tonal"
                                    size="small"
                                    class="mb-2"
                                >
                                    {{ role.name }}
                                </v-chip>
                                <p class="text-caption text-medium-emphasis">
                                    {{
                                        role.name === "Super Admin"
                                            ? "Full system access and control"
                                            : role.name === "Admin"
                                              ? "Manage users and system settings"
                                              : role.name === "Manager"
                                                ? "Oversee operations and reports"
                                                : "Basic operational tasks"
                                    }}
                                </p>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>
        </div>
    </AppLayout>
</template>

<style scoped>
.gap-4 {
    gap: 16px;
}

.space-y-6 > * + * {
    margin-top: 24px;
}
</style>
