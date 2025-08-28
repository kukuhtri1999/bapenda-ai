<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    knowledgeBases: Object,
    filters: Object,
    categories: Object,
    types: Object,
    statuses: Object,
});

const search = ref(props.filters.search || "");
const categoryFilter = ref(props.filters.category || "");
const typeFilter = ref(props.filters.type || "");
const sourceTypeFilter = ref(props.filters.source_type || "");
const statusFilter = ref(props.filters.status || "");
const activeFilter = ref(props.filters.is_active || "");
const selectedItems = ref([]);
const bulkAction = ref("");
const confirmDelete = ref(false);
const itemToDelete = ref(null);

const headers = [
    { title: "Title", key: "title", sortable: true },
    { title: "Category", key: "category", sortable: true },
    { title: "Type", key: "type", sortable: true },
    { title: "Source", key: "source_type", sortable: true },
    { title: "Status", key: "status", sortable: true },
    { title: "Active", key: "is_active", sortable: true },
    { title: "Views", key: "view_count", sortable: true },
    { title: "Created", key: "created_at", sortable: true },
    { title: "Actions", key: "actions", sortable: false },
];

const sourceTypeItems = [
    { title: "All Sources", value: "" },
    { title: "Manual Entry", value: "manual" },
    { title: "File Upload", value: "file" },
];

const activeItems = [
    { title: "All Status", value: "" },
    { title: "Active", value: "true" },
    { title: "Inactive", value: "false" },
];

const bulkActions = [
    { title: "Select Action...", value: "" },
    { title: "Delete Selected", value: "delete" },
    { title: "Activate Selected", value: "activate" },
    { title: "Deactivate Selected", value: "deactivate" },
    { title: "Publish Selected", value: "publish" },
    { title: "Archive Selected", value: "archive" },
];

const filteredKnowledgeBases = computed(() => props.knowledgeBases.data);

const applyFilters = () => {
    router.get(
        route("knowledge-base.index"),
        {
            search: search.value,
            category: categoryFilter.value,
            type: typeFilter.value,
            source_type: sourceTypeFilter.value,
            status: statusFilter.value,
            is_active: activeFilter.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const clearFilters = () => {
    search.value = "";
    categoryFilter.value = "";
    typeFilter.value = "";
    sourceTypeFilter.value = "";
    statusFilter.value = "";
    activeFilter.value = "";
    router.get(route("knowledge-base.index"));
};

const deleteItem = (item) => {
    itemToDelete.value = item;
    confirmDelete.value = true;
};

const confirmDeleteItem = () => {
    if (itemToDelete.value) {
        router.delete(route("knowledge-base.destroy", itemToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                confirmDelete.value = false;
                itemToDelete.value = null;
            },
        });
    }
};

const toggleStatus = (item) => {
    router.post(
        route("knowledge-base.toggle-status", item.id),
        {},
        {
            preserveScroll: true,
        },
    );
};

const executeBulkAction = () => {
    if (!bulkAction.value || selectedItems.value.length === 0) return;

    router.post(
        route("knowledge-base.bulk-action"),
        {
            action: bulkAction.value,
            ids: selectedItems.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedItems.value = [];
                bulkAction.value = "";
            },
        },
    );
};

const getStatusColor = (status) => {
    const colors = {
        published: "success",
        draft: "warning",
        archived: "grey",
    };
    return colors[status] || "grey";
};

const getSourceIcon = (sourceType) => {
    return sourceType === "file" ? "mdi-file-document" : "mdi-keyboard";
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
    <AppLayout title="Knowledge Base Management">
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
                                            >mdi-book-open-variant</v-icon
                                        >
                                        Knowledge Base Management
                                    </h1>
                                    <p class="text-body-1 text-medium-emphasis">
                                        Manage AI knowledge base entries and
                                        content
                                    </p>
                                </v-col>
                                <v-col cols="12" md="6" class="text-right">
                                    <v-btn
                                        color="primary"
                                        size="large"
                                        @click="
                                            $inertia.visit(
                                                route('knowledge-base.create'),
                                            )
                                        "
                                        prepend-icon="mdi-plus"
                                    >
                                        Add New Entry
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
                                <v-col cols="12" md="3">
                                    <v-text-field
                                        v-model="search"
                                        label="Search..."
                                        prepend-inner-icon="mdi-magnify"
                                        variant="outlined"
                                        density="compact"
                                        @keyup.enter="applyFilters"
                                        clearable
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" md="2">
                                    <v-select
                                        v-model="categoryFilter"
                                        :items="[
                                            {
                                                title: 'All Categories',
                                                value: '',
                                            },
                                            ...Object.entries(categories).map(
                                                ([key, value]) => ({
                                                    title: value,
                                                    value: key,
                                                }),
                                            ),
                                        ]"
                                        label="Category"
                                        variant="outlined"
                                        density="compact"
                                    ></v-select>
                                </v-col>
                                <v-col cols="12" md="2">
                                    <v-select
                                        v-model="typeFilter"
                                        :items="[
                                            { title: 'All Types', value: '' },
                                            ...Object.entries(types).map(
                                                ([key, value]) => ({
                                                    title: value,
                                                    value: key,
                                                }),
                                            ),
                                        ]"
                                        label="Type"
                                        variant="outlined"
                                        density="compact"
                                    ></v-select>
                                </v-col>
                                <v-col cols="12" md="2">
                                    <v-select
                                        v-model="sourceTypeFilter"
                                        :items="sourceTypeItems"
                                        label="Source"
                                        variant="outlined"
                                        density="compact"
                                    ></v-select>
                                </v-col>
                                <v-col cols="12" md="2">
                                    <v-select
                                        v-model="statusFilter"
                                        :items="[
                                            { title: 'All Status', value: '' },
                                            ...Object.entries(statuses).map(
                                                ([key, value]) => ({
                                                    title: value,
                                                    value: key,
                                                }),
                                            ),
                                        ]"
                                        label="Status"
                                        variant="outlined"
                                        density="compact"
                                    ></v-select>
                                </v-col>
                                <v-col cols="12" md="1">
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

            <!-- Bulk Actions -->
            <v-row class="mb-4" v-if="selectedItems.length > 0">
                <v-col cols="12">
                    <v-card elevation="2" color="blue-grey-lighten-5">
                        <v-card-text>
                            <v-row align="center">
                                <v-col cols="auto">
                                    <span
                                        class="text-body-1 font-weight-medium"
                                    >
                                        {{ selectedItems.length }} items
                                        selected
                                    </span>
                                </v-col>
                                <v-col cols="auto">
                                    <v-select
                                        v-model="bulkAction"
                                        :items="bulkActions"
                                        variant="outlined"
                                        density="compact"
                                        hide-details
                                        style="min-width: 200px"
                                    ></v-select>
                                </v-col>
                                <v-col cols="auto">
                                    <v-btn
                                        color="primary"
                                        @click="executeBulkAction"
                                        :disabled="!bulkAction"
                                    >
                                        Execute
                                    </v-btn>
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

            <!-- Knowledge Base Table -->
            <v-row>
                <v-col cols="12">
                    <v-card elevation="2">
                        <v-data-table
                            v-model="selectedItems"
                            :headers="headers"
                            :items="filteredKnowledgeBases"
                            :items-per-page="15"
                            class="elevation-0"
                            show-select
                            item-value="id"
                        >
                            <!-- Title Column -->
                            <template #item.title="{ item }">
                                <div class="d-flex align-center">
                                    <v-icon
                                        :icon="getSourceIcon(item.source_type)"
                                        :color="
                                            item.source_type === 'file'
                                                ? 'blue'
                                                : 'green'
                                        "
                                        class="mr-3"
                                        size="small"
                                    ></v-icon>
                                    <div>
                                        <div class="font-weight-medium">
                                            {{ item.title }}
                                        </div>
                                        <small
                                            class="text-medium-emphasis"
                                            v-if="item.excerpt"
                                        >
                                            {{
                                                item.excerpt.substring(0, 100)
                                            }}...
                                        </small>
                                    </div>
                                </div>
                            </template>

                            <!-- Category Column -->
                            <template #item.category="{ item }">
                                <v-chip
                                    :color="
                                        item.category === 'pajak'
                                            ? 'blue'
                                            : item.category === 'stnk'
                                              ? 'green'
                                              : 'grey'
                                    "
                                    variant="tonal"
                                    size="small"
                                >
                                    {{
                                        categories[item.category] ||
                                        item.category
                                    }}
                                </v-chip>
                            </template>

                            <!-- Type Column -->
                            <template #item.type="{ item }">
                                <v-chip
                                    :color="
                                        item.type === 'faq'
                                            ? 'orange'
                                            : item.type === 'sop'
                                              ? 'purple'
                                              : 'blue-grey'
                                    "
                                    variant="tonal"
                                    size="small"
                                >
                                    {{ types[item.type] || item.type }}
                                </v-chip>
                            </template>

                            <!-- Source Type Column -->
                            <template #item.source_type="{ item }">
                                <v-chip
                                    :color="
                                        item.source_type === 'file'
                                            ? 'blue'
                                            : 'green'
                                    "
                                    variant="outlined"
                                    size="small"
                                >
                                    <v-icon
                                        :icon="getSourceIcon(item.source_type)"
                                        class="mr-1"
                                        size="small"
                                    ></v-icon>
                                    {{
                                        item.source_type === "file"
                                            ? "File"
                                            : "Manual"
                                    }}
                                </v-chip>
                            </template>

                            <!-- Status Column -->
                            <template #item.status="{ item }">
                                <v-chip
                                    :color="getStatusColor(item.status)"
                                    variant="tonal"
                                    size="small"
                                >
                                    {{ statuses[item.status] || item.status }}
                                </v-chip>
                            </template>

                            <!-- Active Column -->
                            <template #item.is_active="{ item }">
                                <v-switch
                                    :model-value="item.is_active"
                                    @change="toggleStatus(item)"
                                    color="success"
                                    density="compact"
                                    hide-details
                                ></v-switch>
                            </template>

                            <!-- Views Column -->
                            <template #item.view_count="{ item }">
                                <v-chip
                                    color="info"
                                    variant="outlined"
                                    size="small"
                                >
                                    <v-icon
                                        icon="mdi-eye"
                                        class="mr-1"
                                        size="small"
                                    ></v-icon>
                                    {{ item.view_count || 0 }}
                                </v-chip>
                            </template>

                            <!-- Created Date Column -->
                            <template #item.created_at="{ item }">
                                <div>
                                    {{ formatDate(item.created_at) }}
                                    <div
                                        class="text-caption text-medium-emphasis"
                                        v-if="item.creator"
                                    >
                                        by {{ item.creator.name }}
                                    </div>
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
                                                route(
                                                    'knowledge-base.show',
                                                    item.id,
                                                ),
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
                                                route(
                                                    'knowledge-base.edit',
                                                    item.id,
                                                ),
                                            )
                                        "
                                    ></v-btn>
                                    <v-btn
                                        v-if="item.file_path"
                                        size="small"
                                        color="blue"
                                        variant="tonal"
                                        icon="mdi-download"
                                        @click="
                                            window.open(
                                                route(
                                                    'knowledge-base.download',
                                                    item.id,
                                                ),
                                            )
                                        "
                                    ></v-btn>
                                    <v-btn
                                        size="small"
                                        color="error"
                                        variant="tonal"
                                        icon="mdi-delete"
                                        @click="deleteItem(item)"
                                    ></v-btn>
                                </div>
                            </template>

                            <!-- No Data -->
                            <template #no-data>
                                <div class="text-center pa-6">
                                    <v-icon size="64" color="grey"
                                        >mdi-book-open-variant</v-icon
                                    >
                                    <h3 class="text-h6 mt-3">
                                        No Knowledge Base Entries Found
                                    </h3>
                                    <p class="text-body-2 text-medium-emphasis">
                                        Try adjusting your search criteria or
                                        create a new entry.
                                    </p>
                                </div>
                            </template>
                        </v-data-table>

                        <!-- Pagination -->
                        <v-divider></v-divider>
                        <div class="pa-4 d-flex justify-center">
                            <v-pagination
                                :model-value="knowledgeBases.current_page"
                                :length="knowledgeBases.last_page"
                                @update:model-value="
                                    (page) =>
                                        router.get(
                                            route('knowledge-base.index'),
                                            { ...filters, page },
                                        )
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
                        Are you sure you want to delete "<strong>{{
                            itemToDelete?.title
                        }}</strong
                        >"? This action cannot be undone.
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn @click="confirmDelete = false">Cancel</v-btn>
                        <v-btn color="error" @click="confirmDeleteItem"
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
