<template>
    <AppLayout title="Edit App Setting">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit App Setting
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6">
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Key (readonly) -->
                            <div>
                                <label
                                    for="key"
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Key
                                </label>
                                <input
                                    id="key"
                                    :value="setting.key"
                                    type="text"
                                    readonly
                                    class="w-full border-gray-300 rounded-md shadow-sm bg-gray-50 text-gray-600"
                                />
                                <p class="text-sm text-gray-500 mt-1">
                                    Key cannot be changed after creation
                                </p>
                            </div>

                            <!-- Group -->
                            <div>
                                <label
                                    for="group"
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Group *
                                </label>
                                <input
                                    id="group"
                                    v-model="form.group"
                                    type="text"
                                    required
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="e.g., chat, system, ui"
                                    list="group-suggestions"
                                />
                                <datalist id="group-suggestions">
                                    <option value="chat"></option>
                                    <option value="system"></option>
                                    <option value="ui"></option>
                                    <option value="security"></option>
                                    <option value="email"></option>
                                    <option value="general"></option>
                                </datalist>
                                <div
                                    v-if="errors.group"
                                    class="text-red-500 text-sm mt-1"
                                >
                                    {{ errors.group }}
                                </div>
                            </div>

                            <!-- Type (readonly) -->
                            <div>
                                <label
                                    for="type"
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Type
                                </label>
                                <input
                                    id="type"
                                    :value="setting.type"
                                    type="text"
                                    readonly
                                    class="w-full border-gray-300 rounded-md shadow-sm bg-gray-50 text-gray-600"
                                />
                                <p class="text-sm text-gray-500 mt-1">
                                    Type cannot be changed after creation
                                </p>
                            </div>

                            <!-- Current Value Display -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Current Value
                                </label>
                                <div class="bg-gray-50 p-3 rounded-md">
                                    <ValueDisplay
                                        :setting="setting"
                                        :show-details="true"
                                    />
                                </div>
                            </div>

                            <!-- Value -->
                            <div>
                                <label
                                    for="value"
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    New Value *
                                </label>

                                <!-- Boolean -->
                                <div
                                    v-if="setting.type === 'boolean'"
                                    class="flex items-center"
                                >
                                    <input
                                        id="value"
                                        v-model="form.value"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                    />
                                    <label
                                        for="value"
                                        class="ml-2 text-sm text-gray-700"
                                    >
                                        {{ form.value ? "True" : "False" }}
                                    </label>
                                </div>

                                <!-- JSON -->
                                <textarea
                                    v-else-if="setting.type === 'json'"
                                    id="value"
                                    v-model="form.value"
                                    rows="6"
                                    required
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                                    placeholder='{"example": "value"}'
                                ></textarea>

                                <!-- Number -->
                                <input
                                    v-else-if="
                                        setting.type === 'integer' ||
                                        setting.type === 'float'
                                    "
                                    id="value"
                                    v-model="form.value"
                                    type="number"
                                    :step="
                                        setting.type === 'float' ? '0.01' : '1'
                                    "
                                    required
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                />

                                <!-- String -->
                                <input
                                    v-else
                                    id="value"
                                    v-model="form.value"
                                    type="text"
                                    required
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                />

                                <div
                                    v-if="errors.value"
                                    class="text-red-500 text-sm mt-1"
                                >
                                    {{ errors.value }}
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label
                                    for="description"
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Description
                                </label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Describe what this setting does..."
                                ></textarea>
                                <div
                                    v-if="errors.description"
                                    class="text-red-500 text-sm mt-1"
                                >
                                    {{ errors.description }}
                                </div>
                            </div>

                            <!-- Validation Rules -->
                            <div>
                                <label
                                    for="validation_rules"
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Validation Rules
                                </label>
                                <textarea
                                    id="validation_rules"
                                    v-model="form.validation_rules"
                                    rows="3"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                                    placeholder='{"min": 1, "max": 100}'
                                ></textarea>
                                <p class="text-sm text-gray-500 mt-1">
                                    JSON object with validation rules (optional)
                                </p>
                                <div
                                    v-if="errors.validation_rules"
                                    class="text-red-500 text-sm mt-1"
                                >
                                    {{ errors.validation_rules }}
                                </div>
                            </div>

                            <!-- Is Public -->
                            <div class="flex items-center">
                                <input
                                    id="is_public"
                                    v-model="form.is_public"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                />
                                <label
                                    for="is_public"
                                    class="ml-2 text-sm text-gray-700"
                                >
                                    Public (dapat diakses tanpa autentikasi)
                                </label>
                            </div>

                            <!-- Is Cached -->
                            <div class="flex items-center">
                                <input
                                    id="is_cached"
                                    v-model="form.is_cached"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                />
                                <label
                                    for="is_cached"
                                    class="ml-2 text-sm text-gray-700"
                                >
                                    Cache setting untuk performa
                                </label>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div
                            class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-200"
                        >
                            <Link
                                :href="route('settings.show', setting.id)"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium disabled:opacity-50"
                            >
                                <span v-if="form.processing">Updating...</span>
                                <span v-else>Update Setting</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import ValueDisplay from "./Components/ValueDisplay.vue";

const props = defineProps({
    setting: Object,
    errors: Object,
});

const form = useForm({
    group: props.setting.group,
    value: formatInitialValue(props.setting),
    description: props.setting.description || "",
    validation_rules: props.setting.validation_rules || "",
    is_public: props.setting.is_public,
    is_cached: props.setting.is_cached,
});

function formatInitialValue(setting) {
    if (setting.type === "boolean") {
        return (
            setting.formatted_value === true ||
            setting.formatted_value === "true"
        );
    } else if (setting.type === "json") {
        try {
            return JSON.stringify(setting.formatted_value, null, 2);
        } catch (e) {
            return setting.value;
        }
    } else {
        return setting.formatted_value;
    }
}

const submit = () => {
    form.put(route("settings.update", props.setting.id), {
        onSuccess: () => {
            // Redirect handled by controller
        },
    });
};
</script>
