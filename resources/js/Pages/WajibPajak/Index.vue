<template>
  <AppLayout title="Wajib Pajak">
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold">Daftar Wajib Pajak</h2>
        <div class="flex items-center gap-2">
          <input
            v-model="q"
            @input="debouncedSearch"
            placeholder="Cari nama / nopol / WA"
            class="px-3 py-2 border rounded"
          />
          <button
            @click="search"
            class="px-3 py-2 bg-blue-600 text-white rounded"
          >
            Cari
          </button>
          <button @click="reset" class="px-3 py-2 border rounded">Reset</button>
        </div>
      </div>

      <div class="bg-white rounded shadow">
        <table class="min-w-full text-left">
          <thead class="bg-gray-50">
            <tr>
              <th class="p-3">ID</th>
              <th class="p-3">Nama</th>
              <th class="p-3">Nopol</th>
              <th class="p-3">WA</th>
              <th class="p-3">Created</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in data.data" :key="row.id" class="border-t">
              <td class="p-3">{{ row.id }}</td>
              <td class="p-3">{{ row.nama }}</td>
              <td class="p-3">{{ row.nopol }}</td>
              <td class="p-3">{{ row.nomer_wa }}</td>
              <td class="p-3">{{ formatDate(row.created_at) }}</td>
            </tr>
          </tbody>
        </table>

        <div class="p-3 flex justify-between items-center">
          <div class="text-sm text-gray-600">
            Showing {{ data.from }} - {{ data.to }} of {{ data.total }}
          </div>
          <div class="flex gap-2">
            <button
              @click="goto(data.current_page - 1)"
              :disabled="data.current_page <= 1 || loading"
              class="px-3 py-1 border rounded"
            >
              Prev
            </button>
            <button
              @click="goto(data.current_page + 1)"
              :disabled="data.current_page >= data.last_page || loading"
              class="px-3 py-1 border rounded"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const q = ref('');
const loading = ref(false);
const data = ref({
  data: [],
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0,
});

const fetchList = async (page = 1) => {
  loading.value = true;
  try {
    const res = await axios.get('/admin/wajib-pajak', {
      params: { page, q: q.value || undefined },
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    // Controller should detect AJAX and return JSON paginator when X-Requested-With is set
    data.value = res.data;
  } finally {
    loading.value = false;
  }
};

let searchTimer;
const debouncedSearch = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetchList(1), 300);
};

const search = () => fetchList(1);
const reset = () => {
  q.value = '';
  fetchList(1);
};
const goto = (page) => {
  if (!page || page < 1 || page > (data.value.last_page || 1)) return;
  fetchList(page);
};

onMounted(() => fetchList(1));

const formatDate = (d) => (d ? new Date(d).toLocaleString('id-ID') : '-');
</script>

<style scoped>
.card {
  background: white;
}
</style>
