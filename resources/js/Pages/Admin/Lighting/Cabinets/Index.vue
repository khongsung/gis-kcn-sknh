<template>
  <AdminLayout>
    <div class="cabinets-page">
      <!-- 1. HEADER -->
      <div class="page-header">
        <div>
          <h2>⚡ QUẢN LÝ TỦ ĐIỆN ĐIỀU KHIỂN CHIẾU SÁNG</h2>
          <p>Quản lý các trạm cấp nguồn, tủ điều khiển timer / cảm biến quang cho hệ thống đèn KCN</p>
        </div>
        <button class="btn-primary" @click="openCreateModal">
          ➕ Thêm Tủ Điện Mới
        </button>
      </div>

      <!-- 2. THANH LỌC VÀ THỐNG KÊ -->
      <div class="filter-card">
        <div class="filter-row">
          <div class="search-box">
            <span class="search-icon">🔍</span>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Tìm theo Mã tủ, Tên tủ, Nguồn cấp TBA..." 
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
            <label>Trạng thái:</label>
            <select v-model="statusFilter">
              <option value="">-- Tất cả trạng thái --</option>
              <option value="active">🟢 Đang hoạt động</option>
              <option value="maintenance">🟡 Đang bảo trì</option>
              <option value="inactive">🔴 Ngừng cấp điện</option>
            </select>
          </div>

          <div class="stats-mini">
            <span class="stat-item">
              ⚡ Tổng số tủ: <b>{{ cabinets.length }}</b>
            </span>
            <span class="stat-item highlight">
              🟢 Đang chạy: <b>{{ activeCount }}</b>
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
                <th>Mã Tủ Điện</th>
                <th>Tên Tủ & Tuyến Đường</th>
                <th>Công Suất</th>
                <th>Chế Độ Đóng Cắt</th>
                <th>Liên Kết Hạ Tầng</th>
                <th>Trạng Thái</th>
                <th>Tọa Độ GIS</th>
                <th class="text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(c, index) in filteredCabinets" :key="c.id">
                <td>{{ index + 1 }}</td>
                <td>
                  <span class="zone-badge">
                    🏭 {{ c.zone ? c.zone.name : '—' }}
                  </span>
                </td>
                <td><b class="cabinet-code">⚡ {{ c.code }}</b></td>
                <td>
                  <div class="cabinet-name-cell">
                    <b>{{ c.name }}</b>
                    <small v-if="c.road" class="road-text">🛣️ {{ c.road.name }}</small>
                    <small v-if="c.power_source" class="power-text">🔌 Nguồn: {{ c.power_source }}</small>
                  </div>
                </td>
                <td>
                  <b>{{ c.capacity_kva ? c.capacity_kva + ' kVA' : '—' }}</b>
                </td>
                <td>
                  <span class="control-badge">
                    {{ formatControlType(c.control_type) }}
                  </span>
                </td>
                <td>
                  <div class="infra-counts">
                    <span class="badge-sub" title="Số cột đèn nhận nguồn từ tủ này">
                      💡 {{ c.poles_count || 0 }} cột
                    </span>
                    <span class="badge-sub" title="Số tuyến cáp xuất phát từ tủ này">
                      〰️ {{ c.cables_count || 0 }} lộ cáp
                    </span>
                  </div>
                </td>
                <td>
                  <span class="status-badge" :class="c.status">
                    {{ formatStatus(c.status) }}
                  </span>
                </td>
                <td>
                  <span v-if="c.lat && c.lng" class="badge-coord" :title="`Lat: ${c.lat}, Lng: ${c.lng}`">
                    📍 {{ Number(c.lat).toFixed(4) }}, {{ Number(c.lng).toFixed(4) }}
                  </span>
                  <span v-else class="text-muted">Chưa có vị trí</span>
                </td>
                <td class="text-center actions-cell">
                  <button class="btn-icon btn-edit" @click="openEditModal(c)" title="Sửa thông tin">✏️</button>
                  <button class="btn-icon btn-delete" @click="deleteCabinet(c)" title="Xóa tủ điện">🗑️</button>
                </td>
              </tr>
              <tr v-if="!filteredCabinets.length">
                <td colspan="10" class="text-center text-muted">
                  {{ searchQuery || zoneFilter || statusFilter ? 'Không tìm thấy tủ điện phù hợp với bộ lọc.' : 'Chưa có dữ liệu tủ điện chiếu sáng.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 4. MODAL THÊM / CẬP NHẬT TỦ ĐIỆN -->
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ isEditMode ? '✏️ Cập nhật Tủ Điện Chiếu Sáng' : '➕ Thêm Mới Tủ Điện Chiếu Sáng' }}</h3>
            <button class="close-btn" @click="closeModal">✕</button>
          </div>

          <form @submit.prevent="saveCabinet" class="modal-form">
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
                <label>Tuyến đường đặt tủ</label>
                <select v-model="form.road_id">
                  <option value="">-- Không chọn / Trục nội bộ --</option>
                  <option v-for="r in availableRoads" :key="r.id" :value="r.id">
                    🛣️ {{ r.name }}
                  </option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Mã Tủ Điện <span class="required">*</span></label>
                <input 
                  type="text" 
                  v-model="form.code" 
                  required 
                  placeholder="Ví dụ: TDK-CS-01" 
                  :class="{ 'input-error': form.errors.code }"
                />
                <small v-if="form.errors.code" class="error-text">{{ form.errors.code }}</small>
              </div>

              <div class="form-group half">
                <label>Tên Tủ Điện <span class="required">*</span></label>
                <input 
                  type="text" 
                  v-model="form.name" 
                  required 
                  placeholder="Ví dụ: Tủ điều khiển chiếu sáng Trục D1" 
                  :class="{ 'input-error': form.errors.name }"
                />
                <small v-if="form.errors.name" class="error-text">{{ form.errors.name }}</small>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Công Suất Thiết Kế (kVA/kW)</label>
                <input 
                  type="number" 
                  step="0.1" 
                  v-model="form.capacity_kva" 
                  placeholder="Ví dụ: 25.0" 
                />
              </div>

              <div class="form-group half">
                <label>Chế Độ Đóng Cắt / Điều Khiển <span class="required">*</span></label>
                <select v-model="form.control_type" required>
                  <option value="timer">⏱️ Timer hẹn giờ tự động</option>
                  <option value="photocell">☀️ Cảm biến quang (Photocell)</option>
                  <option value="smart_iot">📡 Điều khiển thông minh (IoT / SCADA)</option>
                  <option value="manual">🖐️ Đóng cắt thủ công</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Nguồn Cấp (Từ Trạm biến áp)</label>
                <input 
                  type="text" 
                  v-model="form.power_source" 
                  placeholder="Ví dụ: TBA KCN Song Khê 1" 
                />
              </div>

              <div class="form-group half">
                <label>Số Lộ / Nhánh Ra</label>
                <input 
                  type="number" 
                  v-model="form.channels_count" 
                  placeholder="2" 
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Trạng Thái Vận Hành <span class="required">*</span></label>
                <select v-model="form.status" required>
                  <option value="active">🟢 Đang hoạt động</option>
                  <option value="maintenance">🟡 Đang bảo dưỡng / Sửa chữa</option>
                  <option value="inactive">🔴 Ngừng cấp điện</option>
                </select>
              </div>

              <div class="form-group half">
                <label>Ngày Đóng Điện / Lắp Đặt</label>
                <input 
                  type="date" 
                  v-model="form.installation_date" 
                />
              </div>
            </div>

            <!-- TỌA ĐỘ VỊ TRÍ TỦ POINT -->
            <div class="form-group full-width">
              <label>Tọa độ vị trí tủ điện (Vĩ độ, Kinh độ WGS84) <span class="required">*</span></label>
              <div class="coord-input-wrapper">
                <input 
                  type="text" 
                  v-model="coordsInput" 
                  placeholder="Ví dụ: 21.246624, 106.178020" 
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
                Sao chép từ Google Maps hoặc bấm nút 🎯 GPS để lấy tọa độ trực tiếp tại hiện trường.
              </small>
            </div>

            <div class="form-group full-width">
              <label>Ghi Chú / Thông Số Kỹ Thuật Thiết Bị</label>
              <textarea 
                v-model="form.notes" 
                rows="2" 
                placeholder="Ghi chú về aptomat tổng, rơ le nhiệt, chống sét lan truyền..."
              ></textarea>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="closeModal">Hủy bỏ</button>
              <button 
                type="submit" 
                class="btn-primary" 
                :disabled="!isFormValid || form.processing"
                :class="{ 'btn-disabled': !isFormValid || form.processing }"
              >
                {{ isEditMode ? 'Lưu Thay Đổi' : 'Tạo Mới Tủ Điện' }}
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
  name: 'LightingCabinetsIndex',
  components: { AdminLayout },
  props: {
    cabinets: { type: Array, default: () => [] },
    zones: { type: Array, default: () => [] },
    roads: { type: Array, default: () => [] }
  },
  data() {
    return {
      showModal: false,
      isEditMode: false,
      selectedCabinetId: null,
      searchQuery: '',
      zoneFilter: '',
      statusFilter: '',
      coordsInput: '',
      form: this.$inertia.form({
        zone_id: '',
        road_id: '',
        code: '',
        name: '',
        capacity_kva: '',
        control_type: 'timer',
        power_source: '',
        channels_count: 2,
        status: 'active',
        installation_date: '',
        notes: '',
        lat: '',
        lng: ''
      })
    };
  },
  computed: {
    activeCount() {
      return this.cabinets.filter(c => c.status === 'active').length;
    },

    availableRoads() {
      if (!this.form.zone_id) return this.roads;
      return this.roads.filter(r => Number(r.zone_id) === Number(this.form.zone_id));
    },

    filteredCabinets() {
      return this.cabinets.filter(c => {
        const q = this.searchQuery.toLowerCase().trim();
        const matchCode = c.code && c.code.toLowerCase().includes(q);
        const matchName = c.name && c.name.toLowerCase().includes(q);
        const matchPower = c.power_source && c.power_source.toLowerCase().includes(q);

        const matchesQuery = !q || matchCode || matchName || matchPower;
        const matchesZone = !this.zoneFilter || Number(c.zone_id) === Number(this.zoneFilter);
        const matchesStatus = !this.statusFilter || c.status === this.statusFilter;

        return matchesQuery && matchesZone && matchesStatus;
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
        this.form.name && this.form.name.trim() !== '' &&
        this.isCoordsValid
      );
    }
  },
  methods: {
    parseCoordinates() {
      if (this.isCoordsValid) {
        const parts = this.coordsInput.split(',').map(s => parseFloat(s.trim()));
        // Phân biệt Lat (< Lng đối với tọa độ miền Bắc VN)
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
      this.selectedCabinetId = null;
      this.form.reset();
      this.form.clearErrors();
      this.coordsInput = '';
      this.form.control_type = 'timer';
      this.form.status = 'active';
      this.form.channels_count = 2;
      this.showModal = true;
    },

    openEditModal(c) {
      this.isEditMode = true;
      this.selectedCabinetId = c.id;
      this.form.clearErrors();
      this.form.zone_id = c.zone_id || '';
      this.form.road_id = c.road_id || '';
      this.form.code = c.code || '';
      this.form.name = c.name || '';
      this.form.capacity_kva = c.capacity_kva || '';
      this.form.control_type = c.control_type || 'timer';
      this.form.power_source = c.power_source || '';
      this.form.channels_count = c.channels_count || 2;
      this.form.status = c.status || 'active';
      this.form.installation_date = c.installation_date || '';
      this.form.notes = c.notes || '';

      if (c.lat && c.lng) {
        this.coordsInput = `${c.lat}, ${c.lng}`;
        this.form.lat = c.lat;
        this.form.lng = c.lng;
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

    saveCabinet() {
      this.parseCoordinates();

      if (this.isEditMode) {
        this.form.put(`/admin/lighting-cabinets/${this.selectedCabinetId}`, {
          onSuccess: () => this.closeModal()
        });
      } else {
        this.form.post('/admin/lighting-cabinets', {
          onSuccess: () => this.closeModal()
        });
      }
    },

    deleteCabinet(c) {
      if (confirm(`Bạn có chắc chắn muốn XÓA Tủ điện [${c.code} - ${c.name}] không?`)) {
        this.$inertia.delete(`/admin/lighting-cabinets/${c.id}`);
      }
    },

    formatStatus(status) {
      if (status === 'active') return '🟢 Đang hoạt động';
      if (status === 'maintenance') return '🟡 Bảo trì';
      return '🔴 Ngừng cấp điện';
    },

    formatControlType(type) {
      if (type === 'timer') return '⏱️ Hẹn giờ';
      if (type === 'photocell') return '☀️ Cảm biến quang';
      if (type === 'smart_iot') return '📡 Smart IoT';
      return '🖐️ Thủ công';
    }
  }
};
</script>

<style scoped>
.cabinets-page { display: flex; flex-direction: column; gap: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
.page-header h2 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; }
.page-header p { margin: 4px 0 0 0; font-size: 12px; color: #64748b; }

.btn-primary { background: #2563eb; color: #fff; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }
.btn-primary:hover { background: #1d4ed8; }
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
.stat-item.highlight { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }

.table-card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.table-responsive { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
.data-table th { background: #f8fafc; padding: 12px; color: #475569; font-weight: 700; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
.data-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }

.zone-badge { background: #f1f5f9; color: #0f172a; border: 1px solid #e2e8f0; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 6px; }
.cabinet-code { color: #d97706; font-size: 13px; }
.cabinet-name-cell { display: flex; flex-direction: column; gap: 2px; }
.road-text { color: #2563eb; font-size: 11px; }
.power-text { color: #64748b; font-size: 11px; }
.control-badge { background: #f8fafc; border: 1px solid #cbd5e1; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 600; }

.infra-counts { display: flex; gap: 6px; }
.badge-sub { background: #fefce8; color: #854d0e; border: 1px solid #fef08a; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 700; }

.status-badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
.status-badge.active { background: #ecfdf5; color: #059669; }
.status-badge.maintenance { background: #fffbeb; color: #d97706; }
.status-badge.inactive { background: #fef2f2; color: #dc2626; }

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