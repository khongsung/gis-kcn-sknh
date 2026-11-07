<template>
  <AdminLayout>
    <div class="surroundings-page">
      <!-- 1. TIÊU ĐỀ TRANG -->
      <div class="page-header">
        <div>
          <h2>📍 QUẢN LÝ VÙNG PHỤ CẬN & ĐỊA DANH LÂN CẬN</h2>
          <p>Định nghĩa các mốc vị trí, khu dân cư, hạ tầng logistic, nút giao đối ngoại hiển thị nhãn khi zoom xa trên bản đồ</p>
        </div>
        <button class="btn-primary" @click="openCreateModal">
          ➕ Thêm Vùng Lân Cận
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
              placeholder="Tìm theo Mã vùng, Tên nhãn, Mô tả..." 
            />
          </div>

          <div class="filter-box">
            <label>Khu công nghiệp kề bên:</label>
            <select v-model="zoneFilter">
              <option value="">-- Tất cả KCN --</option>
              <option v-for="z in zones" :key="z.id" :value="z.id">
                🏭 {{ z.name }}
              </option>
            </select>
          </div>

          <div class="filter-box">
            <label>Phân loại vùng:</label>
            <select v-model="categoryFilter">
              <option value="">-- Tất cả phân loại --</option>
              <option value="residential">🏘️ Khu dân cư / Đô thị</option>
              <option value="transport">🛣️ Hạ tầng giao thông đối ngoại</option>
              <option value="logistics">🚚 Cụm Logistics / Kho bãi</option>
              <option value="ecological">🌊 Mặt nước / Vùng đệm sinh thái</option>
              <option value="industrial">🏭 Cụm công nghiệp lân cận</option>
            </select>
          </div>

          <div class="stats-mini">
            <span class="stat-item">
              📍 Tổng số mốc: <b>{{ locations.length }}</b>
            </span>
            <span class="stat-item highlight">
              🟢 Đang kích hoạt: <b>{{ activeCount }}</b>
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
                <th>KCN Tiếp Giáp</th>
                <th>Mã Vùng</th>
                <th>Tên Nhãn & Phân Loại</th>
                <th>Xem Trước Nhãn (Mũi nhọn)</th>
                <th>Mô Tả Không Gian</th>
                <th>Trạng Thái</th>
                <th>Tọa Độ GIS (WGS84)</th>
                <th class="text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(loc, index) in filteredLocations" :key="loc.id">
                <td>{{ index + 1 }}</td>
                <td>
                  <span class="zone-badge">
                    🏭 {{ loc.zone ? loc.zone.name : 'Vùng chung' }}
                  </span>
                </td>
                <td><b class="loc-code">{{ loc.code }}</b></td>
                <td>
                  <div class="loc-title-cell">
                    <b>{{ loc.name }}</b>
                    <small class="category-text">{{ formatCategory(loc.category) }}</small>
                  </div>
                </td>
                <!-- XEM TRƯỚC NHÃN CÓ MŨI NHỌN -->
                <td>
                  <div class="table-preview-wrapper">
                    <div 
                      class="preview-badge" 
                      :style="{ backgroundColor: loc.bg_color, color: loc.text_color }"
                    >
                      <span>{{ loc.icon || '📍' }} {{ loc.name }}</span>
                      <div 
                        class="preview-pin" 
                        :style="{ borderTopColor: loc.bg_color }"
                      ></div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="desc-text">{{ loc.description || '—' }}</span>
                </td>
                <td>
                  <span class="status-badge" :class="loc.is_active ? 'active' : 'inactive'">
                    {{ loc.is_active ? '🟢 Hiển thị' : '🔴 Ẩn' }}
                  </span>
                </td>
                <td>
                  <span v-if="loc.lat && loc.lng" class="badge-coord" :title="`Lat: ${loc.lat}, Lng: ${loc.lng}`">
                    📍 {{ Number(loc.lat).toFixed(4) }}, {{ Number(loc.lng).toFixed(4) }}
                  </span>
                  <span v-else class="text-muted">Chưa có vị trí</span>
                </td>
                <td class="text-center actions-cell">
                  <button class="btn-icon btn-edit" @click="openEditModal(loc)" title="Sửa thông tin">✏️</button>
                  <button class="btn-icon btn-delete" @click="deleteLocation(loc)" title="Xóa mốc">🗑️</button>
                </td>
              </tr>
              <tr v-if="!filteredLocations.length">
                <td colspan="9" class="text-center text-muted">
                  {{ searchQuery || zoneFilter || categoryFilter ? 'Không tìm thấy mốc vị trí phù hợp.' : 'Chưa có dữ liệu vùng lân cận.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 4. MODAL THÊM / CẬP NHẬT MỐC VÙNG LÂN CẬN -->
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ isEditMode ? '✏️ Cập nhật Vùng Lân Cận' : '➕ Thêm Mới Vùng Lân Cận' }}</h3>
            <button class="close-btn" @click="closeModal">✕</button>
          </div>

          <form @submit.prevent="saveLocation" class="modal-form">
            <div class="form-row">
              <div class="form-group half">
                <label>KCN tiếp giáp (tùy chọn)</label>
                <select v-model="form.zone_id">
                  <option value="">-- Mốc vùng chung / Không gắn --</option>
                  <option v-for="z in zones" :key="z.id" :value="z.id">
                    🏭 {{ z.name }} ({{ z.code }})
                  </option>
                </select>
              </div>

              <div class="form-group half">
                <label>Phân loại vùng <span class="required">*</span></label>
                <select v-model="form.category" required>
                  <option value="residential">🏘️ Khu dân cư / Đô thị</option>
                  <option value="transport">🛣️ Hạ tầng giao thông đối ngoại</option>
                  <option value="logistics">🚚 Cụm Logistics / Kho bãi</option>
                  <option value="ecological">🌊 Mặt nước / Vùng đệm sinh thái</option>
                  <option value="industrial">🏭 Cụm công nghiệp lân cận</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Mã Vùng <span class="required">*</span></label>
                <input 
                  type="text" 
                  v-model="form.code" 
                  required 
                  placeholder="Ví dụ: KDC-SKNH-NAM" 
                  :class="{ 'input-error': form.errors.code }"
                />
                <small v-if="form.errors.code" class="error-text">{{ form.errors.code }}</small>
              </div>

              <div class="form-group half">
                <label>Biểu tượng (Icon Emoji)</label>
                <div class="icon-input-row">
                  <input 
                    type="text" 
                    v-model="form.icon" 
                    placeholder="🏘️" 
                    class="icon-input"
                  />
                  <div class="quick-icons">
                    <button type="button" v-for="ic in quickIcons" :key="ic" @click="form.icon = ic">
                      {{ ic }}
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="form-group full-width">
              <label>Tên nhãn hiển thị trên bản đồ <span class="required">*</span></label>
              <input 
                type="text" 
                v-model="form.name" 
                required 
                placeholder="Ví dụ: Khu dân cư xã Song Khê, Nút giao QL37..." 
                :class="{ 'input-error': form.errors.name }"
              />
              <small v-if="form.errors.name" class="error-text">{{ form.errors.name }}</small>
            </div>

            <!-- CHỌN MÀU NỀN VÀ MÀU CHỮ -->
            <div class="form-row">
              <div class="form-group half">
                <label>Màu nền nhãn (Background)</label>
                <div class="color-picker-box">
                  <input type="color" v-model="form.bg_color" />
                  <input type="text" v-model="form.bg_color" placeholder="#1e293b" />
                </div>
              </div>

              <div class="form-group half">
                <label>Màu chữ (Text Color)</label>
                <div class="color-picker-box">
                  <input type="color" v-model="form.text_color" />
                  <input type="text" v-model="form.text_color" placeholder="#ffffff" />
                </div>
              </div>
            </div>

            <!-- KHUNG XEM TRƯỚC NHÃN BONG BÓNG CÓ MŨI NHỌN TRỰC TIẾP -->
            <div class="form-group full-width preview-container">
              <label>Xem trước hiển thị trên ảnh vệ tinh:</label>
              <div class="satellite-preview-box">
                <div 
                  class="preview-badge live" 
                  :style="{ backgroundColor: form.bg_color, color: form.text_color }"
                >
                  <span>{{ form.icon || '📍' }} {{ form.name || 'Tên nhãn mẫu' }}</span>
                  <div 
                    class="preview-pin" 
                    :style="{ borderTopColor: form.bg_color }"
                  ></div>
                </div>
              </div>
            </div>

            <!-- TỌA ĐỘ VỊ TRÍ POINT -->
            <div class="form-group full-width">
              <label>Tọa độ tâm mốc nhãn (Vĩ độ, Kinh độ WGS84) <span class="required">*</span></label>
              <div class="coord-input-wrapper">
                <input 
                  type="text" 
                  v-model="coordsInput" 
                  placeholder="Ví dụ: 21.254200, 106.176500" 
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
                Mũi nhọn của nhãn sẽ cắm đúng vào tọa độ này trên bản đồ khi zoom nhỏ ra xa.
              </small>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Trạng thái</label>
                <select v-model="form.is_active">
                  <option :value="true">🟢 Hiển thị trên bản đồ</option>
                  <option :value="false">🔴 Tạm ẩn</option>
                </select>
              </div>

              <div class="form-group half">
                <label>Mô tả vị trí</label>
                <input 
                  type="text" 
                  v-model="form.description" 
                  placeholder="Ví dụ: Cách ranh giới phía Bắc 300m" 
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
                {{ isEditMode ? 'Lưu Thay Đổi' : 'Tạo Mới Vùng Lân Cận' }}
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
  name: 'SurroundingLocationsIndex',
  components: { AdminLayout },
  props: {
    locations: { type: Array, default: () => [] },
    zones: { type: Array, default: () => [] }
  },
  data() {
    return {
      showModal: false,
      isEditMode: false,
      selectedLocationId: null,
      searchQuery: '',
      zoneFilter: '',
      categoryFilter: '',
      coordsInput: '',
      quickIcons: ['🏘️', '🛣️', '🚚', '🏭', '🌊', '🏪', '✈️'],
      form: this.$inertia.form({
        zone_id: '',
        code: '',
        name: '',
        category: 'residential',
        bg_color: '#047857',
        text_color: '#ffffff',
        icon: '🏘️',
        description: '',
        is_active: true,
        lat: '',
        lng: ''
      })
    };
  },
  computed: {
    activeCount() {
      return this.locations.filter(l => l.is_active).length;
    },

    filteredLocations() {
      return this.locations.filter(l => {
        const q = this.searchQuery.toLowerCase().trim();
        const matchCode = l.code && l.code.toLowerCase().includes(q);
        const matchName = l.name && l.name.toLowerCase().includes(q);
        const matchDesc = l.description && l.description.toLowerCase().includes(q);

        const matchesQuery = !q || matchCode || matchName || matchDesc;
        const matchesZone = !this.zoneFilter || Number(l.zone_id) === Number(this.zoneFilter);
        const matchesCategory = !this.categoryFilter || l.category === this.categoryFilter;

        return matchesQuery && matchesZone && matchesCategory;
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
        () => alert("Không thể lấy tọa độ vị trí hiện tại."),
        { enableHighAccuracy: true }
      );
    },

    openCreateModal() {
      this.isEditMode = false;
      this.selectedLocationId = null;
      this.form.reset();
      this.form.clearErrors();
      this.coordsInput = '';
      this.form.category = 'residential';
      this.form.bg_color = '#047857';
      this.form.text_color = '#ffffff';
      this.form.icon = '🏘️';
      this.form.is_active = true;
      this.showModal = true;
    },

    openEditModal(loc) {
      this.isEditMode = true;
      this.selectedLocationId = loc.id;
      this.form.clearErrors();
      this.form.zone_id = loc.zone_id || '';
      this.form.code = loc.code || '';
      this.form.name = loc.name || '';
      this.form.category = loc.category || 'residential';
      this.form.bg_color = loc.bg_color || '#1e293b';
      this.form.text_color = loc.text_color || '#ffffff';
      this.form.icon = loc.icon || '📍';
      this.form.description = loc.description || '';
      this.form.is_active = Boolean(loc.is_active);

      if (loc.lat && loc.lng) {
        this.coordsInput = `${loc.lat}, ${loc.lng}`;
        this.form.lat = loc.lat;
        this.form.lng = loc.lng;
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

    saveLocation() {
      this.parseCoordinates();

      if (this.isEditMode) {
        this.form.put(`/admin/surrounding-locations/${this.selectedLocationId}`, {
          onSuccess: () => this.closeModal()
        });
      } else {
        this.form.post('/admin/surrounding-locations', {
          onSuccess: () => this.closeModal()
        });
      }
    },

    deleteLocation(loc) {
      if (confirm(`Bạn có chắc chắn muốn XÓA mốc nhãn [${loc.code} - ${loc.name}] không?`)) {
        this.$inertia.delete(`/admin/surrounding-locations/${loc.id}`);
      }
    },

    formatCategory(cat) {
      switch (cat) {
        case 'residential': return '🏘️ Dân cư / Đô thị';
        case 'transport': return '🛣️ Giao thông đối ngoại';
        case 'logistics': return '🚚 Cụm Logistics / Kho bãi';
        case 'ecological': return '🌊 Sinh thái / Mặt nước';
        case 'industrial': return '🏭 Công nghiệp lân cận';
        default: return '📍 Mốc phụ cận';
      }
    }
  }
};
</script>

<style scoped>
.surroundings-page { display: flex; flex-direction: column; gap: 20px; }
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
.stat-item.highlight { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }

.table-card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.table-responsive { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
.data-table th { background: #f8fafc; padding: 12px; color: #475569; font-weight: 700; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
.data-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

.zone-badge { background: #f1f5f9; color: #0f172a; border: 1px solid #e2e8f0; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 6px; }
.loc-code { color: #2563eb; font-size: 13px; }
.loc-title-cell { display: flex; flex-direction: column; gap: 2px; }
.category-text { color: #64748b; font-size: 11px; }
.desc-text { color: #475569; font-size: 12px; max-width: 200px; display: inline-block; white-space: normal; }

/* 🌟 PHẦN XEM TRƯỚC NHÃN BONG BÓNG CÓ MŨI NHỌN 🌟 */
.table-preview-wrapper { display: flex; justify-content: flex-start; padding: 6px 0; }
.preview-badge {
  position: relative;
  display: inline-flex;
  align-items: center;
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
  box-shadow: 0 3px 10px rgba(0,0,0,0.25);
  border: 1.5px solid rgba(255, 255, 255, 0.9);
  white-space: nowrap;
}
.preview-pin {
  position: absolute;
  bottom: -7px;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 0;
  border-left: 6px solid transparent;
  border-right: 6px solid transparent;
  border-top: 7px solid #1e293b; /* Màu đồng bộ với bg_color */
}

.status-badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
.status-badge.active { background: #ecfdf5; color: #059669; }
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
.form-group input, .form-group select { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 100%; box-sizing: border-box; font-family: inherit; }

.icon-input-row { display: flex; gap: 8px; align-items: center; }
.icon-input { width: 60px !important; text-align: center; font-size: 16px; }
.quick-icons { display: flex; gap: 4px; }
.quick-icons button { border: 1px solid #cbd5e1; background: #f8fafc; border-radius: 6px; padding: 4px 6px; cursor: pointer; font-size: 14px; }
.quick-icons button:hover { background: #e2e8f0; }

.color-picker-box { display: flex; gap: 8px; align-items: center; }
.color-picker-box input[type="color"] { width: 44px; height: 38px; padding: 2px; border-radius: 6px; border: 1px solid #cbd5e1; cursor: pointer; }

/* VÙNG XEM TRƯỚC VỆ TINH TRONG FORM */
.satellite-preview-box {
  background: radial-gradient(circle, #334155 0%, #0f172a 100%);
  padding: 30px;
  border-radius: 8px;
  display: flex;
  justify-content: center;
  align-items: center;
  border: 1px solid #475569;
}
.preview-badge.live {
  font-size: 13px;
  padding: 7px 16px;
}

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