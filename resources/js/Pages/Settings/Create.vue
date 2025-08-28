<template>
    <AppLayout title="Tambah App Setting">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Tambah App Setting
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6">
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Key -->
                            <div>
                                <label
                                    for="key"
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Key *
                                </label>
                                <input
                                    id="key"
                                    v-model="form.key"
                                    type="text"
                                    required
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="e.g., max_messages_per_session"
                                />
                                <p class="text-sm text-gray-500 mt-1">
                                    Unique identifier for this setting
                                    (lowercase, underscores allowed)
                                </p>
                                <div
                                    v-if="errors.key"
                                    class="text-red-500 text-sm mt-1"
                                >
                                    {{ errors.key }}
                                </div>
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
                                <p class="text-sm text-gray-500 mt-1">
                                    Logical grouping for related settings
                                </p>
                                <div
                                    v-if="errors.group"
                                    class="text-red-500 text-sm mt-1"
                                >
                                    {{ errors.group }}
                                </div>
                            </div>

                            <!-- Type -->
                            <div>
                                <label
                                    for="type"
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Type *
                                </label>
                                <select
                                    id="type"
                                    v-model="form.type"
                                    required
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    @change="onTypeChange"
                                >
                                    <option value="">Pilih Type</option>
                                    <option value="string">String</option>
                                    <option value="integer">Integer</option>
                                    <option value="boolean">Boolean</option>
                                    <option value="float">Float</option>
                                    <option value="json">JSON</option>
                                </select>
                                <div
                                    v-if="errors.type"
                                    class="text-red-500 text-sm mt-1"
                                >
                                    {{ errors.type }}
                                </div>
                            </div>

                            <!-- Value -->
                            <div>
                                <label
                                    for="value"
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Value *
                                </label>

                                <!-- Boolean -->
                                <div
                                    v-if="form.type === 'boolean'"
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
                                    v-else-if="form.type === 'json'"
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
                                        form.type === 'integer' ||
                                        form.type === 'float'
                                    "
                                    id="value"
                                    v-model="form.value"
                                    type="number"
                                    :step="form.type === 'float' ? '0.01' : '1'"
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
                                :href="route('settings.index')"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium disabled:opacity-50"
                            >
                                <span v-if="form.processing">Saving...</span>
                                <span v-else>Save Setting</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    errors: Object,
});

const form = useForm({
    key: "",
    group: "",
    type: "",
    value: "",
    description: "",
    validation_rules: "",
    is_public: false,
    is_cached: true,
});

const onTypeChange = () => {
    // Reset value when type changes
    if (form.type === "boolean") {
        form.value = false;
    } else if (form.type === "json") {
        form.value = "{}";
    } else if (form.type === "integer") {
        form.value = 0;
    } else if (form.type === "float") {
        form.value = 0.0;
    } else {
        form.value = "";
    }
};

const submit = () => {
    form.post(route("settings.store"), {
        onSuccess: () => {
            // Redirect handled by controller
        },
    });
};
</script>
