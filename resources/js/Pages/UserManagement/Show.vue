<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref } from "vue";

const props = defineProps({
    user: Object,
});

const confirmDelete = ref(false);

const deleteUser = () => {
    confirmDelete.value = true;
};

const confirmDeleteUser = () => {
    router.delete(route("users.destroy", props.user.id), {
        onSuccess: () => {
            router.visit(route("users.index"));
        },
    });
};

const toggleUserStatus = async () => {
    try {
        const response = await fetch(
            route("users.toggle-status", props.user.id),
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
            },
        );

        if (response.ok) {
            // Refresh page to update user status
            location.reload();
        }
    } catch (error) {
        console.error("Error toggling user status:", error);
    }
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

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("id-ID", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const getUserPermissions = () => {
    if (!props.user.roles || props.user.roles.length === 0) return [];
    return props.user.roles[0].permissions || [];
};
</script>

<template>
    <AppLayout title="User Details">
        <div class="pa-0">
            <!-- Header -->
            <v-row class="mb-6">
                <v-col cols="12">
                    <v-card elevation="2">
                        <v-card-text class="pa-6">
                            <div
                                class="d-flex align-center justify-space-between"
                            >
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
                                                >mdi-account-details</v-icon
                                            >
                                            User Profile
                                        </h1>
                                        <p
                                            class="text-body-1 text-medium-emphasis"
                                        >
                                            Detailed information about
                                            {{ user.name }}
                                        </p>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <v-btn
                                        color="primary"
                                        @click="
                                            $inertia.visit(
                                                route('users.edit', user.id),
                                            )
                                        "
                                        prepend-icon="mdi-pencil"
                                    >
                                        Edit User
                                    </v-btn>
                                    <v-btn
                                        color="error"
                                        variant="outlined"
                                        @click="deleteUser"
                                        prepend-icon="mdi-delete"
                                    >
                                        Delete
                                    </v-btn>
                                </div>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

            <v-row>
                <!-- User Information -->
                <v-col cols="12" lg="8">
                    <!-- Basic Information -->
                    <v-card elevation="2" class="mb-6">
                        <v-card-title class="pa-6 pb-4">
                            <h2 class="text-h5">Basic Information</h2>
                        </v-card-title>
                        <v-card-text class="pa-6 pt-0">
                            <v-row>
                                <v-col cols="12" md="6">
                                    <div class="d-flex align-center mb-4">
                                        <v-avatar
                                            size="120"
                                            :color="
                                                user.is_active
                                                    ? 'primary'
                                                    : 'grey'
                                            "
                                            class="mr-6"
                                        >
                                            <span
                                                class="text-h2 text-white font-weight-bold"
                                            >
                                                {{
                                                    user.name
                                                        .charAt(0)
                                                        .toUpperCase()
                                                }}
                                            </span>
                                        </v-avatar>
                                        <div>
                                            <h3
                                                class="text-h4 font-weight-bold mb-2"
                                            >
                                                {{ user.name }}
                                            </h3>
                                            <p
                                                class="text-h6 text-medium-emphasis mb-3"
                                            >
                                                {{ user.email }}
                                            </p>
                                            <div
                                                class="d-flex align-center gap-2"
                                            >
                                                <v-chip
                                                    :color="
                                                        getRoleColor(
                                                            getUserRole(),
                                                        )
                                                    "
                                                    variant="tonal"
                                                    size="default"
                                                >
                                                    <v-icon start size="small"
                                                        >mdi-shield-account</v-icon
                                                    >
                                                    {{ getUserRole() }}
                                                </v-chip>
                                                <v-chip
                                                    :color="
                                                        user.is_active
                                                            ? 'success'
                                                            : 'error'
                                                    "
                                                    variant="tonal"
                                                    size="default"
                                                >
                                                    <v-icon start size="small">
                                                        {{
                                                            user.is_active
                                                                ? "mdi-check-circle"
                                                                : "mdi-cancel"
                                                        }}
                                                    </v-icon>
                                                    {{
                                                        user.is_active
                                                            ? "Active"
                                                            : "Inactive"
                                                    }}
                                                </v-chip>
                                            </div>
                                        </div>
                                    </div>
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-list density="comfortable">
                                        <v-list-item>
                                            <template #prepend>
                                                <v-icon color="primary"
                                                    >mdi-identifier</v-icon
                                                >
                                            </template>
                                            <v-list-item-title
                                                >User ID</v-list-item-title
                                            >
                                            <v-list-item-subtitle>{{
                                                user.id
                                            }}</v-list-item-subtitle>
                                        </v-list-item>
                                        <v-list-item>
                                            <template #prepend>
                                                <v-icon
                                                    :color="
                                                        user.email_verified_at
                                                            ? 'success'
                                                            : 'error'
                                                    "
                                                >
                                                    {{
                                                        user.email_verified_at
                                                            ? "mdi-check-circle"
                                                            : "mdi-alert-circle"
                                                    }}
                                                </v-icon>
                                            </template>
                                            <v-list-item-title
                                                >Email Status</v-list-item-title
                                            >
                                            <v-list-item-subtitle>
                                                {{
                                                    user.email_verified_at
                                                        ? "Verified"
                                                        : "Not Verified"
                                                }}
                                                {{
                                                    user.email_verified_at
                                                        ? `on ${formatDate(user.email_verified_at)}`
                                                        : ""
                                                }}
                                            </v-list-item-subtitle>
                                        </v-list-item>
                                        <v-list-item>
                                            <template #prepend>
                                                <v-icon color="orange"
                                                    >mdi-calendar</v-icon
                                                >
                                            </template>
                                            <v-list-item-title
                                                >Member Since</v-list-item-title
                                            >
                                            <v-list-item-subtitle>{{
                                                formatDate(user.created_at)
                                            }}</v-list-item-subtitle>
                                        </v-list-item>
                                        <v-list-item>
                                            <template #prepend>
                                                <v-icon color="info"
                                                    >mdi-calendar-edit</v-icon
                                                >
                                            </template>
                                            <v-list-item-title
                                                >Last Updated</v-list-item-title
                                            >
                                            <v-list-item-subtitle>{{
                                                formatDate(user.updated_at)
                                            }}</v-list-item-subtitle>
                                        </v-list-item>
                                    </v-list>
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-card>

                    <!-- Permissions -->
                    <v-card
                        elevation="2"
                        v-if="getUserPermissions().length > 0"
                    >
                        <v-card-title class="pa-6 pb-4">
                            <h2 class="text-h5">
                                <v-icon class="mr-2">mdi-lock-open</v-icon>
                                Permissions
                            </h2>
                        </v-card-title>
                        <v-card-text class="pa-6 pt-0">
                            <p class="text-body-2 text-medium-emphasis mb-4">
                                This user has the following permissions based on
                                their role:
                            </p>
                            <div class="d-flex flex-wrap gap-2">
                                <v-chip
                                    v-for="permission in getUserPermissions()"
                                    :key="permission.id"
                                    color="success"
                                    variant="outlined"
                                    size="small"
                                >
                                    <v-icon start size="small"
                                        >mdi-check</v-icon
                                    >
                                    {{ permission.name }}
                                </v-chip>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>

                <!-- Actions & Info Panel -->
                <v-col cols="12" lg="4">
                    <!-- Quick Actions -->
                    <v-card elevation="2" class="mb-4">
                        <v-card-title class="pa-4 pb-2">
                            <v-icon class="mr-2" color="primary"
                                >mdi-lightning-bolt</v-icon
                            >
                            Quick Actions
                        </v-card-title>
                        <v-card-text class="pa-4">
                            <div class="d-flex flex-column gap-3">
                                <v-btn
                                    color="primary"
                                    variant="flat"
                                    block
                                    @click="
                                        $inertia.visit(
                                            route('users.edit', user.id),
                                        )
                                    "
                                    prepend-icon="mdi-pencil"
                                >
                                    Edit User
                                </v-btn>
                                <v-btn
                                    :color="
                                        user.is_active ? 'warning' : 'success'
                                    "
                                    variant="outlined"
                                    block
                                    @click="toggleUserStatus"
                                    :prepend-icon="
                                        user.is_active
                                            ? 'mdi-pause'
                                            : 'mdi-play'
                                    "
                                >
                                    {{
                                        user.is_active
                                            ? "Deactivate"
                                            : "Activate"
                                    }}
                                    User
                                </v-btn>
                                <v-btn
                                    color="info"
                                    variant="outlined"
                                    block
                                    prepend-icon="mdi-email"
                                    disabled
                                >
                                    Send Welcome Email
                                </v-btn>
                                <v-btn
                                    color="secondary"
                                    variant="outlined"
                                    block
                                    prepend-icon="mdi-key"
                                    disabled
                                >
                                    Reset Password
                                </v-btn>
                            </div>
                        </v-card-text>
                    </v-card>

                    <!-- Account Statistics -->
                    <v-card elevation="2" class="mb-4">
                        <v-card-title class="pa-4 pb-2">
                            <v-icon class="mr-2" color="info"
                                >mdi-chart-line</v-icon
                            >
                            Account Statistics
                        </v-card-title>
                        <v-card-text class="pa-4">
                            <v-list density="compact">
                                <v-list-item>
                                    <template #prepend>
                                        <v-icon color="primary" size="small"
                                            >mdi-login</v-icon
                                        >
                                    </template>
                                    <v-list-item-title class="text-body-2">
                                        Last Login
                                    </v-list-item-title>
                                    <v-list-item-subtitle class="text-caption">
                                        {{
                                            user.last_login_at
                                                ? formatDate(user.last_login_at)
                                                : "Never"
                                        }}
                                    </v-list-item-subtitle>
                                </v-list-item>
                                <v-list-item>
                                    <template #prepend>
                                        <v-icon color="success" size="small"
                                            >mdi-counter</v-icon
                                        >
                                    </template>
                                    <v-list-item-title class="text-body-2">
                                        Login Count
                                    </v-list-item-title>
                                    <v-list-item-subtitle class="text-caption">
                                        {{ user.login_count || 0 }} times
                                    </v-list-item-subtitle>
                                </v-list-item>
                                <v-list-item>
                                    <template #prepend>
                                        <v-icon color="orange" size="small"
                                            >mdi-clock</v-icon
                                        >
                                    </template>
                                    <v-list-item-title class="text-body-2">
                                        Account Age
                                    </v-list-item-title>
                                    <v-list-item-subtitle class="text-caption">
                                        {{
                                            Math.floor(
                                                (new Date() -
                                                    new Date(user.created_at)) /
                                                    (1000 * 60 * 60 * 24),
                                            )
                                        }}
                                        days
                                    </v-list-item-subtitle>
                                </v-list-item>
                            </v-list>
                        </v-card-text>
                    </v-card>

                    <!-- Security Info -->
                    <v-card elevation="2">
                        <v-card-title class="pa-4 pb-2">
                            <v-icon class="mr-2" color="error"
                                >mdi-shield-alert</v-icon
                            >
                            Security Information
                        </v-card-title>
                        <v-card-text class="pa-4">
                            <v-alert
                                type="warning"
                                variant="tonal"
                                density="compact"
                                class="mb-3"
                            >
                                <template #prepend>
                                    <v-icon>mdi-information</v-icon>
                                </template>
                                Be careful when modifying user permissions or
                                deleting accounts.
                            </v-alert>

                            <v-list density="compact">
                                <v-list-item>
                                    <template #prepend>
                                        <v-icon color="success" size="small"
                                            >mdi-two-factor-authentication</v-icon
                                        >
                                    </template>
                                    <v-list-item-title class="text-body-2">
                                        Two-Factor Auth
                                    </v-list-item-title>
                                    <v-list-item-subtitle class="text-caption">
                                        {{
                                            user.two_factor_secret
                                                ? "Enabled"
                                                : "Disabled"
                                        }}
                                    </v-list-item-subtitle>
                                </v-list-item>
                                <v-list-item>
                                    <template #prepend>
                                        <v-icon color="info" size="small"
                                            >mdi-web</v-icon
                                        >
                                    </template>
                                    <v-list-item-title class="text-body-2">
                                        Sessions
                                    </v-list-item-title>
                                    <v-list-item-subtitle class="text-caption">
                                        Active sessions available
                                    </v-list-item-subtitle>
                                </v-list-item>
                            </v-list>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

            <!-- Delete Confirmation Dialog -->
            <v-dialog v-model="confirmDelete" max-width="400">
                <v-card>
                    <v-card-title>
                        <v-icon color="error" class="mr-2">mdi-alert</v-icon>
                        Confirm Deletion
                    </v-card-title>
                    <v-card-text>
                        Are you sure you want to delete user
                        <strong>{{ user.name }}</strong
                        >? This action cannot be undone and will remove all
                        associated data.
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn @click="confirmDelete = false">Cancel</v-btn>
                        <v-btn color="error" @click="confirmDeleteUser"
                            >Delete</v-btn
                        >
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </div>
    </AppLayout>
</template>

<style scoped>
.gap-2 {
    gap: 8px;
}

.gap-3 {
    gap: 12px;
}
</style>
