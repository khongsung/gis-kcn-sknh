<template>
  <AdminLayout>
    <div class="boundaries-page">
      <!-- 1. HEADER -->
      <div class="page-header">
        <div>
          <h2>🔲 QUẢN LÝ RANH GIỚI QUY HOẠCH KCN</h2>
          <p>Quản lý các tuyến đường chỉ giới ranh quy hoạch dạng LineString</p>
        </div>
        <button class="btn-primary" @click="openCreateModal">
          ➕ Thêm Ranh Giới Mới
        </button>
      </div>

      <!-- 2. THANH TÌM KIẾM & LỌC -->
      <div class="filter-card">
        <div class="filter-row">
          <div class="search-box">
            <span class="search-icon">🔍</span>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Tìm theo Mã, Tên ranh giới, Số QĐ..." 
            />
          </div>

          <div class="filter-box">
            <label>Khu công nghiệp:</label>
            <select v-model="zoneFilter">
              <option value="">-- Tất cả KCN --</option>
              <option v-for="z in zones" :key="z.id" :value="z.id">
                🏭 {{ z.name }}
              </option>
            </select>
          </div>

          <div class="stats-mini">
            <span class="stat-item highlight">
              🔲 Tổng số đường ranh: <b>{{ boundaries.length }}</b>
            </span>
          </div>
        </div>
      </div>

      <!-- 3. BẢNG DỮ LIỆU -->
      <div class="table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>STT</th>
                <th>Khu Công Nghiệp</th>
                <th>Mã Ranh Giới</th>
                <th>Tên Ranh Giới</th>
                <th>Quyết Định / Ngày Duyệt</th>
                <th>Màu Nét Vẽ</th>
                <th>Tọa Độ GIS</th>
                <th class="text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(b, index) in filteredBoundaries" :key="b.id">
                <td>{{ index + 1 }}</td>
                <td>
                  <span class="zone-badge">
                    🏭 {{ b.zone ? b.zone.name : '—' }}
                  </span>
                </td>
                <td><b class="boundary-code">🔲 {{ b.code }}</b></td>
                <td>
                  <div class="boundary-name-cell">
                    <b>{{ b.name }}</b>
                    <small v-if="b.legal_basis" class="legal-text">{{ b.legal_basis }}</small>
                  </div>
                </td>
                <td>
                  <div v-if="b.decision_number || b.approval_date">
                    <div><b>{{ b.decision_number || '—' }}</b></div>
                    <small class="text-muted">{{ b.approval_date || '—' }}</small>
                  </div>
                  <span v-else class="text-muted">—</span>
                </td>
                <td>
                  <div class="color-preview-box">
                    <span class="color-dot" :style="{ backgroundColor: b.fill_color || '#e11d48' }"></span>
                    <code>{{ b.fill_color || '#e11d48' }}</code>
                  </div>
                </td>
                <td>
                  <span v-if="b.geojson" class="badge-gis">📍 LineString GeoJSON</span>
                  <span v-else class="text-muted">Chưa có tọa độ</span>
                </td>
                <td class="text-center actions-cell">
                  <button class="btn-icon btn-edit" @click="openEditModal(b)" title="Sửa ranh giới">✏️</button>
                  <button class="btn-icon btn-delete" @click="deleteBoundary(b)" title="Xóa ranh giới">🗑️</button>
                </td>
              </tr>
              <tr v-if="!filteredBoundaries.length">
                <td colspan="8" class="text-center text-muted">
                  {{ searchQuery || zoneFilter ? 'Không tìm thấy ranh giới phù hợp.' : 'Chưa có dữ liệu ranh giới quy hoạch.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 4. MODAL THÊM / CẬP NHẬT -->
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ isEditMode ? '✏️ Cập nhật Ranh Giới LineString' : '➕ Thêm Ranh Giới LineString Mới' }}</h3>
            <button class="close-btn" @click="closeModal">✕</button>
          </div>

          <form @submit.prevent="saveBoundary" class="modal-form">
            <div class="form-row">
              <div class="form-group half">
                <label>Thuộc Khu Công Nghiệp <span class="required">*</span></label>
                <select v-model="form.zone_id" required :class="{ 'input-error': form.errors.zone_id }">
                  <option value="">-- Chọn KCN --</option>
                  <option v-for="z in zones" :key="z.id" :value="z.id">
                    🏭 {{ z.name }} ({{ z.code }})
                  </option>
                </select>
                <small v-if="form.errors.zone_id" class="error-text">{{ form.errors.zone_id }}</small>
              </div>

              <div class="form-group half">
                <label>Mã Ranh Giới <span class="required">*</span></label>
                <input 
                  type="text" 
                  v-model="form.code" 
                  required 
                  placeholder="Ví dụ: RG-SKNH" 
                  :class="{ 'input-error': form.errors.code }"
                />
                <small v-if="form.errors.code" class="error-text">{{ form.errors.code }}</small>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Tên Ranh Giới / Phân Khu <span class="required">*</span></label>
                <input 
                  type="text" 
                  v-model="form.name" 
                  required 
                  placeholder="Ví dụ: Ranh giới KCN Song Khê - Nội Hoàng" 
                  :class="{ 'input-error': form.errors.name }"
                />
                <small v-if="form.errors.name" class="error-text">{{ form.errors.name }}</small>
              </div>

              <div class="form-group half">
                <label>Màu Nét Viền</label>
                <div class="color-picker-row">
                  <input type="color" v-model="form.fill_color" class="color-input" />
                  <input type="text" v-model="form.fill_color" placeholder="#e11d48" class="color-text-input" />
                </div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Số Quyết Định Phê Duyệt</label>
                <input 
                  type="text" 
                  v-model="form.decision_number" 
                  placeholder="Ví dụ: 215/QĐ-UBND" 
                />
              </div>

              <div class="form-group half">
                <label>Ngày Phê Duyệt</label>
                <input 
                  type="date" 
                  v-model="form.approval_date" 
                />
              </div>
            </div>

            <div class="form-group full-width">
              <label>Căn Cứ Pháp Lý / Ghi Chú</label>
              <textarea 
                v-model="form.legal_basis" 
                rows="2" 
                placeholder="Ghi chú về quyết định phê duyệt 1/2000 hoặc 1/500..."
              ></textarea>
            </div>

            <!-- NHẬP TỌA ĐỘ DẠNG LINESTRING -->
            <div class="form-group full-width">
              <label>Tọa độ các mốc đường ranh giới (WGS84) <span class="required">*</span></label>
              <div class="geojson-input-wrapper">
                <span class="prefix-tag">{"type":"LineString","coordinates":[</span>
                <textarea 
                  v-model="coordinates_input" 
                  @input="onCoordinatesChange"
                  rows="3"
                  :class="{ 'input-error': coordinates_input && !isCoordinatesValid }"
                  placeholder="21.246, 106.177; 21.248, 106.177; 21.248, 106.180; 21.246, 106.180" 
                ></textarea>
                <span class="suffix-tag">]}</span>
              </div>
              <small v-if="coordinates_input && !isCoordinatesValid" class="error-text">
                ⚠️ Cần tối thiểu 2 điểm mốc phân cách bằng dấu chấm phẩy (;)
              </small>
              <small v-else class="help-text">
                Nhập danh sách mốc: <code>Vĩ độ, Kinh độ; Vĩ độ, Kinh độ...</code> (Hệ thống tự động nối điểm đầu vào cuối để khép kín vòng ranh).
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
  name: 'BoundariesIndex',
  components: { AdminLayout },
  props: {
    boundaries: { type: Array, default: () => [] },
    zones: { type: Array, default: () => [] }
  },
  data() {
    return {
      showModal: false,
      isEditMode: false,
      selectedBoundaryId: null,
      searchQuery: '',
      zoneFilter: '',
      coordinates_input: '',
      form: this.$inertia.form({
        zone_id: '',
        code: '',
        name: '',
        decision_number: '',
        approval_date: '',
        legal_basis: '',
        fill_color: '#e11d48',
        geojson: ''
      })
    };
  },
  computed: {
    filteredBoundaries() {
      return this.boundaries.filter(b => {
        const query = this.searchQuery.toLowerCase().trim();
        const matchCode = b.code && b.code.toLowerCase().includes(query);
        const matchName = b.name && b.name.toLowerCase().includes(query);
        const matchDecision = b.decision_number && b.decision_number.toLowerCase().includes(query);

        const matchesQuery = !query || matchCode || matchName || matchDecision;
        const matchesZone = !this.zoneFilter || Number(b.zone_id) === Number(this.zoneFilter);

        return matchesQuery && matchesZone;
      });
    },

    isCoordinatesValid() {
      if (!this.coordinates_input || this.coordinates_input.trim() === '') return false;
      const pointStrings = this.coordinates_input.split(';').map(p => p.trim()).filter(p => p !== '');
      if (pointStrings.length < 2) return false;

      for (let pStr of pointStrings) {
        const parts = pStr.split(',').map(item => parseFloat(item.trim()));
        if (parts.length !== 2 || isNaN(parts[0]) || isNaN(parts[1])) return false;
      }
      return true;
    },

    isFormValid() {
      return (
        this.form.zone_id &&
        this.form.code && this.form.code.trim() !== '' &&
        this.form.name && this.form.name.trim() !== '' &&
        this.isCoordinatesValid
      );
    }
  },
  methods: {
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

        // Tự động khép kín vòng ranh nếu điểm cuối chưa trùng điểm đầu
        const firstPt = coords[0];
        const lastPt = coords[coords.length - 1];
        if (firstPt[0] !== lastPt[0] || firstPt[1] !== lastPt[1]) {
          coords.push([firstPt[0], firstPt[1]]);
        }

        // Đóng gói GeoJSON LineString (mảng 1 chiều chứa các cặp [lng, lat])
        this.form.geojson = JSON.stringify({
          type: 'LineString',
          coordinates: coords
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

      if (parsed && typeof parsed === 'object') {
        let coords = [];
        if (parsed.type === 'LineString' && Array.isArray(parsed.coordinates)) {
          coords = [...parsed.coordinates];
        } else if (parsed.type === 'Polygon' && Array.isArray(parsed.coordinates)) {
          coords = [...parsed.coordinates[0]];
        }

        if (coords.length > 1) {
          const first = coords[0];
          const last = coords[coords.length - 1];
          if (first[0] === last[0] && first[1] === last[1]) {
            coords.pop(); // Bỏ điểm trùng cuối để người dùng nhập/sửa tự nhiên
          }
          return coords.map(pt => `${pt[1]}, ${pt[0]}`).join('; ');
        }
      }
      return '';
    },

    openCreateModal() {
      this.isEditMode = false;
      this.selectedBoundaryId = null;
      this.form.reset();
      this.form.clearErrors();
      this.coordinates_input = '';
      this.form.fill_color = '#e11d48';
      this.showModal = true;
    },

    openEditModal(b) {
      this.isEditMode = true;
      this.selectedBoundaryId = b.id;
      this.form.clearErrors();
      this.form.zone_id = b.zone_id || '';
      this.form.code = b.code || '';
      this.form.name = b.name || '';
      this.form.decision_number = b.decision_number || '';
      this.form.approval_date = b.approval_date || '';
      this.form.legal_basis = b.legal_basis || '';
      this.form.fill_color = b.fill_color || '#e11d48';

      this.coordinates_input = this.extractCoordinates(b.geojson || b.geom);
      this.onCoordinatesChange();

      this.showModal = true;
    },

    closeModal() {
      this.showModal = false;
      this.form.reset();
      this.form.clearErrors();
      this.coordinates_input = '';
    },

    saveBoundary() {
      this.onCoordinatesChange();

      if (this.isEditMode) {
        this.form.put(`/admin/boundaries/${this.selectedBoundaryId}`, {
          onSuccess: () => this.closeModal()
        });
      } else {
        this.form.post('/admin/boundaries', {
          onSuccess: () => this.closeModal()
        });
      }
    },

    deleteBoundary(b) {
      if (confirm(`Bạn có chắc chắn muốn XÓA Ranh giới [${b.code} - ${b.name}] không?`)) {
        this.$inertia.delete(`/admin/boundaries/${b.id}`);
      }
    }
  }
};
</script>

<style scoped>
.boundaries-page { display: flex; flex-direction: column; gap: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
.page-header h2 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; }
.page-header p { margin: 4px 0 0 0; font-size: 12px; color: #64748b; }

.btn-primary { background: #e11d48; color: #fff; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }
.btn-primary:hover { background: #be123c; }
.btn-secondary { background: #e2e8f0; color: #334155; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }

.filter-card { background: #fff; border-radius: 12px; padding: 14px 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.filter-row { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
.search-box { display: flex; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; flex: 1; min-width: 240px; }
.search-icon { margin-right: 8px; font-size: 14px; }
.search-box input { border: none; background: transparent; outline: none; width: 100%; font-size: 13px; color: #0f172a; }

.filter-box { display: flex; align-items: center; gap: 8px; }
.filter-box label { font-size: 13px; font-weight: 600; color: #475569; white-space: nowrap; }
.filter-box select { padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; background: #fff; }

.stats-mini { display: flex; gap: 12px; font-size: 12px; color: #475569; margin-left: auto; }
.stat-item { background: #f1f5f9; padding: 6px 12px; border-radius: 6px; }
.stat-item.highlight { background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; }

.table-card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.table-responsive { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
.data-table th { background: #f8fafc; padding: 12px; color: #475569; font-weight: 700; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
.data-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }

.zone-badge { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 6px; }
.boundary-code { color: #e11d48; font-size: 13px; }
.boundary-name-cell { display: flex; flex-direction: column; gap: 2px; }
.legal-text { color: #64748b; font-size: 11px; font-style: italic; }
.color-preview-box { display: flex; align-items: center; gap: 6px; }
.color-dot { width: 14px; height: 14px; border-radius: 50%; border: 1px solid rgba(0,0,0,0.2); display: inline-block; }
.badge-gis { background: #fdf2f8; color: #db2777; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
.text-muted { color: #94a3b8; font-size: 12px; }

.actions-cell { display: flex; justify-content: center; gap: 8px; }
.btn-icon { border: none; background: #f1f5f9; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.btn-edit:hover { background: #fee2e2; }
.btn-delete:hover { background: #fecdd3; }

/* PREFIX WRAPPER LINESTRING */
.geojson-input-wrapper { display: flex; align-items: flex-start; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; font-family: monospace; font-size: 13px; }
.prefix-tag, .suffix-tag { background: #e2e8f0; color: #475569; padding: 8px 10px; font-weight: 600; user-select: none; white-space: nowrap; }
.geojson-input-wrapper textarea { border: none !important; box-shadow: none !important; padding: 8px 10px; flex: 1; outline: none; background: transparent; font-family: monospace; font-weight: 600; color: #0f172a; resize: vertical; }

/* MODAL STYLES */
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 3000; display: flex; align-items: center; justify-content: center; padding: 16px; }
.modal-content { background: #fff; border-radius: 14px; width: 100%; max-width: 640px; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
.modal-header { padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 10; }
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
.color-picker-row { display: flex; gap: 8px; align-items: center; }
.color-input { width: 44px !important; height: 38px; padding: 2px !important; cursor: pointer; }
.color-text-input { flex: 1; }
.input-error { border-color: #dc2626 !important; background-color: #fef2f2 !important; }
.error-text { font-size: 11px; color: #dc2626; margin-top: 2px; display: block; }
.help-text { font-size: 11px; color: #64748b; margin-top: 4px; display: block; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }
.btn-primary:disabled, .btn-disabled { background-color: #fda4af !important; cursor: not-allowed !important; }
</style>