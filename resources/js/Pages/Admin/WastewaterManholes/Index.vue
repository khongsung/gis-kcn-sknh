<template>
  <AdminLayout>
    <div class="manholes-page">
      <!-- 1. HEADER TIÊU ĐỀ TRANG VÀ NÚT TẠO MỚI -->
      <div class="page-header">
        <div>
          <h2>🧪 QUẢN LÝ HỐ GA NƯỚC THẢI</h2>
          <p>Quản lý hệ thống điểm thu gom nước thải, đường kính ống, cao độ và liên kết doanh nghiệp KCN</p>
        </div>
        <button class="btn-primary" @click="openCreateModal">
          ➕ Thêm Hố Ga Mới
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
              placeholder="Tìm theo Mã hố ga, Công ty, Xưởng, Tuyến đường, Đường kính ống..." 
            />
          </div>

          <div class="filter-box">
            <label>Trạng thái:</label>
            <select v-model="statusFilter">
              <option value="">-- Tất cả trạng thái --</option>
              <option value="active">🟢 Đang hoạt động</option>
              <option value="maintenance">🟡 Cần bảo trì</option>
              <option value="blocked">🔴 Bị tắc nghẽn</option>
              <option value="closed">⚪ Tạm ngưng/Đóng</option>
            </select>
          </div>

          <div class="stats-mini">
            <span class="stat-item">
              🧪 Tổng số: <b>{{ manholes.length }}</b>
            </span>
            <span class="stat-item active-stat">
              🟢 Hoạt động: <b>{{ activeCount }}</b>
            </span>
            <span v-if="warningCount > 0" class="stat-item warning-stat">
              ⚠️ Cần xử lý: <b>{{ warningCount }}</b>
            </span>
          </div>
        </div>
      </div>

      <!-- 3. BẢNG DANH SÁCH HỐ GA NƯỚC THẢI -->
      <div class="table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>STT</th>
                <th>Mã Hố Ga</th>
                <th>Thuộc Doanh Nghiệp</th>
                <th>Nhà Xưởng</th>
                <th>Tuyến Đường</th>
                <th>Độ Sâu</th>
                <th>Cao Độ</th>
                <th>Đường Kính Ống</th>
                <th>Trạng Thái</th>
                <th class="text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(m, index) in filteredManholes" :key="m.id">
                <td>{{ index + 1 }}</td>
                <td><b class="manhole-code">🧪 {{ m.code }}</b></td>
                <td>
                  <span v-if="m.company_name" class="company-text">🏢 {{ m.company_name }}</span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td>
                  <span v-if="m.factory_code" class="badge-factory">🏭 {{ m.factory_code }}</span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td>
                  <span v-if="m.road_name" class="badge-road">🛣️ {{ m.road_name }}</span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td>{{ m.depth ? m.depth + ' m' : '—' }}</td>
                <td>{{ m.elevation ? m.elevation + ' m' : '—' }}</td>
                <td>
                  <span v-if="m.pipe_diameter" class="badge-pipe">⌀ {{ m.pipe_diameter }}</span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td>
                  <span class="status-badge" :class="m.status || 'active'">
                    {{ formatStatus(m.status) }}
                  </span>
                </td>
                <td class="text-center actions-cell">
                  <button class="btn-icon btn-edit" @click="openEditModal(m)" title="Sửa thông tin">✏️</button>
                  <button class="btn-icon btn-delete" @click="deleteManhole(m)" title="Xóa hố ga">🗑️</button>
                </td>
              </tr>
              <tr v-if="!filteredManholes.length">
                <td colspan="10" class="text-center text-muted">
                  {{ searchQuery || statusFilter ? 'Không tìm thấy hố ga phù hợp với bộ lọc.' : 'Chưa có dữ liệu hố ga nước thải.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 4. MODAL THÊM / SỬA HỐ GA NƯỚC THẢI -->
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ isEditMode ? '✏️ Cập nhật Hố Ga Nước Thải' : '➕ Thêm Hố Ga Nước Thải Mới' }}</h3>
            <button class="close-btn" @click="closeModal">✕</button>
          </div>

          <form @submit.prevent="saveManhole" class="modal-form">
            <div class="form-group">
              <label>Mã Hố Ga <span class="required">*</span></label>
              <input 
                type="text" 
                v-model="form.code" 
                required 
                placeholder="VD: HGT-SK-01" 
                :class="{ 'input-error': form.errors.code }"
              />
              <small v-if="form.errors.code" class="error-text">{{ form.errors.code }}</small>
            </div>

            <!-- HÀNG LIÊN KẾT HẠ TẦNG -->
            <div class="form-row">
              <div class="form-group third">
                <label>Thuộc Doanh Nghiệp</label>
                <select v-model="form.company_id">
                  <option :value="null">-- Chọn Công ty --</option>
                  <option v-for="c in companies" :key="c.id" :value="c.id">🏢 {{ c.name }}</option>
                </select>
              </div>
              <div class="form-group third">
                <label>Thuộc Nhà Xưởng</label>
                <select v-model="form.factory_id">
                  <option :value="null">-- Chọn Xưởng --</option>
                  <option v-for="f in factories" :key="f.id" :value="f.id">🏭 {{ f.code }}</option>
                </select>
              </div>
              <div class="form-group third">
                <label>Tuyến Đường</label>
                <select v-model="form.road_id">
                  <option :value="null">-- Chọn Đường --</option>
                  <option v-for="r in roads" :key="r.id" :value="r.id">🛣️ {{ r.name }}</option>
                </select>
              </div>
            </div>

            <!-- CỤM INPUT TỌA ĐỘ BỌC TIỀN TỐ PREFIX -->
            <div class="form-group">
              <label>Tọa độ vị trí Hố ga (Vĩ độ, Kinh độ hoặc Kinh độ, Vĩ độ)</label>

              <div class="geojson-input-wrapper">
                <span class="prefix-tag">{"type":"Point","coordinates":[</span>
                <input 
                  type="text" 
                  v-model="coordinates_input" 
                  @input="onCoordinatesChange"
                  :class="{ 'input-error': coordinates_input && !isCoordinatesValid }"
                  placeholder="21.246, 106.1773" 
                />
                <span class="suffix-tag">]}</span>
              </div>

              <small v-if="coordinates_input && !isCoordinatesValid" class="error-text">
                ⚠️ Cú pháp chưa đúng! Nhập dạng: <code>Vĩ độ, Kinh độ</code> (VD: <code>21.246, 106.1773</code>)
              </small>
              <small v-else class="help-text">
                Nhập từ Google Maps: <code>Vĩ độ, Kinh độ</code> (Hệ thống tự động đảo vị trí cho đúng chuẩn GeoJSON <code>[Kinh độ, Vĩ độ]</code>).
              </small>
            </div>

            <!-- HÀNG THÔNG SỐ KỸ THUẬT -->
            <div class="form-row">
              <div class="form-group third">
                <label>Độ sâu (m)</label>
                <input 
                  type="number" 
                  step="0.01" 
                  v-model="form.depth" 
                  placeholder="1.80" 
                  :class="{ 'input-error': form.errors.depth }"
                />
              </div>
              <div class="form-group third">
                <label>Cao độ (m)</label>
                <input 
                  type="number" 
                  step="0.01" 
                  v-model="form.elevation" 
                  placeholder="4.50" 
                  :class="{ 'input-error': form.errors.elevation }"
                />
              </div>
              <div class="form-group third">
                <label>Đường kính ống (mm)</label>
                <input 
                  type="text" 
                  v-model="form.pipe_diameter" 
                  placeholder="VD: D300, D400..." 
                  :class="{ 'input-error': form.errors.pipe_diameter }"
                />
              </div>
            </div>

            <div class="form-group">
              <label>Trạng Thái Hoạt Động <span class="required">*</span></label>
              <select v-model="form.status">
                <option value="active">🟢 Đang hoạt động (Active)</option>
                <option value="maintenance">🟡 Cần bảo trì (Maintenance)</option>
                <option value="blocked">🔴 Bị tắc nghẽn (Blocked)</option>
                <option value="closed">⚪ Tạm ngưng/Đóng (Closed)</option>
              </select>
            </div>

            <div class="form-group">
              <label>Ghi chú kỹ thuật / vị trí xả thải</label>
              <textarea v-model="form.note" rows="2" placeholder="Ghi chú thêm về đấu nối, lưu lượng xả thải..."></textarea>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="closeModal">Hủy bỏ</button>
              <button 
                type="submit" 
                class="btn-primary" 
                :disabled="!isFormValid || form.processing"
                :class="{ 'btn-disabled': !isFormValid || form.processing }"
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
  name: 'WastewaterManholesIndex',
  components: { AdminLayout },
  props: {
    manholes: { type: Array, default: () => [] },
    companies: { type: Array, default: () => [] },
    factories: { type: Array, default: () => [] },
    roads: { type: Array, default: () => [] }
  },
  data() {
    return {
      showModal: false,
      isEditMode: false,
      selectedId: null,
      searchQuery: '',
      statusFilter: '',
      coordinates_input: '',
      form: this.$inertia.form({
        code: '',
        company_id: null,
        factory_id: null,
        road_id: null,
        depth: '',
        elevation: '',
        pipe_diameter: '',
        status: 'active',
        geojson: '',
        note: ''
      })
    };
  },
  computed: {
    // 🌟 Đếm số lượng hố ga nước thải đang hoạt động
    activeCount() {
      return this.manholes.filter(m => (m.status || 'active') === 'active').length;
    },

    // 🌟 Đếm số lượng hố ga bị lỗi / cần bảo trì
    warningCount() {
      return this.manholes.filter(m => m.status === 'maintenance' || m.status === 'blocked').length;
    },

    // 🌟 Lọc danh sách Hố ga nước thải theo Ô tìm kiếm & Dropdown Trạng thái real-time
    filteredManholes() {
      return this.manholes.filter(m => {
        const query = this.searchQuery.toLowerCase().trim();
        const matchCode = m.code && m.code.toLowerCase().includes(query);
        const matchCompany = m.company_name && m.company_name.toLowerCase().includes(query);
        const matchFactory = m.factory_code && m.factory_code.toLowerCase().includes(query);
        const matchRoad = m.road_name && m.road_name.toLowerCase().includes(query);
        const matchPipe = m.pipe_diameter && m.pipe_diameter.toLowerCase().includes(query);

        const matchesQuery = !query || matchCode || matchCompany || matchFactory || matchRoad || matchPipe;
        const matchesStatus = !this.statusFilter || (m.status || 'active') === this.statusFilter;

        return matchesQuery && matchesStatus;
      });
    },

    // 🌟 Kiểm tra cặp tọa độ nhập vào có đúng dạng hợp lệ không
    isCoordinatesValid() {
      if (!this.coordinates_input || this.coordinates_input.trim() === '') return true;

      const parts = this.coordinates_input.split(',').map(item => parseFloat(item.trim()));
      if (parts.length !== 2 || isNaN(parts[0]) || isNaN(parts[1])) return false;

      let val1 = parts[0];
      let val2 = parts[1];

      const hasValidLat = (val1 >= -90 && val1 <= 90) || (val2 >= -90 && val2 <= 90);
      const hasValidLng = (val1 >= -180 && val1 <= 180) || (val2 >= -180 && val2 <= 180);

      return hasValidLat && hasValidLng;
    },

    // Validate tổng thể Form trước khi gửi
    isFormValid() {
      const isCodeValid = this.form.code && this.form.code.trim() !== '';
      return isCodeValid && this.isCoordinatesValid;
    }
  },
  methods: {
    // 🌟 Trích xuất cặp (Lat, Lng) từ dữ liệu GeoJSON trong Database
    extractCoordinates(geomData) {
      if (!geomData) return '';
      let parsed = geomData;
      if (typeof geomData === 'string') {
        try { parsed = JSON.parse(geomData); } catch (e) { return geomData; }
      }

      if (parsed && typeof parsed === 'object' && Array.isArray(parsed.coordinates) && parsed.coordinates.length >= 2) {
        const lng = parsed.coordinates[0]; // Kinh độ trong DB
        const lat = parsed.coordinates[1]; // Vĩ độ trong DB
        
        // Hiển thị ra input dạng Google Maps: Vĩ độ, Kinh độ (21.246, 106.1773)
        return `${lat}, ${lng}`;
      }
      return '';
    },

    // 🌟 Tự động đóng gói GeoJSON từ ô nhập tọa độ
    onCoordinatesChange() {
      if (!this.coordinates_input || this.coordinates_input.trim() === '') {
        this.form.geojson = '';
        return;
      }

      const parts = this.coordinates_input.split(',').map(item => parseFloat(item.trim()));

      if (parts.length === 2 && !isNaN(parts[0]) && !isNaN(parts[1])) {
        let lat, lng;

        // TỰ ĐỘNG ĐẢO VỊ TRÍ CHO ĐÚNG CHUẨN GEOJSON [LNG, LAT]:
        if (parts[0] < parts[1]) {
          lat = parts[0];
          lng = parts[1];
        } else {
          lng = parts[0];
          lat = parts[1];
        }

        // Đóng gói theo chuẩn GeoJSON Point [Lng, Lat]
        this.form.geojson = JSON.stringify({
          type: 'Point',
          coordinates: [lng, lat]
        });
      } else {
        this.form.geojson = '';
      }
    },

    openCreateModal() {
      this.isEditMode = false;
      this.selectedId = null;
      this.form.reset();
      this.form.clearErrors();
      this.form.depth = '1.8';
      this.form.pipe_diameter = 'D300';
      this.form.status = 'active';
      this.coordinates_input = '';
      this.showModal = true;
    },

    openEditModal(m) {
      this.isEditMode = true;
      this.selectedId = m.id;
      this.form.clearErrors();
      this.form.code = m.code || '';
      this.form.company_id = m.company_id || null;
      this.form.factory_id = m.factory_id || null;
      this.form.road_id = m.road_id || null;
      this.form.depth = m.depth || '';
      this.form.elevation = m.elevation || '';
      this.form.pipe_diameter = m.pipe_diameter || '';
      this.form.status = m.status || 'active';
      this.form.note = m.note || '';

      const rawGeom = m.geojson || m.geom;
      this.coordinates_input = this.extractCoordinates(rawGeom);
      this.onCoordinatesChange();

      this.showModal = true;
    },

    closeModal() {
      this.showModal = false;
      this.form.reset();
      this.form.clearErrors();
      this.coordinates_input = '';
    },

    saveManhole() {
      this.onCoordinatesChange();

      if (this.isEditMode) {
        this.form.put(`/admin/wastewater-manholes/${this.selectedId}`, { 
          onSuccess: () => this.closeModal() 
        });
      } else {
        this.form.post('/admin/wastewater-manholes', { 
          onSuccess: () => this.closeModal() 
        });
      }
    },

    deleteManhole(m) {
      if (confirm(`Anh có chắc chắn muốn XÓA hố ga nước thải [${m.code}] không?`)) {
        this.$inertia.delete(`/admin/wastewater-manholes/${m.id}`);
      }
    },

    formatStatus(s) {
      if (s === 'active') return '🟢 Đang hoạt động';
      if (s === 'maintenance') return '🟡 Cần bảo trì';
      if (s === 'blocked') return '🔴 Bị tắc nghẽn';
      return '⚪ Tạm ngưng';
    }
  }
};
</script>

<style scoped>
.manholes-page { display: flex; flex-direction: column; gap: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
.page-header h2 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; }
.page-header p { margin: 4px 0 0 0; font-size: 12px; color: #64748b; }

/* NÚT VÀ MÀU TÍM ĐẶC TRƯNG HỐ GA NƯỚC THẢI */
.btn-primary { background: #800080; color: #fff; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }
.btn-primary:hover { background: #6b006b; }
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
.active-stat { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.warning-stat { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

/* TABLE CARD */
.table-card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.table-responsive { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
.data-table th { background: #f8fafc; padding: 12px; color: #475569; font-weight: 700; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
.data-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }

.manhole-code { color: #800080; font-size: 13px; }
.company-text { font-weight: 600; color: #0f172a; }
.badge-factory { background: #f0fdf4; color: #166534; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; }
.badge-road { background: #f1f5f9; color: #334155; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; }
.badge-pipe { background: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; }
.text-muted { color: #94a3b8; font-size: 12px; }

.status-badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
.status-badge.active { background: #ecfdf5; color: #059669; }
.status-badge.maintenance { background: #fffbeb; color: #d97706; }
.status-badge.blocked { background: #fef2f2; color: #dc2626; }
.status-badge.closed { background: #f1f5f9; color: #64748b; }

.actions-cell { display: flex; justify-content: center; gap: 8px; }
.btn-icon { border: none; background: #f1f5f9; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.btn-edit:hover { background: #f3e8ff; }
.btn-delete:hover { background: #fee2e2; }

/* PREFIX WRAPPER POINT GEOJSON */
.geojson-input-wrapper { display: flex; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; font-family: monospace; font-size: 13px; }
.geojson-input-wrapper:focus-within { border-color: #800080; box-shadow: 0 0 0 2px rgba(128, 0, 128, 0.1); }
.prefix-tag, .suffix-tag { background: #e2e8f0; color: #475569; padding: 8px 10px; font-weight: 600; user-select: none; white-space: nowrap; }
.geojson-input-wrapper input { border: none !important; box-shadow: none !important; padding: 8px 10px; flex: 1; outline: none; background: transparent; font-family: monospace; font-weight: 600; color: #0f172a; }

/* MODAL STYLES */
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 3000; display: flex; align-items: center; justify-content: center; padding: 16px; }
.modal-content { background: #fff; border-radius: 14px; width: 100%; max-width: 620px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
.modal-header { padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
.modal-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: #0f172a; }
.close-btn { border: none; background: transparent; font-size: 18px; cursor: pointer; color: #64748b; }
.modal-form { padding: 20px; display: flex; flex-direction: column; gap: 14px; }
.form-row { display: flex; gap: 10px; }
.third { flex: 1; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 13px; font-weight: 600; color: #334155; }
.required { color: #dc2626; }
.form-group input, .form-group select, .form-group textarea { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 100%; box-sizing: border-box; }
.input-error { border-color: #dc2626 !important; background-color: #fef2f2 !important; }
.error-text { font-size: 11px; color: #dc2626; margin-top: 2px; display: block; }
.help-text { font-size: 11px; color: #64748b; margin-top: 4px; display: block; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }
.btn-primary:disabled, .btn-disabled { background-color: #94a3b8 !important; cursor: not-allowed !important; opacity: 0.7; }
</style>