<template>
  <AdminLayout>
    <div class="users-page">
      <!-- 1. HEADER TIÊU ĐỀ TRANG VÀ NÚT TẠO MỚI -->
      <div class="page-header">
        <div>
          <h2>👥 QUẢN LÝ TÀI KHOẢN NGƯỜI DÙNG</h2>
          <p>Quản lý tài khoản Admin và các tài khoản Doanh nghiệp trong KCN</p>
        </div>
        <button class="btn-primary" @click="openCreateModal">
          ➕ Tạo tài khoản mới
        </button>
      </div>

      <!-- 2. BẢNG THỐNG KÊ NHANH VÀ THANH TÌM KIẾM/LỌC -->
      <div class="filter-card">
        <div class="filter-row">
          <div class="search-box">
            <span class="search-icon">🔍</span>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Tìm theo Họ tên, Email, Tên công ty..." 
            />
          </div>

          <div class="filter-box">
            <label>Vai trò:</label>
            <select v-model="roleFilter">
              <option value="">-- Tất cả vai trò --</option>
              <option value="admin">👑 Quản trị viên</option>
              <option value="company">🏢 Doanh nghiệp</option>
            </select>
          </div>

          <div class="filter-box">
            <label>Trạng thái:</label>
            <select v-model="statusFilter">
              <option value="">-- Tất cả trạng thái --</option>
              <option value="active">✅ Hoạt động</option>
              <option value="blocked">🔒 Đã bị khóa</option>
            </select>
          </div>

          <div class="stats-mini">
            <span class="stat-item">
              👥 Tổng số: <b>{{ users.length }}</b>
            </span>
            <span class="stat-item admin-stat">
              👑 Admin: <b>{{ adminCount }}</b>
            </span>
            <span v-if="blockedCount > 0" class="stat-item warning-stat">
              🔒 Đã khóa: <b>{{ blockedCount }}</b>
            </span>
          </div>
        </div>
      </div>

      <!-- 3. BẢNG DANH SÁCH TÀI KHOẢN NGƯỜI DÙNG -->
      <div class="table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>STT</th>
                <th>Họ & Tên</th>
                <th>Email Đăng Nhập</th>
                <th>Vai Trò</th>
                <th>Doanh Nghiệp Đại Diện</th>
                <th>Trạng Thái</th>
                <th class="text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="(u, index) in filteredUsers" 
                :key="u.id" 
                :class="{ 'blocked-row': u.is_blocked }"
              >
                <td>{{ index + 1 }}</td>
                <td><b class="user-name">{{ u.name }}</b></td>
                <td><code class="email-code">{{ u.email }}</code></td>
                <td>
                  <span class="role-badge" :class="u.role">
                    {{ u.role === 'admin' ? '👑 Quản trị viên' : '🏢 Doanh nghiệp' }}
                  </span>
                </td>
                <td>
                  <span v-if="u.company_name" class="company-text">🏢 {{ u.company_name }}</span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td>
                  <span class="status-badge" :class="u.is_blocked ? 'blocked' : 'active'">
                    {{ u.is_blocked ? '🔒 Đã bị khóa' : '✅ Hoạt động' }}
                  </span>
                </td>
                <td class="text-center actions-cell">
                  <button class="btn-icon btn-edit" @click="openEditModal(u)" title="Sửa thông tin">
                    ✏️
                  </button>
                  <button 
                    class="btn-icon" 
                    :class="u.is_blocked ? 'btn-unblock' : 'btn-block'" 
                    @click="toggleBlockUser(u)"
                    :title="u.is_blocked ? 'Mở khóa tài khoản' : 'Khóa tài khoản'"
                  >
                    {{ u.is_blocked ? '🔓' : '🔒' }}
                  </button>
                </td>
              </tr>
              <tr v-if="!filteredUsers.length">
                <td colspan="7" class="text-center text-muted">
                  {{ searchQuery || roleFilter || statusFilter ? 'Không tìm thấy người dùng phù hợp với bộ lọc.' : 'Chưa có tài khoản người dùng nào.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 4. MODAL THÊM / SỬA USER -->
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ isEditMode ? '✏️ Cập nhật Tài khoản' : '➕ Tạo Tài khoản Mới' }}</h3>
            <button class="close-btn" @click="closeModal">✕</button>
          </div>

          <form @submit.prevent="saveUser" class="modal-form">
            <div class="form-group">
              <label>Họ và Tên <span class="required">*</span></label>
              <input 
                type="text" 
                v-model="form.name" 
                required 
                placeholder="Nhập tên người dùng" 
                :class="{ 'input-error': form.errors.name }"
              />
              <small v-if="form.errors.name" class="error-text">{{ form.errors.name }}</small>
            </div>

            <div class="form-group">
              <label>Email Đăng nhập <span class="required">*</span></label>
              <input 
                type="email" 
                v-model="form.email" 
                required 
                placeholder="example@company.com" 
                :class="{ 'input-error': form.errors.email }"
              />
              <small v-if="form.errors.email" class="error-text">{{ form.errors.email }}</small>
            </div>

            <div class="form-group">
              <label>Mật khẩu {{ isEditMode ? '(Để trống nếu không đổi)' : '*' }}</label>
              <input 
                type="password" 
                v-model="form.password" 
                :required="!isEditMode" 
                placeholder="••••••••" 
                :class="{ 'input-error': form.errors.password }"
              />
              <small v-if="form.errors.password" class="error-text">{{ form.errors.password }}</small>
            </div>

            <div class="form-group">
              <label>Vai trò <span class="required">*</span></label>
              <select v-model="form.role" :class="{ 'input-error': form.errors.role }">
                <option value="company">🏢 Doanh nghiệp</option>
                <option value="admin">👑 Quản trị viên (Admin)</option>
              </select>
              <small v-if="form.errors.role" class="error-text">{{ form.errors.role }}</small>
            </div>

            <div class="form-group" v-if="form.role === 'company'">
              <label>Thuộc Doanh nghiệp</label>
              <select v-model="form.company_id" :class="{ 'input-error': form.errors.company_id }">
                <option :value="null">-- Chọn Công ty đại diện --</option>
                <option v-for="c in companies" :key="c.id" :value="c.id">
                  🏢 {{ c.name }}
                </option>
              </select>
              <small v-if="form.errors.company_id" class="error-text">{{ form.errors.company_id }}</small>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="closeModal">Hủy bỏ</button>
              <button 
                type="submit" 
                class="btn-primary" 
                :disabled="form.processing"
                :class="{ 'btn-disabled': form.processing }"
              >
                {{ isEditMode ? 'Cập nhật' : 'Tạo mới' }}
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </AdminLayout>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';

export default {
  name: 'UsersIndex',
  components: { AdminLayout },
  props: {
    users: { type: Array, default: () => [] },
    companies: { type: Array, default: () => [] }
  },
  data() {
    return {
      showModal: false,
      isEditMode: false,
      selectedUserId: null,
      searchQuery: '',
      roleFilter: '',
      statusFilter: '',
      form: this.$inertia.form({
        name: '',
        email: '',
        password: '',
        role: 'company',
        company_id: null
      })
    };
  },
  computed: {
    // 🌟 Đếm số lượng Admin
    adminCount() {
      return this.users.filter(u => u.role === 'admin').length;
    },

    // 🌟 Đếm số lượng tài khoản bị khóa
    blockedCount() {
      return this.users.filter(u => u.is_blocked).length;
    },

    // 🌟 Lọc danh sách người dùng theo Ô tìm kiếm & Dropdown vai trò/trạng thái real-time
    filteredUsers() {
      return this.users.filter(u => {
        const query = this.searchQuery.toLowerCase().trim();
        const matchName = u.name && u.name.toLowerCase().includes(query);
        const matchEmail = u.email && u.email.toLowerCase().includes(query);
        const matchCompany = u.company_name && u.company_name.toLowerCase().includes(query);

        const matchesQuery = !query || matchName || matchEmail || matchCompany;
        const matchesRole = !this.roleFilter || u.role === this.roleFilter;
        
        let matchesStatus = true;
        if (this.statusFilter === 'active') matchesStatus = !u.is_blocked;
        if (this.statusFilter === 'blocked') matchesStatus = !!u.is_blocked;

        return matchesQuery && matchesRole && matchesStatus;
      });
    }
  },
  methods: {
    openCreateModal() {
      this.isEditMode = false;
      this.selectedUserId = null;
      this.form.reset();
      this.form.clearErrors();
      this.form.role = 'company';
      this.form.company_id = null;
      this.showModal = true;
    },

    openEditModal(user) {
      this.isEditMode = true;
      this.selectedUserId = user.id;
      this.form.clearErrors();
      this.form.name = user.name || '';
      this.form.email = user.email || '';
      this.form.password = '';
      this.form.role = user.role || 'company';
      this.form.company_id = user.company_id || null;
      this.showModal = true;
    },

    closeModal() {
      this.showModal = false;
      this.form.reset();
      this.form.clearErrors();
    },

    saveUser() {
      if (this.isEditMode) {
        this.form.put(`/admin/users/${this.selectedUserId}`, {
          onSuccess: () => this.closeModal()
        });
      } else {
        this.form.post('/admin/users', {
          onSuccess: () => this.closeModal()
        });
      }
    },

    toggleBlockUser(user) {
      const actionText = user.is_blocked ? 'MỞ KHÓA' : 'KHÓA';
      if (confirm(`Anh có chắc chắn muốn ${actionText} tài khoản [${user.name}] không?`)) {
        this.$inertia.post(`/admin/users/${user.id}/toggle-block`);
      }
    }
  }
};
</script>

<style scoped>
.users-page { display: flex; flex-direction: column; gap: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
.page-header h2 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; }
.page-header p { margin: 4px 0 0 0; font-size: 12px; color: #64748b; }

.btn-primary { background: #2563eb; color: #fff; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; transition: background 0.2s; }
.btn-primary:hover { background: #1d4ed8; }
.btn-secondary { background: #e2e8f0; color: #334155; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }

/* FILTER & STATS CARD */
.filter-card { background: #fff; border-radius: 12px; padding: 14px 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.filter-row { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
.search-box { display: flex; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; flex: 1; min-width: 240px; }
.search-icon { margin-right: 8px; font-size: 14px; }
.search-box input { border: none; background: transparent; outline: none; width: 100%; font-size: 13px; color: #0f172a; }

.filter-box { display: flex; align-items: center; gap: 8px; }
.filter-box label { font-size: 13px; font-weight: 600; color: #475569; white-space: nowrap; }
.filter-box select { padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; background: #fff; }

.stats-mini { display: flex; gap: 10px; font-size: 12px; color: #475569; margin-left: auto; }
.stat-item { background: #f1f5f9; padding: 6px 12px; border-radius: 6px; }
.admin-stat { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.warning-stat { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

/* TABLE CARD */
.table-card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.table-responsive { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
.data-table th { background: #f8fafc; padding: 12px; color: #475569; font-weight: 700; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
.data-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }
.blocked-row { background-color: #fff5f5; opacity: 0.8; }

.user-name { color: #0f172a; font-size: 13px; }
.email-code { background: #f1f5f9; color: #2563eb; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 12px; }
.company-text { font-weight: 600; color: #334155; }
.text-muted { color: #94a3b8; font-size: 12px; }

.role-badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
.role-badge.admin { background: #fef2f2; color: #dc2626; }
.role-badge.company { background: #eff6ff; color: #2563eb; }

.status-badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
.status-badge.active { background: #ecfdf5; color: #059669; }
.status-badge.blocked { background: #fef2f2; color: #dc2626; }

.actions-cell { display: flex; justify-content: center; gap: 8px; }
.btn-icon { border: none; background: #f1f5f9; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.btn-edit:hover { background: #dbeafe; }
.btn-block:hover { background: #fee2e2; }
.btn-unblock:hover { background: #d1fae5; }

/* MODAL STYLES */
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 3000; display: flex; align-items: center; justify-content: center; padding: 16px; }
.modal-content { background: #fff; border-radius: 14px; width: 100%; max-width: 500px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; animation: fadeIn 0.2s; }
.modal-header { padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
.modal-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: #0f172a; }
.close-btn { border: none; background: transparent; font-size: 18px; cursor: pointer; color: #64748b; }
.modal-form { padding: 20px; display: flex; flex-direction: column; gap: 14px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 13px; font-weight: 600; color: #334155; }
.required { color: #dc2626; }
.form-group input, .form-group select { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 100%; box-sizing: border-box; }
.input-error { border-color: #dc2626 !important; background-color: #fef2f2 !important; }
.error-text { font-size: 11px; color: #dc2626; margin-top: 2px; display: block; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }
.btn-primary:disabled, .btn-disabled { background-color: #94a3b8 !important; cursor: not-allowed !important; opacity: 0.7; }

@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>