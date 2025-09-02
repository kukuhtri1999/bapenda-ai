<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold">Daftar Wajib Pajak</h2>
      <div class="flex items-center gap-2">
        <input
          v-model="q"
          @keyup.enter="search"
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
            :disabled="!data.prev_page_url"
            class="px-3 py-1 border rounded"
          >
            Prev
          </button>
          <button
            @click="goto(data.current_page + 1)"
            :disabled="!data.next_page_url"
            class="px-3 py-1 border rounded"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    wajibPajak: { type: Object, required: true },
  },
  data() {
    return {
      q: '',
      data: this.wajibPajak,
    };
  },
  methods: {
    formatDate(d) {
      return d ? new Date(d).toLocaleString() : '-';
    },
    async search() {
      const url = `/admin/wajib-pajak?q=${encodeURIComponent(this.q)}`;
      const res = await fetch(url);
      const doc = await res.text();
      // simple navigation to update via Inertia (server will render)
      if (typeof window !== 'undefined') {
        window.location.href = url;
      }
    },
    reset() {
      this.q = '';
      window.location.href = '/admin/wajib-pajak';
    },
    goto(page) {
      if (!page || page < 1) return;
      window.location.href = `/admin/wajib-pajak?page=${page}${this.q ? `&q=${encodeURIComponent(this.q)}` : ''}`;
    },
  },
};
</script>

<style scoped>
.card {
  background: white;
}
</style>
