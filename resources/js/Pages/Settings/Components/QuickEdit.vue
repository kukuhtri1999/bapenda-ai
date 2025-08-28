<template>
    <div class="flex items-center space-x-2">
        <div v-if="!editing" class="flex-1">
            <span
                v-if="setting.type === 'boolean'"
                class="inline-flex items-center"
            >
                <span :class="value ? 'text-green-600' : 'text-red-600'">
                    {{ value ? "True" : "False" }}
                </span>
            </span>
            <span
                v-else-if="setting.type === 'json'"
                class="text-gray-600 text-sm"
            >
                {{ JSON.stringify(value).substring(0, 50)
                }}{{ JSON.stringify(value).length > 50 ? "..." : "" }}
            </span>
            <span v-else>{{ displayValue }}</span>
        </div>

        <div v-else class="flex-1">
            <!-- Boolean toggle -->
            <label v-if="setting.type === 'boolean'" class="flex items-center">
                <input
                    type="checkbox"
                    v-model="editValue"
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                />
                <span class="ml-2 text-sm">{{
                    editValue ? "True" : "False"
                }}</span>
            </label>

            <!-- JSON textarea -->
            <textarea
                v-else-if="setting.type === 'json'"
                v-model="editValue"
                rows="3"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                placeholder="Enter valid JSON"
            ></textarea>

            <!-- Number input -->
            <input
                v-else-if="
                    setting.type === 'integer' || setting.type === 'float'
                "
                type="number"
                v-model="editValue"
                :step="setting.type === 'float' ? '0.01' : '1'"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
            />

            <!-- Text input -->
            <input
                v-else
                type="text"
                v-model="editValue"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
            />
        </div>

        <div class="flex items-center space-x-1">
            <button
                v-if="!editing"
                @click="startEdit"
                class="text-blue-600 hover:text-blue-800 p-1"
                title="Edit"
            >
                <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                    ></path>
                </svg>
            </button>

            <template v-else>
                <button
                    @click="saveEdit"
                    :disabled="saving"
                    class="text-green-600 hover:text-green-800 p-1"
                    title="Save"
                >
                    <svg
                        v-if="!saving"
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        ></path>
                    </svg>
                    <svg
                        v-else
                        class="w-4 h-4 animate-spin"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                </button>

                <button
                    @click="cancelEdit"
                    :disabled="saving"
                    class="text-red-600 hover:text-red-800 p-1"
                    title="Cancel"
                >
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        ></path>
                    </svg>
                </button>
            </template>
        </div>

        <!-- Validation Error -->
        <div v-if="validationError" class="text-red-500 text-xs mt-1">
            {{ validationError }}
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    setting: Object,
});

const emit = defineEmits(["updated"]);

const editing = ref(false);
const saving = ref(false);
const editValue = ref(null);
const validationError = ref(null);

const value = computed(() => {
    return props.setting.formatted_value;
});

const displayValue = computed(() => {
    if (props.setting.type === "json") {
        return JSON.stringify(value.value);
    }
    return String(value.value);
});

const startEdit = () => {
    editing.value = true;
    validationError.value = null;

    if (props.setting.type === "boolean") {
        editValue.value = value.value === true || value.value === "true";
    } else if (props.setting.type === "json") {
        editValue.value = JSON.stringify(value.value, null, 2);
    } else {
        editValue.value = value.value;
    }
};

const cancelEdit = () => {
    editing.value = false;
    editValue.value = null;
    validationError.value = null;
};

const validateValue = () => {
    validationError.value = null;

    if (props.setting.type === "json") {
        try {
            JSON.parse(editValue.value);
        } catch (e) {
            validationError.value = "Invalid JSON format";
            return false;
        }
    }

    if (props.setting.type === "integer") {
        const num = Number(editValue.value);
        if (!Number.isInteger(num)) {
            validationError.value = "Must be an integer";
            return false;
        }
    }

    if (props.setting.type === "float") {
        const num = Number(editValue.value);
        if (isNaN(num)) {
            validationError.value = "Must be a number";
            return false;
        }
    }

    return true;
};

const saveEdit = async () => {
    if (!validateValue()) return;

    saving.value = true;

    try {
        await router.put(
            route("settings.update-value", props.setting.id),
            {
                value: editValue.value,
            },
            {
                preserveState: true,
                onSuccess: (page) => {
                    editing.value = false;
                    editValue.value = null;
                    emit(
                        "updated",
                        page.props.settings?.data?.find(
                            (s) => s.id === props.setting.id,
                        ) || props.setting,
                    );
                },
                onError: (errors) => {
                    if (errors.value) {
                        validationError.value = errors.value[0];
                    }
                },
            },
        );
    } catch (error) {
        validationError.value = "Failed to update setting";
    } finally {
        saving.value = false;
    }
};
</script>
