<template>
    <AppLayout title="App Settings">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                App Settings
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <!-- Header Actions -->
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">
                                    Pengaturan Aplikasi
                                </h3>
                                <p class="text-sm text-gray-600">
                                    Kelola pengaturan aplikasi dan konfigurasi
                                    sistem
                                </p>
                            </div>
                            <div class="flex space-x-3">
                                <button
                                    @click="clearCache"
                                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm font-medium"
                                    :disabled="loading"
                                >
                                    Clear Cache
                                </button>
                                <Link
                                    :href="route('settings.create')"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium"
                                >
                                    Tambah Setting
                                </Link>
                            </div>
                        </div>

                        <!-- Filter dan Search -->
                        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                    >Search</label
                                >
                                <input
                                    v-model="filters.search"
                                    type="text"
                                    placeholder="Cari setting..."
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    @input="debounceSearch"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                    >Group</label
                                >
                                <select
                                    v-model="filters.group"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    @change="applyFilters"
                                >
                                    <option value="">Semua Group</option>
                                    <option
                                        v-for="group in availableGroups"
                                        :key="group"
                                        :value="group"
                                    >
                                        {{ group }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                    >Type</label
                                >
                                <select
                                    v-model="filters.type"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    @change="applyFilters"
                                >
                                    <option value="">Semua Type</option>
                                    <option value="string">String</option>
                                    <option value="integer">Integer</option>
                                    <option value="boolean">Boolean</option>
                                    <option value="float">Float</option>
                                    <option value="json">JSON</option>
                                </select>
                            </div>
                        </div>

                        <!-- Settings Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Setting
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Value
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Group
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Type
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="bg-white divide-y divide-gray-200"
                                >
                                    <tr
                                        v-for="setting in settings.data"
                                        :key="setting.id"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div
                                                    class="text-sm font-medium text-gray-900"
                                                >
                                                    {{ setting.key }}
                                                </div>
                                                <div
                                                    class="text-sm text-gray-500"
                                                    v-if="setting.description"
                                                >
                                                    {{ setting.description }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <QuickEdit
                                                    :setting="setting"
                                                    @updated="
                                                        handleSettingUpdated
                                                    "
                                                />
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                            >
                                                {{ setting.group }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                                            >
                                                {{ setting.type }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2"
                                        >
                                            <Link
                                                :href="
                                                    route(
                                                        'settings.show',
                                                        setting.id,
                                                    )
                                                "
                                                class="text-blue-600 hover:text-blue-900"
                                            >
                                                View
                                            </Link>
                                            <Link
                                                :href="
                                                    route(
                                                        'settings.edit',
                                                        setting.id,
                                                    )
                                                "
                                                class="text-indigo-600 hover:text-indigo-900"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                @click="deleteSetting(setting)"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6" v-if="settings.links">
                            <Pagination :links="settings.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            @close="showDeleteModal = false"
            @confirm="confirmDelete"
            title="Hapus Setting"
            :message="`Apakah Anda yakin ingin menghapus setting '${settingToDelete?.key}'?`"
            confirm-text="Hapus"
            confirm-class="bg-red-500 hover:bg-red-600"
        />
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import { router, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Pagination from "@/Components/Pagination.vue";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import QuickEdit from "./Components/QuickEdit.vue";

const props = defineProps({
    settings: Object,
    filters: Object,
    availableGroups: Array,
});

const loading = ref(false);
const showDeleteModal = ref(false);
const settingToDelete = ref(null);
const searchTimeout = ref(null);

const filters = reactive({
    search: props.filters?.search || "",
    group: props.filters?.group || "",
    type: props.filters?.type || "",
});

const debounceSearch = () => {
    clearTimeout(searchTimeout.value);
    searchTimeout.value = setTimeout(() => {
        applyFilters();
    }, 300);
};

const applyFilters = () => {
    router.get(route("settings.index"), filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearCache = async () => {
    if (loading.value) return;

    loading.value = true;
    try {
        await router.delete(route("settings.clear-cache"), {
            preserveState: true,
            onSuccess: () => {
                // Show success message
            },
        });
    } finally {
        loading.value = false;
    }
};

const deleteSetting = (setting) => {
    settingToDelete.value = setting;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (!settingToDelete.value) return;

    router.delete(route("settings.destroy", settingToDelete.value.id), {
        preserveState: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            settingToDelete.value = null;
        },
    });
};

const handleSettingUpdated = (setting) => {
    // Refresh the list or update the setting in place
    router.reload({ only: ["settings"] });
};
</script>
