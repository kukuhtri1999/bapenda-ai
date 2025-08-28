<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    users: Object,
    roles: Array,
    filters: Object,
});

const search = ref(props.filters.search || "");
const roleFilter = ref(props.filters.role || "");
const statusFilter = ref(props.filters.status || "");
const selectedUsers = ref([]);
const confirmDelete = ref(false);
const userToDelete = ref(null);

const headers = [
    { title: "Name", key: "name", sortable: true },
    { title: "Email", key: "email", sortable: true },
    { title: "Role", key: "roles", sortable: false },
    { title: "Status", key: "is_active", sortable: true },
    { title: "Created", key: "created_at", sortable: true },
    { title: "Actions", key: "actions", sortable: false },
];

const statusItems = [
    { title: "All", value: "" },
    { title: "Active", value: "active" },
    { title: "Inactive", value: "inactive" },
];

const filteredUsers = computed(() => props.users.data);

const applyFilters = () => {
    router.get(
        route("users.index"),
        {
            search: search.value,
            role: roleFilter.value,
            status: statusFilter.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const clearFilters = () => {
    search.value = "";
    roleFilter.value = "";
    statusFilter.value = "";
    router.get(route("users.index"));
};

const deleteUser = (user) => {
    userToDelete.value = user;
    confirmDelete.value = true;
};

const confirmDeleteUser = () => {
    if (userToDelete.value) {
        router.delete(route("users.destroy", userToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                confirmDelete.value = false;
                userToDelete.value = null;
            },
        });
    }
};

const toggleUserStatus = async (user) => {
    try {
        const response = await fetch(route("users.toggle-status", user.id), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
        });

        if (response.ok) {
            // Refresh page to update user status
            router.reload({ only: ["users"] });
        }
    } catch (error) {
        console.error("Error toggling user status:", error);
    }
};

const getUserRole = (user) => {
    return user.roles && user.roles.length > 0 ? user.roles[0].name : "No Role";
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
        month: "short",
        day: "numeric",
    });
};
</script>

<template>
    <AppLayout title="User Management">
        <div class="pa-0">
            <!-- Header -->
            <v-row class="mb-6">
                <v-col cols="12">
                    <v-card elevation="2">
                        <v-card-text class="pa-6">
                            <v-row align="center">
                                <v-col cols="12" md="6">
                                    <h1
                                        class="text-h4 font-weight-bold text-primary mb-2"
                                    >
                                        <v-icon class="mr-3" size="36"
                                            >mdi-account-group</v-icon
                                        >
                                        User Management
                                    </h1>
                                    <p class="text-body-1 text-medium-emphasis">
                                        Manage system users, roles, and
                                        permissions
                                    </p>
                                </v-col>
                                <v-col cols="12" md="6" class="text-right">
                                    <v-btn
                                        color="primary"
                                        size="large"
                                        @click="
                                            $inertia.visit(
                                                route('users.create'),
                                            )
                                        "
                                        prepend-icon="mdi-plus"
                                    >
                                        Add New User
                                    </v-btn>
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

            <!-- Filters -->
            <v-row class="mb-4">
                <v-col cols="12">
                    <v-card elevation="2">
                        <v-card-text>
                            <v-row>
                                <v-col cols="12" md="4">
                                    <v-text-field
                                        v-model="search"
                                        label="Search users..."
                                        prepend-inner-icon="mdi-magnify"
                                        variant="outlined"
                                        density="compact"
                                        @keyup.enter="applyFilters"
                                        clearable
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" md="3">
                                    <v-select
                                        v-model="roleFilter"
                                        :items="[
                                            { title: 'All Roles', value: '' },
                                            ...roles.map((r) => ({
                                                title: r.name,
                                                value: r.name,
                                            })),
                                        ]"
                                        label="Filter by Role"
                                        variant="outlined"
                                        density="compact"
                                    ></v-select>
                                </v-col>
                                <v-col cols="12" md="3">
                                    <v-select
                                        v-model="statusFilter"
                                        :items="statusItems"
                                        label="Filter by Status"
                                        variant="outlined"
                                        density="compact"
                                    ></v-select>
                                </v-col>
                                <v-col cols="12" md="2">
                                    <v-btn
                                        color="primary"
                                        variant="flat"
                                        @click="applyFilters"
                                        block
                                    >
                                        Apply
                                    </v-btn>
                                    <v-btn
                                        variant="outlined"
                                        @click="clearFilters"
                                        block
                                        class="mt-2"
                                    >
                                        Clear
                                    </v-btn>
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

            <!-- Users Table -->
            <v-row>
                <v-col cols="12">
                    <v-card elevation="2">
                        <v-data-table
                            :headers="headers"
                            :items="filteredUsers"
                            :items-per-page="15"
                            class="elevation-0"
                        >
                            <!-- Name Column -->
                            <template #item.name="{ item }">
                                <div class="d-flex align-center">
                                    <v-avatar
                                        size="40"
                                        class="mr-3"
                                        :color="
                                            item.is_active ? 'primary' : 'grey'
                                        "
                                    >
                                        <span
                                            class="text-white font-weight-bold"
                                        >
                                            {{
                                                item.name
                                                    .charAt(0)
                                                    .toUpperCase()
                                            }}
                                        </span>
                                    </v-avatar>
                                    <div>
                                        <div class="font-weight-medium">
                                            {{ item.name }}
                                        </div>
                                        <small class="text-medium-emphasis"
                                            >ID: {{ item.id }}</small
                                        >
                                    </div>
                                </div>
                            </template>

                            <!-- Email Column -->
                            <template #item.email="{ item }">
                                <div>
                                    <div>{{ item.email }}</div>
                                    <small class="text-medium-emphasis">
                                        <v-icon
                                            size="12"
                                            :color="
                                                item.email_verified_at
                                                    ? 'success'
                                                    : 'error'
                                            "
                                        >
                                            {{
                                                item.email_verified_at
                                                    ? "mdi-check-circle"
                                                    : "mdi-alert-circle"
                                            }}
                                        </v-icon>
                                        {{
                                            item.email_verified_at
                                                ? "Verified"
                                                : "Not Verified"
                                        }}
                                    </small>
                                </div>
                            </template>

                            <!-- Role Column -->
                            <template #item.roles="{ item }">
                                <v-chip
                                    :color="getRoleColor(getUserRole(item))"
                                    variant="tonal"
                                    size="small"
                                >
                                    {{ getUserRole(item) }}
                                </v-chip>
                            </template>

                            <!-- Status Column -->
                            <template #item.is_active="{ item }">
                                <v-switch
                                    :model-value="item.is_active"
                                    @change="toggleUserStatus(item)"
                                    color="success"
                                    density="compact"
                                    hide-details
                                ></v-switch>
                            </template>

                            <!-- Created Date Column -->
                            <template #item.created_at="{ item }">
                                <div>
                                    {{ formatDate(item.created_at) }}
                                </div>
                            </template>

                            <!-- Actions Column -->
                            <template #item.actions="{ item }">
                                <div class="d-flex gap-2">
                                    <v-btn
                                        size="small"
                                        color="primary"
                                        variant="tonal"
                                        icon="mdi-eye"
                                        @click="
                                            $inertia.visit(
                                                route('users.show', item.id),
                                            )
                                        "
                                    ></v-btn>
                                    <v-btn
                                        size="small"
                                        color="orange"
                                        variant="tonal"
                                        icon="mdi-pencil"
                                        @click="
                                            $inertia.visit(
                                                route('users.edit', item.id),
                                            )
                                        "
                                    ></v-btn>
                                    <v-btn
                                        size="small"
                                        color="error"
                                        variant="tonal"
                                        icon="mdi-delete"
                                        @click="deleteUser(item)"
                                    ></v-btn>
                                </div>
                            </template>

                            <!-- No Data -->
                            <template #no-data>
                                <div class="text-center pa-6">
                                    <v-icon size="64" color="grey"
                                        >mdi-account-off</v-icon
                                    >
                                    <h3 class="text-h6 mt-3">No Users Found</h3>
                                    <p class="text-body-2 text-medium-emphasis">
                                        Try adjusting your search criteria or
                                        create a new user.
                                    </p>
                                </div>
                            </template>
                        </v-data-table>

                        <!-- Pagination -->
                        <v-divider></v-divider>
                        <div class="pa-4 d-flex justify-center">
                            <v-pagination
                                :model-value="users.current_page"
                                :length="users.last_page"
                                @update:model-value="
                                    (page) =>
                                        router.get(route('users.index'), {
                                            ...filters,
                                            page,
                                        })
                                "
                                total-visible="7"
                            ></v-pagination>
                        </div>
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
                        <strong>{{ userToDelete?.name }}</strong
                        >? This action cannot be undone.
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
</style>
