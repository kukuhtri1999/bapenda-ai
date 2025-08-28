<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";

const props = defineProps({
    user: Object,
    roles: Array,
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: "",
    password_confirmation: "",
    role: props.user.roles[0]?.name || "",
    is_active: props.user.is_active,
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = () => {
    form.put(route("users.update", props.user.id), {
        onSuccess: () => {
            form.reset("password", "password_confirmation");
        },
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
    (v) => !v || v.length >= 8 || "Password must be at least 8 characters",
];

const passwordConfirmationRules = [
    (v) => !v || v === form.password || "Passwords do not match",
];

const roleRules = [(v) => !!v || "Role is required"];

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("id-ID", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const getUserRole = () => {
    return props.user.roles && props.user.roles.length > 0
        ? props.user.roles[0].name
        : "No Role";
};

const getRoleColor = (roleName) => {
    const colors = {
        "Super Admin": "purple",
        Admin: "primary",
        Manager: "orange",
        Operator: "grey",
    };
    return colors[roleName] || "grey";
};
</script>

<template>
    <AppLayout title="Edit User">
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
                                <div class="flex-grow-1">
                                    <h1
                                        class="text-h4 font-weight-bold text-primary mb-2"
                                    >
                                        <v-icon class="mr-3" size="36"
                                            >mdi-account-edit</v-icon
                                        >
                                        Edit User
                                    </h1>
                                    <p class="text-body-1 text-medium-emphasis">
                                        Update user information, role
                                        assignments, and account status
                                    </p>
                                </div>
                                <v-btn
                                    color="primary"
                                    variant="outlined"
                                    @click="
                                        $inertia.visit(
                                            route('users.show', user.id),
                                        )
                                    "
                                    prepend-icon="mdi-eye"
                                >
                                    View Profile
                                </v-btn>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

            <v-row>
                <!-- Form Section -->
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
                                            label="New Password (optional)"
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
                                            placeholder="Leave blank to keep current password"
                                            hint="Leave blank to keep current password"
                                            persistent-hint
                                        ></v-text-field>
                                    </v-col>

                                    <!-- Password Confirmation Field -->
                                    <v-col cols="12" md="6">
                                        <v-text-field
                                            v-model="form.password_confirmation"
                                            label="Confirm New Password"
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
                                            placeholder="Confirm new password"
                                            :disabled="!form.password"
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
                                                Update User
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

                <!-- User Info Panel -->
                <v-col cols="12" lg="4">
                    <!-- Current User Info -->
                    <v-card elevation="2" class="mb-4">
                        <v-card-title class="pa-4 pb-2">
                            <v-icon class="mr-2" color="info"
                                >mdi-account-details</v-icon
                            >
                            Current User Info
                        </v-card-title>
                        <v-card-text class="pa-4">
                            <div class="text-center mb-4">
                                <v-avatar
                                    size="80"
                                    :color="user.is_active ? 'primary' : 'grey'"
                                    class="mb-3"
                                >
                                    <span
                                        class="text-h4 text-white font-weight-bold"
                                    >
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </span>
                                </v-avatar>
                                <h3 class="text-h6">{{ user.name }}</h3>
                                <p class="text-body-2 text-medium-emphasis">
                                    {{ user.email }}
                                </p>
                                <v-chip
                                    :color="getRoleColor(getUserRole())"
                                    variant="tonal"
                                    size="small"
                                    class="mt-2"
                                >
                                    {{ getUserRole() }}
                                </v-chip>
                            </div>

                            <v-divider class="my-4"></v-divider>

                            <v-list density="compact">
                                <v-list-item>
                                    <template #prepend>
                                        <v-icon color="primary" size="small"
                                            >mdi-calendar</v-icon
                                        >
                                    </template>
                                    <v-list-item-title class="text-body-2">
                                        Created:
                                        {{ formatDate(user.created_at) }}
                                    </v-list-item-title>
                                </v-list-item>
                                <v-list-item>
                                    <template #prepend>
                                        <v-icon color="orange" size="small"
                                            >mdi-calendar-edit</v-icon
                                        >
                                    </template>
                                    <v-list-item-title class="text-body-2">
                                        Updated:
                                        {{ formatDate(user.updated_at) }}
                                    </v-list-item-title>
                                </v-list-item>
                                <v-list-item>
                                    <template #prepend>
                                        <v-icon
                                            :color="
                                                user.email_verified_at
                                                    ? 'success'
                                                    : 'error'
                                            "
                                            size="small"
                                        >
                                            {{
                                                user.email_verified_at
                                                    ? "mdi-check-circle"
                                                    : "mdi-alert-circle"
                                            }}
                                        </v-icon>
                                    </template>
                                    <v-list-item-title class="text-body-2">
                                        Email:
                                        {{
                                            user.email_verified_at
                                                ? "Verified"
                                                : "Not Verified"
                                        }}
                                    </v-list-item-title>
                                </v-list-item>
                                <v-list-item>
                                    <template #prepend>
                                        <v-icon
                                            :color="
                                                user.is_active
                                                    ? 'success'
                                                    : 'error'
                                            "
                                            size="small"
                                        >
                                            {{
                                                user.is_active
                                                    ? "mdi-check-circle"
                                                    : "mdi-cancel"
                                            }}
                                        </v-icon>
                                    </template>
                                    <v-list-item-title class="text-body-2">
                                        Status:
                                        {{
                                            user.is_active
                                                ? "Active"
                                                : "Inactive"
                                        }}
                                    </v-list-item-title>
                                </v-list-item>
                            </v-list>
                        </v-card-text>
                    </v-card>

                    <!-- Edit Guidelines -->
                    <v-card elevation="2">
                        <v-card-title class="pa-4 pb-2">
                            <v-icon class="mr-2" color="warning"
                                >mdi-information</v-icon
                            >
                            Edit Guidelines
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
                                        Password field is optional - leave blank
                                        to keep current
                                    </v-list-item-title>
                                </v-list-item>
                                <v-list-item>
                                    <template #prepend>
                                        <v-icon color="success" size="small"
                                            >mdi-check</v-icon
                                        >
                                    </template>
                                    <v-list-item-title class="text-body-2">
                                        Changing role will update user
                                        permissions
                                    </v-list-item-title>
                                </v-list-item>
                                <v-list-item>
                                    <template #prepend>
                                        <v-icon color="warning" size="small"
                                            >mdi-alert</v-icon
                                        >
                                    </template>
                                    <v-list-item-title class="text-body-2">
                                        Deactivating user will prevent login
                                        access
                                    </v-list-item-title>
                                </v-list-item>
                                <v-list-item>
                                    <template #prepend>
                                        <v-icon color="info" size="small"
                                            >mdi-information</v-icon
                                        >
                                    </template>
                                    <v-list-item-title class="text-body-2">
                                        User will be notified of significant
                                        changes
                                    </v-list-item-title>
                                </v-list-item>
                            </v-list>
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
