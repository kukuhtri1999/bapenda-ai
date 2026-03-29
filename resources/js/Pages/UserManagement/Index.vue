<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, reactive, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const authId = computed(() => usePage().props.auth?.user?.id);

const users      = ref([]);
const roles      = ref([]);
const stats      = reactive({ total: 0, active: 0, inactive: 0, deleted: 0 });
const loading    = ref(false);
const saving     = ref(false);
const page       = ref(1);
const lastPage   = ref(1);
const total      = ref(0);
const toast      = reactive({ show: false, msg: '', type: 'success' });

const q            = ref('');
const roleFilter   = ref('');
const statusFilter = ref('all');
const perPage      = ref(15);

const createDialog = ref(false);
const editDialog   = ref(false);
const viewDialog   = ref(false);
const deleteDialog = ref(false);
const pwdDialog    = ref(false);
const forceDialog  = ref(false);

const selectedUser = ref(null);
const formErrors   = ref({});

const createForm = reactive({ name: '', email: '', password: '', password_confirmation: '', role: '', is_active: true });
const editForm   = reactive({ name: '', email: '', role: '', is_active: true });
const pwdForm    = reactive({ password: '', password_confirmation: '' });

const showToast = (msg, type = 'success') => {
  toast.msg = msg; toast.type = type; toast.show = true;
  setTimeout(() => { toast.show = false; }, 3500);
};

const getInitials = (name) =>
  (name || '').split(' ').map(w => w[0] || '').join('').substring(0, 2).toUpperCase();

const avatarColors = ['#6c33a0','#0066cc','#c0392b','#16a085','#8e44ad','#2980b9','#27ae60'];
const getAvatarColor = (name) => avatarColors[(name || '').charCodeAt(0) % avatarColors.length];

const formatDate = (iso) => {
  if (!iso) return '—';
  const d = new Date(iso);
  return `${d.getDate()} ${['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][d.getMonth()]} ${d.getFullYear()}`;
};

const formatDateTime = (iso) => {
  if (!iso) return '—';
  const d = new Date(iso);
  return `${formatDate(iso)}, ${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`;
};

const clearErrors = () => { formErrors.value = {}; };

const fetchUsers = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/admin/users', {
      params: { page: page.value, per_page: perPage.value, q: q.value || undefined, role: roleFilter.value || undefined, status: statusFilter.value },
    });
    users.value   = data.data;
    page.value    = data.current_page;
    lastPage.value = data.last_page;
    total.value   = data.total;
  } catch { showToast('Gagal memuat data.', 'error'); }
  finally { loading.value = false; }
};

const fetchStats = async () => {
  try { const { data } = await axios.get('/api/admin/users/stats'); Object.assign(stats, data); } catch {}
};

const fetchRoles = async () => {
  try { const { data } = await axios.get('/api/admin/roles'); roles.value = data; } catch {}
};

const go = (p) => { if (p < 1 || p > lastPage.value) return; page.value = p; fetchUsers(); };
let dbTimer;
const debouncedFetch = () => { clearTimeout(dbTimer); dbTimer = setTimeout(() => { page.value = 1; fetchUsers(); }, 320); };
const clearFilters = () => { q.value = ''; roleFilter.value = ''; statusFilter.value = 'all'; page.value = 1; fetchUsers(); };

const openCreate = () => {
  Object.assign(createForm, { name: '', email: '', password: '', password_confirmation: '', role: roles.value[0]?.name || '', is_active: true });
  clearErrors(); createDialog.value = true;
};
const submitCreate = async () => {
  clearErrors(); saving.value = true;
  try {
    await axios.post('/api/admin/users', createForm);
    createDialog.value = false; showToast('User berhasil dibuat.');
    await Promise.all([fetchUsers(), fetchStats()]);
  } catch (e) {
    if (e.response?.status === 422) formErrors.value = e.response.data.errors || {};
    else showToast(e.response?.data?.message || 'Gagal membuat user.', 'error');
  } finally { saving.value = false; }
};

const openEdit = (user) => {
  selectedUser.value = user;
  Object.assign(editForm, { name: user.name, email: user.email, role: user.roles[0] || '', is_active: user.is_active });
  clearErrors(); editDialog.value = true;
};
const submitEdit = async () => {
  clearErrors(); saving.value = true;
  try {
    const { data } = await axios.put(`/api/admin/users/${selectedUser.value.id}`, editForm);
    const idx = users.value.findIndex(u => u.id === selectedUser.value.id);
    if (idx !== -1) users.value[idx] = data.user;
    editDialog.value = false; showToast('User berhasil diperbarui.'); fetchStats();
  } catch (e) {
    if (e.response?.status === 422) formErrors.value = e.response.data.errors || {};
    else showToast(e.response?.data?.message || 'Gagal memperbarui user.', 'error');
  } finally { saving.value = false; }
};

const openView  = (user) => { selectedUser.value = user; viewDialog.value = true; };

const openPwd = (user) => {
  selectedUser.value = user; Object.assign(pwdForm, { password: '', password_confirmation: '' }); clearErrors(); pwdDialog.value = true;
};
const submitPwd = async () => {
  clearErrors(); saving.value = true;
  try {
    await axios.post(`/api/admin/users/${selectedUser.value.id}/change-password`, pwdForm);
    pwdDialog.value = false; showToast('Password berhasil diubah.');
  } catch (e) {
    if (e.response?.status === 422) formErrors.value = e.response.data.errors || {};
    else showToast(e.response?.data?.message || 'Gagal mengubah password.', 'error');
  } finally { saving.value = false; }
};

const toggleStatus = async (user) => {
  try {
    const { data } = await axios.post(`/api/admin/users/${user.id}/toggle-status`);
    const idx = users.value.findIndex(u => u.id === user.id);
    if (idx !== -1) users.value[idx] = data.user;
    showToast(data.message); fetchStats();
  } catch (e) { showToast(e.response?.data?.message || 'Gagal mengubah status.', 'error'); }
};

const openDelete  = (user) => { selectedUser.value = user; deleteDialog.value = true; };
const confirmDelete = async () => {
  saving.value = true;
  try {
    await axios.delete(`/api/admin/users/${selectedUser.value.id}`);
    deleteDialog.value = false; showToast('User berhasil dihapus.');
    await Promise.all([fetchUsers(), fetchStats()]);
  } catch (e) { showToast(e.response?.data?.message || 'Gagal menghapus user.', 'error'); }
  finally { saving.value = false; }
};

const restoreUser = async (user) => {
  try {
    const { data } = await axios.post(`/api/admin/users/${user.id}/restore`);
    const idx = users.value.findIndex(u => u.id === user.id);
    if (idx !== -1) users.value[idx] = data.user;
    showToast('User berhasil dipulihkan.'); fetchStats();
  } catch (e) { showToast(e.response?.data?.message || 'Gagal memulihkan user.', 'error'); }
};

const openForce = (user) => { selectedUser.value = user; forceDialog.value = true; };
const confirmForce = async () => {
  saving.value = true;
  try {
    await axios.delete(`/api/admin/users/${selectedUser.value.id}/force-delete`);
    forceDialog.value = false; showToast('User dihapus permanen.');
    await Promise.all([fetchUsers(), fetchStats()]);
  } catch (e) { showToast(e.response?.data?.message || 'Gagal menghapus permanen.', 'error'); }
  finally { saving.value = false; }
};

onMounted(async () => {
  await Promise.all([fetchRoles(), fetchStats()]);
  fetchUsers();
});
</script>

<template>
  <AppLayout title="Manajemen Pengguna">
    <div class="um-page">

      <!-- Toast -->
      <Transition name="toast-slide">
        <div v-if="toast.show" :class="`um-toast um-toast--${toast.type}`">
          <svg v-if="toast.type==='success'" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          {{ toast.msg }}
        </div>
      </Transition>

      <!-- Header -->
      <div class="um-header mb-5">
        <div>
          <h1 class="um-title">Manajemen Pengguna</h1>
          <p class="um-sub">Kelola akun, peran, dan akses pengguna sistem SALMA AI</p>
        </div>
        <button class="um-btn-primary" @click="openCreate">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Tambah Pengguna
        </button>
      </div>

      <!-- Stats -->
      <div class="um-stats mb-5">
        <div class="stat-card stat-card--total">
          <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
          <div><div class="stat-num">{{ stats.total }}</div><div class="stat-lbl">Total</div></div>
        </div>
        <div class="stat-card stat-card--active">
          <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
          <div><div class="stat-num">{{ stats.active }}</div><div class="stat-lbl">Aktif</div></div>
        </div>
        <div class="stat-card stat-card--inactive">
          <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></div>
          <div><div class="stat-num">{{ stats.inactive }}</div><div class="stat-lbl">Nonaktif</div></div>
        </div>
        <div class="stat-card stat-card--deleted">
          <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg></div>
          <div><div class="stat-num">{{ stats.deleted }}</div><div class="stat-lbl">Dihapus</div></div>
        </div>
      </div>

      <!-- Filters -->
      <div class="um-filter-card mb-4">
        <div class="um-filter-grid">
          <div class="um-field">
            <label class="um-label">Cari</label>
            <div class="um-input-wrap">
              <svg class="um-input-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input v-model="q" @input="debouncedFetch" placeholder="Nama atau email…" class="um-input um-input-padded" />
            </div>
          </div>
          <div class="um-field">
            <label class="um-label">Role</label>
            <select v-model="roleFilter" @change="page=1; fetchUsers()" class="um-input">
              <option value="">Semua Role</option>
              <option v-for="r in roles" :key="r.id" :value="r.name">{{ r.name }}</option>
            </select>
          </div>
          <div class="um-field">
            <label class="um-label">Status</label>
            <select v-model="statusFilter" @change="page=1; fetchUsers()" class="um-input">
              <option value="all">Semua</option>
              <option value="active">Aktif</option>
              <option value="inactive">Nonaktif</option>
              <option value="deleted">Dihapus</option>
            </select>
          </div>
          <div class="um-field">
            <label class="um-label">Per Halaman</label>
            <select v-model.number="perPage" @change="page=1; fetchUsers()" class="um-input">
              <option v-for="n in [10,15,25,50]" :key="n" :value="n">{{ n }}</option>
            </select>
          </div>
          <div class="um-field um-field-action">
            <button @click="clearFilters" class="um-btn-outline">
              <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.51"/></svg>
              Reset
            </button>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="um-table-card">
        <Transition name="fade">
          <div v-if="loading" class="um-loading">
            <svg class="um-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
            <span>Memuat data…</span>
          </div>
        </Transition>
        <div class="um-table-wrap">
          <table class="um-table">
            <thead>
              <tr>
                <th>Pengguna</th>
                <th class="th-role">Role</th>
                <th class="th-status">Status</th>
                <th class="th-date">Dibuat</th>
                <th class="th-act">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users" :key="user.id" :class="{ 'tr-deleted': user.deleted_at }">
                <td>
                  <div class="um-user-cell">
                    <div class="um-avatar" :style="{ background: getAvatarColor(user.name) }">{{ getInitials(user.name) }}</div>
                    <div class="um-user-info">
                      <div class="um-user-name">{{ user.name }}</div>
                      <div class="um-user-email">{{ user.email }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <span v-if="user.roles.length" class="um-role-badge">{{ user.roles[0] }}</span>
                  <span v-else class="td-empty">—</span>
                </td>
                <td>
                  <span v-if="user.deleted_at"    class="um-sbadge um-sbadge--deleted">Dihapus</span>
                  <span v-else-if="user.is_active" class="um-sbadge um-sbadge--active">Aktif</span>
                  <span v-else                     class="um-sbadge um-sbadge--inactive">Nonaktif</span>
                </td>
                <td class="td-date">{{ formatDate(user.created_at) }}</td>
                <td>
                  <div class="um-row-actions">
                    <button class="act-btn act-btn--view" @click="openView(user)" title="Lihat Detail">
                      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                    <template v-if="!user.deleted_at">
                      <button class="act-btn act-btn--edit" @click="openEdit(user)" title="Edit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                      </button>
                      <button class="act-btn act-btn--pwd" @click="openPwd(user)" title="Ubah Password">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                      </button>
                      <button v-if="user.id !== authId" class="act-btn" :class="user.is_active ? 'act-btn--deactivate' : 'act-btn--activate'" @click="toggleStatus(user)" :title="user.is_active ? 'Nonaktifkan' : 'Aktifkan'">
                        <svg v-if="user.is_active"  xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                      </button>
                      <button v-if="user.id !== authId" class="act-btn act-btn--delete" @click="openDelete(user)" title="Hapus">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                      </button>
                    </template>
                    <template v-else>
                      <button class="act-btn act-btn--restore" @click="restoreUser(user)" title="Pulihkan">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.51"/></svg>
                      </button>
                      <button class="act-btn act-btn--delete" @click="openForce(user)" title="Hapus Permanen">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                      </button>
                    </template>
                  </div>
                </td>
              </tr>
              <tr v-if="!loading && users.length === 0">
                <td colspan="5" class="um-empty">
                  <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                  <span>Tidak ada pengguna ditemukan</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="um-footer">
          <div class="um-page-info">Halaman <strong>{{ page }}</strong> dari <strong>{{ lastPage }}</strong> &mdash; <strong>{{ total }}</strong> pengguna</div>
          <div class="um-page-btns">
            <button :disabled="page<=1||loading" @click="go(page-1)" class="um-page-btn"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>Prev</button>
            <button :disabled="page>=lastPage||loading" @click="go(page+1)" class="um-page-btn">Next<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>
          </div>
        </div>
      </div>

      <!-- ── CREATE DIALOG ─────────────────────────────────────────────── -->
      <Transition name="dlg-fade"><div v-if="createDialog" class="dlg-backdrop" @click.self="createDialog = false">
        <div class="dlg-box">
          <div class="dlg-head">
            <div class="dlg-head-left">
              <div class="dlg-icon dlg-icon--create"><svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></div>
              <div><div class="dlg-title">Tambah Pengguna Baru</div><div class="dlg-sub">Isi semua field yang wajib diisi</div></div>
            </div>
            <button class="dlg-close" @click="createDialog = false"><svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
          </div>
          <div class="dlg-body">
            <div class="form-grid">
              <div class="form-field" :class="{'form-field--error':formErrors.name}">
                <label class="form-label">Nama Lengkap <span class="req">*</span></label>
                <input v-model="createForm.name" class="form-input" placeholder="Masukkan nama lengkap" />
                <span v-if="formErrors.name" class="form-error">{{ formErrors.name[0] }}</span>
              </div>
              <div class="form-field" :class="{'form-field--error':formErrors.email}">
                <label class="form-label">Email <span class="req">*</span></label>
                <input v-model="createForm.email" type="email" class="form-input" placeholder="nama@email.com" />
                <span v-if="formErrors.email" class="form-error">{{ formErrors.email[0] }}</span>
              </div>
              <div class="form-field" :class="{'form-field--error':formErrors.password}">
                <label class="form-label">Password <span class="req">*</span></label>
                <input v-model="createForm.password" type="password" class="form-input" placeholder="Min. 8 karakter" />
                <span v-if="formErrors.password" class="form-error">{{ formErrors.password[0] }}</span>
              </div>
              <div class="form-field">
                <label class="form-label">Konfirmasi Password <span class="req">*</span></label>
                <input v-model="createForm.password_confirmation" type="password" class="form-input" placeholder="Ulangi password" />
              </div>
              <div class="form-field" :class="{'form-field--error':formErrors.role}">
                <label class="form-label">Role <span class="req">*</span></label>
                <select v-model="createForm.role" class="form-input">
                  <option value="" disabled>Pilih role...</option>
                  <option v-for="r in roles" :key="r.id" :value="r.name">{{ r.name }}</option>
                </select>
                <span v-if="formErrors.role" class="form-error">{{ formErrors.role[0] }}</span>
              </div>
              <div class="form-field">
                <label class="form-label">Status Akun</label>
                <label class="form-toggle" :class="{active: createForm.is_active}">
                  <input type="checkbox" v-model="createForm.is_active" style="display:none" />
                  <span class="toggle-knob"></span>
                  <span class="toggle-label">{{ createForm.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </label>
              </div>
            </div>
          </div>
          <div class="dlg-foot">
            <button class="um-btn-outline" @click="createDialog = false">Batal</button>
            <button class="um-btn-primary" :disabled="saving" @click="submitCreate">
              <svg v-if="saving" class="btn-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
              {{ saving ? 'Menyimpan…' : 'Buat Pengguna' }}
            </button>
          </div>
        </div>
      </div></Transition>

      <!-- ── EDIT DIALOG ───────────────────────────────────────────────── -->
      <Transition name="dlg-fade"><div v-if="editDialog" class="dlg-backdrop" @click.self="editDialog = false">
        <div class="dlg-box">
          <div class="dlg-head">
            <div class="dlg-head-left">
              <div class="dlg-icon dlg-icon--edit"><svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></div>
              <div><div class="dlg-title">Edit Pengguna</div><div class="dlg-sub" v-if="selectedUser">{{ selectedUser.email }}</div></div>
            </div>
            <button class="dlg-close" @click="editDialog = false"><svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
          </div>
          <div class="dlg-body">
            <div class="form-grid">
              <div class="form-field" :class="{'form-field--error':formErrors.name}">
                <label class="form-label">Nama Lengkap <span class="req">*</span></label>
                <input v-model="editForm.name" class="form-input" />
                <span v-if="formErrors.name" class="form-error">{{ formErrors.name[0] }}</span>
              </div>
              <div class="form-field" :class="{'form-field--error':formErrors.email}">
                <label class="form-label">Email <span class="req">*</span></label>
                <input v-model="editForm.email" type="email" class="form-input" />
                <span v-if="formErrors.email" class="form-error">{{ formErrors.email[0] }}</span>
              </div>
              <div class="form-field" :class="{'form-field--error':formErrors.role}">
                <label class="form-label">Role <span class="req">*</span></label>
                <select v-model="editForm.role" class="form-input">
                  <option v-for="r in roles" :key="r.id" :value="r.name">{{ r.name }}</option>
                </select>
                <span v-if="formErrors.role" class="form-error">{{ formErrors.role[0] }}</span>
              </div>
              <div class="form-field">
                <label class="form-label">Status Akun</label>
                <label class="form-toggle" :class="{active: editForm.is_active}">
                  <input type="checkbox" v-model="editForm.is_active" style="display:none" />
                  <span class="toggle-knob"></span>
                  <span class="toggle-label">{{ editForm.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </label>
              </div>
            </div>
          </div>
          <div class="dlg-foot">
            <button class="um-btn-outline" @click="editDialog = false">Batal</button>
            <button class="um-btn-primary" :disabled="saving" @click="submitEdit">
              <svg v-if="saving" class="btn-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
              {{ saving ? 'Menyimpan…' : 'Simpan Perubahan' }}
            </button>
          </div>
        </div>
      </div></Transition>

      <!-- ── CHANGE PASSWORD DIALOG ────────────────────────────────────── -->
      <Transition name="dlg-fade"><div v-if="pwdDialog" class="dlg-backdrop" @click.self="pwdDialog = false">
        <div class="dlg-box dlg-box--sm">
          <div class="dlg-head">
            <div class="dlg-head-left">
              <div class="dlg-icon dlg-icon--pwd"><svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
              <div><div class="dlg-title">Ubah Password</div><div class="dlg-sub" v-if="selectedUser">{{ selectedUser.name }}</div></div>
            </div>
            <button class="dlg-close" @click="pwdDialog = false"><svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
          </div>
          <div class="dlg-body">
            <div class="form-grid">
              <div class="form-field form-field--full" :class="{'form-field--error':formErrors.password}">
                <label class="form-label">Password Baru <span class="req">*</span></label>
                <input v-model="pwdForm.password" type="password" class="form-input" placeholder="Min. 8 karakter" />
                <span v-if="formErrors.password" class="form-error">{{ formErrors.password[0] }}</span>
              </div>
              <div class="form-field form-field--full">
                <label class="form-label">Konfirmasi Password <span class="req">*</span></label>
                <input v-model="pwdForm.password_confirmation" type="password" class="form-input" placeholder="Ulangi password baru" />
              </div>
            </div>
          </div>
          <div class="dlg-foot">
            <button class="um-btn-outline" @click="pwdDialog = false">Batal</button>
            <button class="um-btn-primary um-btn--pwd" :disabled="saving" @click="submitPwd">
              <svg v-if="saving" class="btn-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
              {{ saving ? 'Menyimpan…' : 'Simpan Password' }}
            </button>
          </div>
        </div>
      </div></Transition>

      <!-- ── VIEW DIALOG ───────────────────────────────────────────────── -->
      <Transition name="dlg-fade"><div v-if="viewDialog && selectedUser" class="dlg-backdrop" @click.self="viewDialog = false">
        <div class="dlg-box dlg-box--sm">
          <div class="dlg-head">
            <div class="dlg-head-left">
              <div class="um-avatar um-avatar--lg" :style="{background: getAvatarColor(selectedUser.name)}">{{ getInitials(selectedUser.name) }}</div>
              <div><div class="dlg-title">{{ selectedUser.name }}</div><div class="dlg-sub">{{ selectedUser.email }}</div></div>
            </div>
            <button class="dlg-close" @click="viewDialog = false"><svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
          </div>
          <div class="dlg-body">
            <div class="view-grid">
              <div class="view-row"><span class="view-lbl">Role</span><span class="um-role-badge" v-if="selectedUser.roles.length">{{ selectedUser.roles[0] }}</span><span v-else class="td-empty">Tidak ada role</span></div>
              <div class="view-row"><span class="view-lbl">Status</span>
                <span v-if="selectedUser.deleted_at"    class="um-sbadge um-sbadge--deleted">Dihapus</span>
                <span v-else-if="selectedUser.is_active" class="um-sbadge um-sbadge--active">Aktif</span>
                <span v-else                             class="um-sbadge um-sbadge--inactive">Nonaktif</span>
              </div>
              <div class="view-row"><span class="view-lbl">Email Terverifikasi</span><span class="view-val">{{ selectedUser.email_verified_at ? formatDateTime(selectedUser.email_verified_at) : 'Belum' }}</span></div>
              <div class="view-row"><span class="view-lbl">Dibuat</span><span class="view-val">{{ formatDateTime(selectedUser.created_at) }}</span></div>
              <div class="view-row"><span class="view-lbl">Diperbarui</span><span class="view-val">{{ formatDateTime(selectedUser.updated_at) }}</span></div>
              <div v-if="selectedUser.deleted_at" class="view-row"><span class="view-lbl">Dihapus Pada</span><span class="view-val view-val--danger">{{ formatDateTime(selectedUser.deleted_at) }}</span></div>
              <div class="view-row"><span class="view-lbl">ID Pengguna</span><span class="view-val view-val--mono">#{{ selectedUser.id }}</span></div>
            </div>
          </div>
          <div class="dlg-foot">
            <button class="um-btn-outline" @click="viewDialog = false">Tutup</button>
            <button v-if="!selectedUser.deleted_at" class="um-btn-secondary" @click="viewDialog = false; openEdit(selectedUser)">Edit</button>
          </div>
        </div>
      </div></Transition>

      <!-- ── DELETE CONFIRM ────────────────────────────────────────────── -->
      <Transition name="dlg-fade"><div v-if="deleteDialog && selectedUser" class="dlg-backdrop" @click.self="deleteDialog = false">
        <div class="dlg-box dlg-box--confirm">
          <div class="dlg-confirm-icon dlg-confirm-icon--warn"><svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg></div>
          <div class="dlg-confirm-title">Hapus Pengguna?</div>
          <div class="dlg-confirm-sub">Pengguna <strong>{{ selectedUser.name }}</strong> akan dipindahkan ke tempat sampah dan bisa dipulihkan nanti.</div>
          <div class="dlg-confirm-btns">
            <button class="um-btn-outline" @click="deleteDialog = false">Batal</button>
            <button class="um-btn-danger" :disabled="saving" @click="confirmDelete">
              <svg v-if="saving" class="btn-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
              {{ saving ? 'Menghapus…' : 'Ya, Hapus' }}
            </button>
          </div>
        </div>
      </div></Transition>

      <!-- ── FORCE DELETE CONFIRM ──────────────────────────────────────── -->
      <Transition name="dlg-fade"><div v-if="forceDialog && selectedUser" class="dlg-backdrop" @click.self="forceDialog = false">
        <div class="dlg-box dlg-box--confirm">
          <div class="dlg-confirm-icon dlg-confirm-icon--danger"><svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
          <div class="dlg-confirm-title">Hapus Permanen?</div>
          <div class="dlg-confirm-sub">Pengguna <strong>{{ selectedUser.name }}</strong> akan dihapus <strong>permanen</strong> dan tidak dapat dipulihkan kembali.</div>
          <div class="dlg-confirm-btns">
            <button class="um-btn-outline" @click="forceDialog = false">Batal</button>
            <button class="um-btn-danger" :disabled="saving" @click="confirmForce">
              <svg v-if="saving" class="btn-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
              {{ saving ? 'Menghapus…' : 'Hapus Permanen' }}
            </button>
          </div>
        </div>
      </div></Transition>

    </div>
  </AppLayout>
</template>

<style scoped>
.mb-4 { margin-bottom: 16px; }
.mb-5 { margin-bottom: 20px; }
.um-page { padding: 24px; max-width: 1280px; margin: 0 auto; }

/* Toast */
.um-toast { position:fixed; top:20px; right:20px; z-index:9999; display:inline-flex; align-items:center; gap:8px; padding:12px 18px; border-radius:10px; font-size:.875rem; font-weight:500; box-shadow:0 8px 24px rgba(0,0,0,.14); }
.um-toast--success { background:#1e293b; color:#fff; }
.um-toast--error   { background:#7f1d1d; color:#fca5a5; }
.toast-slide-enter-active,.toast-slide-leave-active { transition:all .3s ease; }
.toast-slide-enter-from,.toast-slide-leave-to { opacity:0; transform:translateX(30px); }

/* Header */
.um-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
.um-title  { font-size:1.375rem; font-weight:700; color:#1e293b; margin:0 0 2px; }
.um-sub    { font-size:.875rem; color:#64748b; margin:0; }

/* Stats */
.um-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }
@media(max-width:640px){ .um-stats { grid-template-columns:1fr 1fr; } }
.stat-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:16px 18px; display:flex; align-items:center; gap:14px; transition:box-shadow .2s; }
.stat-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.08); }
.stat-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.stat-card--total    .stat-icon { background:#ede9fe; color:#5b21b6; }
.stat-card--active   .stat-icon { background:#dcfce7; color:#15803d; }
.stat-card--inactive .stat-icon { background:#fef3c7; color:#b45309; }
.stat-card--deleted  .stat-icon { background:#fee2e2; color:#b91c1c; }
.stat-num { font-size:1.5rem; font-weight:700; color:#1e293b; line-height:1; }
.stat-lbl { font-size:.72rem; color:#64748b; margin-top:3px; }

/* Filters */
.um-filter-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px 18px; }
.um-filter-grid { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; }
.um-field       { display:flex; flex-direction:column; gap:5px; }
.um-field-action{ justify-content:flex-end; margin-left:auto; }
.um-label       { font-size:.72rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; }
.um-input-wrap  { position:relative; display:flex; align-items:center; }
.um-input-icon  { position:absolute; left:10px; color:#94a3b8; pointer-events:none; }
.um-input { height:36px; padding:0 10px; border:1px solid #e2e8f0; border-radius:8px; font-size:.875rem; color:#1e293b; background:#fff; outline:none; min-width:140px; transition:border-color .2s; appearance:auto; }
.um-input-padded { padding-left:32px; }
.um-input:focus  { border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.08); }

/* Buttons */
.um-btn-primary { display:inline-flex; align-items:center; gap:6px; height:38px; padding:0 18px; border:none; border-radius:9px; background:linear-gradient(135deg,#6c33a0,#9b59d0); color:#fff; font-size:.875rem; font-weight:600; cursor:pointer; transition:all .2s; white-space:nowrap; }
.um-btn-primary:hover:not(:disabled) { background:linear-gradient(135deg,#5b2a8a,#8b49c0); box-shadow:0 4px 14px rgba(108,51,160,.35); }
.um-btn-primary:disabled { opacity:.6; cursor:not-allowed; }
.um-btn--pwd { background:linear-gradient(135deg,#0066cc,#00aed6) !important; }
.um-btn--pwd:hover:not(:disabled) { background:linear-gradient(135deg,#0055aa,#009ab8) !important; box-shadow:0 4px 14px rgba(0,102,204,.35); }
.um-btn-secondary { display:inline-flex; align-items:center; gap:6px; height:38px; padding:0 18px; border:1.5px solid #7c3aed; border-radius:9px; background:transparent; color:#7c3aed; font-size:.875rem; font-weight:600; cursor:pointer; transition:all .2s; }
.um-btn-secondary:hover { background:#faf5ff; }
.um-btn-outline { display:inline-flex; align-items:center; gap:6px; height:38px; padding:0 16px; border:1px solid #e2e8f0; border-radius:9px; background:#fff; color:#64748b; font-size:.875rem; font-weight:500; cursor:pointer; transition:all .15s; white-space:nowrap; }
.um-btn-outline:hover { border-color:#7c3aed; color:#7c3aed; background:#faf5ff; }
.um-btn-danger { display:inline-flex; align-items:center; gap:6px; height:38px; padding:0 18px; border:none; border-radius:9px; background:linear-gradient(135deg,#dc2626,#ef4444); color:#fff; font-size:.875rem; font-weight:600; cursor:pointer; transition:all .2s; }
.um-btn-danger:hover:not(:disabled) { background:linear-gradient(135deg,#b91c1c,#dc2626); }
.um-btn-danger:disabled { opacity:.6; cursor:not-allowed; }

/* Table */
.um-table-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; position:relative; }
.um-loading { position:absolute; inset:0; background:rgba(255,255,255,.8); backdrop-filter:blur(4px); display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; z-index:10; font-size:.875rem; color:#64748b; }
.um-spinner  { width:28px; height:28px; animation:spin .8s linear infinite; }
.btn-spinner { width:14px; height:14px; animation:spin .8s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }
.um-table-wrap { overflow-x:auto; }
.um-table { width:100%; border-collapse:collapse; font-size:.875rem; }
.um-table thead tr { background:#f8fafc; }
.um-table th { padding:11px 14px; text-align:left; font-size:.72rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; border-bottom:1px solid #e2e8f0; }
.um-table td { padding:12px 14px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.um-table tbody tr:last-child td { border-bottom:none; }
.um-table tbody tr:hover { background:#fafafa; }
.tr-deleted td { opacity:.55; }
.th-role   { width:130px; }
.th-status { width:110px; }
.th-date   { width:120px; white-space:nowrap; }
.th-act    { width:180px; }
.td-date   { color:#64748b; font-size:.8125rem; }
.td-empty  { color:#94a3b8; font-size:.8125rem; }

/* User cell */
.um-user-cell  { display:flex; align-items:center; gap:10px; }
.um-avatar { width:36px; height:36px; border-radius:10px; color:#fff; font-size:.75rem; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.um-avatar--lg { width:44px; height:44px; border-radius:12px; font-size:1rem; }
.um-user-name  { font-weight:600; color:#1e293b; }
.um-user-email { font-size:.78rem; color:#64748b; }

/* Badges */
.um-role-badge { display:inline-block; padding:3px 10px; border-radius:20px; background:#ede9fe; color:#5b21b6; font-size:.75rem; font-weight:600; text-transform:capitalize; }
.um-sbadge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.75rem; font-weight:600; }
.um-sbadge--active   { background:#dcfce7; color:#15803d; }
.um-sbadge--inactive { background:#fef3c7; color:#b45309; }
.um-sbadge--deleted  { background:#fee2e2; color:#b91c1c; }

/* Row actions */
.um-row-actions { display:flex; align-items:center; gap:5px; flex-wrap:wrap; }
.act-btn { width:30px; height:30px; border-radius:7px; border:1px solid #e2e8f0; background:#fff; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; transition:all .15s; }
.act-btn--view:hover       { background:#e0f2fe; border-color:#7dd3fc; color:#0369a1; }
.act-btn--edit:hover       { background:#ede9fe; border-color:#c4b5fd; color:#5b21b6; }
.act-btn--pwd:hover        { background:#dbeafe; border-color:#93c5fd; color:#0066cc; }
.act-btn--activate:hover   { background:#dcfce7; border-color:#86efac; color:#15803d; }
.act-btn--deactivate:hover { background:#fef3c7; border-color:#fcd34d; color:#b45309; }
.act-btn--delete:hover     { background:#fee2e2; border-color:#fca5a5; color:#b91c1c; }
.act-btn--restore:hover    { background:#dcfce7; border-color:#86efac; color:#15803d; }
.act-btn--view       { color:#0369a1; }
.act-btn--edit       { color:#5b21b6; }
.act-btn--pwd        { color:#0066cc; }
.act-btn--activate   { color:#15803d; }
.act-btn--deactivate { color:#b45309; }
.act-btn--delete     { color:#b91c1c; }
.act-btn--restore    { color:#15803d; }

/* Empty */
.um-empty { text-align:center !important; padding:56px 16px !important; color:#94a3b8; }
.um-empty svg  { display:block; margin:0 auto 12px; opacity:.35; }
.um-empty span { display:block; font-size:.9rem; }

/* Pagination */
.um-footer   { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-top:1px solid #f1f5f9; flex-wrap:wrap; gap:8px; }
.um-page-info { font-size:.8rem; color:#64748b; }
.um-page-btns { display:flex; gap:8px; }
.um-page-btn  { display:inline-flex; align-items:center; gap:4px; height:32px; padding:0 12px; border:1px solid #e2e8f0; border-radius:7px; background:#fff; color:#374151; font-size:.8125rem; font-weight:500; cursor:pointer; transition:all .15s; }
.um-page-btn:hover:not(:disabled) { border-color:#7c3aed; color:#7c3aed; background:#faf5ff; }
.um-page-btn:disabled { opacity:.4; cursor:not-allowed; }

/* Dialog */
.dlg-backdrop { position:fixed; inset:0; background:rgba(15,10,35,.55); backdrop-filter:blur(4px); z-index:1000; display:flex; align-items:center; justify-content:center; padding:20px; }
.dlg-box      { background:#fff; border-radius:16px; width:100%; max-width:700px; max-height:92vh; display:flex; flex-direction:column; box-shadow:0 24px 60px rgba(0,0,0,.22); overflow:hidden; }
.dlg-box--sm  { max-width:480px; }
.dlg-box--confirm { max-width:420px; align-items:center; padding:36px 32px; gap:14px; text-align:center; border-radius:20px; }
.dlg-head     { display:flex; align-items:center; justify-content:space-between; padding:18px 22px 14px; border-bottom:1px solid #f1f5f9; flex-shrink:0; }
.dlg-head-left { display:flex; align-items:center; gap:12px; }
.dlg-icon  { width:40px; height:40px; border-radius:10px; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.dlg-icon--create { background:linear-gradient(135deg,#6c33a0,#c68efd); }
.dlg-icon--edit   { background:linear-gradient(135deg,#0066cc,#00aed6); }
.dlg-icon--pwd    { background:linear-gradient(135deg,#0052a3,#4f46e5); }
.dlg-title { font-size:1rem; font-weight:700; color:#1e293b; }
.dlg-sub   { font-size:.75rem; color:#94a3b8; margin-top:1px; }
.dlg-close { width:32px; height:32px; border:1px solid #e2e8f0; border-radius:8px; background:#fff; color:#64748b; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all .15s; flex-shrink:0; }
.dlg-close:hover { border-color:#ef4444; color:#ef4444; background:#fef2f2; }
.dlg-body  { padding:20px 22px; overflow-y:auto; flex:1; }
.dlg-foot  { padding:14px 22px; border-top:1px solid #f1f5f9; display:flex; justify-content:flex-end; gap:10px; flex-shrink:0; }
.dlg-confirm-icon { width:64px; height:64px; border-radius:50%; display:flex; align-items:center; justify-content:center; }
.dlg-confirm-icon--warn   { background:#fef3c7; color:#b45309; }
.dlg-confirm-icon--danger { background:#fee2e2; color:#b91c1c; }
.dlg-confirm-title { font-size:1.125rem; font-weight:700; color:#1e293b; }
.dlg-confirm-sub   { font-size:.875rem; color:#64748b; line-height:1.55; max-width:300px; }
.dlg-confirm-btns  { display:flex; gap:10px; margin-top:8px; }

/* Forms */
.form-grid  { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.form-field { display:flex; flex-direction:column; gap:5px; }
.form-field--full { grid-column:span 2; }
.form-label { font-size:.78rem; font-weight:600; color:#374151; }
.req        { color:#ef4444; }
.form-input { height:40px; padding:0 12px; border:1.5px solid #e2e8f0; border-radius:9px; font-size:.875rem; color:#1e293b; background:#fff; outline:none; transition:border-color .2s; appearance:auto; }
.form-input:focus { border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.1); }
.form-field--error .form-input { border-color:#f87171; }
.form-error { font-size:.75rem; color:#ef4444; }
.form-toggle { display:inline-flex; align-items:center; gap:10px; cursor:pointer; user-select:none; padding:8px 0; }
.toggle-knob { width:40px; height:22px; border-radius:11px; background:#cbd5e1; position:relative; transition:background .25s; flex-shrink:0; }
.toggle-knob::after { content:''; position:absolute; top:3px; left:3px; width:16px; height:16px; border-radius:50%; background:#fff; transition:transform .25s; box-shadow:0 1px 3px rgba(0,0,0,.2); }
.form-toggle.active .toggle-knob { background:#6c33a0; }
.form-toggle.active .toggle-knob::after { transform:translateX(18px); }
.toggle-label { font-size:.875rem; color:#374151; font-weight:500; }

/* View */
.view-grid { display:flex; flex-direction:column; gap:0; }
.view-row  { display:flex; align-items:center; padding:12px 0; border-bottom:1px solid #f1f5f9; gap:16px; }
.view-row:last-child { border-bottom:none; }
.view-lbl  { font-size:.72rem; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:.04em; width:160px; flex-shrink:0; }
.view-val  { font-size:.875rem; color:#1e293b; }
.view-val--danger { color:#b91c1c; }
.view-val--mono   { font-family:monospace; color:#64748b; }

/* Transitions */
.dlg-fade-enter-active,.dlg-fade-leave-active { transition:opacity .2s ease; }
.dlg-fade-enter-from,.dlg-fade-leave-to { opacity:0; }
.fade-enter-active,.fade-leave-active { transition:opacity .2s ease; }
.fade-enter-from,.fade-leave-to { opacity:0; }

@media(max-width:640px){
  .um-page { padding:14px; }
  .um-header { flex-direction:column; align-items:flex-start; }
  .um-filter-grid { flex-direction:column; }
  .um-field { width:100%; }
  .um-field-action { margin-left:0; }
  .um-input { width:100%; min-width:0; }
  .form-grid { grid-template-columns:1fr; }
  .form-field--full { grid-column:span 1; }
  .view-lbl { width:110px; }
}
</style>
