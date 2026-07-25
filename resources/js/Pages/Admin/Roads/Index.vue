<template>
  <AdminLayout>
    <div class="roads-page">
      <!-- 1. HEADER TIÊU ĐỀ TRANG VÀ NÚT TẠO MỚI -->
      <div class="page-header">
        <div>
          <h2>🛣️ QUẢN LÝ ĐƯỜNG GIAO THÔNG</h2>
          <p>Danh sách các tuyến đường, chỉ giới đường đỏ (lộ giới), lòng đường và tuyến LineString trên bản đồ KCN</p>
        </div>
        <button class="btn-primary" @click="openCreateModal">
          ➕ Thêm Route Đường Mới
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
              placeholder="Tìm theo Tên tuyến đường, Lộ giới, Lòng đường..." 
            />
          </div>

          <div class="stats-mini">
            <span class="stat-item">
              🛣️ Tổng tuyến: <b>{{ roads.length }}</b>
            </span>
            <span class="stat-item highlight" v-if="avgRoadWidth > 0">
              📏 Lòng đường TB: <b>{{ avgRoadWidth }} m</b>
            </span>
          </div>
        </div>
      </div>

      <!-- 3. BẢNG DANH SÁCH TUYẾN ĐƯỜNG -->
      <div class="table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>STT</th>
                <th>Tên Tuyến Đường</th>
                <th>Lộ Giới (m)</th>
                <th>Lòng Đường (m)</th>
                <th>Định Vị GIS</th>
                <th class="text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(r, index) in filteredRoads" :key="r.id">
                <td>{{ index + 1 }}</td>
                <td><b class="road-name">🛣️ {{ r.name }}</b></td>
                <td><span class="badge-width">{{ r.right_of_way ? r.right_of_way + ' m' : '—' }}</span></td>
                <td><span class="badge-width alt">{{ r.road_width ? r.road_width + ' m' : '—' }}</span></td>
                <td>
                  <span v-if="r.geojson || r.geom" class="badge-gis">📍 LineString GeoJSON</span>
                  <span v-else class="text-muted">Chưa có tọa độ</span>
                </td>
                <td class="text-center actions-cell">
                  <button class="btn-icon btn-edit" @click="openEditModal(r)" title="Sửa thông tin">✏️</button>
                  <button class="btn-icon btn-delete" @click="deleteRoad(r)" title="Xóa đường">🗑️</button>
                </td>
              </tr>
              <tr v-if="!filteredRoads.length">
                <td colspan="6" class="text-center text-muted">
                  {{ searchQuery ? 'Không tìm thấy tuyến đường phù hợp với từ khóa.' : 'Chưa có dữ liệu tuyến đường.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 4. MODAL THÊM / SỬA TUYẾN ĐƯỜNG -->
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ isEditMode ? '✏️ Cập nhật Tuyến Đường' : '➕ Thêm Tuyến Đường Mới' }}</h3>
            <button class="close-btn" @click="closeModal">✕</button>
          </div>

          <form @submit.prevent="saveRoad" class="modal-form">
            <div class="form-group">
              <label>Tên Tuyến Đường <span class="required">*</span></label>
              <input 
                type="text" 
                v-model="form.name" 
                required 
                placeholder="VD: Đường D1 (KCN Bắc Giang)" 
                :class="{ 'input-error': form.errors.name }"
              />
              <small v-if="form.errors.name" class="error-text">{{ form.errors.name }}</small>
            </div>

            <!-- HÀNG LỘ GIỚI & LÒNG ĐƯỜNG -->
            <div class="form-row">
              <div class="form-group half">
                <label>Lộ giới / Chỉ giới đường đỏ (m)</label>
                <input 
                  type="number" 
                  step="0.01" 
                  v-model="form.right_of_way" 
                  placeholder="20.5" 
                  :class="{ 'input-error': form.errors.right_of_way }"
                />
                <small v-if="form.errors.right_of_way" class="error-text">{{ form.errors.right_of_way }}</small>
              </div>

              <div class="form-group half">
                <label>Chiều rộng Lòng đường (m)</label>
                <input 
                  type="number" 
                  step="0.01" 
                  v-model="form.road_width" 
                  placeholder="10.5" 
                  :class="{ 'input-error': form.errors.road_width }"
                />
                <small v-if="form.errors.road_width" class="error-text">{{ form.errors.road_width }}</small>
              </div>
            </div>

            <!-- TỌA ĐỘ LINESTRING GEOJSON (TẬP HỢP ĐIỂM) -->
            <div class="form-group">
              <label>Tọa độ tuyến đường (Các điểm nối tiếp nhau)</label>
              <div class="geojson-input-wrapper">
                <span class="prefix-tag">{"type":"LineString","coordinates":[</span>
                <textarea 
                  v-model="coordinates_input" 
                  @input="onCoordinatesChange"
                  rows="3"
                  :class="{ 'input-error': coordinates_input && !isCoordinatesValid }"
                  placeholder="21.246, 106.1773; 21.248, 106.1795; 21.250, 106.1820" 
                ></textarea>
                <span class="suffix-tag">]}</span>
              </div>
              <small v-if="coordinates_input && !isCoordinatesValid" class="error-text">
                ⚠️ Tuyến đường cần tối thiểu 2 điểm nối tiếp nhau, phân cách bằng dấu chấm phẩy (;)
              </small>
              <small v-else class="help-text">
                Nhập danh sách điểm từ Google Maps: <code>Vĩ độ, Kinh độ; Vĩ độ, Kinh độ...</code> (Phân cách các điểm bằng dấu <code>;</code>).
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
  name: 'RoadsIndex',
  components: { AdminLayout },
  props: { 
    roads: { type: Array, default: () => [] } 
  },
  data() {
    return {
      showModal: false,
      isEditMode: false,
      selectedId: null,
      searchQuery: '',
      coordinates_input: '',
      form: this.$inertia.form({
        name: '',
        right_of_way: '',
        road_width: '',
        geom: ''
      })
    };
  },
  computed: {
    // 🌟 Tính trung bình chiều rộng lòng đường
    avgRoadWidth() {
      const validRoads = this.roads.filter(r => r.road_width && !isNaN(r.road_width));
      if (!validRoads.length) return 0;
      const total = validRoads.reduce((sum, r) => sum + parseFloat(r.road_width), 0);
      return (total / validRoads.length).toFixed(1);
    },

    // 🌟 Lọc danh sách Tuyến đường theo từ khóa tìm kiếm
    filteredRoads() {
      return this.roads.filter(r => {
        const query = this.searchQuery.toLowerCase().trim();
        if (!query) return true;

        const matchName = r.name && r.name.toLowerCase().includes(query);
        const matchROW = r.right_of_way && r.right_of_way.toString().includes(query);
        const matchWidth = r.road_width && r.road_width.toString().includes(query);

        return matchName || matchROW || matchWidth;
      });
    },

    // Validate LineString: Phải có ít nhất 2 điểm hợp lệ
    isCoordinatesValid() {
      if (!this.coordinates_input || this.coordinates_input.trim() === '') return true;
      const pointStrings = this.coordinates_input.split(';').map(p => p.trim()).filter(p => p !== '');
      if (pointStrings.length < 2) return false;

      for (let pStr of pointStrings) {
        const parts = pStr.split(',').map(item => parseFloat(item.trim()));
        if (parts.length !== 2 || isNaN(parts[0]) || isNaN(parts[1])) return false;
      }
      return true;
    },

    // Validate tổng thể Form trước khi gửi
    isFormValid() {
      const isNameValid = this.form.name && this.form.name.trim() !== '';
      return isNameValid && this.isCoordinatesValid;
    }
  },
  methods: {
    // 🌟 Tự động chuyển đổi chuỗi tọa độ nhập vào thành GeoJSON LineString chuẩn
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
          // Tự động nhận diện & đảo vị trí [Vĩ độ, Kinh độ] sang [Lng, Lat] chuẩn GeoJSON
          if (parts[0] < parts[1]) {
            lat = parts[0];
            lng = parts[1];
          } else {
            lng = parts[0];
            lat = parts[1];
          }
          return [lng, lat];
        });

        this.form.geom = JSON.stringify({
          type: 'LineString',
          coordinates: coords
        });
      } else {
        this.form.geom = '';
      }
    },

    // 🌟 Trích xuất tọa độ hiển thị lại ô textarea khi mở Modal sửa
    extractCoordinates(geomData) {
      if (!geomData) return '';

      // Bỏ qua nếu là chuỗi mã hóa nhị phân EWKB của PostGIS
      if (typeof geomData === 'string' && /^[0-9A-FA-f]{16,}/.test(geomData)) {
        return '';
      }

      let parsed = geomData;
      if (typeof geomData === 'string') {
        try { 
          parsed = JSON.parse(geomData); 
        } catch (e) { 
          if (geomData.includes(',')) return geomData.trim();
          return ''; 
        }
      }

      if (parsed && typeof parsed === 'object' && Array.isArray(parsed.coordinates)) {
        // Đảo lại thứ tự hiển thị ra input dạng Google Maps: Vĩ độ, Kinh độ
        return parsed.coordinates
          .map(pt => `${pt[1]}, ${pt[0]}`)
          .join('; ');
      }
      return '';
    },

    openCreateModal() {
      this.isEditMode = false;
      this.selectedId = null;
      this.form.reset();
      this.form.clearErrors();
      this.coordinates_input = '';
      this.form.geom = '';
      this.showModal = true;
    },

    openEditModal(r) {
      this.isEditMode = true;
      this.selectedId = r.id;
      this.form.clearErrors();
      this.form.name = r.name || '';
      this.form.right_of_way = r.right_of_way || '';
      this.form.road_width = r.road_width || '';

      const rawGeom = r.geojson || r.geom;
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

    saveRoad() {
      this.onCoordinatesChange();
      if (this.isEditMode) {
        this.form.put(`/admin/roads/${this.selectedId}`, { 
          onSuccess: () => this.closeModal() 
        });
      } else {
        this.form.post('/admin/roads', { 
          onSuccess: () => this.closeModal() 
        });
      }
    },

    deleteRoad(r) {
      if (confirm(`Anh có chắc chắn muốn XÓA tuyến đường [${r.name}] không?`)) {
        this.$inertia.delete(`/admin/roads/${r.id}`);
      }
    }
  }
};
</script>

<style scoped>
.roads-page { display: flex; flex-direction: column; gap: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
.page-header h2 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; }
.page-header p { margin: 4px 0 0 0; font-size: 12px; color: #64748b; }

.btn-primary { background: #2563eb; color: #fff; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }
.btn-primary:hover { background: #1d4ed8; }
.btn-secondary { background: #e2e8f0; color: #334155; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }

/* FILTER & STATS CARD */
.filter-card { background: #fff; border-radius: 12px; padding: 14px 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.filter-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; }
.search-box { display: flex; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; flex: 1; min-width: 260px; }
.search-icon { margin-right: 8px; font-size: 14px; }
.search-box input { border: none; background: transparent; outline: none; width: 100%; font-size: 13px; color: #0f172a; }

.stats-mini { display: flex; gap: 12px; font-size: 12px; color: #475569; }
.stat-item { background: #f1f5f9; padding: 6px 12px; border-radius: 6px; }
.stat-item.highlight { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }

/* TABLE CARD */
.table-card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.table-responsive { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
.data-table th { background: #f8fafc; padding: 12px; color: #475569; font-weight: 700; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
.data-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }

.road-name { color: #2563eb; font-size: 13px; }
.badge-width { background: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
.badge-width.alt { background: #fdf4ff; color: #86198f; border-color: #f5d0fe; }
.badge-gis { background: #ecfdf5; color: #047857; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
.text-muted { color: #94a3b8; font-size: 12px; }

.actions-cell { display: flex; justify-content: center; gap: 8px; }
.btn-icon { border: none; background: #f1f5f9; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.btn-edit:hover { background: #dbeafe; }
.btn-delete:hover { background: #fee2e2; }

/* GEOJSON PREFIX WRAPPER LINESTRING */
.geojson-input-wrapper { display: flex; align-items: flex-start; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; font-family: monospace; font-size: 13px; }
.geojson-input-wrapper:focus-within { border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1); }
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