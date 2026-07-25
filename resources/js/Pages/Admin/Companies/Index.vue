<template>
  <AdminLayout>
    <div class="companies-page">
      <!-- 1. HEADER TIÊU ĐỀ & NÚT THÊM -->
      <div class="page-header">
        <div>
          <h2>🏢 QUẢN LÝ CÁC DOANH NGHIỆP / CÔNG TY</h2>
          <p>Danh sách các doanh nghiệp đang đầu tư và hoạt động trong KCN</p>
        </div>
        <button class="btn-primary" @click="openCreateModal">
          ➕ Thêm Công Ty Mới
        </button>
      </div>

      <!-- 2. BẢNG THỐNG KÊ NHANH & BỘ LỌC TÌM KIẾM -->
      <div class="filter-card">
        <div class="filter-row">
          <div class="search-box">
            <span class="search-icon">🔍</span>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Tìm theo Tên công ty, MST, SĐT, Email..." 
            />
          </div>

          <div class="filter-box" v-if="availableIndustries.length">
            <label>Ngành nghề:</label>
            <select v-model="industryFilter">
              <option value="">-- Tất cả ngành nghề --</option>
              <option v-for="ind in availableIndustries" :key="ind" :value="ind">
                {{ ind }}
              </option>
            </select>
          </div>

          <div class="stats-mini">
            <span class="stat-item">
              🏢 Tổng doanh nghiệp: <b>{{ companies.length }}</b>
            </span>
            <span class="stat-item highlight">
              🏭 Tổng xưởng thuê: <b>{{ totalFactoriesRented }}</b>
            </span>
          </div>
        </div>
      </div>

      <!-- 3. BẢNG DANH SÁCH CÔNG TY -->
      <div class="table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>STT</th>
                <th>Tên Doanh Nghiệp</th>
                <th>Màu Map</th>
                <th>Mã Số Thuế</th>
                <th>Ngành Nghề Sản Xuất</th>
                <th>Liên Hệ</th>
                <th>Số Nhà Xưởng</th>
                <th class="text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(c, index) in filteredCompanies" :key="c.id">
                <td>{{ index + 1 }}</td>
                <td>
                  <div class="company-name-cell">
                    <span class="color-dot-indicator" :style="{ backgroundColor: c.color || '#2563eb' }"></span>
                    <b class="company-name">{{ c.name }}</b>
                  </div>
                </td>
                <td>
                  <span class="color-badge" :style="{ backgroundColor: c.color || '#2563eb' }">
                    {{ c.color || '#2563eb' }}
                  </span>
                </td>
                <td><code class="tax-code">{{ c.tax_code || '—' }}</code></td>
                <td>
                  <span v-if="c.industry" class="industry-badge">{{ c.industry }}</span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td>
                  <div class="contact-info">
                    <span v-if="c.phone" title="Số điện thoại">📞 {{ c.phone }}</span>
                    <span v-if="c.email" title="Email liên hệ">✉️ {{ c.email }}</span>
                    <span v-if="!c.phone && !c.email" class="text-muted">—</span>
                  </div>
                </td>
                <td>
                  <span class="factory-count-badge">
                    🏭 {{ c.factories_count || 0 }} xưởng
                  </span>
                </td>
                <td class="text-center actions-cell">
                  <button class="btn-icon btn-edit" @click="openEditModal(c)" title="Sửa thông tin">✏️</button>
                  <button class="btn-icon btn-delete" @click="deleteCompany(c)" title="Xóa công ty">🗑️</button>
                </td>
              </tr>
              <tr v-if="!filteredCompanies.length">
                <td colspan="8" class="text-center text-muted">
                  {{ searchQuery || industryFilter ? 'Không tìm thấy doanh nghiệp phù hợp với bộ lọc.' : 'Chưa có dữ liệu công ty.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 4. MODAL THÊM / SỬA CÔNG TY (GIAO DIỆN TỐI ƯU SANG TRỌNG) -->
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <div>
              <h3>{{ isEditMode ? '✏️ Cập nhật Thông tin Công ty' : '➕ Thêm Công ty Mới' }}</h3>
              <p class="modal-subtitle">Quản lý hồ sơ pháp lý & màu hiển thị thương hiệu trên bản đồ WebGIS</p>
            </div>
            <button class="close-btn" @click="closeModal">✕</button>
          </div>

          <form @submit.prevent="saveCompany" class="modal-form">
            <!-- SECTION 1: THÔNG TIN PHÁP LÝ & NHẬN DIỆN -->
            <div class="form-section">
              <div class="section-title">📌 THÔNG TIN PHÁP LÝ & NHẬN DIỆN</div>

              <div class="form-group">
                <label>Tên Doanh Nghiệp / Công ty <span class="required">*</span></label>
                <input 
                  type="text" 
                  v-model="form.name" 
                  required 
                  placeholder="VD: CÔNG TY TNHH SẢN XUẤT ABC" 
                  :class="{ 'input-error': form.errors.name }"
                />
                <small v-if="form.errors.name" class="error-text">{{ form.errors.name }}</small>
              </div>

              <div class="form-row">
                <div class="form-group third">
                  <label>Mã Số Thuế</label>
                  <input 
                    type="text" 
                    v-model="form.tax_code"  
                    placeholder="0101234567" 
                    :class="{ 'input-error': form.errors.tax_code }"
                  />
                  <small v-if="form.errors.tax_code" class="error-text">{{ form.errors.tax_code }}</small>
                </div>

                <div class="form-group third">
                  <label>Ngành Nghề Sản Xuất</label>
                  <input 
                    type="text" 
                    v-model="form.industry" 
                    placeholder="VD: Linh kiện điện tử..." 
                    :class="{ 'input-error': form.errors.industry }"
                  />
                  <small v-if="form.errors.industry" class="error-text">{{ form.errors.industry }}</small>
                </div>

                <!-- Ô CHỌN MÀU BẢN ĐỒ TỐI ƯU CỰC ĐẸP -->
                <div class="form-group third">
                  <label>Màu Hiển Thị Map</label>
                  <div class="color-picker-container">
                    <input type="color" v-model="form.color" class="color-picker-btn" />
                    <input type="text" v-model="form.color" class="color-code-input" placeholder="#2563EB" />
                    <span class="color-preview-badge" :style="{ backgroundColor: form.color || '#2563eb' }"></span>
                  </div>
                </div>
              </div>
            </div>

            <!-- SECTION 2: THÔNG TIN LIÊN HỆ -->
            <div class="form-section">
              <div class="section-title">📞 THÔNG TIN LIÊN HỆ</div>

              <div class="form-row">
                <div class="form-group half">
                  <label>Số Điện Thoại Liên Hệ</label>
                  <input 
                    type="text" 
                    v-model="form.phone" 
                    placeholder="0204 3829 111" 
                    :class="{ 'input-error': form.errors.phone }"
                  />
                  <small v-if="form.errors.phone" class="error-text">{{ form.errors.phone }}</small>
                </div>

                <div class="form-group half">
                  <label>Email Đăng Ký</label>
                  <input 
                    type="email" 
                    v-model="form.email" 
                    placeholder="contact@company.com" 
                    :class="{ 'input-error': form.errors.email }"
                  />
                  <small v-if="form.errors.email" class="error-text">{{ form.errors.email }}</small>
                </div>
              </div>

              <div class="form-group">
                <label>Mô Tả / Giới Thiệu Ngắn</label>
                <textarea 
                  v-model="form.description" 
                  rows="2" 
                  placeholder="Giới thiệu quy mô, năng lực sản xuất, sản phẩm chính..."
                ></textarea>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="closeModal">Hủy bỏ</button>
              <button 
                type="submit" 
                class="btn-primary" 
                :disabled="!isFormValid || form.processing"
                :class="{ 'btn-disabled': !isFormValid || form.processing }"
              >
                {{ isEditMode ? '💾 Lưu Cập Nhật' : '➕ Tạo Mới Công Ty' }}
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
  name: 'CompaniesIndex',
  components: { AdminLayout },
  props: { 
    companies: { type: Array, default: () => [] } 
  },
  data() {
    return {
      showModal: false,
      isEditMode: false,
      selectedId: null,
      searchQuery: '',
      industryFilter: '',
      form: this.$inertia.form({
        name: '',
        tax_code: '',
        industry: '',
        color: '#2563eb',
        phone: '',
        email: '',
        description: ''
      })
    };
  },
  computed: {
    totalFactoriesRented() {
      return this.companies.reduce((sum, c) => sum + (parseInt(c.factories_count) || 0), 0);
    },

    availableIndustries() {
      const list = this.companies
        .map(c => c.industry)
        .filter(ind => ind && ind.trim() !== '');
      return [...new Set(list)];
    },

    filteredCompanies() {
      return this.companies.filter(c => {
        const query = this.searchQuery.toLowerCase().trim();
        const matchesQuery = !query ||
          (c.name && c.name.toLowerCase().includes(query)) ||
          (c.tax_code && c.tax_code.toLowerCase().includes(query)) ||
          (c.phone && c.phone.toLowerCase().includes(query)) ||
          (c.email && c.email.toLowerCase().includes(query));

        const matchesIndustry = !this.industryFilter || c.industry === this.industryFilter;

        return matchesQuery && matchesIndustry;
      });
    },

    isFormValid() {
      return this.form.name && this.form.name.trim() !== '';
    }
  },
  methods: {
    openCreateModal() {
      this.isEditMode = false;
      this.selectedId = null;
      this.form.reset();
      this.form.clearErrors();
      this.form.color = '#2563eb';
      this.showModal = true;
    },

    openEditModal(c) {
      this.isEditMode = true;
      this.selectedId = c.id;
      this.form.clearErrors();
      this.form.name = c.name || '';
      this.form.tax_code = c.tax_code || '';
      this.form.industry = c.industry || '';
      this.form.color = c.color || '#2563eb';
      this.form.phone = c.phone || '';
      this.form.email = c.email || '';
      this.form.description = c.description || '';
      this.showModal = true;
    },

    closeModal() {
      this.showModal = false;
      this.form.reset();
      this.form.clearErrors();
    },

    saveCompany() {
      if (this.isEditMode) {
        this.form.put(`/admin/companies/${this.selectedId}`, {
          onSuccess: () => this.closeModal()
        });
      } else {
        this.form.post('/admin/companies', {
          onSuccess: () => this.closeModal()
        });
      }
    },

    deleteCompany(c) {
      if (confirm(`Anh có chắc chắn muốn XÓA công ty [${c.name}] không?`)) {
        this.$inertia.delete(`/admin/companies/${c.id}`);
      }
    }
  }
};
</script>

<style scoped>
.companies-page { display: flex; flex-direction: column; gap: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
.page-header h2 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; }
.page-header p { margin: 4px 0 0 0; font-size: 12px; color: #64748b; }

.btn-primary { background: #2563eb; color: #fff; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; transition: all 0.2s; }
.btn-primary:hover { background: #1d4ed8; }
.btn-secondary { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }

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
.stat-item.highlight { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

/* TABLE CARD */
.table-card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.table-responsive { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
.data-table th { background: #f8fafc; padding: 12px; color: #475569; font-weight: 700; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
.data-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }

.company-name-cell { display: flex; align-items: center; gap: 8px; }
.color-dot-indicator { width: 10px; height: 10px; border-radius: 50%; display: inline-block; flex-shrink: 0; box-shadow: 0 0 2px rgba(0,0,0,0.3); }
.company-name { color: #0f172a; font-size: 13px; }

.color-badge { color: #ffffff; padding: 2px 8px; border-radius: 4px; font-family: monospace; font-size: 11px; font-weight: bold; text-shadow: 0 1px 2px rgba(0,0,0,0.5); }
.tax-code { background: #f1f5f9; color: #2563eb; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-weight: bold; font-size: 12px; }
.industry-badge { background: #eff6ff; color: #1d4ed8; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
.contact-info { display: flex; flex-direction: column; gap: 2px; font-size: 11px; color: #475569; }
.factory-count-badge { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: bold; white-space: nowrap; }
.text-muted { color: #94a3b8; font-size: 12px; }

.actions-cell { display: flex; justify-content: center; gap: 8px; }
.btn-icon { border: none; background: #f1f5f9; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.btn-edit:hover { background: #dbeafe; }
.btn-delete:hover { background: #fee2e2; }

/* MODAL TỐI ƯU GIAO DIỆN (MODAL ADVANCED STYLES) */
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 3000; display: flex; align-items: center; justify-content: center; padding: 16px; }
.modal-content { background: #ffffff; border-radius: 16px; width: 100%; max-width: 620px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); overflow: hidden; animation: modalSlide 0.2s cubic-bezier(0.16, 1, 0.3, 1); }
.modal-header { padding: 18px 24px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: flex-start; }
.modal-header h3 { margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; }
.modal-subtitle { margin: 4px 0 0 0; font-size: 12px; color: #64748b; }
.close-btn { border: none; background: #e2e8f0; width: 28px; height: 28px; border-radius: 50%; font-size: 14px; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.close-btn:hover { background: #cbd5e1; color: #0f172a; }

.modal-form { padding: 24px; display: flex; flex-direction: column; gap: 20px; max-height: 80vh; overflow-y: auto; }
.form-section { display: flex; flex-direction: column; gap: 12px; background: #fafafa; padding: 14px; border-radius: 10px; border: 1px solid #f1f5f9; }
.section-title { font-size: 11px; font-weight: 800; color: #64748b; letter-spacing: 0.5px; margin-bottom: 2px; }

.form-row { display: flex; gap: 12px; }
.half { flex: 1; }
.third { flex: 1; min-width: 0; }

.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 12px; font-weight: 700; color: #334155; }
.required { color: #dc2626; }
.form-group input, .form-group textarea { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; width: 100%; box-sizing: border-box; background: #ffffff; transition: border-color 0.2s; }
.form-group input:focus, .form-group textarea:focus { border-color: #2563eb; outline: none; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }

/* DESIGN RIÊNG CHO COLOR PICKER */
.color-picker-container { display: flex; align-items: center; gap: 6px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 3px 6px; }
.color-picker-btn { width: 32px; height: 32px; border: none; padding: 0; background: transparent; cursor: pointer; border-radius: 6px; }
.color-code-input { border: none !important; padding: 4px 6px !important; font-family: monospace; font-weight: 700; font-size: 12px !important; text-transform: uppercase; color: #0f172a; flex: 1; width: 60px !important; }
.color-preview-badge { width: 14px; height: 14px; border-radius: 50%; display: inline-block; box-shadow: 0 0 3px rgba(0,0,0,0.3); margin-right: 4px; }

.input-error { border-color: #dc2626 !important; background-color: #fef2f2 !important; }
.error-text { font-size: 11px; color: #dc2626; margin-top: 2px; display: block; }

.modal-footer { 
  display: flex; 
  justify-content: flex-end; 
  gap: 10px; 
  margin-top: 8px; 
  padding-top: 12px; /* 👈 Sửa pt-3 thành padding-top chuẩn CSS */
  border-top: 1px solid #f1f5f9; 
}
.btn-primary:disabled, .btn-disabled { background-color: #94a3b8 !important; cursor: not-allowed !important; opacity: 0.7; }

@keyframes modalSlide { from { opacity: 0; transform: translateY(12px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
</style>