<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, onMounted } from "vue";

const stats = ref([
    {
        title: "Total Taxpayers",
        value: "12,345",
        change: "+12%",
        changeColor: "green",
        icon: "mdi-account-group",
        color: "primary",
    },
    {
        title: "Active Cases",
        value: "89",
        change: "-5%",
        changeColor: "red",
        icon: "mdi-file-document-multiple",
        color: "orange",
    },
    {
        title: "AI Conversations",
        value: "1,567",
        change: "+23%",
        changeColor: "green",
        icon: "mdi-robot",
        color: "blue",
    },
    {
        title: "Revenue Collected",
        value: "Rp 2.4B",
        change: "+8%",
        changeColor: "green",
        icon: "mdi-currency-usd",
        color: "success",
    },
]);

const recentActivities = ref([
    {
        id: 1,
        type: "chat",
        title: "New AI chat session started",
        description: "Taxpayer ID: WP123456 initiated a consultation",
        time: "5 minutes ago",
        icon: "mdi-chat",
        color: "blue",
    },
    {
        id: 2,
        type: "user",
        title: "New user registered",
        description: "John Doe joined the system",
        time: "12 minutes ago",
        icon: "mdi-account-plus",
        color: "green",
    },
    {
        id: 3,
        type: "document",
        title: "Tax document processed",
        description: "Document ABC-123 has been reviewed",
        time: "1 hour ago",
        icon: "mdi-file-check",
        color: "orange",
    },
    {
        id: 4,
        type: "payment",
        title: "Payment received",
        description: "Rp 125,000,000 from taxpayer WP789012",
        time: "2 hours ago",
        icon: "mdi-cash",
        color: "success",
    },
]);

const quickActions = ref([
    {
        title: "Manage Users",
        description: "Add, edit, or remove system users",
        icon: "mdi-account-cog",
        color: "primary",
        route: "users.index",
    },
    {
        title: "View Chat Logs",
        description: "Monitor AI customer service interactions",
        icon: "mdi-chat-processing",
        color: "blue",
        route: "chat",
    },
    {
        title: "Generate Reports",
        description: "Create analytical reports and insights",
        icon: "mdi-chart-line",
        color: "orange",
        route: "reports.index",
    },
    {
        title: "System Settings",
        description: "Configure application preferences",
        icon: "mdi-cog",
        color: "grey",
        route: "settings.index",
    },
]);

onMounted(() => {
    // Here you can fetch real data from API
    console.log("Dashboard mounted");
});
</script>

<template>
    <AppLayout title="Dashboard">
        <div class="pa-0">
            <!-- Welcome Section -->
            <v-row class="mb-6">
                <v-col cols="12">
                    <v-card
                        class="bg-gradient-to-r from-primary to-blue-600 text-white"
                        elevation="8"
                    >
                        <v-card-text class="pa-8">
                            <v-row align="center">
                                <v-col cols="12" md="8">
                                    <h1 class="text-h4 font-weight-bold mb-2">
                                        Welcome back,
                                        {{ $page.props.auth.user.name }}!
                                    </h1>
                                    <p class="text-h6 font-weight-light mb-4">
                                        Here's what's happening with your tax
                                        management system today.
                                    </p>
                                    <v-chip
                                        class="ma-1"
                                        color="rgba(255,255,255,0.2)"
                                        text-color="white"
                                    >
                                        <v-icon left>mdi-clock</v-icon>
                                        {{
                                            new Date().toLocaleDateString(
                                                "id-ID",
                                                {
                                                    weekday: "long",
                                                    year: "numeric",
                                                    month: "long",
                                                    day: "numeric",
                                                },
                                            )
                                        }}
                                    </v-chip>
                                </v-col>
                                <v-col cols="12" md="4" class="text-center">
                                    <v-icon size="120" class="opacity-50"
                                        >mdi-city-variant</v-icon
                                    >
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

            <!-- Statistics Cards -->
            <v-row class="mb-6">
                <v-col
                    v-for="stat in stats"
                    :key="stat.title"
                    cols="12"
                    sm="6"
                    lg="3"
                >
                    <v-card elevation="4" class="h-100">
                        <v-card-text>
                            <v-row align="center">
                                <v-col cols="8">
                                    <p
                                        class="text-body-2 text-medium-emphasis mb-1"
                                    >
                                        {{ stat.title }}
                                    </p>
                                    <h2 class="text-h4 font-weight-bold">
                                        {{ stat.value }}
                                    </h2>
                                    <v-chip
                                        :color="stat.changeColor"
                                        size="small"
                                        variant="tonal"
                                        class="mt-2"
                                    >
                                        {{ stat.change }} from last month
                                    </v-chip>
                                </v-col>
                                <v-col cols="4" class="text-center">
                                    <v-avatar
                                        size="60"
                                        :color="stat.color"
                                        variant="tonal"
                                    >
                                        <v-icon size="30">{{
                                            stat.icon
                                        }}</v-icon>
                                    </v-avatar>
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

            <!-- Content Row -->
            <v-row>
                <!-- Quick Actions -->
                <v-col cols="12" lg="8">
                    <v-card elevation="4">
                        <v-card-title class="d-flex align-center">
                            <v-icon class="mr-2">mdi-lightning-bolt</v-icon>
                            Quick Actions
                        </v-card-title>
                        <v-card-text>
                            <v-row>
                                <v-col
                                    v-for="action in quickActions"
                                    :key="action.title"
                                    cols="12"
                                    sm="6"
                                >
                                    <v-card
                                        variant="outlined"
                                        hover
                                        class="h-100 cursor-pointer"
                                        @click="
                                            $inertia.visit(route(action.route))
                                        "
                                    >
                                        <v-card-text class="text-center pa-6">
                                            <v-avatar
                                                size="60"
                                                :color="action.color"
                                                variant="tonal"
                                                class="mb-4"
                                            >
                                                <v-icon size="30">{{
                                                    action.icon
                                                }}</v-icon>
                                            </v-avatar>
                                            <h3
                                                class="text-h6 font-weight-medium mb-2"
                                            >
                                                {{ action.title }}
                                            </h3>
                                            <p
                                                class="text-body-2 text-medium-emphasis"
                                            >
                                                {{ action.description }}
                                            </p>
                                        </v-card-text>
                                    </v-card>
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-card>
                </v-col>

                <!-- Recent Activities -->
                <v-col cols="12" lg="4">
                    <v-card elevation="4" class="h-100">
                        <v-card-title class="d-flex align-center">
                            <v-icon class="mr-2">mdi-history</v-icon>
                            Recent Activities
                        </v-card-title>
                        <v-card-text class="pa-0">
                            <v-list>
                                <v-list-item
                                    v-for="activity in recentActivities"
                                    :key="activity.id"
                                    class="px-4"
                                >
                                    <template #prepend>
                                        <v-avatar
                                            size="40"
                                            :color="activity.color"
                                            variant="tonal"
                                        >
                                            <v-icon>{{ activity.icon }}</v-icon>
                                        </v-avatar>
                                    </template>
                                    <v-list-item-title
                                        class="font-weight-medium"
                                    >
                                        {{ activity.title }}
                                    </v-list-item-title>
                                    <v-list-item-subtitle class="mt-1">
                                        {{ activity.description }}
                                    </v-list-item-subtitle>
                                    <template #append>
                                        <v-list-item-action>
                                            <small
                                                class="text-caption text-medium-emphasis"
                                            >
                                                {{ activity.time }}
                                            </small>
                                        </v-list-item-action>
                                    </template>
                                </v-list-item>
                            </v-list>
                        </v-card-text>
                        <v-card-actions>
                            <v-btn variant="text" color="primary" block>
                                View All Activities
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-col>
            </v-row>
        </div>
    </AppLayout>
</template>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}

.bg-gradient-to-r {
    background: linear-gradient(
        to right,
        var(--v-theme-primary),
        var(--v-theme-blue-600)
    );
}
</style>
