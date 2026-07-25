<template>
  <AdminLayout>
    <div class="water-page">
      <!-- 1. HEADER TIÊU ĐỀ TRANG VÀ NÚT TẠO MỚI -->
      <div class="page-header">
        <div>
          <h2>🌊 QUẢN LÝ MẶT NƯỚC & HỒ ĐIỀU HÒA (WATER BODIES)</h2>
          <p>Quản lý ranh giới, diện tích mặt nước hồ điều hòa, kênh mương tiêu thoát nước KCN</p>
        </div>
        <button class="btn-primary" @click="openCreateModal">
          ➕ Thêm Vùng Mặt Nước Mới
        </button>
      </div>

      <!-- 2. BẢNG THỐNG KÊ NHANH VÀ THANH TÌM KIẾM -->
      <div class="filter-card">
        <div class="filter-row">
          <div class="search-box">
            <span class="search-icon">🔍</span>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Tìm kiếm theo Mã vùng, Độ sâu, Chức năng, Mô tả..." 
            />
          </div>

          <div class="stats-mini">
            <span class="stat-item">
              🌊 Tổng số vùng: <b>{{ waterBodies.length }}</b>
            </span>
            <span class="stat-item highlight">
              💧 Tổng diện tích: <b>{{ formatNumber(totalWaterArea) }} m²</b>
            </span>
          </div>
        </div>
      </div>

      <!-- 3. BẢNG DANH SÁCH MẶT NƯỚC -->
      <div class="table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>STT</th>
                <th>Mã Vùng Mặt Nước</th>
                <th>Diện Tích (m²)</th>
                <th>Mô Tả / Độ Sâu / Chức Năng</th>
                <th>Thời Gian Cập Nhật</th>
                <th>Tọa Độ GIS</th>
                <th class="text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in filteredWaterBodies" :key="item.id">
                <td>{{ index + 1 }}</td>
                <td><b class="water-code">🌊 {{ item.code }}</b></td>
                <td>
                  <span class="area-text">
                    {{ item.area ? formatNumber(item.area) + ' m²' : '—' }}
                  </span>
                </td>
                <td>{{ item.description || '—' }}</td>
                <td><small class="text-muted">{{ formatDate(item.updated_at) }}</small></td>
                <td>
                  <span v-if="item.geojson || item.geom" class="badge-gis">📍 Polygon GeoJSON</span>
                  <span v-else class="text-muted">Chưa có tọa độ</span>
                </td>
                <td class="text-center actions-cell">
                  <button class="btn-icon btn-edit" @click="openEditModal(item)" title="Sửa thông tin">✏️</button>
                  <button class="btn-icon btn-delete" @click="deleteWaterBody(item)" title="Xóa vùng mặt nước">🗑️</button>
                </td>
              </tr>
              <tr v-if="!filteredWaterBodies.length">
                <td colspan="7" class="text-center text-muted">
                  {{ searchQuery ? 'Không tìm thấy vùng mặt nước phù hợp với từ khóa.' : 'Chưa có dữ liệu diện tích mặt nước.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 4. MODAL THÊM / SỬA MẶT NƯỚC -->
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ isEditMode ? '✏️ Cập nhật Vùng Mặt Nước' : '➕ Thêm Vùng Mặt Nước Mới' }}</h3>
            <button class="close-btn" @click="closeModal">✕</button>
          </div>

          <form @submit.prevent="saveWaterBody" class="modal-form">
            <div class="form-row">
              <div class="form-group half">
                <label>Mã Khu Vực Mặt Nước <span class="required">*</span></label>
                <input 
                  type="text" 
                  v-model="form.code" 
                  required 
                  placeholder="VD: MN-01, HDH-01" 
                  :class="{ 'input-error': form.errors.code }"
                />
                <small v-if="form.errors.code" class="error-text">{{ form.errors.code }}</small>
              </div>

              <div class="form-group half">
                <label>Diện tích (m²)</label>
                <input 
                  type="number" 
                  step="0.01" 
                  v-model="form.area" 
                  placeholder="VD: 5000" 
                  :class="{ 'input-error': form.errors.area }"
                />
                <small v-if="form.errors.area" class="error-text">{{ form.errors.area }}</small>
              </div>
            </div>

            <div class="form-group">
              <label>Mô tả / Chức năng / Độ sâu</label>
              <textarea 
                v-model="form.description" 
                rows="2" 
                placeholder="VD: Hồ điều hòa trung tâm KCN, độ sâu 2.5m, thu gom nước mưa phân khu A..."
              ></textarea>
            </div>

            <!-- NHẬP TỌA ĐỘ RANH GIỚI POLYGON CÓ PREFIX -->
            <div class="form-group">
              <label>Tọa độ ranh giới mặt nước (Các đỉnh đa giác)</label>
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
                ⚠️ Cần tối thiểu 3 góc/đỉnh phân cách bằng dấu chấm phẩy (;)
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
  name: 'WaterBodyIndex',
  components: { AdminLayout },
  props: {
    waterBodies: { type: Array, default: () => [] }
  },
  data() {
    return {
      showModal: false,
      isEditMode: false,
      selectedId: null,
      searchQuery: '',
      coordinates_input: '',
      form: this.$inertia.form({
        code: '',
        area: '',
        description: '',
        geom: ''
      })
    };
  },
  computed: {
    // 🌟 Tính tổng diện tích toàn bộ vùng mặt nước
    totalWaterArea() {
      return this.waterBodies.reduce((sum, item) => sum + (parseFloat(item.area) || 0), 0);
    },

    // 🌟 Lọc danh sách vùng mặt nước theo từ khóa tìm kiếm
    filteredWaterBodies() {
      return this.waterBodies.filter(item => {
        const query = this.searchQuery.toLowerCase().trim();
        if (!query) return true;

        const matchCode = item.code && item.code.toLowerCase().includes(query);
        const matchDesc = item.description && item.description.toLowerCase().includes(query);
        return matchCode || matchDesc;
      });
    },

    // Validate Polygon: Phải có ít nhất 3 điểm
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

    // Validate Form cơ bản
    isFormValid() {
      const isCodeValid = this.form.code && this.form.code.trim() !== '';
      return isCodeValid && this.isCoordinatesValid;
    }
  },
  methods: {
    // 🌟 Tự động đóng gói GeoJSON từ chuỗi tọa độ Google Maps
    onCoordinatesChange() {
      if (!this.coordinates_input || this.coordinates_input.trim() === '') {
        this.form.geom = '';
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

        // Tự động khép kín đa giác
        const firstPt = coords[0];
        const lastPt = coords[coords.length - 1];
        if (firstPt[0] !== lastPt[0] || firstPt[1] !== lastPt[1]) {
          coords.push([firstPt[0], firstPt[1]]);
        }

        this.form.geom = JSON.stringify({
          type: 'Polygon',
          coordinates: [coords]
        });
      } else {
        this.form.geom = '';
      }
    },

    // Trích xuất tọa độ hiển thị lại ô textarea khi mở Modal sửa
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

        return pointsToDisplay.map(pt => `${pt[1]}, ${pt[0]}`).join('; ');
      }
      return '';
    },

    openCreateModal() {
      this.isEditMode = false;
      this.selectedId = null;
      this.form.reset();
      this.form.clearErrors();
      this.coordinates_input = '';
      this.showModal = true;
    },

    openEditModal(item) {
      this.isEditMode = true;
      this.selectedId = item.id;
      this.form.clearErrors();
      this.form.code = item.code || '';
      this.form.area = item.area || '';
      this.form.description = item.description || '';

      const rawGeom = item.geojson || item.geom;
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

    saveWaterBody() {
      this.onCoordinatesChange();

      if (this.isEditMode) {
        this.form.put(`/admin/water-bodies/${this.selectedId}`, { 
          onSuccess: () => this.closeModal() 
        });
      } else {
        this.form.post('/admin/water-bodies', { 
          onSuccess: () => this.closeModal() 
        });
      }
    },

    deleteWaterBody(item) {
      if (confirm(`Anh có chắc chắn muốn XÓA vùng mặt nước [${item.code}] không?`)) {
        this.$inertia.delete(`/admin/water-bodies/${item.id}`);
      }
    },

    formatNumber(num) {
      if (!num) return '0';
      return new Intl.NumberFormat('vi-VN').format(num);
    },

    formatDate(dateStr) {
      if (!dateStr) return '—';
      const d = new Date(dateStr);
      return d.toLocaleDateString('vi-VN') + ' ' + d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
    }
  }
};
</script>

<style scoped>
.water-page { display: flex; flex-direction: column; gap: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
.page-header h2 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; }
.page-header p { margin: 4px 0 0 0; font-size: 12px; color: #64748b; }

.btn-primary { background: #0284c7; color: #fff; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }
.btn-primary:hover { background: #0369a1; }
.btn-secondary { background: #e2e8f0; color: #334155; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }

/* FILTER & STATS CARD */
.filter-card { background: #fff; border-radius: 12px; padding: 14px 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.filter-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; }
.search-box { display: flex; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; flex: 1; min-width: 260px; }
.search-icon { margin-right: 8px; font-size: 14px; }
.search-box input { border: none; background: transparent; outline: none; width: 100%; font-size: 13px; color: #0f172a; }

.stats-mini { display: flex; gap: 12px; font-size: 12px; color: #475569; }
.stat-item { background: #f1f5f9; padding: 6px 12px; border-radius: 6px; }
.stat-item.highlight { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }

/* TABLE CARD */
.table-card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.table-responsive { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
.data-table th { background: #f8fafc; padding: 12px; color: #475569; font-weight: 700; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
.data-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }

.water-code { color: #0284c7; font-size: 13px; }
.area-text { font-weight: 600; color: #0f172a; }
.badge-gis { background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
.text-muted { color: #94a3b8; font-size: 12px; }

.actions-cell { display: flex; justify-content: center; gap: 8px; }
.btn-icon { border: none; background: #f1f5f9; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.btn-edit:hover { background: #e0f2fe; }
.btn-delete:hover { background: #fee2e2; }

/* PREFIX WRAPPER POLYGON */
.geojson-input-wrapper { display: flex; align-items: flex-start; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; font-family: monospace; font-size: 13px; }
.geojson-input-wrapper:focus-within { border-color: #0284c7; box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.1); }
.prefix-tag, .suffix-tag { background: #e2e8f0; color: #475569; padding: 8px 10px; font-weight: 600; user-select: none; white-space: nowrap; }
.geojson-input-wrapper textarea { border: none !important; box-shadow: none !important; padding: 8px 10px; flex: 1; outline: none; background: transparent; font-family: monospace; font-weight: 600; color: #0f172a; resize: vertical; }

/* MODAL STYLES */
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 3000; display: flex; align-items: center; justify-content: center; padding: 16px; }
.modal-content { background: #fff; border-radius: 14px; width: 100%; max-width: 580px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
.modal-header { padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
.modal-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: #0f172a; }
.close-btn { border: none; background: transparent; font-size: 18px; cursor: pointer; color: #64748b; }
.modal-form { padding: 20px; display: flex; flex-direction: column; gap: 14px; }
.form-row { display: flex; gap: 10px; }
.half { flex: 1; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 13px; font-weight: 600; color: #334155; }
.required { color: #dc2626; }
.form-group input, .form-group textarea { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 100%; box-sizing: border-box; }
.input-error { border-color: #dc2626 !important; background-color: #fef2f2 !important; }
.error-text { font-size: 11px; color: #dc2626; margin-top: 2px; display: block; }
.help-text { font-size: 11px; color: #64748b; margin-top: 4px; display: block; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }
.btn-primary:disabled, .btn-disabled { background-color: #94a3b8 !important; cursor: not-allowed !important; opacity: 0.7; }
</style>