<template>
  <AdminLayout>
    <div class="poles-page">
      <!-- 1. HEADER -->
      <div class="page-header">
        <div>
          <h2>💡 QUẢN LÝ CỘT ĐÈN CHIẾU SÁNG</h2>
          <p>Quản lý các cột đèn chiếu sáng dọc vỉa hè KCN, loại bóng LED, công suất và tủ điện cấp nguồn</p>
        </div>
        <button class="btn-primary" @click="openCreateModal">
          ➕ Thêm Cột Đèn Mới
        </button>
      </div>

      <!-- 2. BỘ LỌC VÀ THỐNG KÊ -->
      <div class="filter-card">
        <div class="filter-row">
          <div class="search-box">
            <span class="search-icon">🔍</span>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Tìm theo Mã cột, Chiều cao, Loại bóng..." 
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
            <label>Tủ điều khiển:</label>
            <select v-model="cabinetFilter">
              <option value="">-- Tất cả tủ điện --</option>
              <option v-for="c in filteredCabinetsForSelect" :key="c.id" :value="c.id">
                ⚡ {{ c.code }} - {{ c.name }}
              </option>
            </select>
          </div>

          <div class="filter-box">
            <label>Tình trạng:</label>
            <select v-model="statusFilter">
              <option value="">-- Tất cả --</option>
              <option value="working">🟢 Sáng bình thường</option>
              <option value="broken">🔴 Hỏng bóng / Đứt nguồn</option>
              <option value="maintenance">🟡 Đang sửa chữa</option>
            </select>
          </div>

          <div class="stats-mini">
            <span class="stat-item">
              💡 Tổng số cột: <b>{{ poles.length }}</b>
            </span>
            <span class="stat-item highlight">
              🟢 Hoạt động: <b>{{ workingCount }}</b>
            </span>
            <span v-if="brokenCount > 0" class="stat-item alert">
              🔴 Hỏng: <b>{{ brokenCount }}</b>
            </span>
          </div>
        </div>
      </div>

      <!-- 3. BẢNG DỮ LIỆU CỘT ĐÈN -->
      <div class="table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>STT</th>
                <th>Khu Công Nghiệp</th>
                <th>Mã Cột</th>
                <th>Tủ Điện Nguồn</th>
                <th>Tuyến Đường</th>
                <th>Kết Cấu Cột</th>
                <th>Loại Bóng & Công Suất</th>
                <th>Tình Trạng</th>
                <th>Tọa Độ GIS</th>
                <th class="text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(p, index) in filteredPoles" :key="p.id">
                <td>{{ index + 1 }}</td>
                <td>
                  <span class="zone-badge">
                    🏭 {{ p.zone ? p.zone.name : '—' }}
                  </span>
                </td>
                <td><b class="pole-code">💡 {{ p.code }}</b></td>
                <td>
                  <span v-if="p.cabinet" class="cabinet-tag" :title="p.cabinet.name">
                    ⚡ {{ p.cabinet.code }}
                  </span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td>
                  <span v-if="p.road" class="road-text">🛣️ {{ p.road.name }}</span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td>
                  <div class="pole-desc-cell">
                    <span><b>{{ p.height_m }}m</b> - {{ p.arm_type || 'Cần đơn' }}</span>
                    <small class="text-muted">{{ p.pole_material || 'Thép mạ kẽm' }}</small>
                  </div>
                </td>
                <td>
                  <div class="lamp-cell">
                    <span class="lamp-badge">{{ p.lamp_type || 'LED' }}</span>
                    <b>{{ p.wattage }} W</b>
                  </div>
                </td>
                <td>
                  <span class="status-badge" :class="p.status">
                    {{ formatStatus(p.status) }}
                  </span>
                </td>
                <td>
                  <span v-if="p.lat && p.lng" class="badge-coord" :title="`Lat: ${p.lat}, Lng: ${p.lng}`">
                    📍 {{ Number(p.lat).toFixed(4) }}, {{ Number(p.lng).toFixed(4) }}
                  </span>
                  <span v-else class="text-muted">Chưa có vị trí</span>
                </td>
                <td class="text-center actions-cell">
                  <button class="btn-icon btn-edit" @click="openEditModal(p)" title="Sửa thông tin">✏️</button>
                  <button class="btn-icon btn-delete" @click="deletePole(p)" title="Xóa cột đèn">🗑️</button>
                </td>
              </tr>
              <tr v-if="!filteredPoles.length">
                <td colspan="10" class="text-center text-muted">
                  {{ searchQuery || zoneFilter || cabinetFilter || statusFilter ? 'Không tìm thấy cột đèn phù hợp.' : 'Chưa có dữ liệu cột đèn chiếu sáng.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 4. MODAL THÊM / CẬP NHẬT CỘT ĐÈN -->
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ isEditMode ? '✏️ Cập nhật Cột Đèn Chiếu Sáng' : '➕ Thêm Mới Cột Đèn Chiếu Sáng' }}</h3>
            <button class="close-btn" @click="closeModal">✕</button>
          </div>

          <form @submit.prevent="savePole" class="modal-form">
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
                <label>Tủ điện cấp nguồn</label>
                <select v-model="form.cabinet_id">
                  <option value="">-- Chưa gán tủ điện --</option>
                  <option v-for="cab in availableCabinets" :key="cab.id" :value="cab.id">
                    ⚡ {{ cab.code }} - {{ cab.name }}
                  </option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Mã Cột Đèn <span class="required">*</span></label>
                <input 
                  type="text" 
                  v-model="form.code" 
                  required 
                  placeholder="Ví dụ: C-CS-D1-01" 
                  :class="{ 'input-error': form.errors.code }"
                />
                <small v-if="form.errors.code" class="error-text">{{ form.errors.code }}</small>
              </div>

              <div class="form-group half">
                <label>Tuyến đường bố trí</label>
                <select v-model="form.road_id">
                  <option value="">-- Tuyến đường đặt cột --</option>
                  <option v-for="r in availableRoads" :key="r.id" :value="r.id">
                    🛣️ {{ r.name }}
                  </option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Chiều cao cột (mét)</label>
                <input 
                  type="number" 
                  step="0.5" 
                  v-model="form.height_m" 
                  placeholder="8.0" 
                />
              </div>

              <div class="form-group half">
                <label>Chất liệu cột</label>
                <select v-model="form.pole_material">
                  <option value="Thép mạ kẽm">Thép mạ kẽm nhúng nóng</option>
                  <option value="Bê tông ly tâm">Bê tông ly tâm</option>
                  <option value="Gang đúc">Gang đúc mỹ thuật</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Loại bóng đèn <span class="required">*</span></label>
                <select v-model="form.lamp_type" required>
                  <option value="LED">💡 Đèn LED tiết kiệm điện</option>
                  <option value="Sodium Cao Áp">💡 Cao áp Sodium (Ánh sáng vàng)</option>
                  <option value="Metal Halide">💡 Metal Halide (Trắng sáng)</option>
                  <option value="Năng lượng mặt trời">☀️ Đèn LED năng lượng mặt trời</option>
                </select>
              </div>

              <div class="form-group half">
                <label>Công suất đèn (Watt)</label>
                <input 
                  type="number" 
                  v-model="form.wattage" 
                  placeholder="150" 
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Kiểu cần đèn</label>
                <select v-model="form.arm_type">
                  <option value="Cần đơn">Cần đơn (1 nhánh vươn)</option>
                  <option value="Cần đôi">Cần đôi (2 nhánh chữ Y/V)</option>
                  <option value="Đầu cột vươn">Gắn trực tiếp đỉnh cột</option>
                </select>
              </div>

              <div class="form-group half">
                <label>Tình trạng chiếu sáng <span class="required">*</span></label>
                <select v-model="form.status" required>
                  <option value="working">🟢 Đang chiếu sáng tốt</option>
                  <option value="broken">🔴 Hỏng bóng / Mất nguồn</option>
                  <option value="maintenance">🟡 Đang bảo trì / Thay thế</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Ngày dựng cột / Thay bóng</label>
                <input 
                  type="date" 
                  v-model="form.installation_date" 
                />
              </div>

              <div class="form-group half">
                <label>Ghi chú</label>
                <input 
                  type="text" 
                  v-model="form.notes" 
                  placeholder="Vị trí hố móng, tiếp địa..." 
                />
              </div>
            </div>

            <!-- TỌA ĐỘ VỊ TRÍ CỘT POINT -->
            <div class="form-group full-width">
              <label>Tọa độ vị trí cột đèn (Vĩ độ, Kinh độ WGS84) <span class="required">*</span></label>
              <div class="coord-input-wrapper">
                <input 
                  type="text" 
                  v-model="coordsInput" 
                  placeholder="Ví dụ: 21.246700, 106.178150" 
                  required 
                  :class="{ 'input-error': coordsInput && !isCoordsValid }"
                />
                <button type="button" class="btn-locate" @click="getCurrentLocation" title="Lấy vị trí GPS hiện tại">
                  🎯 GPS
                </button>
              </div>
              <small v-if="coordsInput && !isCoordsValid" class="error-text">
                ⚠️ Định dạng tọa độ không hợp lệ. Cần nhập: <code>Vĩ độ, Kinh độ</code>
              </small>
              <small v-else class="help-text">
                Sao chép từ Google Maps hoặc bấm 🎯 GPS để lấy tọa độ trực tiếp tại chân cột.
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
                {{ isEditMode ? 'Lưu Thay Đổi' : 'Tạo Mới Cột Đèn' }}
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
  name: 'LightingPolesIndex',
  components: { AdminLayout },
  props: {
    poles: { type: Array, default: () => [] },
    zones: { type: Array, default: () => [] },
    cabinets: { type: Array, default: () => [] },
    roads: { type: Array, default: () => [] }
  },
  data() {
    return {
      showModal: false,
      isEditMode: false,
      selectedPoleId: null,
      searchQuery: '',
      zoneFilter: '',
      cabinetFilter: '',
      statusFilter: '',
      coordsInput: '',
      form: this.$inertia.form({
        zone_id: '',
        cabinet_id: '',
        road_id: '',
        code: '',
        height_m: 8.0,
        pole_material: 'Thép mạ kẽm',
        arm_type: 'Cần đơn',
        lamp_type: 'LED',
        wattage: 150,
        status: 'working',
        installation_date: '',
        notes: '',
        lat: '',
        lng: ''
      })
    };
  },
  computed: {
    workingCount() {
      return this.poles.filter(p => p.status === 'working').length;
    },

    brokenCount() {
      return this.poles.filter(p => p.status === 'broken').length;
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

    filteredPoles() {
      return this.poles.filter(p => {
        const q = this.searchQuery.toLowerCase().trim();
        const matchCode = p.code && p.code.toLowerCase().includes(q);
        const matchLamp = p.lamp_type && p.lamp_type.toLowerCase().includes(q);

        const matchesQuery = !q || matchCode || matchLamp;
        const matchesZone = !this.zoneFilter || Number(p.zone_id) === Number(this.zoneFilter);
        const matchesCabinet = !this.cabinetFilter || Number(p.cabinet_id) === Number(this.cabinetFilter);
        const matchesStatus = !this.statusFilter || p.status === this.statusFilter;

        return matchesQuery && matchesZone && matchesCabinet && matchesStatus;
      });
    },

    isCoordsValid() {
      if (!this.coordsInput || !this.coordsInput.trim()) return false;
      const parts = this.coordsInput.split(',').map(s => parseFloat(s.trim()));
      return parts.length === 2 && !isNaN(parts[0]) && !isNaN(parts[1]);
    },

    isFormValid() {
      return (
        this.form.zone_id &&
        this.form.code && this.form.code.trim() !== '' &&
        this.form.lamp_type &&
        this.isCoordsValid
      );
    }
  },
  methods: {
    parseCoordinates() {
      if (this.isCoordsValid) {
        const parts = this.coordsInput.split(',').map(s => parseFloat(s.trim()));
        if (parts[0] < parts[1]) {
          this.form.lat = parts[0];
          this.form.lng = parts[1];
        } else {
          this.form.lng = parts[0];
          this.form.lat = parts[1];
        }
      }
    },

    getCurrentLocation() {
      if (!navigator.geolocation) {
        alert("Trình duyệt không hỗ trợ định vị GPS.");
        return;
      }
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          this.coordsInput = `${pos.coords.latitude.toFixed(6)}, ${pos.coords.longitude.toFixed(6)}`;
          this.parseCoordinates();
        },
        () => alert("Không thể lấy tọa độ vị trí hiện tại."),
        { enableHighAccuracy: true }
      );
    },

    openCreateModal() {
      this.isEditMode = false;
      this.selectedPoleId = null;
      this.form.reset();
      this.form.clearErrors();
      this.coordsInput = '';
      this.form.height_m = 8.0;
      this.form.pole_material = 'Thép mạ kẽm';
      this.form.arm_type = 'Cần đơn';
      this.form.lamp_type = 'LED';
      this.form.wattage = 150;
      this.form.status = 'working';
      this.showModal = true;
    },

    openEditModal(p) {
      this.isEditMode = true;
      this.selectedPoleId = p.id;
      this.form.clearErrors();
      this.form.zone_id = p.zone_id || '';
      this.form.cabinet_id = p.cabinet_id || '';
      this.form.road_id = p.road_id || '';
      this.form.code = p.code || '';
      this.form.height_m = p.height_m || 8.0;
      this.form.pole_material = p.pole_material || 'Thép mạ kẽm';
      this.form.arm_type = p.arm_type || 'Cần đơn';
      this.form.lamp_type = p.lamp_type || 'LED';
      this.form.wattage = p.wattage || 150;
      this.form.status = p.status || 'working';
      this.form.installation_date = p.installation_date || '';
      this.form.notes = p.notes || '';

      if (p.lat && p.lng) {
        this.coordsInput = `${p.lat}, ${p.lng}`;
        this.form.lat = p.lat;
        this.form.lng = p.lng;
      } else {
        this.coordsInput = '';
      }

      this.showModal = true;
    },

    closeModal() {
      this.showModal = false;
      this.form.reset();
      this.form.clearErrors();
      this.coordsInput = '';
    },

    savePole() {
      this.parseCoordinates();

      if (this.isEditMode) {
        this.form.put(`/admin/lighting-poles/${this.selectedPoleId}`, {
          onSuccess: () => this.closeModal()
        });
      } else {
        this.form.post('/admin/lighting-poles', {
          onSuccess: () => this.closeModal()
        });
      }
    },

    deletePole(p) {
      if (confirm(`Bạn có chắc chắn muốn XÓA Cột đèn [${p.code}] không?`)) {
        this.$inertia.delete(`/admin/lighting-poles/${p.id}`);
      }
    },

    formatStatus(status) {
      if (status === 'working') return '🟢 Hoạt động tốt';
      if (status === 'broken') return '🔴 Hỏng / Mất điện';
      return '🟡 Đang sửa chữa';
    }
  }
};
</script>

<style scoped>
.poles-page { display: flex; flex-direction: column; gap: 20px; }
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

.stats-mini { display: flex; gap: 10px; font-size: 12px; color: #475569; margin-left: auto; }
.stat-item { background: #f1f5f9; padding: 6px 12px; border-radius: 6px; }
.stat-item.highlight { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
.stat-item.alert { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

.table-card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.table-responsive { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
.data-table th { background: #f8fafc; padding: 12px; color: #475569; font-weight: 700; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
.data-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }

.zone-badge { background: #f1f5f9; color: #0f172a; border: 1px solid #e2e8f0; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 6px; }
.pole-code { color: #2563eb; font-size: 13px; }
.cabinet-tag { background: #fefce8; color: #a16207; border: 1px solid #fef08a; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.road-text { color: #0284c7; font-size: 12px; font-weight: 600; }

.pole-desc-cell { display: flex; flex-direction: column; gap: 2px; }
.lamp-cell { display: flex; align-items: center; gap: 6px; }
.lamp-badge { background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 700; }

.status-badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
.status-badge.working { background: #ecfdf5; color: #059669; }
.status-badge.maintenance { background: #fffbeb; color: #d97706; }
.status-badge.broken { background: #fef2f2; color: #dc2626; }

.badge-coord { background: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 11px; font-weight: 600; }
.text-muted { color: #94a3b8; font-size: 12px; }

.actions-cell { display: flex; justify-content: center; gap: 8px; }
.btn-icon { border: none; background: #f1f5f9; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.btn-edit:hover { background: #dbeafe; }
.btn-delete:hover { background: #fee2e2; }

/* MODAL STYLES */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 3000; display: flex; align-items: center; justify-content: center; padding: 16px; }
.modal-content { background: #fff; border-radius: 14px; width: 100%; max-width: 620px; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
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
.coord-input-wrapper { display: flex; gap: 8px; }
.coord-input-wrapper input { flex: 1; font-family: monospace; font-weight: 600; }
.btn-locate { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; border-radius: 6px; padding: 0 12px; font-size: 12px; font-weight: bold; cursor: pointer; white-space: nowrap; }
.btn-locate:hover { background: #dbeafe; }
.input-error { border-color: #dc2626 !important; background-color: #fef2f2 !important; }
.error-text { font-size: 11px; color: #dc2626; margin-top: 2px; display: block; }
.help-text { font-size: 11px; color: #64748b; margin-top: 4px; display: block; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }
.btn-primary:disabled, .btn-disabled { background-color: #94a3b8 !important; cursor: not-allowed !important; }
</style>