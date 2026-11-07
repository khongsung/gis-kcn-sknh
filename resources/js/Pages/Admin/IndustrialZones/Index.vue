<template>
  <AdminLayout>
    <div class="zones-page">
      <!-- 1. TIÊU ĐỀ TRANG -->
      <div class="page-header">
        <div>
          <h2>🏭 QUẢN LÝ KHU CÔNG NGHIỆP</h2>
          <p>Thiết lập danh mục KCN, tọa độ trung tâm và mức zoom mặc định cho WebGIS</p>
        </div>
        <button class="btn-primary" @click="openCreateModal">
          ➕ Thêm Khu Công Nghiệp
        </button>
      </div>

      <!-- 2. BỘ LỌC VÀ TÌM KIẾM -->
      <div class="filter-card">
        <div class="filter-row">
          <div class="search-box">
            <span class="search-icon">🔍</span>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Tìm theo Mã KCN, Tên KCN, Tỉnh thành..." 
            />
          </div>

          <div class="stats-mini">
            <span class="stat-item">
              🏭 Tổng số KCN: <b>{{ zones.length }}</b>
            </span>
            <span class="stat-item highlight">
              🟢 Đang hoạt động: <b>{{ activeCount }}</b>
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
                <th>Mã KCN</th>
                <th>Tên Khu Công Nghiệp</th>
                <th>Địa Bàn</th>
                <th>Diện Tích (ha)</th>
                <th>Tâm Bản Đồ (WGS84)</th>
                <th>Zoom Chuẩn</th>
                <th>Dữ Liệu Hạ Tầng</th>
                <th>Trạng Thái</th>
                <th class="text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(zone, index) in filteredZones" :key="zone.id">
                <td>{{ index + 1 }}</td>
                <td><b class="zone-code">{{ zone.code }}</b></td>
                <td>
                  <div class="zone-name-cell">
                    <b>{{ zone.name }}</b>
                    <small class="text-muted">{{ zone.description || 'Chưa có mô tả' }}</small>
                  </div>
                </td>
                <td>{{ zone.province || 'Bắc Giang' }}</td>
                <td><b>{{ zone.total_area ? Number(zone.total_area).toLocaleString() : '—' }}</b></td>
                <td>
                  <span v-if="zone.lat && zone.lng" class="badge-coord" :title="`Lat: ${zone.lat}, Lng: ${zone.lng}`">
                    📍 {{ Number(zone.lat).toFixed(4) }}, {{ Number(zone.lng).toFixed(4) }}
                  </span>
                  <span v-else class="text-muted">Chưa gắn tâm</span>
                </td>
                <td>
                  <span class="badge-zoom">Zoom {{ zone.default_zoom || 15 }}</span>
                </td>
                <td>
                  <div class="infrastructure-counts">
                    <span title="Số nhà xưởng">🏭 {{ zone.factories_count || 0 }} xưởng</span>
                    <span title="Số lô đất">📐 {{ zone.plots_count || 0 }} lô</span>
                    <span title="Địa danh lân cận">📍 {{ zone.surrounding_locations_count || 0 }} mốc</span>
                  </div>
                </td>
                <td>
                  <span class="status-badge" :class="zone.is_active ? 'active' : 'inactive'">
                    {{ zone.is_active ? '🟢 Hoạt động' : '🔴 Tạm khóa' }}
                  </span>
                </td>
                <td class="text-center actions-cell">
                  <button class="btn-icon btn-edit" @click="openEditModal(zone)" title="Sửa KCN">✏️</button>
                  <button class="btn-icon btn-delete" @click="deleteZone(zone)" title="Xóa KCN">🗑️</button>
                </td>
              </tr>
              <tr v-if="!filteredZones.length">
                <td colspan="10" class="text-center text-muted">
                  {{ searchQuery ? 'Không tìm thấy Khu công nghiệp phù hợp.' : 'Chưa có dữ liệu KCN.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 4. MODAL THÊM / CẬP NHẬT KCN -->
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ isEditMode ? '✏️ Cập nhật Khu Công Nghiệp' : '➕ Thêm Mới Khu Công Nghiệp' }}</h3>
            <button class="close-btn" @click="closeModal">✕</button>
          </div>

          <form @submit.prevent="saveZone" class="modal-form">
            <div class="form-row">
              <div class="form-group half">
                <label>Mã KCN <span class="required">*</span></label>
                <input 
                  type="text" 
                  v-model="form.code" 
                  required 
                  placeholder="Ví dụ: SK_NH, DT, VT..." 
                  :class="{ 'input-error': form.errors.code }"
                  style="text-transform: uppercase;"
                />
                <small v-if="form.errors.code" class="error-text">{{ form.errors.code }}</small>
              </div>

              <div class="form-group half">
                <label>Tỉnh / Thành phố</label>
                <input 
                  type="text" 
                  v-model="form.province" 
                  placeholder="Bắc Giang" 
                />
              </div>
            </div>

            <div class="form-group full-width">
              <label>Tên đầy đủ Khu Công Nghiệp <span class="required">*</span></label>
              <input 
                type="text" 
                v-model="form.name" 
                required 
                placeholder="Ví dụ: Khu công nghiệp Song Khê - Nội Hoàng, Khu công nghiệp Đình Trám..." 
                :class="{ 'input-error': form.errors.name }"
              />
              <small v-if="form.errors.name" class="error-text">{{ form.errors.name }}</small>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Diện tích quy hoạch (ha)</label>
                <input 
                  type="number" 
                  step="0.1" 
                  v-model="form.total_area" 
                  placeholder="Ví dụ: 180" 
                />
              </div>

              <div class="form-group half">
                <label>Mức Zoom khởi tạo bản đồ</label>
                <select v-model="form.default_zoom">
                  <option :value="14">Zoom 14 (Nhìn bao quát vùng)</option>
                  <option :value="15">Zoom 15 (Tiêu chuẩn KCN)</option>
                  <option :value="16">Zoom 16 (Cận cảnh tuyến đường)</option>
                </select>
              </div>
            </div>

            <!-- TỌA ĐỘ TÂM WGS84 -->
            <div class="form-group full-width">
              <label>Tọa độ tâm bản đồ (Vĩ độ Lat, Kinh độ Lng) <span class="required">*</span></label>
              <div class="coord-input-wrapper">
                <input 
                  type="text" 
                  v-model="coordsInput" 
                  placeholder="Ví dụ: 21.248200, 106.176500" 
                  required 
                  :class="{ 'input-error': coordsInput && !isCoordsValid }"
                />
                <button type="button" class="btn-locate" @click="getCurrentLocation" title="Lấy tọa độ thiết bị">
                  🎯 GPS
                </button>
              </div>
              <small v-if="coordsInput && !isCoordsValid" class="error-text">
                ⚠️ Định dạng tọa độ không hợp lệ. Cần nhập: <code>Vĩ độ, Kinh độ</code>
              </small>
              <small v-else class="help-text">
                Khi chọn KCN này, WebGIS sẽ tự động định vị trung tâm màn hình về tọa độ trên.
              </small>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Trạng thái</label>
                <select v-model="form.is_active">
                  <option :value="true">🟢 Đang hoạt động / Khai thác</option>
                  <option :value="false">🔴 Tạm ngưng hiển thị</option>
                </select>
              </div>

              <div class="form-group half">
                <label>Ghi chú / Mô tả</label>
                <input 
                  type="text" 
                  v-model="form.description" 
                  placeholder="Ví dụ: Đã lấp đầy 95% diện tích" 
                />
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
                {{ isEditMode ? 'Lưu Thay Đổi' : 'Tạo Mới KCN' }}
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
  name: 'IndustrialZonesIndex',
  components: { AdminLayout },
  props: {
    zones: { type: Array, default: () => [] }
  },
  data() {
    return {
      showModal: false,
      isEditMode: false,
      selectedZoneId: null,
      searchQuery: '',
      coordsInput: '',
      form: this.$inertia.form({
        code: '',
        name: '',
        province: 'Bắc Giang',
        total_area: '',
        default_zoom: 15,
        description: '',
        is_active: true,
        lat: '',
        lng: ''
      })
    };
  },
  computed: {
    activeCount() {
      return this.zones.filter(z => z.is_active).length;
    },

    filteredZones() {
      return this.zones.filter(z => {
        const q = this.searchQuery.toLowerCase().trim();
        const matchCode = z.code && z.code.toLowerCase().includes(q);
        const matchName = z.name && z.name.toLowerCase().includes(q);
        const matchProv = z.province && z.province.toLowerCase().includes(q);
        return !q || matchCode || matchName || matchProv;
      });
    },

    isCoordsValid() {
      if (!this.coordsInput || !this.coordsInput.trim()) return false;
      const parts = this.coordsInput.split(',').map(s => parseFloat(s.trim()));
      return parts.length === 2 && !isNaN(parts[0]) && !isNaN(parts[1]);
    },

    isFormValid() {
      return (
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
        () => alert("Không thể lấy tọa độ hiện tại."),
        { enableHighAccuracy: true }
      );
    },

    openCreateModal() {
      this.isEditMode = false;
      this.selectedZoneId = null;
      this.form.reset();
      this.form.clearErrors();
      this.coordsInput = '';
      this.form.province = 'Bắc Giang';
      this.form.default_zoom = 15;
      this.form.is_active = true;
      this.showModal = true;
    },

    openEditModal(zone) {
      this.isEditMode = true;
      this.selectedZoneId = zone.id;
      this.form.clearErrors();
      this.form.code = zone.code || '';
      this.form.name = zone.name || '';
      this.form.province = zone.province || 'Bắc Giang';
      this.form.total_area = zone.total_area || '';
      this.form.default_zoom = zone.default_zoom || 15;
      this.form.description = zone.description || '';
      this.form.is_active = Boolean(zone.is_active);

      if (zone.lat && zone.lng) {
        this.coordsInput = `${zone.lat}, ${zone.lng}`;
        this.form.lat = zone.lat;
        this.form.lng = zone.lng;
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

    saveZone() {
      this.parseCoordinates();

      if (this.isEditMode) {
        this.form.put(`/admin/industrial-zones/${this.selectedZoneId}`, {
          onSuccess: () => this.closeModal()
        });
      } else {
        this.form.post('/admin/industrial-zones', {
          onSuccess: () => this.closeModal()
        });
      }
    },

    deleteZone(zone) {
      if (confirm(`Bạn có chắc chắn muốn XÓA Khu công nghiệp [${zone.code} - ${zone.name}] không?`)) {
        this.$inertia.delete(`/admin/industrial-zones/${zone.id}`);
      }
    }
  }
};
</script>

<style scoped>
.zones-page { display: flex; flex-direction: column; gap: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
.page-header h2 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; }
.page-header p { margin: 4px 0 0 0; font-size: 12px; color: #64748b; }

.btn-primary { background: #2563eb; color: #fff; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }
.btn-primary:hover { background: #1d4ed8; }
.btn-secondary { background: #e2e8f0; color: #334155; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }

.filter-card { background: #fff; border-radius: 12px; padding: 14px 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.filter-row { display: flex; gap: 14px; align-items: center; flex-wrap: wrap; }
.search-box { display: flex; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; flex: 1; min-width: 260px; }
.search-icon { margin-right: 8px; font-size: 14px; }
.search-box input { border: none; background: transparent; outline: none; width: 100%; font-size: 13px; color: #0f172a; }

.stats-mini { display: flex; gap: 10px; font-size: 12px; color: #475569; margin-left: auto; }
.stat-item { background: #f1f5f9; padding: 6px 12px; border-radius: 6px; }
.stat-item.highlight { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }

.table-card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.table-responsive { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
.data-table th { background: #f8fafc; padding: 12px; color: #475569; font-weight: 700; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
.data-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

.zone-code { color: #2563eb; font-size: 13px; letter-spacing: 0.5px; }
.zone-name-cell { display: flex; flex-direction: column; gap: 2px; }
.badge-coord { background: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 11px; font-weight: 600; }
.badge-zoom { background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; }

.infrastructure-counts { display: flex; flex-direction: column; gap: 2px; font-size: 11px; color: #475569; }

.status-badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
.status-badge.active { background: #ecfdf5; color: #059669; }
.status-badge.inactive { background: #fef2f2; color: #dc2626; }

.text-muted { color: #94a3b8; font-size: 12px; }
.actions-cell { display: flex; justify-content: center; gap: 8px; }
.btn-icon { border: none; background: #f1f5f9; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.btn-edit:hover { background: #dbeafe; }
.btn-delete:hover { background: #fee2e2; }

/* MODAL */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 3000; display: flex; align-items: center; justify-content: center; padding: 16px; }
.modal-content { background: #fff; border-radius: 14px; width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
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
.form-group input, .form-group select { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 100%; box-sizing: border-box; }

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