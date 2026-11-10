<template>
  <AdminLayout>
    <div class="company-lands-page">
      <!-- 1. TIÊU ĐỀ TRANG & NÚT THÊM MỚI -->
      <div class="page-header">
        <div>
          <h2>📜 QUẢN LÝ ĐẤT DOANH NGHIỆP (BÌA ĐỎ & THỜI HẠN THUÊ)</h2>
          <p>Quản lý ranh giới đất thuê thứ cấp, số chứng nhận QSDĐ, thời hạn hợp đồng và hiện trạng sử dụng</p>
        </div>
        <button class="btn-primary" @click="openCreateModal">
          ➕ Thêm Mảnh Đất Doanh Nghiệp
        </button>
      </div>

      <!-- 2. THANH BỘ LỌC & THỐNG KÊ NHANH -->
      <div class="filter-card">
        <div class="filter-row">
          <!-- Ô tìm kiếm từ khóa -->
          <div class="search-box">
            <span class="search-icon">🔍</span>
            <input 
              type="text" 
              v-model="searchQuery" 
              @keyup.enter="applyFilters"
              placeholder="Tìm theo Mã đất, Số bìa đỏ, Hợp đồng, Tên DN..." 
            />
          </div>

          <!-- Lọc theo Khu Công Nghiệp -->
          <div class="select-box" v-if="zones && zones.length">
            <select v-model="selectedZoneId" @change="applyFilters">
              <option value="">🏢 Tất cả KCN</option>
              <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.name }}</option>
            </select>
          </div>

          <!-- Lọc theo Hiện trạng sử dụng -->
          <div class="select-box">
            <select v-model="selectedStatus" @change="applyFilters">
              <option value="">⚡ Tất cả hiện trạng</option>
              <option value="active">🟢 Đang hoạt động / Sản xuất</option>
              <option value="under_construction">🟡 Đang xây dựng xưởng</option>
              <option value="vacant">⚪ Đất trống / Chưa xây dựng</option>
              <option value="disputed">🔴 Tranh chấp / Tạm dừng</option>
            </select>
          </div>

          <button class="btn-filter" @click="applyFilters">Lọc dữ liệu</button>
          <button class="btn-reset" @click="resetFilters" v-if="searchQuery || selectedZoneId || selectedStatus">✕ Đặt lại</button>

          <!-- Thống kê nhanh -->
          <div class="stats-mini">
            <span class="stat-item">
              📦 Tổng số mảnh: <b>{{ companyLands.total || companyLands.data?.length || 0 }}</b>
            </span>
          </div>
        </div>
      </div>

      <!-- 3. BẢNG DANH SÁCH THỬA ĐẤT DOANH NGHIỆP -->
      <div class="table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>STT</th>
                <th>Mã Thửa Đất</th>
                <th>Doanh Nghiệp Thuê</th>
                <th>Lô Quy Hoạch</th>
                <th>Pháp Lý Bìa Đỏ / HĐ</th>
                <th>Diện Tích (m²)</th>
                <th>Thời Hạn Thuê</th>
                <th>Hiện Trạng</th>
                <th>Tọa Độ GIS</th>
                <th class="text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(land, index) in landList" :key="land.id">
                <td>{{ getRowIndex(index) }}</td>
                <td><b class="land-code">📜 {{ land.land_code }}</b></td>
                
                <!-- Tên doanh nghiệp + Mã màu -->
                <td>
                  <div class="company-info-cell">
                    <span 
                      class="company-color-bar" 
                      :style="{ backgroundColor: land.company?.color || '#0284c7' }"
                    ></span>
                    <div>
                      <div class="company-name">{{ land.company?.name || 'Chưa gắn công ty' }}</div>
                      <small class="company-tax">MST: {{ land.company?.tax_code || '---' }}</small>
                    </div>
                  </div>
                </td>

                <!-- Lô quy hoạch gốc -->
                <td>
                  <span v-if="land.plot" class="badge-plot">
                    🗺️ {{ land.plot.code }}
                  </span>
                  <span v-else class="text-muted">—</span>
                </td>

                <!-- Số bìa đỏ & Số hợp đồng -->
                <td>
                  <div class="legal-cell">
                    <div v-if="land.certificate_number"><b>Sổ:</b> {{ land.certificate_number }}</div>
                    <div v-if="land.contract_number" class="text-sub"><b>HĐ:</b> {{ land.contract_number }}</div>
                    <div v-if="!land.certificate_number && !land.contract_number" class="text-muted">Đang hoàn thiện hồ sơ</div>
                  </div>
                </td>

                <!-- Diện tích -->
                <td>
                  <div class="area-cell">
                    <div class="area-main">{{ formatNumber(land.leased_area) }} m²</div>
                    <small v-if="land.calculated_area" class="area-gis" title="Diện tích đo đạc từ GIS">
                      GIS: {{ formatNumber(land.calculated_area) }} m²
                    </small>
                  </div>
                </td>

                <!-- Thời hạn thuê & Cảnh báo -->
                <td>
                  <div class="lease-cell">
                    <div>{{ formatDate(land.lease_start_date) }} ➔ {{ formatDate(land.lease_end_date) }}</div>
                    <span v-if="getLeaseAlert(land.lease_end_date)" class="badge-alert" :class="getLeaseAlert(land.lease_end_date).type">
                      {{ getLeaseAlert(land.lease_end_date).text }}
                    </span>
                  </div>
                </td>

                <!-- Trạng thái -->
                <td>
                  <span class="badge-status" :class="land.status">
                    {{ formatStatus(land.status) }}
                  </span>
                </td>

                <!-- Trạng thái GIS -->
                <td>
                  <span v-if="land.geojson || land.geom" class="badge-gis">📍 Polygon GIS</span>
                  <span v-else class="text-muted">Chưa có</span>
                </td>

                <!-- Nút thao tác -->
                <td class="text-center actions-cell">
                  <button class="btn-icon btn-edit" @click="openEditModal(land)" title="Sửa thông tin">✏️</button>
                  <button class="btn-icon btn-delete" @click="deleteLand(land)" title="Xóa thửa đất">🗑️</button>
                </td>
              </tr>
              <tr v-if="!landList.length">
                <td colspan="10" class="text-center text-muted py-4">
                  Không tìm thấy thửa đất doanh nghiệp nào phù hợp.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- PHÂN TRANG INERTIA -->
        <div class="pagination-container" v-if="companyLands.links && companyLands.links.length > 3">
          <button 
            v-for="(link, i) in companyLands.links" 
            :key="i"
            class="page-link-btn"
            :class="{ active: link.active, disabled: !link.url }"
            :disabled="!link.url"
            @click="goToPage(link.url)"
            v-html="link.label"
          ></button>
        </div>
      </div>

      <!-- 4. MODAL THÊM / CẬP NHẬT THỬA ĐẤT DOANH NGHIỆP -->
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ isEditMode ? '✏️ Cập nhật Đất Doanh Nghiệp' : '➕ Thêm Mới Đất Doanh Nghiệp' }}</h3>
            <button class="close-btn" @click="closeModal">✕</button>
          </div>

          <form @submit.prevent="saveLand" class="modal-form">
            <!-- HÀNG 1: MÃ THỬA ĐẤT & CÔNG TY THUÊ -->
            <div class="form-row">
              <div class="form-group half">
                <label>Mã Thửa Đất DN <span class="required">*</span></label>
                <input 
                  type="text" 
                  v-model="form.land_code" 
                  required 
                  placeholder="VD: DN-LO01-A"
                  :class="{ 'input-error': form.errors.land_code }"
                />
                <small v-if="form.errors.land_code" class="error-text">{{ form.errors.land_code }}</small>
              </div>

              <div class="form-group half">
                <label>Doanh nghiệp đứng tên thuê <span class="required">*</span></label>
                <select v-model="form.company_id" required :class="{ 'input-error': form.errors.company_id }">
                  <option value="" disabled>-- Chọn công ty --</option>
                  <option v-for="c in companies" :key="c.id" :value="c.id">
                    🏢 {{ c.name }} (MST: {{ c.tax_code || '---' }})
                  </option>
                </select>
                <small v-if="form.errors.company_id" class="error-text">{{ form.errors.company_id }}</small>
              </div>
            </div>

            <!-- HÀNG 2: THUỘC LÔ QUY HOẠCH GỐC & KHU CÔNG NGHIỆP -->
            <div class="form-row">
              <div class="form-group half">
                <label>Thuộc Lô đất quy hoạch gốc</label>
                <select v-model="form.plot_id">
                  <option value="">-- Để trống nếu thuê gộp nhiều lô --</option>
                  <option v-for="p in plots" :key="p.id" :value="p.id">
                    🗺️ Lô {{ p.code }} ({{ formatNumber(p.total_area) }} m²)
                  </option>
                </select>
              </div>

              <div class="form-group half" v-if="zones && zones.length">
                <label>Khu công nghiệp</label>
                <select v-model="form.zone_id">
                  <option value="">-- Chọn KCN --</option>
                  <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.name }}</option>
                </select>
              </div>
            </div>

            <!-- HÀNG 3: PHÁP LÝ BÌA ĐỎ (SỐ GCN & NGÀY CẤP) -->
            <div class="form-row">
              <div class="form-group half">
                <label>Số GCN QSDĐ (Số sổ bìa đỏ)</label>
                <input 
                  type="text" 
                  v-model="form.certificate_number" 
                  placeholder="VD: CN 123456" 
                />
              </div>

              <div class="form-group half">
                <label>Số vào sổ cấp GCN</label>
                <input 
                  type="text" 
                  v-model="form.cadastral_book_number" 
                  placeholder="VD: CS 00987" 
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Ngày cấp Giấy chứng nhận</label>
                <input type="date" v-model="form.issue_date" />
              </div>
              <div class="form-group half">
                <label>Cơ quan cấp</label>
                <input type="text" v-model="form.issuing_authority" placeholder="UBND tỉnh Bắc Giang" />
              </div>
            </div>

            <!-- HÀNG 4: HỢP ĐỒNG & THỜI HẠN THUÊ -->
            <div class="form-row">
              <div class="form-group quarter">
                <label>Số Hợp đồng thuê</label>
                <input type="text" v-model="form.contract_number" placeholder="VD: 05/2026/HĐTĐ" />
              </div>

              <div class="form-group quarter">
                <label>Ngày ký HĐ</label>
                <input type="date" v-model="form.contract_date" />
              </div>

              <div class="form-group quarter">
                <label>Ngày bắt đầu thuê</label>
                <input type="date" v-model="form.lease_start_date" />
              </div>

              <div class="form-group quarter">
                <label>Ngày hết hạn thuê</label>
                <input type="date" v-model="form.lease_end_date" />
              </div>
            </div>

            <!-- HÀNG 5: DIỆN TÍCH, HÌNH THỨC TRẢ & HIỆN TRẠNG -->
            <div class="form-row">
              <div class="form-group half">
                <label>Diện tích trên sổ / HĐ (m²)</label>
                <input 
                  type="number" 
                  step="0.01" 
                  v-model.number="form.leased_area" 
                  placeholder="Để trống sẽ tự tính theo tọa độ GIS" 
                  :class="{ 'input-error': form.errors.leased_area }"
                />
                <small v-if="form.errors.leased_area" class="error-text">{{ form.errors.leased_area }}</small>
              </div>

              <div class="form-group quarter">
                <label>Hình thức trả tiền <span class="required">*</span></label>
                <select v-model="form.payment_type" required>
                  <option value="one_time">Trả 1 lần (Một lần)</option>
                  <option value="annual">Trả tiền hàng năm</option>
                </select>
              </div>

              <div class="form-group quarter">
                <label>Hiện trạng sử dụng <span class="required">*</span></label>
                <select v-model="form.status" required>
                  <option value="active">🟢 Đang sản xuất</option>
                  <option value="under_construction">🟡 Đang xây dựng</option>
                  <option value="vacant">⚪ Đất trống</option>
                  <option value="disputed">🔴 Tranh chấp</option>
                </select>
              </div>
            </div>

            <!-- HÀNG 6: MỤC ĐÍCH & MẬT ĐỘ XÂY DỰNG -->
            <div class="form-row">
              <div class="form-group half">
                <label>Mục đích sử dụng đất</label>
                <input type="text" v-model="form.land_purpose" placeholder="VD: Nhà máy sản xuất linh kiện bán dẫn" />
              </div>

              <div class="form-group half">
                <label>Mật độ xây dựng tối đa (%)</label>
                <input type="number" step="0.1" v-model.number="form.building_density_limit" placeholder="VD: 70" />
              </div>
            </div>

            <!-- TỌA ĐỘ RANH GIỚI POLYGON GEOJSON -->
            <div class="form-group full-width">
              <label>Tọa độ ranh giới thửa đất doanh nghiệp (Các đỉnh đa giác)</label>
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
                ⚠️ Đa giác cần tối thiểu 3 góc/đỉnh phân cách bằng dấu chấm phẩy (;)
              </small>
              <small v-else class="help-text">
                Nhập danh sách tọa độ: <code>Vĩ độ, Kinh độ; Vĩ độ, Kinh độ...</code> (Hệ thống tự động đảo chuẩn Leaflet/GeoJSON và tự tính diện tích nếu ô diện tích để trống).
              </small>
            </div>

            <!-- GHI CHÚ -->
            <div class="form-group full-width">
              <label>Ghi chú / Thông tin bổ sung</label>
              <textarea v-model="form.description" rows="2" placeholder="Ghi chú hồ sơ mốc giới, biên bản bàn giao..."></textarea>
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
  name: 'CompanyLandsIndex',
  components: { AdminLayout },
  props: {
    companyLands: { type: Object, default: () => ({ data: [] }) },
    companies: { type: Array, default: () => [] },
    plots: { type: Array, default: () => [] },
    zones: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) }
  },
  data() {
    return {
      showModal: false,
      isEditMode: false,
      selectedLandId: null,
      searchQuery: this.filters.search || '',
      selectedZoneId: this.filters.zone_id || '',
      selectedStatus: this.filters.status || '',
      coordinates_input: '',
      form: this.$inertia.form({
        land_code: '',
        company_id: '',
        plot_id: '',
        zone_id: '',
        certificate_number: '',
        cadastral_book_number: '',
        issue_date: '',
        issuing_authority: 'UBND tỉnh Bắc Giang',
        contract_number: '',
        contract_date: '',
        lease_start_date: '',
        lease_end_date: '',
        payment_type: 'one_time',
        leased_area: '',
        land_purpose: '',
        building_density_limit: '',
        status: 'active',
        is_handed_over: true,
        description: '',
        geojson: ''
      })
    };
  },
  computed: {
    landList() {
      return this.companyLands.data || this.companyLands || [];
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
      return Boolean(
        this.form.land_code && 
        this.form.land_code.trim() !== '' && 
        this.form.company_id && 
        this.isCoordinatesValid
      );
    }
  },
  methods: {
    // 🔍 BỘ LỌC VÀ TÌM KIẾM
    applyFilters() {
      this.$inertia.get('/admin/company-lands', {
        search: this.searchQuery,
        zone_id: this.selectedZoneId,
        status: this.selectedStatus
      }, {
        preserveState: true,
        replace: true
      });
    },

    resetFilters() {
      this.searchQuery = '';
      this.selectedZoneId = '';
      this.selectedStatus = '';
      this.applyFilters();
    },

    goToPage(url) {
      if (url) {
        this.$inertia.visit(url, { preserveState: true });
      }
    },

    getRowIndex(index) {
      const from = this.companyLands.from || 1;
      return from + index;
    },

    // 🌐 XỬ LÝ CHUỖI TỌA ĐỘ VÀ ĐÓNG GÓI GEOJSON POLYGON
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
          // Tự nhận diện Vĩ độ (< Kinh độ ở VN) để đưa về đúng thứ tự GeoJSON [Lng, Lat]
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

        this.form.geojson = JSON.stringify({
          type: 'Polygon',
          coordinates: [coords]
        });

        // Tự tính diện tích gần đúng nếu ô leased_area đang rỗng
        if (!this.form.leased_area || Number(this.form.leased_area) === 0) {
          this.calculateApproxArea(coords);
        }
      } else {
        this.form.geojson = '';
      }
    },

    calculateApproxArea(coords) {
      if (!coords || coords.length < 4) return;
      const R = 6378137;
      let area = 0;
      for (let i = 0; i < coords.length - 1; i++) {
        const p1 = coords[i];
        const p2 = coords[i + 1];
        const x1 = (p1[0] * Math.PI) / 180;
        const y1 = (p1[1] * Math.PI) / 180;
        const x2 = (p2[0] * Math.PI) / 180;
        const y2 = (p2[1] * Math.PI) / 180;
        area += (x2 - x1) * (2 + Math.sin(y1) + Math.sin(y2));
      }
      area = Math.abs((area * R * R) / 2.0);
      if (area > 0) {
        this.form.leased_area = Math.round(area * 10) / 10;
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

        let points = [...ring];
        if (points.length > 1) {
          const first = points[0];
          const last = points[points.length - 1];
          if (first[0] === last[0] && first[1] === last[1]) {
            points.pop();
          }
        }
        return points.map(pt => `${pt[1]}, ${pt[0]}`).join('; ');
      }
      return '';
    },

    // 📋 MODAL ACTIONS
    openCreateModal() {
      this.isEditMode = false;
      this.selectedLandId = null;
      this.form.reset();
      this.form.clearErrors();
      this.form.issuing_authority = 'UBND tỉnh Bắc Giang';
      this.form.payment_type = 'one_time';
      this.form.status = 'active';
      this.form.is_handed_over = true;
      this.coordinates_input = '';
      this.showModal = true;
    },

    openEditModal(land) {
      this.isEditMode = true;
      this.selectedLandId = land.id;
      this.form.clearErrors();

      this.form.land_code = land.land_code || '';
      this.form.company_id = land.company_id || '';
      this.form.plot_id = land.plot_id || '';
      this.form.zone_id = land.zone_id || '';
      this.form.certificate_number = land.certificate_number || '';
      this.form.cadastral_book_number = land.cadastral_book_number || '';
      this.form.issue_date = land.issue_date ? land.issue_date.substring(0, 10) : '';
      this.form.issuing_authority = land.issuing_authority || 'UBND tỉnh Bắc Giang';
      this.form.contract_number = land.contract_number || '';
      this.form.contract_date = land.contract_date ? land.contract_date.substring(0, 10) : '';
      this.form.lease_start_date = land.lease_start_date ? land.lease_start_date.substring(0, 10) : '';
      this.form.lease_end_date = land.lease_end_date ? land.lease_end_date.substring(0, 10) : '';
      this.form.payment_type = land.payment_type || 'one_time';
      this.form.leased_area = land.leased_area || '';
      this.form.land_purpose = land.land_purpose || '';
      this.form.building_density_limit = land.building_density_limit || '';
      this.form.status = land.status || 'active';
      this.form.is_handed_over = Boolean(land.is_handed_over);
      this.form.description = land.description || '';

      const rawGeom = land.geojson || land.geom;
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

    saveLand() {
      this.onCoordinatesChange();

      if (this.isEditMode) {
        this.form.put(`/admin/company-lands/${this.selectedLandId}`, {
          onSuccess: () => this.closeModal()
        });
      } else {
        this.form.post('/admin/company-lands', {
          onSuccess: () => this.closeModal()
        });
      }
    },

    deleteLand(land) {
      if (confirm(`Anh có chắc chắn muốn XÓA Thửa đất [${land.land_code}] của công ty [${land.company?.name || ''}] không?`)) {
        this.$inertia.delete(`/admin/company-lands/${land.id}`);
      }
    },

    // 🕒 ĐỊNH DẠNG & CẢNH BÁO
    formatNumber(val) {
      if (!val) return '0';
      return new Intl.NumberFormat('vi-VN').format(val);
    },

    formatDate(dateStr) {
      if (!dateStr) return '---';
      const d = new Date(dateStr);
      return d.toLocaleDateString('vi-VN');
    },

    formatStatus(status) {
      switch (status) {
        case 'active': return '🟢 Đang sản xuất';
        case 'under_construction': return '🟡 Đang xây dựng';
        case 'vacant': return '⚪ Đất trống';
        case 'disputed': return '🔴 Tranh chấp';
        default: return status || '---';
      }
    },

    getLeaseAlert(endDateStr) {
      if (!endDateStr) return null;
      const end = new Date(endDateStr);
      const now = new Date();
      const diffMonths = (end.getFullYear() - now.getFullYear()) * 12 + (end.getMonth() - now.getMonth());

      if (diffMonths < 0) {
        return { type: 'alert-expired', text: '⚠️ Đã hết hạn' };
      }
      if (diffMonths <= 6) {
        return { type: 'alert-danger', text: '⏱️ Hết hạn < 6 tháng' };
      }
      if (diffMonths <= 12) {
        return { type: 'alert-warning', text: '⚡ Hết hạn < 1 năm' };
      }
      return null;
    }
  }
};
</script>

<style scoped>
.company-lands-page { display: flex; flex-direction: column; gap: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
.page-header h2 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; }
.page-header p { margin: 4px 0 0 0; font-size: 12px; color: #64748b; }

.btn-primary { background: #2563eb; color: #fff; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }
.btn-primary:hover { background: #1d4ed8; }
.btn-secondary { background: #e2e8f0; color: #334155; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }

/* BỘ LỌC */
.filter-card { background: #fff; border-radius: 12px; padding: 14px 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.filter-row { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
.search-box { display: flex; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; flex: 1; min-width: 260px; }
.search-icon { margin-right: 8px; font-size: 14px; }
.search-box input { border: none; background: transparent; outline: none; width: 100%; font-size: 13px; color: #0f172a; }
.select-box select { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; background: #f8fafc; font-size: 13px; outline: none; }
.btn-filter { background: #0f172a; color: #fff; border: none; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-reset { background: transparent; border: 1px dashed #94a3b8; color: #64748b; padding: 7px 12px; border-radius: 8px; font-size: 12px; cursor: pointer; }

.stats-mini { margin-left: auto; font-size: 13px; color: #475569; }
.stat-item { background: #f1f5f9; padding: 6px 12px; border-radius: 6px; }

/* BẢNG DỮ LIỆU */
.table-card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.table-responsive { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
.data-table th { background: #f8fafc; padding: 12px 10px; color: #475569; font-weight: 700; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
.data-table td { padding: 12px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }

.land-code { color: #0f172a; font-family: monospace; font-size: 13px; }
.company-info-cell { display: flex; align-items: center; gap: 8px; min-width: 180px; }
.company-color-bar { width: 4px; height: 32px; border-radius: 2px; flex-shrink: 0; }
.company-name { font-weight: 700; color: #0f172a; line-height: 1.3; }
.company-tax { color: #64748b; font-size: 11px; }

.badge-plot { background: #fef3c7; color: #b45309; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }
.legal-cell { font-size: 12px; line-height: 1.4; min-width: 130px; }
.text-sub { color: #64748b; font-size: 11px; }
.area-cell { min-width: 110px; }
.area-main { font-weight: 700; color: #0f172a; }
.area-gis { color: #059669; font-size: 11px; display: block; }

.lease-cell { font-size: 12px; min-width: 160px; }
.badge-alert { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; margin-top: 3px; }
.alert-warning { background: #fef3c7; color: #d97706; }
.alert-danger { background: #fee2e2; color: #dc2626; }
.alert-expired { background: #450a0a; color: #fecaca; }

.badge-status { padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.badge-status.active { background: #ecfdf5; color: #047857; }
.badge-status.under_construction { background: #fefce8; color: #ca8a04; }
.badge-status.vacant { background: #f1f5f9; color: #475569; }
.badge-status.disputed { background: #fef2f2; color: #dc2626; }

.badge-gis { background: #ecfdf5; color: #047857; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
.text-muted { color: #94a3b8; font-size: 12px; }

.actions-cell { display: flex; justify-content: center; gap: 6px; }
.btn-icon { border: none; background: #f1f5f9; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.btn-edit:hover { background: #dbeafe; }
.btn-delete:hover { background: #fee2e2; }

/* PHÂN TRANG */
.pagination-container { display: flex; justify-content: flex-end; gap: 4px; margin-top: 16px; }
.page-link-btn { padding: 6px 12px; border: 1px solid #cbd5e1; background: #fff; border-radius: 6px; cursor: pointer; font-size: 12px; }
.page-link-btn.active { background: #2563eb; color: #fff; border-color: #2563eb; font-weight: bold; }
.page-link-btn.disabled { opacity: 0.5; cursor: not-allowed; }

/* PREFIX WRAPPER POLYGON */
.geojson-input-wrapper { display: flex; align-items: flex-start; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; font-family: monospace; font-size: 13px; }
.prefix-tag, .suffix-tag { background: #e2e8f0; color: #475569; padding: 8px 10px; font-weight: 600; user-select: none; white-space: nowrap; }
.geojson-input-wrapper textarea { border: none !important; box-shadow: none !important; padding: 8px 10px; flex: 1; outline: none; background: transparent; font-family: monospace; font-weight: 600; color: #0f172a; resize: vertical; }

/* MODAL */
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 3000; display: flex; align-items: center; justify-content: center; padding: 16px; }
.modal-content { background: #fff; border-radius: 14px; width: 100%; max-width: 760px; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
.modal-header { padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 10; }
.modal-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: #0f172a; }
.close-btn { border: none; background: transparent; font-size: 18px; cursor: pointer; color: #64748b; }
.modal-form { padding: 20px; display: flex; flex-direction: column; gap: 14px; }
.form-row { display: flex; gap: 12px; flex-wrap: wrap; }
.half { flex: 1; min-width: 240px; }
.quarter { flex: 1; min-width: 140px; }
.full-width { width: 100%; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 13px; font-weight: 600; color: #334155; }
.required { color: #dc2626; }
.form-group input, .form-group select, .form-group textarea { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 100%; box-sizing: border-box; font-family: inherit; }
.form-group textarea { resize: vertical; min-height: 60px; line-height: 1.5; }
.input-error { border-color: #dc2626 !important; background-color: #fef2f2 !important; }
.error-text { font-size: 11px; color: #dc2626; margin-top: 2px; display: block; }
.help-text { font-size: 11px; color: #64748b; margin-top: 4px; display: block; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; position: sticky; bottom: 0; background: #fff; padding-top: 10px; }
.btn-primary:disabled, .btn-disabled { background-color: #94a3b8 !important; cursor: not-allowed !important; opacity: 0.7; }
</style>