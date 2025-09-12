<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  knowledgeBases: Object,
  filters: Object,
  categories: Object,
  types: Object,
  statuses: Object,
});

const search = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category || '');
const typeFilter = ref(props.filters.type || '');
const sourceTypeFilter = ref(props.filters.source_type || '');
const statusFilter = ref(props.filters.status || '');
const activeFilter = ref(props.filters.is_active || '');
const selectedItems = ref([]);
const bulkAction = ref('');
const confirmDelete = ref(false);
const itemToDelete = ref(null);

const headers = [
  { title: 'Title', key: 'title', sortable: true },
  { title: 'Category', key: 'category', sortable: true },
  { title: 'Type', key: 'type', sortable: true },
  // { title: "Source", key: "source_type", sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Active', key: 'is_active', sortable: true },
  // { title: "Views", key: "view_count", sortable: true },
  { title: 'Created', key: 'created_at', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false },
];

const sourceTypeItems = [
  { title: 'All Sources', value: '' },
  { title: 'Manual Entry', value: 'manual' },
  { title: 'File Upload', value: 'file' },
];

const activeItems = [
  { title: 'All Status', value: '' },
  { title: 'Active', value: 'true' },
  { title: 'Inactive', value: 'false' },
];

const bulkActions = [
  { title: 'Select Action...', value: '' },
  { title: 'Delete Selected', value: 'delete' },
  { title: 'Activate Selected', value: 'activate' },
  { title: 'Deactivate Selected', value: 'deactivate' },
  { title: 'Publish Selected', value: 'publish' },
  { title: 'Archive Selected', value: 'archive' },
];

const filteredKnowledgeBases = computed(() => props.knowledgeBases.data);

const applyFilters = () => {
  router.get(
    route('knowledge-base.index'),
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
  search.value = '';
  categoryFilter.value = '';
  typeFilter.value = '';
  sourceTypeFilter.value = '';
  statusFilter.value = '';
  activeFilter.value = '';
  router.get(route('knowledge-base.index'));
};

const deleteItem = (item) => {
  itemToDelete.value = item;
  confirmDelete.value = true;
};

const confirmDeleteItem = () => {
  if (itemToDelete.value) {
    router.delete(route('knowledge-base.destroy', itemToDelete.value.id), {
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
    route('knowledge-base.toggle-status', item.id),
    {},
    {
      preserveScroll: true,
    },
  );
};

const executeBulkAction = () => {
  if (!bulkAction.value || selectedItems.value.length === 0) return;

  router.post(
    route('knowledge-base.bulk-action'),
    {
      action: bulkAction.value,
      ids: selectedItems.value,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        selectedItems.value = [];
        bulkAction.value = '';
      },
    },
  );
};

const getStatusColor = (status) => {
  const colors = {
    published: 'success',
    draft: 'warning',
    archived: 'grey',
  };
  return colors[status] || 'grey';
};

const getSourceIcon = (sourceType) => (sourceType === 'file' ? 'mdi-file-document' : 'mdi-keyboard');

const formatDate = (date) => new Date(date).toLocaleDateString('id-ID', {
  year: 'numeric',
  month: 'short',
  day: 'numeric',
});
</script>

<template>
  <AppLayout title="Knowledge Base Management">
    <div class="pa-0">
      <!-- Header -->
      <VRow class="mb-6">
        <VCol cols="12">
          <VCard elevation="2">
            <VCardText class="pa-6">
              <VRow align="center">
                <VCol cols="12" md="6">
                  <h1 class="text-h4 font-weight-bold text-primary mb-2">
                    <VIcon class="mr-3" size="36">mdi-book-open-variant</VIcon>
                    Knowledge Base Management
                  </h1>
                  <p class="text-body-1 text-medium-emphasis">
                    Manage AI knowledge base entries and content
                  </p>
                </VCol>
                <VCol cols="12" md="6" class="text-right">
                  <VBtn
                    color="primary"
                    size="large"
                    @click="$inertia.visit(route('knowledge-base.create'))"
                    prepend-icon="mdi-plus"
                  >
                    Add New Entry
                  </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Filters -->
      <VRow class="mb-4">
        <VCol cols="12">
          <VCard elevation="2">
            <VCardText>
              <VRow>
                <VCol cols="12" md="3">
                  <VTextField
                    v-model="search"
                    label="Search..."
                    prepend-inner-icon="mdi-magnify"
                    variant="outlined"
                    density="compact"
                    @keyup.enter="applyFilters"
                    clearable
                  ></VTextField>
                </VCol>
                <VCol cols="12" md="2">
                  <VSelect
                    v-model="categoryFilter"
                    :items="[
                      { title: 'All Categories', value: '' },
                      ...Object.entries(categories || {}).map(
                        ([key, value]) => ({ title: value, value: key }),
                      ),
                    ]"
                    label="Category"
                    variant="outlined"
                    density="compact"
                  ></VSelect>
                </VCol>
                <VCol cols="12" md="2">
                  <VSelect
                    v-model="typeFilter"
                    :items="[
                      { title: 'All Types', value: '' },
                      ...Object.entries(types || {}).map(([key, value]) => ({
                        title: value,
                        value: key,
                      })),
                    ]"
                    label="Type"
                    variant="outlined"
                    density="compact"
                  ></VSelect>
                </VCol>
                <!-- <v-col cols="12" md="2">
                                    <v-select
                                        v-model="sourceTypeFilter"
                                        :items="sourceTypeItems"
                                        label="Source"
                                        variant="outlined"
                                        density="compact"
                                    ></v-select>
                                </v-col> -->
                <VCol cols="12" md="2">
                  <VSelect
                    v-model="statusFilter"
                    :items="[
                      { title: 'All Status', value: '' },
                      ...Object.entries(statuses || {}).map(([key, value]) => ({
                        title: value,
                        value: key,
                      })),
                    ]"
                    label="Status"
                    variant="outlined"
                    density="compact"
                  ></VSelect>
                </VCol>
                <VCol cols="12" md="2" class="d-flex">
                  <VBtn
                    color="primary"
                    variant="flat"
                    class="w-auto"
                    @click="applyFilters"
                  >
                    Apply
                  </VBtn>
                  <VBtn variant="outlined" @click="clearFilters" class="mx-2">
                    Clear
                  </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Bulk Actions -->
      <VRow class="mb-4" v-if="selectedItems.length > 0">
        <VCol cols="12">
          <VCard elevation="2" color="blue-grey-lighten-5">
            <VCardText>
              <VRow align="center">
                <VCol cols="auto">
                  <span class="text-body-1 font-weight-medium">
                    {{ selectedItems.length }} items selected
                  </span>
                </VCol>
                <VCol cols="auto">
                  <VSelect
                    v-model="bulkAction"
                    :items="bulkActions"
                    variant="outlined"
                    density="compact"
                    hide-details
                    style="min-width: 200px"
                  ></VSelect>
                </VCol>
                <VCol cols="auto">
                  <VBtn
                    color="primary"
                    @click="executeBulkAction"
                    :disabled="!bulkAction"
                  >
                    Execute
                  </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Knowledge Base Table -->
      <VRow>
        <VCol cols="12">
          <VCard elevation="2">
            <VDataTable
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
                  <VIcon
                    :icon="getSourceIcon(item.source_type)"
                    :color="item.source_type === 'file' ? 'blue' : 'green'"
                    class="mr-3"
                    size="small"
                  ></VIcon>
                  <div>
                    <div class="font-weight-medium">
                      {{ item.title }}
                    </div>
                    <small class="text-medium-emphasis" v-if="item.excerpt">
                      {{ item.excerpt.substring(0, 100) }}...
                    </small>
                  </div>
                </div>
              </template>

              <!-- Category Column -->
              <template #item.category="{ item }">
                <VChip
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
                  {{ categories[item.category] || item.category }}
                </VChip>
              </template>

              <!-- Type Column -->
              <template #item.type="{ item }">
                <VChip
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
                </VChip>
              </template>

              <!-- Source Type Column -->
              <template #item.source_type="{ item }">
                <VChip
                  :color="item.source_type === 'file' ? 'blue' : 'green'"
                  variant="outlined"
                  size="small"
                >
                  <VIcon
                    :icon="getSourceIcon(item.source_type)"
                    class="mr-1"
                    size="small"
                  ></VIcon>
                  {{ item.source_type === 'file' ? 'File' : 'Manual' }}
                </VChip>
              </template>

              <!-- Status Column -->
              <template #item.status="{ item }">
                <VChip
                  :color="getStatusColor(item.status)"
                  variant="tonal"
                  size="small"
                >
                  {{ statuses[item.status] || item.status }}
                </VChip>
              </template>

              <!-- Active Column -->
              <template #item.is_active="{ item }">
                <VSwitch
                  :model-value="item.is_active"
                  @change="toggleStatus(item)"
                  color="success"
                  density="compact"
                  hide-details
                ></VSwitch>
              </template>

              <!-- Views Column -->
              <template #item.view_count="{ item }">
                <VChip color="info" variant="outlined" size="small">
                  <VIcon icon="mdi-eye" class="mr-1" size="small"></VIcon>
                  {{ item.view_count || 0 }}
                </VChip>
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
                  <VBtn
                    size="small"
                    color="primary"
                    variant="tonal"
                    icon="mdi-eye"
                    @click="
                      $inertia.visit(route('knowledge-base.show', item.id))
                    "
                  ></VBtn>
                  <VBtn
                    size="small"
                    color="orange"
                    variant="tonal"
                    icon="mdi-pencil"
                    @click="
                      $inertia.visit(route('knowledge-base.edit', item.id))
                    "
                  ></VBtn>
                  <VBtn
                    v-if="item.file_path"
                    size="small"
                    color="blue"
                    variant="tonal"
                    icon="mdi-download"
                    @click="
                      window.open(route('knowledge-base.download', item.id))
                    "
                  ></VBtn>
                  <VBtn
                    size="small"
                    color="error"
                    variant="tonal"
                    icon="mdi-delete"
                    @click="deleteItem(item)"
                  ></VBtn>
                </div>
              </template>

              <!-- No Data -->
              <template #no-data>
                <div class="text-center pa-6">
                  <VIcon size="64" color="grey">mdi-book-open-variant</VIcon>
                  <h3 class="text-h6 mt-3">No Knowledge Base Entries Found</h3>
                  <p class="text-body-2 text-medium-emphasis">
                    Try adjusting your search criteria or create a new entry.
                  </p>
                </div>
              </template>
            </VDataTable>

            <!-- Pagination -->
            <VDivider></VDivider>
            <div class="pa-4 d-flex justify-center">
              <VPagination
                :model-value="knowledgeBases.current_page"
                :length="knowledgeBases.last_page"
                @update:model-value="
                  (page) =>
                    router.get(route('knowledge-base.index'), {
                      ...filters,
                      page,
                    })
                "
                total-visible="7"
              ></VPagination>
            </div>
          </VCard>
        </VCol>
      </VRow>

      <!-- Delete Confirmation Dialog -->
      <VDialog v-model="confirmDelete" max-width="400">
        <VCard>
          <VCardTitle>
            <VIcon color="error" class="mr-2">mdi-alert</VIcon>
            Confirm Deletion
          </VCardTitle>
          <VCardText>
            Are you sure you want to delete "<strong>{{
              itemToDelete?.title
            }}</strong
            >"? This action cannot be undone.
          </VCardText>
          <VCardActions>
            <VSpacer></VSpacer>
            <VBtn @click="confirmDelete = false">Cancel</VBtn>
            <VBtn color="error" @click="confirmDeleteItem">Delete</VBtn>
          </VCardActions>
        </VCard>
      </VDialog>
    </div>
  </AppLayout>
</template>

<style scoped>
.gap-2 {
  gap: 8px;
}
</style>
