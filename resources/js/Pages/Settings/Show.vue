<template>
    <AppLayout title="Detail App Setting">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail App Setting
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <!-- Header -->
                        <div class="flex justify-between items-start mb-8">
                            <div>
                                <h3
                                    class="text-2xl font-semibold text-gray-900"
                                >
                                    {{ setting.key }}
                                </h3>
                                <p
                                    class="text-gray-600 mt-1"
                                    v-if="setting.description"
                                >
                                    {{ setting.description }}
                                </p>
                            </div>
                            <div class="flex space-x-3">
                                <Link
                                    :href="route('settings.edit', setting.id)"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium"
                                >
                                    Edit
                                </Link>
                                <Link
                                    :href="route('settings.index')"
                                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium"
                                >
                                    Back to List
                                </Link>
                            </div>
                        </div>

                        <!-- Setting Details -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Basic Information -->
                            <div class="space-y-6">
                                <div>
                                    <h4
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Basic Information
                                    </h4>

                                    <dl class="space-y-4">
                                        <div>
                                            <dt
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Key
                                            </dt>
                                            <dd
                                                class="mt-1 text-sm text-gray-900 font-mono bg-gray-50 px-3 py-2 rounded"
                                            >
                                                {{ setting.key }}
                                            </dd>
                                        </div>

                                        <div>
                                            <dt
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Group
                                            </dt>
                                            <dd class="mt-1">
                                                <span
                                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800"
                                                >
                                                    {{ setting.group }}
                                                </span>
                                            </dd>
                                        </div>

                                        <div>
                                            <dt
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Type
                                            </dt>
                                            <dd class="mt-1">
                                                <span
                                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800"
                                                >
                                                    {{ setting.type }}
                                                </span>
                                            </dd>
                                        </div>

                                        <div>
                                            <dt
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Current Value
                                            </dt>
                                            <dd class="mt-1">
                                                <ValueDisplay
                                                    :setting="setting"
                                                />
                                            </dd>
                                        </div>

                                        <div v-if="setting.description">
                                            <dt
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Description
                                            </dt>
                                            <dd
                                                class="mt-1 text-sm text-gray-900"
                                            >
                                                {{ setting.description }}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                <!-- Flags -->
                                <div>
                                    <h4
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Flags
                                    </h4>
                                    <div class="space-y-2">
                                        <div class="flex items-center">
                                            <span class="flex items-center">
                                                <span
                                                    :class="
                                                        setting.is_public
                                                            ? 'text-green-500'
                                                            : 'text-gray-400'
                                                    "
                                                >
                                                    <svg
                                                        class="w-5 h-5"
                                                        fill="currentColor"
                                                        viewBox="0 0 20 20"
                                                    >
                                                        <path
                                                            v-if="
                                                                setting.is_public
                                                            "
                                                            fill-rule="evenodd"
                                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                            clip-rule="evenodd"
                                                        ></path>
                                                        <path
                                                            v-else
                                                            fill-rule="evenodd"
                                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                            clip-rule="evenodd"
                                                        ></path>
                                                    </svg>
                                                </span>
                                                <span
                                                    class="ml-2 text-sm text-gray-700"
                                                    >Public Access</span
                                                >
                                            </span>
                                        </div>

                                        <div class="flex items-center">
                                            <span class="flex items-center">
                                                <span
                                                    :class="
                                                        setting.is_cached
                                                            ? 'text-green-500'
                                                            : 'text-gray-400'
                                                    "
                                                >
                                                    <svg
                                                        class="w-5 h-5"
                                                        fill="currentColor"
                                                        viewBox="0 0 20 20"
                                                    >
                                                        <path
                                                            v-if="
                                                                setting.is_cached
                                                            "
                                                            fill-rule="evenodd"
                                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                            clip-rule="evenodd"
                                                        ></path>
                                                        <path
                                                            v-else
                                                            fill-rule="evenodd"
                                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                            clip-rule="evenodd"
                                                        ></path>
                                                    </svg>
                                                </span>
                                                <span
                                                    class="ml-2 text-sm text-gray-700"
                                                    >Cached</span
                                                >
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Technical Details -->
                            <div class="space-y-6">
                                <!-- Validation Rules -->
                                <div v-if="setting.validation_rules">
                                    <h4
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Validation Rules
                                    </h4>
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <pre
                                            class="text-sm text-gray-700 whitespace-pre-wrap"
                                            >{{
                                                formatJson(
                                                    setting.validation_rules,
                                                )
                                            }}</pre
                                        >
                                    </div>
                                </div>

                                <!-- Raw Value -->
                                <div>
                                    <h4
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Raw Value
                                    </h4>
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <pre
                                            class="text-sm text-gray-700 whitespace-pre-wrap font-mono"
                                            >{{ setting.value }}</pre
                                        >
                                    </div>
                                </div>

                                <!-- Metadata -->
                                <div>
                                    <h4
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Metadata
                                    </h4>
                                    <dl class="space-y-3 text-sm">
                                        <div>
                                            <dt class="text-gray-500">
                                                Created
                                            </dt>
                                            <dd class="text-gray-900">
                                                {{
                                                    formatDate(
                                                        setting.created_at,
                                                    )
                                                }}
                                                <span
                                                    v-if="setting.created_by"
                                                    class="text-gray-500"
                                                >
                                                    by
                                                    {{
                                                        setting.creator?.name ||
                                                        "Unknown"
                                                    }}
                                                </span>
                                            </dd>
                                        </div>

                                        <div>
                                            <dt class="text-gray-500">
                                                Last Updated
                                            </dt>
                                            <dd class="text-gray-900">
                                                {{
                                                    formatDate(
                                                        setting.updated_at,
                                                    )
                                                }}
                                                <span
                                                    v-if="setting.updated_by"
                                                    class="text-gray-500"
                                                >
                                                    by
                                                    {{
                                                        setting.updater?.name ||
                                                        "Unknown"
                                                    }}
                                                </span>
                                            </dd>
                                        </div>

                                        <div>
                                            <dt class="text-gray-500">ID</dt>
                                            <dd class="text-gray-900 font-mono">
                                                {{ setting.id }}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Edit -->
                        <div class="mt-8 pt-8 border-t border-gray-200">
                            <h4 class="text-lg font-medium text-gray-900 mb-4">
                                Quick Edit Value
                            </h4>
                            <QuickEdit
                                :setting="setting"
                                @updated="handleUpdated"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import QuickEdit from "./Components/QuickEdit.vue";
import ValueDisplay from "./Components/ValueDisplay.vue";

const props = defineProps({
    setting: Object,
});

const formatJson = (value) => {
    try {
        return JSON.stringify(JSON.parse(value), null, 2);
    } catch (e) {
        return value;
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString();
};

const handleUpdated = (updatedSetting) => {
    // Page will be refreshed by QuickEdit component
    console.log("Setting updated:", updatedSetting);
};
</script>
