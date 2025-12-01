<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  stats: Object,
  settings: Object,
  predeterminedWinners: Array,
});

// Settings form
const lotreMode = ref(props.settings.lotre_mode);
const spinDuration = ref(props.settings.spin_duration_ms);
const savingSettings = ref(false);

// Winner picker
const searchQuery = ref('');
const searchResults = ref([]);
const searching = ref(false);
const searchTimeout = ref(null);
const selectedWinners = ref(
  props.predeterminedWinners.map((w, idx) => ({
    id: w.id,
    nama: w.nama,
    nopol: w.nopol,
    alamat: w.alamat,
    order: w.predetermined_winner_order || idx + 1,
  })),
);
const savingWinners = ref(false);

const isCustomMode = computed(() => lotreMode.value === 'custom');

// Search participants
const searchParticipants = async () => {
  if (searchQuery.value.length < 2) {
    searchResults.value = [];
    return;
  }

  searching.value = true;
  try {
    const response = await axios.get(route('admin.lotre.search-participants'), {
      params: { q: searchQuery.value },
    });
    // Filter out already selected winners
    const selectedIds = selectedWinners.value.map((w) => w.id);
    searchResults.value = response.data.filter(
      (p) => !selectedIds.includes(p.id),
    );
  } catch (error) {
    console.error('Search failed:', error);
    searchResults.value = [];
  } finally {
    searching.value = false;
  }
};

// Debounced search
watch(searchQuery, () => {
  clearTimeout(searchTimeout.value);
  searchTimeout.value = setTimeout(searchParticipants, 300);
});

// Add winner
const addWinner = (participant) => {
  const nextOrder = selectedWinners.value.length > 0
    ? Math.max(...selectedWinners.value.map((w) => w.order)) + 1
    : 1;

  selectedWinners.value.push({
    id: participant.id,
    nama: participant.nama,
    nopol: participant.nopol,
    alamat: participant.alamat,
    order: nextOrder,
  });

  searchQuery.value = '';
  searchResults.value = [];
};

// Remove winner
const removeWinner = (index) => {
  selectedWinners.value.splice(index, 1);
  // Reorder remaining winners
  selectedWinners.value.forEach((w, idx) => {
    w.order = idx + 1;
  });
};

// Move winner up/down
const moveWinner = (index, direction) => {
  const newIndex = index + direction;
  if (newIndex < 0 || newIndex >= selectedWinners.value.length) return;

  const temp = selectedWinners.value[index];
  selectedWinners.value[index] = selectedWinners.value[newIndex];
  selectedWinners.value[newIndex] = temp;

  // Update order numbers
  selectedWinners.value.forEach((w, idx) => {
    w.order = idx + 1;
  });
};

// Save settings
const saveSettings = async () => {
  savingSettings.value = true;
  try {
    await axios.post(route('admin.lotre.update-settings'), {
      lotre_mode: lotreMode.value,
      spin_duration_ms: spinDuration.value,
    });

    Swal.fire({
      icon: 'success',
      title: 'Berhasil',
      text: 'Pengaturan berhasil disimpan.',
      timer: 2000,
      showConfirmButton: false,
    });
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: error.response?.data?.message || 'Gagal menyimpan pengaturan.',
    });
  } finally {
    savingSettings.value = false;
  }
};

// Save predetermined winners
const saveWinners = async () => {
  savingWinners.value = true;
  try {
    const winners = selectedWinners.value.map((w) => ({
      id: w.id,
      order: w.order,
    }));

    const response = await axios.post(
      route('admin.lotre.set-predetermined-winners'),
      { winners },
    );

    Swal.fire({
      icon: 'success',
      title: 'Berhasil',
      text: 'Daftar pemenang berhasil disimpan.',
      timer: 2000,
      showConfirmButton: false,
    });
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: error.response?.data?.message || 'Gagal menyimpan daftar pemenang.',
    });
  } finally {
    savingWinners.value = false;
  }
};

// Clear all winners
const clearAllWinners = async () => {
  const result = await Swal.fire({
    icon: 'warning',
    title: 'Hapus Semua Pemenang?',
    text: 'Semua pemenang yang sudah ditentukan akan dihapus.',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'Ya, Hapus!',
    cancelButtonText: 'Batal',
  });

  if (!result.isConfirmed) return;

  try {
    await axios.post(route('admin.lotre.clear-predetermined-winners'));
    selectedWinners.value = [];

    Swal.fire({
      icon: 'success',
      title: 'Berhasil',
      text: 'Semua pemenang telah dihapus.',
      timer: 2000,
      showConfirmButton: false,
    });
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: error.response?.data?.message || 'Gagal menghapus pemenang.',
    });
  }
};
</script>

<template>
  <AppLayout title="Pengaturan Lotre">
    <Head title="Pengaturan Lotre" />

    <div class="space-y-6">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <VCard class="rounded-xl" variant="flat">
          <VCardText class="text-center">
            <div class="text-3xl font-bold text-blue-600">
              {{ stats.total_participants.toLocaleString() }}
            </div>
            <div class="text-gray-600">Total Peserta</div>
          </VCardText>
        </VCard>
        <VCard class="rounded-xl" variant="flat">
          <VCardText class="text-center">
            <div class="text-3xl font-bold text-green-600">
              {{ stats.total_winners }}
            </div>
            <div class="text-gray-600">Pemenang Terpilih</div>
          </VCardText>
        </VCard>
        <VCard class="rounded-xl" variant="flat">
          <VCardText class="text-center">
            <div class="text-3xl font-bold text-purple-600">
              {{ selectedWinners.length }}
            </div>
            <div class="text-gray-600">Pemenang Ditentukan</div>
          </VCardText>
        </VCard>
      </div>

      <!-- Settings Section -->
      <VCard class="rounded-xl" variant="flat">
        <VCardTitle class="text-lg font-semibold">
          <VIcon class="mr-2">mdi-cog</VIcon>
          Pengaturan Mode Lotre
        </VCardTitle>
        <VDivider />
        <VCardText>
          <div class="space-y-6">
            <!-- Mode Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Mode Pemilihan Pemenang
              </label>
              <VRadioGroup v-model="lotreMode" inline>
                <VRadio label="Random (Acak)" value="random" color="primary" />
                <VRadio
                  label="Custom (Pemenang Ditentukan)"
                  value="custom"
                  color="primary"
                />
              </VRadioGroup>
              <p class="text-sm text-gray-500 mt-1">
                <template v-if="lotreMode === 'random'">
                  Pemenang akan dipilih secara acak dari database peserta.
                </template>
                <template v-else>
                  Pemenang akan mengikuti urutan yang sudah ditentukan, tetapi
                  akan terlihat acak di tampilan publik.
                </template>
              </p>
            </div>

            <!-- Spin Duration -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Durasi Animasi Spin:
                {{ (spinDuration / 1000).toFixed(1) }} detik
              </label>
              <VSlider
                v-model="spinDuration"
                :min="1000"
                :max="15000"
                :step="500"
                color="primary"
                thumb-label
              >
                <template #thumb-label="{ modelValue }">
                  {{ (modelValue / 1000).toFixed(1) }}s
                </template>
              </VSlider>
            </div>

            <VBtn
              color="primary"
              :loading="savingSettings"
              @click="saveSettings"
              class="mt-4"
            >
              <VIcon class="mr-2">mdi-content-save</VIcon>
              Simpan Pengaturan
            </VBtn>
          </div>
        </VCardText>
      </VCard>

      <!-- Predetermined Winners Section (only shown for custom mode) -->
      <VCard v-if="isCustomMode" class="rounded-xl" variant="flat">
        <VCardTitle class="text-lg font-semibold">
          <VIcon class="mr-2">mdi-trophy</VIcon>
          Pemenang yang Ditentukan
        </VCardTitle>
        <VDivider />
        <VCardText>
          <!-- Search Input -->
          <div class="mb-4">
            <VTextField
              v-model="searchQuery"
              label="Cari peserta (nama/nopol)"
              placeholder="Ketik minimal 2 karakter..."
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="comfortable"
              :loading="searching"
              clearable
              hide-details
            />

            <!-- Search Results -->
            <VList
              v-if="searchResults.length > 0"
              class="mt-2 border rounded-lg max-h-60 overflow-y-auto"
              density="compact"
            >
              <VListItem
                v-for="participant in searchResults"
                :key="participant.id"
                @click="addWinner(participant)"
                class="cursor-pointer hover:bg-gray-50"
              >
                <template #prepend>
                  <VAvatar color="blue" size="36">
                    <VIcon color="white">mdi-account</VIcon>
                  </VAvatar>
                </template>
                <VListItemTitle class="font-medium">
                  {{ participant.nama }}
                </VListItemTitle>
                <VListItemSubtitle>
                  {{ participant.nopol }} • {{ participant.alamat }}
                </VListItemSubtitle>
                <template #append>
                  <VBtn icon size="small" color="success" variant="text">
                    <VIcon>mdi-plus</VIcon>
                  </VBtn>
                </template>
              </VListItem>
            </VList>

            <p
              v-if="
                searchQuery.length >= 2 &&
                searchResults.length === 0 &&
                !searching
              "
              class="text-sm text-gray-500 mt-2"
            >
              Tidak ada peserta ditemukan.
            </p>
          </div>

          <!-- Selected Winners List -->
          <div v-if="selectedWinners.length > 0" class="mt-6">
            <div class="flex justify-between items-center mb-3">
              <h4 class="font-medium text-gray-800">
                Urutan Pemenang ({{ selectedWinners.length }} orang)
              </h4>
              <div class="flex gap-2">
                <VBtn
                  color="error"
                  variant="outlined"
                  size="small"
                  @click="clearAllWinners"
                >
                  <VIcon class="mr-1">mdi-delete-sweep</VIcon>
                  Hapus Semua
                </VBtn>
                <VBtn
                  color="success"
                  size="small"
                  :loading="savingWinners"
                  @click="saveWinners"
                >
                  <VIcon class="mr-1">mdi-content-save</VIcon>
                  Simpan Urutan
                </VBtn>
              </div>
            </div>

            <VList class="border rounded-lg" density="compact">
              <TransitionGroup name="list">
                <VListItem
                  v-for="(winner, index) in selectedWinners"
                  :key="winner.id"
                  class="border-b last:border-b-0"
                >
                  <template #prepend>
                    <div
                      class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white mr-3"
                      :class="{
                        'bg-yellow-500': index === 0,
                        'bg-gray-400': index === 1,
                        'bg-orange-600': index === 2,
                        'bg-blue-500': index > 2,
                      }"
                    >
                      {{ index + 1 }}
                    </div>
                  </template>
                  <VListItemTitle class="font-medium">
                    {{ winner.nama }}
                  </VListItemTitle>
                  <VListItemSubtitle>
                    {{ winner.nopol }} • {{ winner.alamat }}
                  </VListItemSubtitle>
                  <template #append>
                    <div class="flex items-center gap-1">
                      <VBtn
                        icon
                        size="x-small"
                        variant="text"
                        :disabled="index === 0"
                        @click="moveWinner(index, -1)"
                      >
                        <VIcon>mdi-arrow-up</VIcon>
                      </VBtn>
                      <VBtn
                        icon
                        size="x-small"
                        variant="text"
                        :disabled="index === selectedWinners.length - 1"
                        @click="moveWinner(index, 1)"
                      >
                        <VIcon>mdi-arrow-down</VIcon>
                      </VBtn>
                      <VBtn
                        icon
                        size="x-small"
                        color="error"
                        variant="text"
                        @click="removeWinner(index)"
                      >
                        <VIcon>mdi-close</VIcon>
                      </VBtn>
                    </div>
                  </template>
                </VListItem>
              </TransitionGroup>
            </VList>
          </div>

          <VAlert v-else type="info" variant="tonal" class="mt-4">
            Belum ada pemenang yang ditentukan. Gunakan pencarian di atas untuk
            menambahkan peserta sebagai pemenang.
          </VAlert>
        </VCardText>
      </VCard>
    </div>
  </AppLayout>
</template>

<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(-30px);
}
</style>
