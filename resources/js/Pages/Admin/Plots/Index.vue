<template>
  <AdminLayout>
    <div class="plots-page">
      <!-- 1. HEADER TIÊU ĐỀ TRANG VÀ NÚT TẠO MỚI -->
      <div class="page-header">
        <div>
          <h2>🗺️ QUẢN LÝ LÔ ĐẤT QUY HOẠCH KCN</h2>
          <p>Quản lý lô đất, diện tích và các công ty liên kết</p>
        </div>
        <button class="btn-primary" @click="openCreateModal">
          ➕ Thêm Lô Đất Mới
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
              placeholder="Tìm theo Mã lô, Mô tả, Tên công ty thuê..." 
            />
          </div>

          <div class="stats-mini">
            <span class="stat-item">
              📦 Tổng số lô: <b>{{ plots.length }}</b>
            </span>
            <span class="stat-item highlight">
              📐 Tổng diện tích: <b>{{ formatNumber(totalPlotsArea) }} m²</b>
            </span>
          </div>
        </div>
      </div>

      <!-- 3. BẢNG DANH SÁCH LÔ ĐẤT -->
      <div class="table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>STT</th>
                <th>Mã Lô Đất</th>
                <th>Loại Đất</th>
                <th>Diện Tích (m²)</th>
                <th style="min-width: 200px;">Giới Thiệu / Trích Ngang</th>
                <th>Tọa Độ GIS</th>
                <th>Các Đơn Vị Thuê</th>
                <th class="text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(p, index) in filteredPlots" :key="p.id">
                <td>{{ index + 1 }}</td>
                <td><b class="plot-code">🗺️ {{ p.code }}</b></td>
                <!-- 🌟 CỘT HIỂN THỊ LOẠI ĐẤT & MÀU SẮC -->
                <td>
                  <span 
                    class="badge-land-type" 
                    :style="{ 
                      backgroundColor: p.color ? p.color + '15' : '#b4530915', 
                      borderColor: p.color || '#b45309',
                      color: p.color || '#b45309'
                    }"
                  >
                    <span 
                      class="color-dot" 
                      :style="{ backgroundColor: p.color || '#b45309' }"
                    ></span>
                    {{ p.land_type === 'commercial' ? 'Đất Thương mại dịch vụ' : 'Đất Công nghiệp' }}
                  </span>
                </td>
                <td>
                  <span class="area-text">
                    {{ formatNumber(p.total_area || p.area) }} m²
                  </span>
                </td>
                <td>
                  <div class="desc-cell" :title="p.description">
                    {{ p.description || '—' }}
                  </div>
                </td>
                <td>
                  <span v-if="p.geojson || p.geom" class="badge-gis">📍 Polygon GeoJSON</span>
                  <span v-else class="text-muted">Chưa có tọa độ</span>
                </td>
                <td>
                  <div v-if="p.companies && p.companies.length" class="company-tags">
                    <span v-for="c in p.companies" :key="c.id" class="company-tag">
                      🏢 {{ c.name }}
                    </span>
                  </div>
                  <span v-else class="text-muted">—</span>
                </td>
                <td class="text-center actions-cell">
                  <button class="btn-icon btn-edit" @click="openEditModal(p)" title="Sửa thông tin">✏️</button>
                  <button class="btn-icon btn-delete" @click="deletePlot(p)" title="Xóa lô đất">🗑️</button>
                </td>
              </tr>
              <tr v-if="!filteredPlots.length">
                <td colspan="7" class="text-center text-muted">
                  {{ searchQuery || statusFilter ? 'Không tìm thấy lô đất phù hợp với bộ lọc.' : 'Chưa có dữ liệu lô đất.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 4. MODAL THÊM / SỬA LÔ ĐẤT -->
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ isEditMode ? '✏️ Cập nhật Lô Đất' : '➕ Thêm Lô Đất Mới' }}</h3>
            <button class="close-btn" @click="closeModal">✕</button>
          </div>

          <form @submit.prevent="savePlot" class="modal-form">
            <div class="form-row">
              <div class="form-group half">
                <label>Mã Lô Đất <span class="required">*</span></label>
                <input 
                  type="text" 
                  v-model="form.code" 
                  required 
                  placeholder="Ví dụ: LO-A01" 
                  :class="{ 'input-error': form.errors.code }"
                />
                <small v-if="form.errors.code" class="error-text">{{ form.errors.code }}</small>
              </div>

              <div class="form-group half">
                <label>Loại đất quy hoạch <span class="required">*</span></label>
                <select v-model="form.land_type" @change="onLandTypeChange" required>
                  <option value="industrial">🏭 Đất Công nghiệp (CN)</option>
                  <option value="commercial">🏬 Đất Thương mại dịch vụ (TMDV)</option>
                </select>
                <small v-if="form.errors.land_type" class="error-text">{{ form.errors.land_type }}</small>
              </div>
            </div>

            <!-- 2. BẢNG MÀU COLOR PICKER -->
            <div class="form-row">
              <div class="form-group half">
                <label>Diện tích (m²)</label>
                <input 
                  type="number" 
                  step="0.1" 
                  v-model="form.area"  
                  placeholder="10000" 
                  :class="{ 'input-error': form.errors.area }"
                />
                <small v-if="form.errors.area" class="error-text">{{ form.errors.area }}</small>
              </div>
              <div class="form-group half">
                <label>Màu sắc hiển thị trên WebGIS</label>
                <div class="color-picker-wrapper">
                  <input 
                    type="color" 
                    v-model="form.color" 
                    class="color-input"
                    title="Chọn màu sắc"
                  />
                  <input 
                    type="text" 
                    v-model="form.color" 
                    placeholder="#b45309"
                    class="color-text-input"
                    maxlength="7"
                  />
                  <!-- Nút hoàn nguyên màu mặc định theo loại đất -->
                  <button 
                    type="button" 
                    class="btn-reset-color" 
                    @click="resetDefaultColor" 
                    title="Khôi phục màu mặc định"
                  >
                    ↺ Mặc định
                  </button>
                </div>
                <small v-if="form.errors.color" class="error-text">{{ form.errors.color }}</small>
              </div>
            </div>
            

            <!-- 🌟 Ô GIỚI THIỆU / MÔ TẢ ĐÃ CHUYỂN THÀNH TEXTAREA CHIẾM TRỌN CHIỀU RỘNG -->
            <div class="form-group full-width">
              <label>Giới thiệu / Trích ngang Lô đất</label>
              <textarea 
                v-model="form.description" 
                rows="4"
                placeholder="Nhập trích ngang, thông số kỹ thuật, hiện trạng mặt bằng, các ngành nghề ưu tiên thu hút đầu tư..."
                :class="{ 'input-error': form.errors.description }"
              ></textarea>
              <small v-if="form.errors.description" class="error-text">{{ form.errors.description }}</small>
            </div>

            <!-- NHẬP TỌA ĐỘ RANH GIỚI POLYGON CÓ PREFIX -->
            <div class="form-group full-width">
              <label>Tọa độ ranh giới Lô đất (Các đỉnh đa giác)</label>
              <div class="geojson-input-wrapper">
                <span class="prefix-tag">{"type":"Polygon","coordinates":[[</span>
                <textarea 
                  v-model="coordinates_input" 
                  @input="onCoordinatesChange"
                  rows="3"
                  :class="{ 'input-error': coordinates_input && !isCoordinatesValid }"
                  placeholder="21.246, 106.177; 21.248, 106.177; 21.248, 106.180; 21.246, 106.180" 
                ></textarea>
                <span class="suffix-tag">]]}</span>
              </div>
              <small v-if="coordinates_input && !isCoordinatesValid" class="error-text">
                ⚠️ Đa giác Lô đất cần tối thiểu 3 góc/đỉnh phân cách bằng dấu chấm phẩy (;)
              </small>
              <small v-else class="help-text">
                Nhập danh sách các đỉnh từ Google Maps: <code>Vĩ độ, Kinh độ; Vĩ độ, Kinh độ...</code> (Hệ thống tự động khép kín đa giác).
              </small>
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
  name: 'PlotsIndex',
  components: { AdminLayout },
  props: { 
    plots: { type: Array, default: () => [] }, 
    companies: { type: Array, default: () => [] } 
  },
  data() {
    return {
      showModal: false,
      isEditMode: false,
      selectedPlotId: null,
      searchQuery: '',
      statusFilter: '',
      coordinates_input: '',
      form: this.$inertia.form({
        code: '',
        land_type: 'industrial', // 🌟 Mặc định Đất công nghiệp
        color: '#b45309',
        area: '',
        status: 'available',
        description: '',
        geojson: ''
      })
    };
  },
  computed: {
    totalPlotsArea() {
      return this.plots.reduce((sum, p) => sum + (parseFloat(p.total_area || p.area) || 0), 0);
    },

    filteredPlots() {
      return this.plots.filter(p => {
        const query = this.searchQuery.toLowerCase().trim();
        const matchCode = p.code && p.code.toLowerCase().includes(query);
        const matchDesc = p.description && p.description.toLowerCase().includes(query);
        const matchCompany = p.companies && p.companies.some(c => c.name && c.name.toLowerCase().includes(query));

        const matchesQuery = !query || matchCode || matchDesc || matchCompany;
        const matchesStatus = !this.statusFilter || (p.status || 'available') === this.statusFilter;

        return matchesQuery && matchesStatus;
      });
    },

    isCoordinatesValid() {
      if (!this.coordinates_input || this.coordinates_input.trim() === '') return true;
      const pointStrings = this.coordinates_input.split(';').map(p => p.trim()).filter(p => p !== '');
      if (pointStrings.length < 3) return false;

      for (let pStr of pointStrings) {
        const parts = pStr.split(',').map(item => parseFloat(item.trim()));
        if (parts.length !== 2 || isNaN(parts[0]) || isNaN(parts[1])) return false;
      }
      return true;
    },

    isFormValid() {
      const isCodeValid = Boolean(this.form.code && this.form.code.trim() !== '');
      const isLandTypeValid = Boolean(this.form.land_type && this.form.land_type.trim() !== '');
      return isCodeValid && isLandTypeValid && this.isCoordinatesValid;
    }
  },
  methods: {
    // 🌟 Tự động đổi màu khi chuyển đổi loại đất
    onLandTypeChange() {
      if (this.form.land_type === 'commercial') {
        this.form.color = '#e11d48'; // Hồng đỏ cho đất TMDV
      } else {
        this.form.color = '#b45309'; // Nâu cam cho đất công nghiệp
      }
    },

    // 🌟 Khôi phục lại màu chuẩn
    resetDefaultColor() {
      this.onLandTypeChange();
    },

    onCoordinatesChange() {
      if (!this.coordinates_input || this.coordinates_input.trim() === '') {
        this.form.geojson = '';
        return;
      }

      if (this.isCoordinatesValid) {
        const pointStrings = this.coordinates_input.split(';').map(p => p.trim()).filter(p => p !== '');
        
        const coords = pointStrings.map(pStr => {
          const parts = pStr.split(',').map(item => parseFloat(item.trim()));
          let lat, lng;
          if (parts[0] < parts[1]) {
            lat = parts[0];
            lng = parts[1];
          } else {
            lng = parts[0];
            lat = parts[1];
          }
          return [lng, lat];
        });

        const firstPt = coords[0];
        const lastPt = coords[coords.length - 1];
        if (firstPt[0] !== lastPt[0] || firstPt[1] !== lastPt[1]) {
          coords.push([firstPt[0], firstPt[1]]);
        }

        this.form.geojson = JSON.stringify({
          type: 'Polygon',
          coordinates: [coords]
        });
      } else {
        this.form.geojson = '';
      }
    },

    extractCoordinates(geomData) {
      if (!geomData) return '';
      let parsed = geomData;
      if (typeof geomData === 'string') {
        try { parsed = JSON.parse(geomData); } catch (e) { return geomData; }
      }

      if (parsed && typeof parsed === 'object' && Array.isArray(parsed.coordinates) && parsed.coordinates.length > 0) {
        const ring = parsed.coordinates[0];
        if (!Array.isArray(ring)) return '';

        let pointsToDisplay = [...ring];
        if (pointsToDisplay.length > 1) {
          const first = pointsToDisplay[0];
          const last = pointsToDisplay[pointsToDisplay.length - 1];
          if (first[0] === last[0] && first[1] === last[1]) {
            pointsToDisplay.pop();
          }
        }

        return pointsToDisplay
          .map(pt => `${pt[1]}, ${pt[0]}`)
          .join('; ');
      }
      return '';
    },

    openCreateModal() {
      this.isEditMode = false;
      this.selectedPlotId = null;
      this.form.reset();
      this.form.clearErrors();
      this.form.land_type = 'industrial';
      this.form.color = '#b45309';
      this.coordinates_input = '';
      this.form.status = 'available';
      this.form.description = '';
      this.form.geojson = '';
      this.showModal = true;
    },

    openEditModal(plot) {
      this.isEditMode = true;
      this.selectedPlotId = plot.id;
      this.form.clearErrors();
      this.form.code = plot.code || '';
      // 🌟 Nạp dữ liệu loại đất và màu sắc (có fallback)
      this.form.land_type = plot.land_type || 'industrial';
      this.form.color = plot.color || (this.form.land_type === 'commercial' ? '#e11d48' : '#b45309');
      this.form.area = plot.total_area || plot.area || '';
      this.form.status = plot.status || 'available';
      this.form.description = plot.description || '';

      const rawGeom = plot.geojson || plot.geom;
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

    savePlot() {
      this.onCoordinatesChange();

      if (this.isEditMode) {
        this.form.put(`/admin/plots/${this.selectedPlotId}`, { 
          onSuccess: () => this.closeModal() 
        });
      } else {
        this.form.post('/admin/plots', { 
          onSuccess: () => this.closeModal() 
        });
      }
    },

    deletePlot(plot) {
      if (confirm(`Anh có chắc chắn muốn XÓA Lô đất [${plot.code}] không?`)) {
        this.$inertia.delete(`/admin/plots/${plot.id}`);
      }
    },

    formatNumber(num) { 
      if (!num) return '0';
      return new Intl.NumberFormat('vi-VN').format(num); 
    }
  }
};
</script>

<style scoped>
.plots-page { display: flex; flex-direction: column; gap: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
.page-header h2 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; }
.page-header p { margin: 4px 0 0 0; font-size: 12px; color: #64748b; }

.btn-primary { background: #2563eb; color: #fff; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }
.btn-primary:hover { background: #1d4ed8; }
.btn-secondary { background: #e2e8f0; color: #334155; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }

/* FILTER & STATS CARD */
.filter-card { background: #fff; border-radius: 12px; padding: 14px 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.filter-row { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
.search-box { display: flex; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; flex: 1; min-width: 240px; }
.search-icon { margin-right: 8px; font-size: 14px; }
.search-box input { border: none; background: transparent; outline: none; width: 100%; font-size: 13px; color: #0f172a; }

.stats-mini { display: flex; gap: 12px; font-size: 12px; color: #475569; margin-left: auto; }
.stat-item { background: #f1f5f9; padding: 6px 12px; border-radius: 6px; }
.stat-item.highlight { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }

/* TABLE CARD */
.table-card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.table-responsive { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
.data-table th { background: #f8fafc; padding: 12px; color: #475569; font-weight: 700; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
.data-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }

.plot-code { color: #b45309; font-size: 13px; }
.area-text { font-weight: 600; color: #0f172a; }
.desc-cell { max-width: 260px; line-height: 1.5; color: #475569; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.badge-gis { background: #ecfdf5; color: #047857; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
.text-muted { color: #94a3b8; font-size: 12px; }

.company-tags { display: flex; flex-wrap: wrap; gap: 4px; }
.company-tag { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; font-size: 11px; font-weight: 600; padding: 2px 6px; border-radius: 4px; }

.actions-cell { display: flex; justify-content: center; gap: 8px; }
.btn-icon { border: none; background: #f1f5f9; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.btn-edit:hover { background: #dbeafe; }
.btn-delete:hover { background: #fee2e2; }

/* PREFIX WRAPPER POLYGON */
.geojson-input-wrapper { display: flex; align-items: flex-start; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; font-family: monospace; font-size: 13px; }
.prefix-tag, .suffix-tag { background: #e2e8f0; color: #475569; padding: 8px 10px; font-weight: 600; user-select: none; white-space: nowrap; }
.geojson-input-wrapper textarea { border: none !important; box-shadow: none !important; padding: 8px 10px; flex: 1; outline: none; background: transparent; font-family: monospace; font-weight: 600; color: #0f172a; resize: vertical; }

/* MODAL STYLES */
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 3000; display: flex; align-items: center; justify-content: center; padding: 16px; }
.modal-content { background: #fff; border-radius: 14px; width: 100%; max-width: 620px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
.modal-header { padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
.modal-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: #0f172a; }
.close-btn { border: none; background: transparent; font-size: 18px; cursor: pointer; color: #64748b; }
.modal-form { padding: 20px; display: flex; flex-direction: column; gap: 14px; }
.form-row { display: flex; gap: 12px; }
.half { flex: 1; }
.full-width { width: 100%; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 13px; font-weight: 600; color: #334155; }
.required { color: #dc2626; }
.form-group input, .form-group select, .form-group textarea { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 100%; box-sizing: border-box; font-family: inherit; }
.form-group textarea { resize: vertical; min-height: 85px; line-height: 1.5; }
.input-error { border-color: #dc2626 !important; background-color: #fef2f2 !important; }
.error-text { font-size: 11px; color: #dc2626; margin-top: 2px; display: block; }
.help-text { font-size: 11px; color: #64748b; margin-top: 4px; display: block; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }
.btn-primary:disabled, .btn-disabled { background-color: #94a3b8 !important; cursor: not-allowed !important; opacity: 0.7; }

/* BADGE HIỂN THỊ LOẠI ĐẤT TRÊN BẢNG */
.badge-land-type {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
  border: 1px solid transparent;
  white-space: nowrap;
}

.color-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
  flex-shrink: 0;
}

/* KHỐI COLOR PICKER CHUYÊN NGHIỆP */
.color-picker-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
}

.color-input {
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  width: 38px;
  height: 38px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  padding: 2px;
  background: #ffffff;
  cursor: pointer;
  flex-shrink: 0;
}

.color-input::-webkit-color-swatch-wrapper {
  padding: 0;
}

.color-input::-webkit-color-swatch {
  border: none;
  border-radius: 4px;
}

.color-text-input {
  font-family: monospace;
  font-weight: 700;
  text-transform: uppercase;
  color: #0f172a;
}

.btn-reset-color {
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  color: #475569;
  padding: 9px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s ease;
}

.btn-reset-color:hover {
  background: #e2e8f0;
  color: #0f172a;
}
</style>