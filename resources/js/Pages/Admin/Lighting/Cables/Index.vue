<template>
  <AdminLayout>
    <div class="cables-page">
      <!-- 1. HEADER -->
      <div class="page-header">
        <div>
          <h2>〰️ QUẢN LÝ TUYẾN CÁP ĐIỆN CHIẾU SÁNG</h2>
          <p>Quản lý các lộ cáp ngầm, cáp treo, chiều dài tuyến và mạng lưới cấp nguồn từ tủ điện KCN</p>
        </div>
        <button class="btn-primary" @click="openCreateModal">
          ➕ Thêm Tuyến Cáp Mới
        </button>
      </div>

      <!-- 2. BỘ LỌC VÀ THỐNG KÊ CHIỀU DÀI -->
      <div class="filter-card">
        <div class="filter-row">
          <div class="search-box">
            <span class="search-icon">🔍</span>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Tìm theo Mã cáp, Tên tuyến, Quy cách dây..." 
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

          <div class="filter-box">
            <label>Tủ cấp nguồn:</label>
            <select v-model="cabinetFilter">
              <option value="">-- Tất cả tủ điện --</option>
              <option v-for="c in filteredCabinetsForSelect" :key="c.id" :value="c.id">
                ⚡ {{ c.code }} - {{ c.name }}
              </option>
            </select>
          </div>

          <div class="filter-box">
            <label>Trạng thái:</label>
            <select v-model="statusFilter">
              <option value="">-- Tất cả --</option>
              <option value="normal">🟢 Bình thường</option>
              <option value="fault">🔴 Sự cố / Đứt cáp</option>
              <option value="repairing">🟡 Đang thi công</option>
            </select>
          </div>

          <div class="stats-mini">
            <span class="stat-item">
              〰️ Tuyến cáp: <b>{{ cables.length }}</b>
            </span>
            <span class="stat-item highlight">
              📏 Tổng chiều dài: <b>{{ formatNumber(totalLengthMeters) }} m</b> ({{ (totalLengthMeters / 1000).toFixed(2) }} km)
            </span>
          </div>
        </div>
      </div>

      <!-- 3. BẢNG DỮ LIỆU TUYẾN CÁP -->
      <div class="table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>STT</th>
                <th>Khu Công Nghiệp</th>
                <th>Mã Tuyến Cáp</th>
                <th>Tên Tuyến & Trục Đường</th>
                <th>Nguồn Từ Tủ Điện</th>
                <th>Quy Cách / Ống Bảo Vệ</th>
                <th>Phương Thức</th>
                <th>Chiều Dài</th>
                <th>Trạng Thái</th>
                <th>Tọa Độ GIS</th>
                <th class="text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(c, index) in filteredCables" :key="c.id">
                <td>{{ index + 1 }}</td>
                <td>
                  <span class="zone-badge">
                    🏭 {{ c.zone ? c.zone.name : '—' }}
                  </span>
                </td>
                <td><b class="cable-code">〰️ {{ c.code }}</b></td>
                <td>
                  <div class="cable-name-cell">
                    <b>{{ c.name }}</b>
                    <small v-if="c.road" class="road-text">🛣️ {{ c.road.name }}</small>
                  </div>
                </td>
                <td>
                  <span v-if="c.cabinet" class="cabinet-tag" :title="c.cabinet.name">
                    ⚡ {{ c.cabinet.code }}
                  </span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td>
                  <div class="spec-cell">
                    <span class="spec-main">{{ c.cable_spec || 'Cu/XLPE 4x16mm2' }}</span>
                    <small v-if="c.conduit_type" class="text-muted">Ống: {{ c.conduit_type }}</small>
                  </div>
                </td>
                <td>
                  <span class="type-badge" :class="c.installation_type">
                    {{ c.installation_type === 'underground' ? '🚇 Cáp ngầm' : '🪢 Cáp treo' }}
                  </span>
                </td>
                <td>
                  <span class="length-text">
                    {{ formatNumber(c.calculated_length_m || c.length_m) }} m
                  </span>
                </td>
                <td>
                  <span class="status-badge" :class="c.status">
                    {{ formatStatus(c.status) }}
                  </span>
                </td>
                <td>
                  <span v-if="c.geojson" class="badge-gis">📍 LineString</span>
                  <span v-else class="text-muted">Chưa có tọa độ</span>
                </td>
                <td class="text-center actions-cell">
                  <button class="btn-icon btn-edit" @click="openEditModal(c)" title="Sửa thông tin">✏️</button>
                  <button class="btn-icon btn-delete" @click="deleteCable(c)" title="Xóa tuyến cáp">🗑️</button>
                </td>
              </tr>
              <tr v-if="!filteredCables.length">
                <td colspan="11" class="text-center text-muted">
                  {{ searchQuery || zoneFilter || cabinetFilter || statusFilter ? 'Không tìm thấy tuyến cáp phù hợp.' : 'Chưa có dữ liệu tuyến cáp chiếu sáng.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 4. MODAL THÊM / CẬP NHẬT TUYẾN CÁP -->
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ isEditMode ? '✏️ Cập nhật Tuyến Cáp Chiếu Sáng' : '➕ Thêm Mới Tuyến Cáp Chiếu Sáng' }}</h3>
            <button class="close-btn" @click="closeModal">✕</button>
          </div>

          <form @submit.prevent="saveCable" class="modal-form">
            <div class="form-row">
              <div class="form-group half">
                <label>Thuộc KCN <span class="required">*</span></label>
                <select v-model="form.zone_id" required :class="{ 'input-error': form.errors.zone_id }">
                  <option value="">-- Chọn KCN --</option>
                  <option v-for="z in zones" :key="z.id" :value="z.id">
                    🏭 {{ z.name }} ({{ z.code }})
                  </option>
                </select>
                <small v-if="form.errors.zone_id" class="error-text">{{ form.errors.zone_id }}</small>
              </div>

              <div class="form-group half">
                <label>Nguồn Cấp Từ Tủ Điện</label>
                <select v-model="form.cabinet_id">
                  <option value="">-- Chọn tủ điện nguồn --</option>
                  <option v-for="cab in availableCabinets" :key="cab.id" :value="cab.id">
                    ⚡ {{ cab.code }} - {{ cab.name }}
                  </option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Mã Tuyến Cáp <span class="required">*</span></label>
                <input 
                  type="text" 
                  v-model="form.code" 
                  required 
                  placeholder="Ví dụ: CAP-CS-L01" 
                  :class="{ 'input-error': form.errors.code }"
                />
                <small v-if="form.errors.code" class="error-text">{{ form.errors.code }}</small>
              </div>

              <div class="form-group half">
                <label>Tên Tuyến Cáp <span class="required">*</span></label>
                <input 
                  type="text" 
                  v-model="form.name" 
                  required 
                  placeholder="Ví dụ: Tuyến cáp chiếu sáng Trục D1" 
                  :class="{ 'input-error': form.errors.name }"
                />
                <small v-if="form.errors.name" class="error-text">{{ form.errors.name }}</small>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Tuyến Đường Đi Qua</label>
                <select v-model="form.road_id">
                  <option value="">-- Tuyến đường đặt cáp --</option>
                  <option v-for="r in availableRoads" :key="r.id" :value="r.id">
                    🛣️ {{ r.name }}
                  </option>
                </select>
              </div>

              <div class="form-group half">
                <label>Phương Thức Lắp Đặt <span class="required">*</span></label>
                <select v-model="form.installation_type" required>
                  <option value="underground">🚇 Cáp ngầm hào / rãnh kỹ thuật</option>
                  <option value="overhead">🪢 Cáp vặn xoắn treo cột</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Quy Cách Dây Cáp</label>
                <input 
                  type="text" 
                  v-model="form.cable_spec" 
                  placeholder="Cu/XLPE/PVC/DSTA/PVC 4x16mm2" 
                />
              </div>

              <div class="form-group half">
                <label>Ống Luồn Bảo Vệ</label>
                <input 
                  type="text" 
                  v-model="form.conduit_type" 
                  placeholder="Ống nhựa xoắn HDPE D65/50" 
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Độ Sâu Chôn Cáp (m)</label>
                <input 
                  type="number" 
                  step="0.05" 
                  v-model="form.depth_m" 
                  placeholder="Ví dụ: 0.7" 
                />
              </div>

              <div class="form-group half">
                <label>Tình Trạng Tuyến Cáp <span class="required">*</span></label>
                <select v-model="form.status" required>
                  <option value="normal">🟢 Bình thường / Đang cấp điện</option>
                  <option value="fault">🔴 Sự cố chập / Đứt cáp</option>
                  <option value="repairing">🟡 Đang đào đường kéo cáp</option>
                </select>
              </div>
            </div>

            <!-- TỌA ĐỘ TIM TUYẾN CÁP (LINESTRING) -->
            <div class="form-group full-width">
              <label>Tọa độ các mốc tim tuyến cáp (WGS84) <span class="required">*</span></label>
              <div class="geojson-input-wrapper">
                <span class="prefix-tag">{"type":"LineString","coordinates":[</span>
                <textarea 
                  v-model="coordinates_input" 
                  @input="onCoordinatesChange"
                  rows="3" 
                  :class="{ 'input-error': coordinates_input && !isCoordinatesValid }"
                  placeholder="21.2466, 106.1780; 21.2468, 106.1782; 21.2470, 106.1785" 
                ></textarea>
                <span class="suffix-tag">]}</span>
              </div>
              <small v-if="coordinates_input && !isCoordinatesValid" class="error-text">
                ⚠️ Cần tối thiểu 2 điểm mốc tọa độ phân cách bằng dấu chấm phẩy (;)
              </small>
              <small v-else class="help-text">
                Nhập danh sách tọa độ: <code>Vĩ độ, Kinh độ; Vĩ độ, Kinh độ...</code> (Hệ thống tự động nối thành chuỗi LineString và tính chiều dài mét).
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
                {{ isEditMode ? 'Lưu Thay Đổi' : 'Tạo Tuyến Cáp' }}
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
  name: 'LightingCablesIndex',
  components: { AdminLayout },
  props: {
    cables: { type: Array, default: () => [] },
    zones: { type: Array, default: () => [] },
    cabinets: { type: Array, default: () => [] },
    roads: { type: Array, default: () => [] }
  },
  data() {
    return {
      showModal: false,
      isEditMode: false,
      selectedCableId: null,
      searchQuery: '',
      zoneFilter: '',
      cabinetFilter: '',
      statusFilter: '',
      coordinates_input: '',
      form: this.$inertia.form({
        zone_id: '',
        cabinet_id: '',
        road_id: '',
        code: '',
        name: '',
        cable_spec: 'Cu/XLPE/PVC/DSTA/PVC 4x16mm2',
        installation_type: 'underground',
        depth_m: 0.7,
        conduit_type: 'Ống nhựa xoắn HDPE D65/50',
        status: 'normal',
        geojson: ''
      })
    };
  },
  computed: {
    totalLengthMeters() {
      return this.cables.reduce((sum, c) => sum + (parseFloat(c.calculated_length_m || c.length_m) || 0), 0);
    },

    availableCabinets() {
      if (!this.form.zone_id) return this.cabinets;
      return this.cabinets.filter(c => Number(c.zone_id) === Number(this.form.zone_id));
    },

    availableRoads() {
      if (!this.form.zone_id) return this.roads;
      return this.roads.filter(r => Number(r.zone_id) === Number(this.form.zone_id));
    },

    filteredCabinetsForSelect() {
      if (!this.zoneFilter) return this.cabinets;
      return this.cabinets.filter(c => Number(c.zone_id) === Number(this.zoneFilter));
    },

    filteredCables() {
      return this.cables.filter(c => {
        const q = this.searchQuery.toLowerCase().trim();
        const matchCode = c.code && c.code.toLowerCase().includes(q);
        const matchName = c.name && c.name.toLowerCase().includes(q);
        const matchSpec = c.cable_spec && c.cable_spec.toLowerCase().includes(q);

        const matchesQuery = !q || matchCode || matchName || matchSpec;
        const matchesZone = !this.zoneFilter || Number(c.zone_id) === Number(this.zoneFilter);
        const matchesCabinet = !this.cabinetFilter || Number(c.cabinet_id) === Number(this.cabinetFilter);
        const matchesStatus = !this.statusFilter || c.status === this.statusFilter;

        return matchesQuery && matchesZone && matchesCabinet && matchesStatus;
      });
    },

    isCoordinatesValid() {
      if (!this.coordinates_input || !this.coordinates_input.trim()) return false;
      const pointStrings = this.coordinates_input.split(';').map(p => p.trim()).filter(Boolean);
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
      if (!this.coordinates_input || !this.coordinates_input.trim()) {
        this.form.geojson = '';
        return;
      }

      if (this.isCoordinatesValid) {
        const pointStrings = this.coordinates_input.split(';').map(p => p.trim()).filter(Boolean);
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

      if (parsed && parsed.coordinates && Array.isArray(parsed.coordinates)) {
        return parsed.coordinates.map(pt => `${pt[1]}, ${pt[0]}`).join('; ');
      }
      return '';
    },

    openCreateModal() {
      this.isEditMode = false;
      this.selectedCableId = null;
      this.form.reset();
      this.form.clearErrors();
      this.coordinates_input = '';
      this.form.installation_type = 'underground';
      this.form.cable_spec = 'Cu/XLPE/PVC/DSTA/PVC 4x16mm2';
      this.form.conduit_type = 'Ống nhựa xoắn HDPE D65/50';
      this.form.depth_m = 0.7;
      this.form.status = 'normal';
      this.showModal = true;
    },

    openEditModal(c) {
      this.isEditMode = true;
      this.selectedCableId = c.id;
      this.form.clearErrors();
      this.form.zone_id = c.zone_id || '';
      this.form.cabinet_id = c.cabinet_id || '';
      this.form.road_id = c.road_id || '';
      this.form.code = c.code || '';
      this.form.name = c.name || '';
      this.form.cable_spec = c.cable_spec || '';
      this.form.installation_type = c.installation_type || 'underground';
      this.form.depth_m = c.depth_m || 0.7;
      this.form.conduit_type = c.conduit_type || '';
      this.form.status = c.status || 'normal';

      this.coordinates_input = this.extractCoordinates(c.geojson || c.geom);
      this.onCoordinatesChange();

      this.showModal = true;
    },

    closeModal() {
      this.showModal = false;
      this.form.reset();
      this.form.clearErrors();
      this.coordinates_input = '';
    },

    saveCable() {
      this.onCoordinatesChange();

      if (this.isEditMode) {
        this.form.put(`/admin/lighting-cables/${this.selectedCableId}`, {
          onSuccess: () => this.closeModal()
        });
      } else {
        this.form.post('/admin/lighting-cables', {
          onSuccess: () => this.closeModal()
        });
      }
    },

    deleteCable(c) {
      if (confirm(`Bạn có chắc chắn muốn XÓA Tuyến cáp [${c.code} - ${c.name}] không?`)) {
        this.$inertia.delete(`/admin/lighting-cables/${c.id}`);
      }
    },

    formatNumber(num) {
      if (!num) return '0';
      return new Intl.NumberFormat('vi-VN').format(num);
    },

    formatStatus(status) {
      if (status === 'normal') return '🟢 Bình thường';
      if (status === 'repairing') return '🟡 Đang thi công';
      return '🔴 Sự cố đứt cáp';
    }
  }
};
</script>

<style scoped>
.cables-page { display: flex; flex-direction: column; gap: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
.page-header h2 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; }
.page-header p { margin: 4px 0 0 0; font-size: 12px; color: #64748b; }

.btn-primary { background: #2563eb; color: #fff; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }
.btn-primary:hover { background: #1d4ed8; }
.btn-secondary { background: #e2e8f0; color: #334155; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }

.filter-card { background: #fff; border-radius: 12px; padding: 14px 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.filter-row { display: flex; gap: 14px; align-items: center; flex-wrap: wrap; }
.search-box { display: flex; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; flex: 1; min-width: 220px; }
.search-icon { margin-right: 8px; font-size: 14px; }
.search-box input { border: none; background: transparent; outline: none; width: 100%; font-size: 13px; color: #0f172a; }

.filter-box { display: flex; align-items: center; gap: 8px; }
.filter-box label { font-size: 13px; font-weight: 600; color: #475569; white-space: nowrap; }
.filter-box select { padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; background: #fff; }

.stats-mini { display: flex; gap: 12px; font-size: 12px; color: #475569; margin-left: auto; }
.stat-item { background: #f1f5f9; padding: 6px 12px; border-radius: 6px; }
.stat-item.highlight { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }

.table-card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.table-responsive { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
.data-table th { background: #f8fafc; padding: 12px; color: #475569; font-weight: 700; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
.data-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }

.zone-badge { background: #f1f5f9; color: #0f172a; border: 1px solid #e2e8f0; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 6px; }
.cable-code { color: #0284c7; font-size: 13px; }
.cable-name-cell { display: flex; flex-direction: column; gap: 2px; }
.road-text { color: #2563eb; font-size: 11px; }

.cabinet-tag { background: #fefce8; color: #a16207; border: 1px solid #fef08a; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.spec-cell { display: flex; flex-direction: column; gap: 2px; }
.spec-main { font-weight: 600; color: #1e293b; font-size: 12px; }

.type-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
.type-badge.underground { background: #ecfdf5; color: #047857; }
.type-badge.overhead { background: #fef3c7; color: #b45309; }

.length-text { font-weight: 700; color: #0f172a; }

.status-badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
.status-badge.normal { background: #ecfdf5; color: #059669; }
.status-badge.repairing { background: #fffbeb; color: #d97706; }
.status-badge.fault { background: #fef2f2; color: #dc2626; }

.badge-gis { background: #eff6ff; color: #2563eb; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
.text-muted { color: #94a3b8; font-size: 12px; }

.actions-cell { display: flex; justify-content: center; gap: 8px; }
.btn-icon { border: none; background: #f1f5f9; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.btn-edit:hover { background: #dbeafe; }
.btn-delete:hover { background: #fee2e2; }

/* PREFIX WRAPPER LINESTRING */
.geojson-input-wrapper { display: flex; align-items: flex-start; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; font-family: monospace; font-size: 12px; }
.prefix-tag, .suffix-tag { background: #e2e8f0; color: #475569; padding: 8px 10px; font-weight: 600; user-select: none; white-space: nowrap; }
.geojson-input-wrapper textarea { border: none !important; box-shadow: none !important; padding: 8px 10px; flex: 1; outline: none; background: transparent; font-family: monospace; font-weight: 600; color: #0f172a; resize: vertical; }

/* MODAL STYLES */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 3000; display: flex; align-items: center; justify-content: center; padding: 16px; }
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
.input-error { border-color: #dc2626 !important; background-color: #fef2f2 !important; }
.error-text { font-size: 11px; color: #dc2626; margin-top: 2px; display: block; }
.help-text { font-size: 11px; color: #64748b; margin-top: 4px; display: block; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }
.btn-primary:disabled, .btn-disabled { background-color: #94a3b8 !important; cursor: not-allowed !important; }
</style>