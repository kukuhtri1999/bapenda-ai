<template>
    <div class="value-display">
        <!-- Boolean -->
        <div v-if="setting.type === 'boolean'" class="flex items-center">
            <span
                :class="
                    value
                        ? 'text-green-600 bg-green-100'
                        : 'text-red-600 bg-red-100'
                "
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
            >
                <svg
                    v-if="value"
                    class="w-4 h-4 mr-1"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path
                        fill-rule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clip-rule="evenodd"
                    ></path>
                </svg>
                <svg
                    v-else
                    class="w-4 h-4 mr-1"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path
                        fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clip-rule="evenodd"
                    ></path>
                </svg>
                {{ value ? "True" : "False" }}
            </span>
        </div>

        <!-- JSON -->
        <div
            v-else-if="setting.type === 'json'"
            class="bg-gray-50 rounded-lg p-4"
        >
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700"
                    >JSON Value</span
                >
                <button
                    @click="toggleJsonExpanded"
                    class="text-sm text-blue-600 hover:text-blue-800"
                >
                    {{ jsonExpanded ? "Collapse" : "Expand" }}
                </button>
            </div>

            <div v-if="jsonExpanded" class="overflow-x-auto">
                <pre
                    class="text-sm text-gray-700 whitespace-pre-wrap font-mono"
                    >{{ formatJson(value) }}</pre
                >
            </div>
            <div v-else class="text-sm text-gray-600">
                {{ jsonPreview }}
            </div>
        </div>

        <!-- Number -->
        <div
            v-else-if="setting.type === 'integer' || setting.type === 'float'"
            class="flex items-center"
        >
            <span
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 font-mono"
            >
                {{ formattedNumber }}
            </span>
            <span
                v-if="setting.type === 'integer'"
                class="ml-2 text-xs text-gray-500"
                >(integer)</span
            >
            <span v-else class="ml-2 text-xs text-gray-500">(float)</span>
        </div>

        <!-- String -->
        <div v-else class="flex items-center">
            <span
                class="inline-block bg-gray-100 text-gray-800 px-3 py-1 rounded font-mono text-sm"
            >
                "{{ value }}"
            </span>
            <span class="ml-2 text-xs text-gray-500">(string)</span>
        </div>

        <!-- Additional Info -->
        <div class="mt-2 text-xs text-gray-500" v-if="showDetails">
            <div>
                Raw value:
                <code class="bg-gray-100 px-1 rounded">{{
                    setting.value
                }}</code>
            </div>
            <div v-if="setting.type !== 'string'">
                Formatted:
                <code class="bg-gray-100 px-1 rounded">{{ value }}</code>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
    setting: Object,
    showDetails: {
        type: Boolean,
        default: false,
    },
});

const jsonExpanded = ref(false);

const value = computed(() => {
    return props.setting.formatted_value;
});

const formattedNumber = computed(() => {
    if (props.setting.type === "integer") {
        return Number(value.value).toLocaleString();
    } else if (props.setting.type === "float") {
        return Number(value.value).toFixed(2);
    }
    return value.value;
});

const jsonPreview = computed(() => {
    try {
        const jsonStr = JSON.stringify(value.value);
        return jsonStr.length > 100
            ? jsonStr.substring(0, 100) + "..."
            : jsonStr;
    } catch (e) {
        return String(value.value).substring(0, 100) + "...";
    }
});

const formatJson = (val) => {
    try {
        return JSON.stringify(val, null, 2);
    } catch (e) {
        return String(val);
    }
};

const toggleJsonExpanded = () => {
    jsonExpanded.value = !jsonExpanded.value;
};
</script>
