<template>
  <AdminLayout>
    <div class="factories-page">
      <!-- 1. TIÊU ĐỀ TRANG VÀ NÚT TẠO MỚI -->
      <div class="page-header">
        <div>
          <h2>🏭 QUẢN LÝ KHỐI NHÀ XƯỞNG</h2>
          <p>Quản lý các tòa nhà xưởng, khu công nghiệp trực thuộc, diện tích và liên kết với lô đất</p>
        </div>
        <button class="btn-primary" @click="openCreateModal">
          ➕ Thêm Nhà Xưởng Mới
        </button>
      </div>

      <!-- 2. THANH LỌC VÀ TÌM KIẾM DỮ LIỆU -->
      <div class="filter-card">
        <div class="filter-row">
          <div class="search-box">
            <span class="search-icon">🔍</span>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Tìm theo Mã xưởng, Lô đất, Công ty thuê..." 
            />
          </div>

          <!-- Bộ lọc Khu Công Nghiệp -->
          <div class="filter-box" v-if="zones.length">
            <label>Khu công nghiệp:</label>
            <select v-model="zoneFilter">
              <option value="">-- Tất cả KCN --</option>
              <option v-for="z in zones" :key="z.id" :value="z.id">
                🏭 {{ z.name }}
              </option>
            </select>
          </div>

          <!-- Bộ lọc Trạng thái -->
          <div class="filter-box">
            <label>Trạng thái:</label>
            <select v-model="statusFilter">
              <option value="">-- Tất cả trạng thái --</option>
              <option value="available">🟢 Đang trống</option>
              <option value="rented">🔴 Đang hoạt động</option>
              <option value="maintenance">🟡 Đang bảo trì</option>
            </select>
          </div>

          <!-- Thống kê mini -->
          <div class="stats-mini">
            <span class="stat-item">
              🏭 Số xưởng: <b>{{ filteredFactories.length }} / {{ factories.length }}</b>
            </span>
            <span class="stat-item highlight">
              📐 Tổng diện tích: <b>{{ formatNumber(totalFactoriesArea) }} m²</b>
            </span>
          </div>
        </div>
      </div>

      <!-- 3. BẢNG DANH SÁCH NHÀ XƯỞNG -->
      <div class="table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>STT</th>
                <th>Mã Xưởng</th>
                <th>Khu Công Nghiệp</th>
                <th>Thuộc Lô Đất</th>
                <th>Diện Tích</th>
                <th>Trạng Thái</th>
                <th>Công ty đang thuê</th>
                <th>Tọa Độ GIS</th>
                <th class="text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(f, index) in filteredFactories" :key="f.id">
                <td>{{ index + 1 }}</td>
                <td><b class="factory-code">🏭 {{ f.code }}</b></td>
                
                <!-- Cột hiển thị Khu Công Nghiệp -->
                <td>
                  <span v-if="f.zone || getZoneName(f.zone_id)" class="badge-zone">
                    🏭 {{ f.zone ? f.zone.name : getZoneName(f.zone_id) }}
                  </span>
                  <span v-else class="text-muted">—</span>
                </td>

                <td>
                  <span v-if="f.plot_code" class="plot-badge">🗺️ {{ f.plot_code }}</span>
                  <span v-else-if="f.plot?.code" class="plot-badge">🗺️ {{ f.plot.code }}</span>
                  <span v-else class="text-muted">Chưa gắn</span>
                </td>
                <td><b>{{ f.area ? formatNumber(f.area) + ' m²' : '—' }}</b></td>
                <td>
                  <span class="status-badge" :class="f.status">
                    {{ formatStatus(f.status) }}
                  </span>
                </td>
                <td>
                  <span v-if="f.company_name || f.company?.name" class="company-text">
                    🏢 {{ f.company_name || f.company?.name }}
                  </span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td>
                  <span v-if="f.geojson || f.geom" class="badge-gis">📍 Polygon GeoJSON</span>
                  <span v-else class="text-muted">Chưa có tọa độ</span>
                </td>
                <td class="text-center actions-cell">
                  <button class="btn-icon btn-edit" @click="openEditModal(f)" title="Chỉnh sửa">✏️</button>
                  <button class="btn-icon btn-delete" @click="deleteFactory(f)" title="Xóa xưởng">🗑️</button>
                </td>
              </tr>
              <tr v-if="!filteredFactories.length">
                <td colspan="9" class="text-center text-muted">
                  {{ searchQuery || statusFilter || zoneFilter ? 'Không tìm thấy nhà xưởng phù hợp với bộ lọc.' : 'Chưa có dữ liệu nhà xưởng.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 4. MODAL THÊM / SỬA NHÀ XƯỞNG -->
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ isEditMode ? '✏️ Cập nhật Nhà xưởng' : '➕ Thêm Nhà xưởng Mới' }}</h3>
            <button class="close-btn" @click="closeModal">✕</button>
          </div>

          <form @submit.prevent="saveFactory" class="modal-form">
            <!-- HÀNG 1: MÃ XƯỞNG & KHU CÔNG NGHIỆP TRỰC THUỘC -->
            <div class="form-row">
              <div class="form-group half">
                <label>Mã Nhà Xưởng <span class="required">*</span></label>
                <input 
                  type="text" 
                  v-model="form.code" 
                  required 
                  placeholder="VD: X-A01" 
                  :class="{ 'input-error': form.errors.code }"
                />
                <small v-if="form.errors.code" class="error-text">{{ form.errors.code }}</small>
              </div>

              <div class="form-group half">
                <label>Khu Công Nghiệp <span class="required">*</span></label>
                <select v-model="form.zone_id" @change="onZoneChange" required :class="{ 'input-error': form.errors.zone_id }">
                  <option value="" disabled>-- Chọn Khu Công Nghiệp --</option>
                  <option v-for="z in zones" :key="z.id" :value="z.id">
                    🏭 {{ z.name }}
                  </option>
                </select>
                <small v-if="form.errors.zone_id" class="error-text">{{ form.errors.zone_id }}</small>
              </div>
            </div>

            <!-- HÀNG 2: DIỆN TÍCH (KHÔNG BẮT BUỘC) & LÔ ĐẤT (ĐÃ LỌC THEO KCN) -->
            <div class="form-row">
              <div class="form-group half">
                <label>Diện tích xưởng (m²)</label>
                <input 
                  type="number" 
                  step="0.1" 
                  v-model="form.area" 
                  placeholder="Để trống sẽ tự động tính theo tọa độ GIS" 
                  :class="{ 'input-error': form.errors.area }"
                />
                <small v-if="form.errors.area" class="error-text">{{ form.errors.area }}</small>
              </div>

              <div class="form-group half">
                <label>Nằm trên Lô đất</label>
                <select v-model="form.plot_id" :disabled="!form.zone_id">
                  <option :value="null">
                    {{ form.zone_id ? '-- Không gắn lô / Chọn lô đất --' : '-- Hãy chọn KCN trước --' }}
                  </option>
                  <option v-for="p in availablePlotsByZone" :key="p.id" :value="p.id">
                    🗺️ Lô: {{ p.code }} ({{ formatNumber(p.total_area || p.area) }} m²)
                  </option>
                </select>
                <small v-if="!form.zone_id" class="help-text">Vui lòng chọn KCN ở trên để xem danh sách lô đất.</small>
              </div>
            </div>

            <!-- HÀNG 3: TRẠNG THÁI & CÔNG TY THUÊ -->
            <div class="form-row">
              <div class="form-group half">
                <label>Trạng Thái Xưởng <span class="required">*</span></label>
                <select v-model="form.status">
                  <option value="available">🟢 Đang trống / Có sẵn</option>
                  <option value="rented">🔴 Đang hoạt động</option>
                  <option value="maintenance">🟡 Đang sửa chữa / Bảo trì</option>
                </select>
              </div>

              <div class="form-group half">
                <label>Công ty đang thuê</label>
                <select v-model="form.company_id" :disabled="form.status !== 'rented'">
                  <option :value="null">-- Chọn Công ty --</option>
                  <option v-for="c in modalCompanies" :key="c.id" :value="c.id">
                    🏢 {{ c.name }}
                  </option>
                </select>
              </div>
            </div>

            <!-- NHẬP TỌA ĐỘ RANH GIỚI POLYGON CÓ PREFIX -->
            <div class="form-group">
              <label>Tọa độ ranh giới Nhà xưởng (Các đỉnh đa giác)</label>
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
                ⚠️ Đa giác Nhà xưởng cần tối thiểu 3 góc/đỉnh phân cách bằng dấu chấm phẩy (;)
              </small>
              <small v-else class="help-text">
                Nhập danh sách các đỉnh: <code>Vĩ độ, Kinh độ; Vĩ độ, Kinh độ...</code> (Hệ thống tự động khép kín đa giác).
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
  name: 'FactoriesIndex',
  components: { AdminLayout },
  props: { 
    factories: { type: Array, default: () => [] }, 
    companies: { type: Array, default: () => [] }, 
    plots: { type: Array, default: () => [] },
    zones: { type: Array, default: () => [] } // Danh sách KCN từ FactoryController
  },
  data() {
    return {
      showModal: false,
      isEditMode: false,
      selectedId: null,
      searchQuery: '',
      zoneFilter: '',
      statusFilter: '',
      coordinates_input: '',
      form: this.$inertia.form({
        code: '',
        zone_id: '',
        area: '',
        status: 'available',
        plot_id: null,
        company_id: null,
        geojson: ''
      })
    };
  },
  computed: {
    totalFactoriesArea() {
      return this.filteredFactories.reduce((sum, f) => sum + (parseFloat(f.area) || 0), 0);
    },

    // 🌟 Chỉ lấy các lô đất thuộc đúng zone_id đang chọn trong modal
    availablePlotsByZone() {
      if (!this.form.zone_id) return [];
      return this.plots.filter(p => String(p.zone_id) === String(this.form.zone_id));
    },

    // 🌟 Lọc danh sách Lô đất trong Modal theo KCN đã chọn
    modalPlots() {
      if (!this.form.zone_id) return this.plots;
      return this.plots.filter(p => !p.zone_id || String(p.zone_id) === String(this.form.zone_id));
    },

    // 🌟 Lọc danh sách Công ty trong Modal theo KCN đã chọn
    modalCompanies() {
      if (!this.form.zone_id) return this.companies;
      return this.companies.filter(c => !c.zone_id || String(c.zone_id) === String(this.form.zone_id));
    },

    // 🌟 Lọc danh sách Nhà xưởng theo ô Tìm kiếm, Trạng thái & KCN
    filteredFactories() {
      return this.factories.filter(f => {
        const query = this.searchQuery.toLowerCase().trim();
        const matchesQuery = !query || 
          (f.code && f.code.toLowerCase().includes(query)) ||
          (f.plot_code && f.plot_code.toLowerCase().includes(query)) ||
          (f.company_name && f.company_name.toLowerCase().includes(query)) ||
          (f.company?.name && f.company.name.toLowerCase().includes(query));

        const matchesStatus = !this.statusFilter || f.status === this.statusFilter;
        const matchesZone = !this.zoneFilter || String(f.zone_id) === String(this.zoneFilter);

        return matchesQuery && matchesStatus && matchesZone;
      });
    },

    // Validate Polygon: Phải có ít nhất 3 đỉnh phân biệt
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

    // Validate Form
    isFormValid() {
      const isCodeValid = Boolean(this.form.code && this.form.code.trim() !== '');
      const isZoneValid = Boolean(this.form.zone_id);
      return isCodeValid && isZoneValid && this.isCoordinatesValid;
    }
  },
  methods: {
    getZoneName(zoneId) {
      if (!zoneId) return null;
      const found = this.zones.find(z => String(z.id) === String(zoneId));
      return found ? found.name : null;
    },

    onZoneChange() {
      // Nếu lô đất đang chọn không thuộc KCN mới chọn thì reset
      if (this.form.plot_id) {
        const isPlotValid = this.availablePlotsByZone.some(p => p.id === this.form.plot_id);
        if (!isPlotValid) {
          this.form.plot_id = null;
        }
      }
      // Tương tự với công ty
      if (this.form.company_id) {
        const currentCompany = this.companies.find(c => c.id === this.form.company_id);
        if (currentCompany && currentCompany.zone_id && String(currentCompany.zone_id) !== String(this.form.zone_id)) {
          this.form.company_id = null;
        }
      }
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
      this.selectedId = null;
      this.form.reset();
      this.coordinates_input = '';
      this.form.zone_id = this.zones.length ? this.zones[0].id : '';
      this.form.company_id = null;
      this.form.plot_id = null;
      this.form.status = 'available';
      this.form.geojson = '';
      this.showModal = true;
    },

    openEditModal(f) {
      this.isEditMode = true;
      this.selectedId = f.id;
      this.form.clearErrors();
      this.form.code = f.code || '';
      this.form.zone_id = f.zone_id || (this.zones.length ? this.zones[0].id : '');
      this.form.area = f.area || '';
      this.form.status = f.status || 'available';
      this.form.plot_id = f.plot_id || null;
      this.form.company_id = f.company_id || null;

      const rawGeom = f.geojson || f.geom;
      this.coordinates_input = this.extractCoordinates(rawGeom);
      this.onCoordinatesChange();

      this.showModal = true;
    },

    closeModal() {
      this.showModal = false;
      this.form.reset();
      this.coordinates_input = '';
    },

    saveFactory() {
      this.onCoordinatesChange();

      if (this.isEditMode) {
        this.form.put(`/admin/factories/${this.selectedId}`, { 
          onSuccess: () => this.closeModal() 
        });
      } else {
        this.form.post('/admin/factories', { 
          onSuccess: () => this.closeModal() 
        });
      }
    },

    deleteFactory(f) {
      if (confirm(`Anh có chắc chắn muốn xóa Nhà xưởng [${f.code}] không?`)) {
        this.$inertia.delete(`/admin/factories/${f.id}`);
      }
    },

    formatNumber(num) { 
      if (!num) return '0';
      return new Intl.NumberFormat('vi-VN').format(num); 
    },

    formatStatus(s) {
      if (s === 'rented') return '🔴 Đang hoạt động';
      if (s === 'available') return '🟢 Đang trống';
      return '🟡 Đang bảo trì';
    }
  }
};
</script>

<style scoped>
.factories-page { display: flex; flex-direction: column; gap: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
.page-header h2 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; }
.page-header p { margin: 4px 0 0 0; font-size: 12px; color: #64748b; }

.btn-primary { background: #2563eb; color: #fff; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }
.btn-primary:hover { background: #1d4ed8; }
.btn-secondary { background: #e2e8f0; color: #334155; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }

/* FILTER & SEARCH CARD */
.filter-card { background: #fff; border-radius: 12px; padding: 14px 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.filter-row { display: flex; gap: 14px; align-items: center; flex-wrap: wrap; }
.search-box { display: flex; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; flex: 1; min-width: 240px; }
.search-icon { margin-right: 8px; font-size: 14px; }
.search-box input { border: none; background: transparent; outline: none; width: 100%; font-size: 13px; color: #0f172a; }

.filter-box { display: flex; align-items: center; gap: 6px; }
.filter-box label { font-size: 12px; font-weight: 700; color: #475569; white-space: nowrap; }
.filter-box select { padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12px; outline: none; background: #fff; }

.stats-mini { display: flex; gap: 10px; font-size: 12px; color: #475569; margin-left: auto; }
.stat-item { background: #f1f5f9; padding: 6px 12px; border-radius: 6px; }
.stat-item.highlight { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }

/* TABLE CARD */
.table-card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.table-responsive { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
.data-table th { background: #f8fafc; padding: 12px 10px; color: #475569; font-weight: 700; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
.data-table td { padding: 12px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }

.factory-code { color: #10b981; font-size: 13px; }
.badge-zone { background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; padding: 2px 8px; border-radius: 5px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.plot-badge { background: #e0e7ff; color: #4338ca; padding: 3px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; }
.badge-gis { background: #ecfdf5; color: #047857; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; white-space: nowrap; }
.company-text { font-weight: 600; color: #0f172a; }
.text-muted { color: #94a3b8; font-size: 12px; }

.status-badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; white-space: nowrap; }
.status-badge.rented { background: #fef2f2; color: #dc2626; }
.status-badge.available { background: #ecfdf5; color: #059669; }
.status-badge.maintenance { background: #fffbeb; color: #d97706; }

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
.modal-form { padding: 20px; display: flex; flex-direction: column; gap: 14px; max-height: 85vh; overflow-y: auto; }
.form-row { display: flex; gap: 12px; flex-wrap: wrap; }
.half { flex: 1; min-width: 240px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 12px; font-weight: 700; color: #334155; }
.required { color: #dc2626; }
.form-group input, .form-group select, .form-group textarea { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 100%; box-sizing: border-box; font-family: inherit; }
.form-group textarea { resize: vertical; min-height: 75px; line-height: 1.5; }
.input-error { border-color: #dc2626 !important; background-color: #fef2f2 !important; }
.error-text { font-size: 11px; color: #dc2626; margin-top: 2px; display: block; }
.help-text { font-size: 11px; color: #64748b; margin-top: 4px; display: block; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px; }
.btn-primary:disabled, .btn-disabled { background-color: #94a3b8 !important; cursor: not-allowed !important; opacity: 0.7; }
</style>