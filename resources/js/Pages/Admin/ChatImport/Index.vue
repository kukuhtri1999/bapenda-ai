<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const file = ref(null);
const uploading = ref(false);
const result = ref(null);
const errors = ref([]);

const onFileChange = (e) => {
  const f = e.target.files?.[0];
  if (!f) return;
  const allowed = [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel',
    'text/csv',
  ];
  if (!allowed.includes(f.type)) {
    errors.value = ['Format file tidak didukung. Gunakan XLSX/XLS/CSV.'];
    file.value = null;
    return;
  }
  if (f.size > 20 * 1024 * 1024) {
    errors.value = ['Ukuran file melebihi 20MB.'];
    file.value = null;
    return;
  }
  errors.value = [];
  file.value = f;
};

const submit = async () => {
  if (!file.value) {
    errors.value = ['Pilih file terlebih dahulu.'];
    return;
  }
  uploading.value = true;
  result.value = null;
  errors.value = [];
  try {
    const form = new FormData();
    form.append('file', file.value);
    const res = await window.axios.post(
      route('admin.chat-import.upload'),
      form,
      {
        headers: { 'Content-Type': 'multipart/form-data' },
      },
    );
    result.value = res.data;
    if (!res.data?.success) {
      errors.value = [res.data?.message || 'Import gagal.'];
    }
  } catch (e) {
    errors.value = [
      e?.response?.data?.message
        || e.message
        || 'Terjadi kesalahan saat upload.',
    ];
  } finally {
    uploading.value = false;
  }
};
</script>

<template>
  <AppLayout title="Import Chat Messages">
    <div class="pa-4">
      <VCard elevation="4">
        <VCardTitle class="d-flex align-center">
          <VIcon class="mr-2">mdi-file-excel</VIcon>
          Import Chat Messages (XLSX/XLS/CSV)
        </VCardTitle>
        <VCardText>
          <p class="text-body-2 mb-4">
            Unggah file dengan header: chat_id, role, content, answer, topic,
            sentiment, metadata (JSON opsional), sent_at (YYYY-MM-DD HH:mm:ss).
          </p>

          <VAlert
            v-if="errors.length"
            type="error"
            variant="tonal"
            class="mb-4"
          >
            <div v-for="(err, i) in errors" :key="i">{{ err }}</div>
          </VAlert>

          <VFileInput
            label="Pilih file XLSX/XLS/CSV"
            accept=".xlsx,.xls,.csv"
            prepend-icon="mdi-paperclip"
            @change="onFileChange"
            :disabled="uploading"
          />

          <div class="d-flex mt-4">
            <VBtn color="primary" :loading="uploading" @click="submit">
              Mulai Import
            </VBtn>
          </div>

          <div v-if="result" class="mt-6">
            <VAlert
              :type="result.success ? 'success' : 'error'"
              variant="tonal"
            >
              <div v-if="result.success">
                Berhasil mengimpor: {{ result.imported }} baris. Terlewat:
                {{ result.skipped }}.
              </div>
              <div v-else>
                {{ result.message || 'Import gagal.' }}
              </div>
            </VAlert>

            <VCard
              v-if="result?.errors?.length"
              class="mt-4"
              variant="outlined"
            >
              <VCardTitle>Detail Error (maks 100 baris)</VCardTitle>
              <VCardText>
                <VList density="compact">
                  <VListItem
                    v-for="(err, i) in result.errors.slice(0, 100)"
                    :key="i"
                  >
                    <VListItemTitle>{{ err }}</VListItemTitle>
                  </VListItem>
                </VList>
              </VCardText>
            </VCard>
          </div>
        </VCardText>
      </VCard>
    </div>
  </AppLayout>
</template>
