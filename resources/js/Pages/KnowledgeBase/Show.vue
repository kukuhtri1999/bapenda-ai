<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    knowledgeBase: Object,
    categories: Object,
    types: Object,
    statuses: Object,
});

const confirmDelete = ref(false);
const showContent = ref(true);

const deleteItem = () => {
    router.delete(route("knowledge-base.destroy", props.knowledgeBase.id));
};

const toggleStatus = () => {
    router.post(
        route("knowledge-base.toggle-status", props.knowledgeBase.id),
        {},
        {
            preserveScroll: true,
        },
    );
};

const downloadFile = () => {
    window.open(route("knowledge-base.download", props.knowledgeBase.id));
};

const getStatusColor = (status) => {
    const colors = {
        published: "success",
        draft: "warning",
        archived: "grey",
    };
    return colors[status] || "grey";
};

const getPriorityColor = (priority) => {
    const colors = {
        1: "green",
        2: "blue",
        3: "orange",
        4: "red",
    };
    return colors[priority] || "grey";
};

const getPriorityText = (priority) => {
    const texts = {
        1: "Low",
        2: "Normal",
        3: "High",
        4: "Critical",
    };
    return texts[priority] || "Unknown";
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

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text).then(() => {
        // Could add a toast notification here
    });
};
</script>

<template>
    <AppLayout :title="knowledgeBase.title">
        <div class="pa-0">
            <!-- Header -->
            <v-row class="mb-6">
                <v-col cols="12">
                    <v-card elevation="2">
                        <v-card-text class="pa-6">
                            <v-row align="center">
                                <v-col cols="12" md="8">
                                    <div class="d-flex align-center mb-3">
                                        <v-chip
                                            :color="
                                                knowledgeBase.source_type ===
                                                'file'
                                                    ? 'blue'
                                                    : 'green'
                                            "
                                            variant="tonal"
                                            size="small"
                                            class="mr-3"
                                        >
                                            <v-icon
                                                :icon="
                                                    knowledgeBase.source_type ===
                                                    'file'
                                                        ? 'mdi-file-document'
                                                        : 'mdi-keyboard'
                                                "
                                                class="mr-1"
                                                size="small"
                                            ></v-icon>
                                            {{
                                                knowledgeBase.source_type ===
                                                "file"
                                                    ? "File"
                                                    : "Manual"
                                            }}
                                        </v-chip>
                                        <v-chip
                                            :color="
                                                getStatusColor(
                                                    knowledgeBase.status,
                                                )
                                            "
                                            variant="tonal"
                                            size="small"
                                            class="mr-3"
                                        >
                                            {{
                                                statuses[
                                                    knowledgeBase.status
                                                ] || knowledgeBase.status
                                            }}
                                        </v-chip>
                                        <v-chip
                                            :color="
                                                knowledgeBase.is_active
                                                    ? 'success'
                                                    : 'error'
                                            "
                                            variant="tonal"
                                            size="small"
                                        >
                                            {{
                                                knowledgeBase.is_active
                                                    ? "Active"
                                                    : "Inactive"
                                            }}
                                        </v-chip>
                                    </div>
                                    <h1
                                        class="text-h4 font-weight-bold text-primary mb-2"
                                    >
                                        {{ knowledgeBase.title }}
                                    </h1>
                                    <p
                                        class="text-body-1 text-medium-emphasis mb-3"
                                        v-if="knowledgeBase.excerpt"
                                    >
                                        {{ knowledgeBase.excerpt }}
                                    </p>
                                    <div class="d-flex align-center gap-4">
                                        <v-chip variant="outlined" size="small">
                                            <v-icon class="mr-1" size="small"
                                                >mdi-eye</v-icon
                                            >
                                            {{
                                                knowledgeBase.view_count || 0
                                            }}
                                            views
                                        </v-chip>
                                        <v-chip variant="outlined" size="small">
                                            <v-icon class="mr-1" size="small"
                                                >mdi-calendar</v-icon
                                            >
                                            {{
                                                formatDate(
                                                    knowledgeBase.created_at,
                                                )
                                            }}
                                        </v-chip>
                                        <v-chip
                                            v-if="knowledgeBase.creator"
                                            variant="outlined"
                                            size="small"
                                        >
                                            <v-icon class="mr-1" size="small"
                                                >mdi-account</v-icon
                                            >
                                            {{ knowledgeBase.creator.name }}
                                        </v-chip>
                                    </div>
                                </v-col>
                                <v-col cols="12" md="4" class="text-right">
                                    <v-btn
                                        variant="outlined"
                                        @click="
                                            $inertia.visit(
                                                route('knowledge-base.index'),
                                            )
                                        "
                                        class="mr-2"
                                    >
                                        <v-icon class="mr-2"
                                            >mdi-arrow-left</v-icon
                                        >
                                        Back to List
                                    </v-btn>
                                    <v-menu>
                                        <template #activator="{ props }">
                                            <v-btn
                                                color="primary"
                                                v-bind="props"
                                            >
                                                Actions
                                                <v-icon class="ml-2"
                                                    >mdi-chevron-down</v-icon
                                                >
                                            </v-btn>
                                        </template>
                                        <v-list>
                                            <v-list-item
                                                @click="
                                                    $inertia.visit(
                                                        route(
                                                            'knowledge-base.edit',
                                                            knowledgeBase.id,
                                                        ),
                                                    )
                                                "
                                            >
                                                <template #prepend>
                                                    <v-icon>mdi-pencil</v-icon>
                                                </template>
                                                <v-list-item-title
                                                    >Edit</v-list-item-title
                                                >
                                            </v-list-item>
                                            <v-list-item @click="toggleStatus">
                                                <template #prepend>
                                                    <v-icon>{{
                                                        knowledgeBase.is_active
                                                            ? "mdi-eye-off"
                                                            : "mdi-eye"
                                                    }}</v-icon>
                                                </template>
                                                <v-list-item-title>
                                                    {{
                                                        knowledgeBase.is_active
                                                            ? "Deactivate"
                                                            : "Activate"
                                                    }}
                                                </v-list-item-title>
                                            </v-list-item>
                                            <v-list-item
                                                v-if="knowledgeBase.file_path"
                                                @click="downloadFile"
                                            >
                                                <template #prepend>
                                                    <v-icon
                                                        >mdi-download</v-icon
                                                    >
                                                </template>
                                                <v-list-item-title
                                                    >Download
                                                    File</v-list-item-title
                                                >
                                            </v-list-item>
                                            <v-divider></v-divider>
                                            <v-list-item
                                                @click="confirmDelete = true"
                                                class="text-error"
                                            >
                                                <template #prepend>
                                                    <v-icon color="error"
                                                        >mdi-delete</v-icon
                                                    >
                                                </template>
                                                <v-list-item-title
                                                    >Delete</v-list-item-title
                                                >
                                            </v-list-item>
                                        </v-list>
                                    </v-menu>
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

            <v-row>
                <!-- Main Content -->
                <v-col cols="12" md="8">
                    <!-- Content -->
                    <v-card elevation="2" class="mb-4">
                        <v-card-title
                            class="bg-primary text-white d-flex align-center justify-space-between"
                        >
                            <div class="d-flex align-center">
                                <v-icon class="mr-2"
                                    >mdi-file-document-outline</v-icon
                                >
                                Content
                            </div>
                            <v-btn
                                icon
                                variant="text"
                                @click="showContent = !showContent"
                                color="white"
                            >
                                <v-icon>{{
                                    showContent
                                        ? "mdi-chevron-up"
                                        : "mdi-chevron-down"
                                }}</v-icon>
                            </v-btn>
                        </v-card-title>
                        <v-expand-transition>
                            <v-card-text v-show="showContent" class="pa-6">
                                <div
                                    v-if="knowledgeBase.content"
                                    class="content-display"
                                >
                                    <div
                                        v-html="
                                            knowledgeBase.content.replace(
                                                /\n/g,
                                                '<br>',
                                            )
                                        "
                                    ></div>
                                </div>
                                <div
                                    v-else-if="
                                        knowledgeBase.source_type === 'file'
                                    "
                                    class="text-center pa-8"
                                >
                                    <v-icon size="64" color="blue"
                                        >mdi-file-document</v-icon
                                    >
                                    <h3 class="text-h6 mt-3">File Content</h3>
                                    <p class="text-body-2 text-medium-emphasis">
                                        This entry is based on an uploaded file.
                                        <span v-if="knowledgeBase.file_path">
                                            <br />Download the file to view the
                                            content.
                                        </span>
                                    </p>
                                    <v-btn
                                        v-if="knowledgeBase.file_path"
                                        color="blue"
                                        variant="outlined"
                                        @click="downloadFile"
                                        class="mt-3"
                                    >
                                        <v-icon class="mr-2"
                                            >mdi-download</v-icon
                                        >
                                        Download File
                                    </v-btn>
                                </div>
                                <div v-else class="text-center pa-8">
                                    <v-icon size="64" color="grey"
                                        >mdi-text</v-icon
                                    >
                                    <h3 class="text-h6 mt-3">No Content</h3>
                                    <p class="text-body-2 text-medium-emphasis">
                                        This entry doesn't have any content yet.
                                    </p>
                                </div>
                            </v-card-text>
                        </v-expand-transition>
                    </v-card>

                    <!-- File Information -->
                    <v-card
                        v-if="knowledgeBase.file_path"
                        elevation="2"
                        class="mb-4"
                    >
                        <v-card-title class="bg-blue text-white">
                            <v-icon class="mr-2">mdi-file-document</v-icon>
                            File Information
                        </v-card-title>
                        <v-card-text class="pa-6">
                            <v-row>
                                <v-col cols="12" md="6">
                                    <div class="mb-3">
                                        <strong>File Name:</strong>
                                        <div class="mt-1">
                                            {{
                                                knowledgeBase.file_name ||
                                                "Unknown"
                                            }}
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <strong>File Type:</strong>
                                        <div class="mt-1">
                                            <v-chip
                                                size="small"
                                                variant="outlined"
                                            >
                                                {{
                                                    knowledgeBase.file_type?.toUpperCase() ||
                                                    "Unknown"
                                                }}
                                            </v-chip>
                                        </div>
                                    </div>
                                </v-col>
                                <v-col cols="12" md="6">
                                    <div class="mb-3">
                                        <strong>File Size:</strong>
                                        <div class="mt-1">
                                            {{
                                                knowledgeBase.file_size
                                                    ? (
                                                          knowledgeBase.file_size /
                                                          1024 /
                                                          1024
                                                      ).toFixed(2) + " MB"
                                                    : "Unknown"
                                            }}
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Actions:</strong>
                                        <div class="mt-2">
                                            <v-btn
                                                color="blue"
                                                variant="outlined"
                                                size="small"
                                                @click="downloadFile"
                                            >
                                                <v-icon class="mr-1"
                                                    >mdi-download</v-icon
                                                >
                                                Download
                                            </v-btn>
                                        </div>
                                    </div>
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-card>

                    <!-- Tags -->
                    <v-card
                        v-if="
                            knowledgeBase.tags && knowledgeBase.tags.length > 0
                        "
                        elevation="2"
                    >
                        <v-card-title class="bg-green text-white">
                            <v-icon class="mr-2">mdi-tag-multiple</v-icon>
                            Tags
                        </v-card-title>
                        <v-card-text class="pa-6">
                            <div class="d-flex flex-wrap gap-2">
                                <v-chip
                                    v-for="tag in knowledgeBase.tags"
                                    :key="tag"
                                    variant="tonal"
                                    size="small"
                                >
                                    <v-icon class="mr-1" size="small"
                                        >mdi-tag</v-icon
                                    >
                                    {{ tag }}
                                </v-chip>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>

                <!-- Sidebar -->
                <v-col cols="12" md="4">
                    <!-- Metadata -->
                    <v-card elevation="2" class="mb-4">
                        <v-card-title class="bg-orange text-white">
                            <v-icon class="mr-2">mdi-information</v-icon>
                            Details
                        </v-card-title>
                        <v-card-text class="pa-6">
                            <div class="mb-4">
                                <strong>Category:</strong>
                                <div class="mt-1">
                                    <v-chip
                                        :color="
                                            knowledgeBase.category === 'pajak'
                                                ? 'blue'
                                                : knowledgeBase.category ===
                                                    'stnk'
                                                  ? 'green'
                                                  : 'grey'
                                        "
                                        variant="tonal"
                                        size="small"
                                    >
                                        {{
                                            categories[
                                                knowledgeBase.category
                                            ] || knowledgeBase.category
                                        }}
                                    </v-chip>
                                </div>
                            </div>

                            <div class="mb-4">
                                <strong>Type:</strong>
                                <div class="mt-1">
                                    <v-chip
                                        :color="
                                            knowledgeBase.type === 'faq'
                                                ? 'orange'
                                                : knowledgeBase.type === 'sop'
                                                  ? 'purple'
                                                  : 'blue-grey'
                                        "
                                        variant="tonal"
                                        size="small"
                                    >
                                        {{
                                            types[knowledgeBase.type] ||
                                            knowledgeBase.type
                                        }}
                                    </v-chip>
                                </div>
                            </div>

                            <div class="mb-4">
                                <strong>Priority:</strong>
                                <div class="mt-1">
                                    <v-chip
                                        :color="
                                            getPriorityColor(
                                                knowledgeBase.priority,
                                            )
                                        "
                                        variant="tonal"
                                        size="small"
                                    >
                                        {{
                                            getPriorityText(
                                                knowledgeBase.priority,
                                            )
                                        }}
                                    </v-chip>
                                </div>
                            </div>

                            <div class="mb-4">
                                <strong>Status:</strong>
                                <div class="mt-1">
                                    <v-chip
                                        :color="
                                            getStatusColor(knowledgeBase.status)
                                        "
                                        variant="tonal"
                                        size="small"
                                    >
                                        {{
                                            statuses[knowledgeBase.status] ||
                                            knowledgeBase.status
                                        }}
                                    </v-chip>
                                </div>
                            </div>

                            <div class="mb-4">
                                <strong>Active:</strong>
                                <div class="mt-1">
                                    <v-chip
                                        :color="
                                            knowledgeBase.is_active
                                                ? 'success'
                                                : 'error'
                                        "
                                        variant="tonal"
                                        size="small"
                                    >
                                        {{
                                            knowledgeBase.is_active
                                                ? "Yes"
                                                : "No"
                                        }}
                                    </v-chip>
                                </div>
                            </div>

                            <div>
                                <strong>Views:</strong>
                                <div class="mt-1">
                                    <v-chip
                                        color="info"
                                        variant="outlined"
                                        size="small"
                                    >
                                        <v-icon class="mr-1" size="small"
                                            >mdi-eye</v-icon
                                        >
                                        {{ knowledgeBase.view_count || 0 }}
                                    </v-chip>
                                </div>
                            </div>
                        </v-card-text>
                    </v-card>

                    <!-- Timestamps -->
                    <v-card elevation="2" class="mb-4">
                        <v-card-title class="bg-blue-grey text-white">
                            <v-icon class="mr-2">mdi-clock</v-icon>
                            Timestamps
                        </v-card-title>
                        <v-card-text class="pa-6">
                            <div class="mb-3">
                                <strong>Created:</strong>
                                <div class="mt-1 text-body-2">
                                    {{ formatDate(knowledgeBase.created_at) }}
                                </div>
                                <div
                                    v-if="knowledgeBase.creator"
                                    class="text-caption text-medium-emphasis"
                                >
                                    by {{ knowledgeBase.creator.name }}
                                </div>
                            </div>

                            <div
                                v-if="
                                    knowledgeBase.updated_at !==
                                    knowledgeBase.created_at
                                "
                                class="mb-3"
                            >
                                <strong>Last Updated:</strong>
                                <div class="mt-1 text-body-2">
                                    {{ formatDate(knowledgeBase.updated_at) }}
                                </div>
                                <div
                                    v-if="knowledgeBase.updater"
                                    class="text-caption text-medium-emphasis"
                                >
                                    by {{ knowledgeBase.updater.name }}
                                </div>
                            </div>

                            <div v-if="knowledgeBase.published_at">
                                <strong>Published:</strong>
                                <div class="mt-1 text-body-2">
                                    {{ formatDate(knowledgeBase.published_at) }}
                                </div>
                            </div>
                        </v-card-text>
                    </v-card>

                    <!-- Quick Actions -->
                    <v-card elevation="2">
                        <v-card-title class="bg-purple text-white">
                            <v-icon class="mr-2">mdi-lightning-bolt</v-icon>
                            Quick Actions
                        </v-card-title>
                        <v-card-text class="pa-6">
                            <v-btn
                                color="primary"
                                variant="outlined"
                                block
                                @click="
                                    $inertia.visit(
                                        route(
                                            'knowledge-base.edit',
                                            knowledgeBase.id,
                                        ),
                                    )
                                "
                                class="mb-3"
                            >
                                <v-icon class="mr-2">mdi-pencil</v-icon>
                                Edit Entry
                            </v-btn>

                            <v-btn
                                :color="
                                    knowledgeBase.is_active
                                        ? 'warning'
                                        : 'success'
                                "
                                variant="outlined"
                                block
                                @click="toggleStatus"
                                class="mb-3"
                            >
                                <v-icon class="mr-2">{{
                                    knowledgeBase.is_active
                                        ? "mdi-eye-off"
                                        : "mdi-eye"
                                }}</v-icon>
                                {{
                                    knowledgeBase.is_active
                                        ? "Deactivate"
                                        : "Activate"
                                }}
                            </v-btn>

                            <v-btn
                                v-if="knowledgeBase.file_path"
                                color="blue"
                                variant="outlined"
                                block
                                @click="downloadFile"
                                class="mb-3"
                            >
                                <v-icon class="mr-2">mdi-download</v-icon>
                                Download File
                            </v-btn>

                            <v-btn
                                color="info"
                                variant="outlined"
                                block
                                @click="copyToClipboard(knowledgeBase.title)"
                                class="mb-3"
                            >
                                <v-icon class="mr-2">mdi-content-copy</v-icon>
                                Copy Title
                            </v-btn>

                            <v-btn
                                color="error"
                                variant="outlined"
                                block
                                @click="confirmDelete = true"
                            >
                                <v-icon class="mr-2">mdi-delete</v-icon>
                                Delete Entry
                            </v-btn>
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
                        Are you sure you want to delete "<strong>{{
                            knowledgeBase.title
                        }}</strong
                        >"? This action cannot be undone.
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn @click="confirmDelete = false">Cancel</v-btn>
                        <v-btn color="error" @click="deleteItem">Delete</v-btn>
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

.gap-4 {
    gap: 16px;
}

.content-display {
    line-height: 1.6;
    font-size: 16px;
}

.content-display h1,
.content-display h2,
.content-display h3 {
    margin-top: 1.5em;
    margin-bottom: 0.5em;
}

.content-display p {
    margin-bottom: 1em;
}

.content-display ul,
.content-display ol {
    margin-bottom: 1em;
    padding-left: 2em;
}
</style>
