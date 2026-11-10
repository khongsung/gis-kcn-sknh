<template>
  <AdminLayout>
    <div class="companies-page">
      <!-- 1. HEADER TIÊU ĐỀ & NÚT THÊM -->
      <div class="page-header">
        <div>
          <h2>🏢 QUẢN LÝ DOANH NGHIỆP / CÔNG TY ĐẦU TƯ</h2>
          <p>Hồ sơ pháp lý, Khu công nghiệp trực thuộc, Giấy chứng nhận đầu tư (IRC), thời hạn thuê đất & màu sắc WebGIS</p>
        </div>
        <button class="btn-primary" @click="openCreateModal">
          ➕ Thêm Công Ty Mới
        </button>
      </div>

      <!-- 2. BẢNG THỐNG KÊ NHANH & BỘ LỌC TÌM KIẾM -->
      <div class="filter-card">
        <div class="filter-row">
          <div class="search-box">
            <span class="search-icon">🔍</span>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Tìm theo Tên công ty, MST, Số GCNĐT, SĐT..." 
            />
          </div>

          <!-- Lọc theo Khu Công Nghiệp -->
          <div class="filter-box" v-if="zones.length">
            <label>Khu công nghiệp:</label>
            <select v-model="zoneFilter">
              <option value="">-- Tất cả KCN --</option>
              <option v-for="z in zones" :key="z.id" :value="z.id">
                🏭 {{ z.name }}
              </option>
            </select>
          </div>

          <!-- Lọc Quốc Gia -->
          <div class="filter-box" v-if="availableCountries.length">
            <label>Quốc gia:</label>
            <select v-model="countryFilter">
              <option value="">-- Tất cả quốc gia --</option>
              <option v-for="c in availableCountries" :key="c" :value="c">
                {{ formatCountryFlag(c) }} {{ c }}
              </option>
            </select>
          </div>

          <!-- Lọc Ngành Nghề -->
          <div class="filter-box" v-if="availableIndustries.length">
            <label>Ngành nghề:</label>
            <select v-model="industryFilter">
              <option value="">-- Tất cả ngành nghề --</option>
              <option v-for="ind in availableIndustries" :key="ind" :value="ind">
                {{ ind }}
              </option>
            </select>
          </div>

          <div class="stats-mini">
            <span class="stat-item">
              🏢 Doanh nghiệp: <b>{{ filteredCompanies.length }} / {{ companies.length }}</b>
            </span>
            <span class="stat-item highlight">
              📐 Tổng diện tích: <b>{{ formatNumber(totalLeasedAreaAll) }} m²</b>
            </span>
          </div>
        </div>
      </div>

      <!-- 3. BẢNG DANH SÁCH CÔNG TY -->
      <div class="table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>STT</th>
                <th>Doanh Nghiệp / Quốc Gia</th>
                <th>Khu Công Nghiệp</th>
                <th>Màu Map</th>
                <th>Mã Số Thuế / GCNĐT</th>
                <th>Ngành Nghề Sản Xuất</th>
                <th>Diện Tích Thuê</th>
                <th>Thời Hạn Hoạt Động</th>
                <th>Liên Hệ</th>
                <th class="text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(c, index) in filteredCompanies" :key="c.id">
                <td>{{ index + 1 }}</td>
                <td>
                  <div class="company-name-cell">
                    <span class="color-dot-indicator" :style="{ backgroundColor: c.color || '#2563eb' }"></span>
                    <div>
                      <b class="company-name">{{ c.name }}</b>
                      <div class="country-sub">
                        <span>{{ formatCountryFlag(c.country) }} {{ c.country || 'Việt Nam' }}</span>
                        <span v-if="c.factories_count" class="badge-factory-mini">
                          • {{ c.factories_count }} xưởng
                        </span>
                      </div>
                    </div>
                  </div>
                </td>
                <td>
                  <span v-if="c.zone || getZoneName(c.zone_id)" class="badge-zone">
                    🏭 {{ c.zone ? c.zone.name : getZoneName(c.zone_id) }}
                  </span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td>
                  <span class="color-badge" :style="{ backgroundColor: c.color || '#2563eb' }">
                    {{ c.color || '#2563eb' }}
                  </span>
                </td>
                <td>
                  <div class="legal-id-cell">
                    <div><b>MST:</b> <code class="tax-code">{{ c.tax_code || '—' }}</code></div>
                    <div v-if="c.investment_certificate_number" class="irc-sub">
                      <b>IRC:</b> {{ c.investment_certificate_number }}
                    </div>
                  </div>
                </td>
                <td>
                  <div class="industry-cell">
                    <span v-if="c.main_industry" class="industry-badge main">{{ c.main_industry }}</span>
                    <span v-else-if="c.industry" class="industry-badge">{{ c.industry }}</span>
                    <span v-else class="text-muted">—</span>
                  </div>
                </td>
                <td>
                  <div class="area-cell">
                    <b>{{ formatNumber(c.total_leased_area) }} m²</b>
                  </div>
                </td>
                <td>
                  <div class="lease-date-cell">
                    <div v-if="c.lease_start_date || c.lease_end_date">
                      {{ formatDate(c.lease_start_date) }} ➔ {{ formatDate(c.lease_end_date) }}
                    </div>
                    <span v-else class="text-muted">Chưa cập nhật</span>
                    
                    <span v-if="getLeaseAlert(c.lease_end_date)" class="badge-alert" :class="getLeaseAlert(c.lease_end_date).type">
                      {{ getLeaseAlert(c.lease_end_date).text }}
                    </span>
                  </div>
                </td>
                <td>
                  <div class="contact-info">
                    <span v-if="c.phone" title="Số điện thoại">📞 {{ c.phone }}</span>
                    <span v-if="c.email" title="Email liên hệ">✉️ {{ c.email }}</span>
                    <span v-if="!c.phone && !c.email" class="text-muted">—</span>
                  </div>
                </td>
                <td class="text-center actions-cell">
                  <button class="btn-icon btn-edit" @click="openEditModal(c)" title="Sửa thông tin">✏️</button>
                  <button class="btn-icon btn-delete" @click="deleteCompany(c)" title="Xóa công ty">🗑️</button>
                </td>
              </tr>
              <tr v-if="!filteredCompanies.length">
                <td colspan="10" class="text-center text-muted">
                  {{ searchQuery || industryFilter || countryFilter || zoneFilter ? 'Không tìm thấy doanh nghiệp phù hợp với bộ lọc.' : 'Chưa có dữ liệu công ty.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 4. MODAL THÊM / SỬA CÔNG TY -->
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <div>
              <h3>{{ isEditMode ? '✏️ Cập nhật Hồ Sơ Công Ty' : '➕ Thêm Doanh Nghiệp Mới' }}</h3>
              <p class="modal-subtitle">Quản lý hồ sơ đầu tư, KCN trực thuộc, diện tích thuê, thời hạn hoạt động & màu nhận diện WebGIS</p>
            </div>
            <button class="close-btn" @click="closeModal">✕</button>
          </div>

          <form @submit.prevent="saveCompany" class="modal-form">
            <!-- SECTION 1: NHẬN DIỆN & KHU CÔNG NGHIỆP & QUỐC GIA -->
            <div class="form-section">
              <div class="section-title">📌 THÔNG TIN DOANH NGHIỆP & KHU CÔNG NGHIỆP</div>

              <div class="form-row">
                <div class="form-group half">
                  <label>Tên Doanh Nghiệp / Công ty <span class="required">*</span></label>
                  <input 
                    type="text" 
                    v-model="form.name" 
                    required 
                    placeholder="VD: CÔNG TY TNHH CÔNG NGHỆ CHÍNH XÁC ABC" 
                    :class="{ 'input-error': form.errors.name }"
                  />
                  <small v-if="form.errors.name" class="error-text">{{ form.errors.name }}</small>
                </div>

                <div class="form-group half">
                  <label>Khu Công Nghiệp trực thuộc</label>
                  <select v-model="form.zone_id" :class="{ 'input-error': form.errors.zone_id }">
                    <option value="">-- Chưa chỉ định / Ngoài KCN --</option>
                    <option v-for="z in zones" :key="z.id" :value="z.id">
                      🏭 {{ z.name }}
                    </option>
                  </select>
                  <small v-if="form.errors.zone_id" class="error-text">{{ form.errors.zone_id }}</small>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group third">
                  <label>Quốc gia / Nguồn vốn</label>
                  <select v-model="form.country">
                    <option value="Hàn Quốc">🇰🇷 Hàn Quốc</option>
                    <option value="Trung Quốc">🇨🇳 Trung Quốc</option>
                    <option value="Việt Nam">🇻🇳 Việt Nam</option>
                    <option value="Đài Loan">🇹🇼 Đài Loan</option>
                    <option value="Nhật Bản">🇯🇵 Nhật Bản</option>
                    <option value="Ấn Độ">🇮🇳 Ấn Độ</option>
                    <option value="Khác">🌐 Quốc gia khác</option>
                  </select>
                </div>

                <div class="form-group third">
                  <label>Mã Số Thuế (MST)</label>
                  <input 
                    type="text" 
                    v-model="form.tax_code"  
                    placeholder="VD: 0101234567" 
                    :class="{ 'input-error': form.errors.tax_code }"
                  />
                  <small v-if="form.errors.tax_code" class="error-text">{{ form.errors.tax_code }}</small>
                </div>

                <!-- COLOR PICKER WEBGIS -->
                <div class="form-group third">
                  <label>Màu Hiển Thị Map</label>
                  <div class="color-picker-container">
                    <input type="color" v-model="form.color" class="color-picker-btn" />
                    <input type="text" v-model="form.color" class="color-code-input" placeholder="#2563EB" maxlength="7" />
                    <span class="color-preview-badge" :style="{ backgroundColor: form.color || '#2563eb' }"></span>
                  </div>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group half">
                  <label>Ngành nghề sản xuất chính</label>
                  <input 
                    type="text" 
                    v-model="form.main_industry" 
                    placeholder="VD: Sản xuất vi mạch & linh kiện bán dẫn" 
                  />
                </div>
                <div class="form-group half">
                  <label>Chi tiết ngành nghề / Phân nhóm</label>
                  <input 
                    type="text" 
                    v-model="form.industry" 
                    placeholder="VD: Công nghiệp điện tử, phụ tùng ô tô..." 
                  />
                </div>
              </div>
            </div>

            <!-- SECTION 2: PHÁP LÝ ĐẦU TƯ & DIỆN TÍCH, THỜI HẠN THUÊ -->
            <div class="form-section">
              <div class="section-title">📜 PHÁP LÝ ĐẦU TƯ & THỜI HẠN THUÊ ĐẤT</div>

              <div class="form-row">
                <div class="form-group half">
                  <label>Số GCN Đăng Ký Đầu Tư (IRC)</label>
                  <input 
                    type="text" 
                    v-model="form.investment_certificate_number" 
                    placeholder="VD: 1234567890 (Mã số dự án)" 
                  />
                </div>
                <!-- Ngày cấp GCNĐT -->
                <div class="form-group half">
                  <label>Ngày cấp GCNĐT</label>
                  <input 
                    type="text" 
                    class="datepicker-input" 
                    v-model="form.investment_certificate_date" 
                    placeholder="dd/mm/yyyy" 
                  />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group third">
                  <label>Tổng diện tích thuê (m²)</label>
                  <input 
                    type="number" 
                    step="0.01" 
                    v-model.number="form.total_leased_area" 
                    placeholder="VD: 30000" 
                  />
                </div>
                <!-- Ngày bắt đầu thuê -->
                <div class="form-group third">
                  <label>Ngày bắt đầu thuê</label>
                  <input 
                    type="text" 
                    class="datepicker-input" 
                    v-model="form.lease_start_date" 
                    placeholder="dd/mm/yyyy" 
                  />
                </div>
                <!-- Ngày hết hạn thuê -->
                <div class="form-group third">
                  <label>Ngày hết hạn thuê</label>
                  <input 
                    type="text" 
                    class="datepicker-input" 
                    v-model="form.lease_end_date" 
                    placeholder="dd/mm/yyyy" 
                  />
                </div>
              </div>
            </div>

            <!-- SECTION 3: LIÊN HỆ & GHI CHÚ NỘI BỘ -->
            <div class="form-section">
              <div class="section-title">📞 THÔNG TIN LIÊN HỆ & VẬN HÀNH</div>

              <div class="form-row">
                <div class="form-group half">
                  <label>Số Điện Thoại</label>
                  <input 
                    type="text" 
                    v-model="form.phone" 
                    placeholder="VD: 0204 3829 111" 
                    :class="{ 'input-error': form.errors.phone }"
                  />
                </div>
                <div class="form-group half">
                  <label>Email Đăng Ký</label>
                  <input 
                    type="email" 
                    v-model="form.email" 
                    placeholder="contact@company.com" 
                    :class="{ 'input-error': form.errors.email }"
                  />
                </div>
              </div>

              <div class="form-group">
                <label>Mô Tả / Năng Lực Sản Xuất</label>
                <textarea 
                  v-model="form.description" 
                  rows="2" 
                  placeholder="Giới thiệu quy mô, dây chuyền công nghệ..."
                ></textarea>
              </div>

              <div class="form-group">
                <label>Ghi Chú Vận Hành / Hồ Sơ Môi Trường</label>
                <textarea 
                  v-model="form.notes" 
                  rows="2" 
                  placeholder="Ghi chú cấp phép môi trường PCCC, hiện trạng đấu nối nước thải..."
                ></textarea>
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
                {{ isEditMode ? '💾 Lưu Cập Nhật' : '➕ Tạo Mới Công Ty' }}
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </AdminLayout>
</template>

<script>
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';
import { Vietnamese } from 'flatpickr/dist/l10n/vn.js';
import AdminLayout from '@/Layouts/AdminLayout.vue';

export default {
  name: 'CompaniesIndex',
  components: { AdminLayout },
  props: { 
    companies: { type: Array, default: () => [] },
    zones: { type: Array, default: () => [] }
  },
  data() {
    return {
      showModal: false,
      isEditMode: false,
      selectedId: null,
      searchQuery: '',
      zoneFilter: '',
      industryFilter: '',
      countryFilter: '',
      fpInstances: [],
      form: this.$inertia.form({
        name: '',
        zone_id: '',
        tax_code: '',
        total_leased_area: '',
        investment_certificate_number: '',
        investment_certificate_date: '',
        lease_start_date: '',
        lease_end_date: '',
        industry: '',
        main_industry: '',
        country: 'Hàn Quốc',
        color: '#2563eb',
        phone: '',
        email: '',
        description: '',
        notes: ''
      })
    };
  },
  computed: {
    totalLeasedAreaAll() {
      return this.filteredCompanies.reduce((sum, c) => sum + (parseFloat(c.total_leased_area) || 0), 0);
    },

    availableIndustries() {
      const list = this.companies
        .map(c => c.main_industry || c.industry)
        .filter(ind => ind && ind.trim() !== '');
      return [...new Set(list)];
    },

    availableCountries() {
      const list = this.companies
        .map(c => c.country)
        .filter(ctry => ctry && ctry.trim() !== '');
      return [...new Set(list)];
    },

    filteredCompanies() {
      return this.companies.filter(c => {
        const query = this.searchQuery.toLowerCase().trim();
        const matchesQuery = !query ||
          (c.name && c.name.toLowerCase().includes(query)) ||
          (c.tax_code && c.tax_code.toLowerCase().includes(query)) ||
          (c.investment_certificate_number && c.investment_certificate_number.toLowerCase().includes(query)) ||
          (c.phone && c.phone.toLowerCase().includes(query)) ||
          (c.email && c.email.toLowerCase().includes(query));

        const matchesZone = !this.zoneFilter || String(c.zone_id) === String(this.zoneFilter);

        const matchesIndustry = !this.industryFilter || 
          c.main_industry === this.industryFilter || 
          c.industry === this.industryFilter;

        const matchesCountry = !this.countryFilter || c.country === this.countryFilter;

        return matchesQuery && matchesZone && matchesIndustry && matchesCountry;
      });
    },

    isFormValid() {
      return Boolean(this.form.name && this.form.name.trim() !== '');
    }
  },
  beforeUnmount() {
    this.destroyDatePickers();
  },
  methods: {
    destroyDatePickers() {
      if (this.fpInstances && this.fpInstances.length) {
        this.fpInstances.forEach(instance => instance && instance.destroy && instance.destroy());
        this.fpInstances = [];
      }
    },

    initDatePickers() {
      this.destroyDatePickers();

      const config = {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd/m/Y',
        altInputClass: 'flatpickr-custom-input',
        locale: Vietnamese,
        allowInput: true
      };

      const inputs = document.querySelectorAll('.datepicker-input');
      inputs.forEach(el => {
        const instance = flatpickr(el, config);
        this.fpInstances.push(instance);
      });
    },

    getZoneName(zoneId) {
      if (!zoneId) return null;
      const found = this.zones.find(z => String(z.id) === String(zoneId));
      return found ? found.name : null;
    },

    openCreateModal() {
      this.isEditMode = false;
      this.selectedId = null;
      this.form.reset();
      this.form.clearErrors();
      this.form.zone_id = this.zones.length ? this.zones[0].id : '';
      this.form.country = 'Hàn Quốc';
      this.form.color = '#2563eb';
      this.showModal = true;
      this.$nextTick(() => this.initDatePickers());
    },

    openEditModal(c) {
      this.isEditMode = true;
      this.selectedId = c.id;
      this.form.clearErrors();

      this.form.name = c.name || '';
      this.form.zone_id = c.zone_id || '';
      this.form.tax_code = c.tax_code || '';
      this.form.total_leased_area = c.total_leased_area || '';
      this.form.investment_certificate_number = c.investment_certificate_number || '';
      this.form.investment_certificate_date = c.investment_certificate_date ? c.investment_certificate_date.substring(0, 10) : '';
      this.form.lease_start_date = c.lease_start_date ? c.lease_start_date.substring(0, 10) : '';
      this.form.lease_end_date = c.lease_end_date ? c.lease_end_date.substring(0, 10) : '';
      this.form.industry = c.industry || '';
      this.form.main_industry = c.main_industry || '';
      this.form.country = c.country || 'Việt Nam';
      this.form.color = c.color || '#2563eb';
      this.form.phone = c.phone || '';
      this.form.email = c.email || '';
      this.form.description = c.description || '';
      this.form.notes = c.notes || '';

      this.showModal = true;
      this.$nextTick(() => this.initDatePickers());
    },

    closeModal() {
      this.destroyDatePickers();
      this.showModal = false;
      this.form.reset();
      this.form.clearErrors();
    },

    saveCompany() {
      if (this.isEditMode) {
        this.form.put(`/admin/companies/${this.selectedId}`, {
          onSuccess: () => this.closeModal()
        });
      } else {
        this.form.post('/admin/companies', {
          onSuccess: () => this.closeModal()
        });
      }
    },

    deleteCompany(c) {
      if (confirm(`Anh có chắc chắn muốn XÓA công ty [${c.name}] không?`)) {
        this.$inertia.delete(`/admin/companies/${c.id}`);
      }
    },

    formatNumber(num) {
      if (!num) return '0';
      return new Intl.NumberFormat('vi-VN').format(num);
    },

    formatDate(dateStr) {
      if (!dateStr) return '—';
      const d = new Date(dateStr);
      return d.toLocaleDateString('vi-VN');
    },

    formatCountryFlag(country) {
      switch (country) {
        case 'Hàn Quốc': return '🇰🇷';
        case 'Trung Quốc': return '🇨🇳';
        case 'Việt Nam': return '🇻🇳';
        case 'Đài Loan': return '🇹🇼';
        case 'Nhật Bản': return '🇯🇵';
        case 'Ấn Độ': return '🇮🇳';
        default: return '🌐';
      }
    },

    getLeaseAlert(endDateStr) {
      if (!endDateStr) return null;
      const end = new Date(endDateStr);
      const now = new Date();
      const diffMonths = (end.getFullYear() - now.getFullYear()) * 12 + (end.getMonth() - now.getMonth());

      if (diffMonths < 0) {
        return { type: 'alert-expired', text: '⚠️ Hết hạn' };
      }
      if (diffMonths <= 12) {
        return { type: 'alert-danger', text: '⏱️ < 1 năm' };
      }
      return null;
    }
  }
};
</script>

<style scoped>
.companies-page { display: flex; flex-direction: column; gap: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
.page-header h2 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; }
.page-header p { margin: 4px 0 0 0; font-size: 12px; color: #64748b; }

.btn-primary { background: #2563eb; color: #fff; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; transition: all 0.2s; }
.btn-primary:hover { background: #1d4ed8; }
.btn-secondary { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }

/* FILTER & STATS CARD */
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

.company-name-cell { display: flex; align-items: center; gap: 8px; min-width: 200px; }
.color-dot-indicator { width: 10px; height: 10px; border-radius: 50%; display: inline-block; flex-shrink: 0; box-shadow: 0 0 2px rgba(0,0,0,0.3); }
.company-name { color: #0f172a; font-size: 13px; line-height: 1.3; }
.country-sub { font-size: 11px; color: #64748b; margin-top: 2px; }
.badge-factory-mini { color: #166534; font-weight: 600; }

.badge-zone { background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; padding: 2px 8px; border-radius: 5px; font-size: 11px; font-weight: 700; white-space: nowrap; }

.color-badge { color: #ffffff; padding: 2px 7px; border-radius: 4px; font-family: monospace; font-size: 11px; font-weight: bold; text-shadow: 0 1px 2px rgba(0,0,0,0.5); }
.legal-id-cell { font-size: 12px; line-height: 1.4; }
.tax-code { background: #f1f5f9; color: #2563eb; padding: 2px 5px; border-radius: 4px; font-family: monospace; font-weight: bold; font-size: 11px; }
.irc-sub { font-size: 11px; color: #475569; margin-top: 2px; }

.industry-cell { min-width: 140px; }
.industry-badge { background: #f1f5f9; color: #475569; padding: 3px 7px; border-radius: 5px; font-size: 11px; font-weight: 600; display: inline-block; }
.industry-badge.main { background: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; }

.area-cell { font-family: monospace; font-size: 13px; color: #0f172a; white-space: nowrap; }
.lease-date-cell { font-size: 12px; min-width: 170px; }
.badge-alert { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; margin-top: 2px; }
.alert-danger { background: #fee2e2; color: #dc2626; }
.alert-expired { background: #450a0a; color: #fecaca; }

.contact-info { display: flex; flex-direction: column; gap: 2px; font-size: 11px; color: #475569; min-width: 130px; }
.text-muted { color: #94a3b8; font-size: 12px; }

.actions-cell { display: flex; justify-content: center; gap: 6px; }
.btn-icon { border: none; background: #f1f5f9; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.btn-edit:hover { background: #dbeafe; }
.btn-delete:hover { background: #fee2e2; }

/* MODAL STYLES */
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 3000; display: flex; align-items: center; justify-content: center; padding: 16px; }
.modal-content { background: #ffffff; border-radius: 16px; width: 100%; max-width: 720px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); overflow: hidden; animation: modalSlide 0.2s cubic-bezier(0.16, 1, 0.3, 1); }
.modal-header { padding: 18px 24px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: flex-start; }
.modal-header h3 { margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; }
.modal-subtitle { margin: 4px 0 0 0; font-size: 12px; color: #64748b; }
.close-btn { border: none; background: #e2e8f0; width: 28px; height: 28px; border-radius: 50%; font-size: 14px; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.close-btn:hover { background: #cbd5e1; color: #0f172a; }

.modal-form { padding: 22px; display: flex; flex-direction: column; gap: 16px; max-height: 82vh; overflow-y: auto; }
.form-section { display: flex; flex-direction: column; gap: 12px; background: #fafafa; padding: 14px; border-radius: 10px; border: 1px solid #f1f5f9; }
.section-title { font-size: 11px; font-weight: 800; color: #64748b; letter-spacing: 0.5px; margin-bottom: 2px; }

.form-row { display: flex; gap: 12px; flex-wrap: wrap; }
.half { flex: 1; min-width: 240px; }
.third { flex: 1; min-width: 170px; }

.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 12px; font-weight: 700; color: #334155; }
.required { color: #dc2626; }
.form-group input, .form-group select, .form-group textarea { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; width: 100%; box-sizing: border-box; background: #ffffff; transition: border-color 0.2s; font-family: inherit; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #2563eb; outline: none; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }

/* COLOR PICKER */
.color-picker-container { display: flex; align-items: center; gap: 6px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 3px 6px; }
.color-picker-btn { width: 32px; height: 32px; border: none; padding: 0; background: transparent; cursor: pointer; border-radius: 6px; }
.color-code-input { border: none !important; padding: 4px 6px !important; font-family: monospace; font-weight: 700; font-size: 12px !important; text-transform: uppercase; color: #0f172a; flex: 1; width: 60px !important; }
.color-preview-badge { width: 14px; height: 14px; border-radius: 50%; display: inline-block; box-shadow: 0 0 3px rgba(0,0,0,0.3); margin-right: 4px; }

.input-error { border-color: #dc2626 !important; background-color: #fef2f2 !important; }
.error-text { font-size: 11px; color: #dc2626; margin-top: 2px; display: block; }

.modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px; padding-top: 12px; border-top: 1px solid #f1f5f9; }
.btn-primary:disabled, .btn-disabled { background-color: #94a3b8 !important; cursor: not-allowed !important; opacity: 0.7; }

/* 🌟 TUỲ BIẾN ĐỒNG BỘ CSS FLATPICKR VỚI CÁC INPUT TRONG MODAL */
:deep(input.flatpickr-custom-input),
:deep(.form-group input.flatpickr-input) {
  padding: 9px 12px !important;
  border: 1px solid #cbd5e1 !important;
  border-radius: 8px !important;
  font-size: 13px !important;
  width: 100% !important;
  box-sizing: border-box !important;
  background-color: #ffffff !important;
  color: #0f172a !important;
  font-family: inherit !important;
  cursor: pointer;
  transition: border-color 0.2s, box-shadow 0.2s;
}

:deep(input.flatpickr-custom-input:focus),
:deep(.form-group input.flatpickr-input:focus) {
  border-color: #2563eb !important;
  outline: none !important;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
}

:deep(.flatpickr-calendar) {
  border-radius: 12px !important;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
  border: 1px solid #e2e8f0 !important;
  font-family: inherit !important;
  z-index: 3500 !important; /* Luôn nổi trên modal-overlay (z-index: 3000) */
}

:deep(.flatpickr-months) {
  background: #f8fafc !important;
  border-top-left-radius: 12px !important;
  border-top-right-radius: 12px !important;
  padding: 4px 0 !important;
}

:deep(.flatpickr-current-month) {
  font-weight: 700 !important;
  font-size: 13px !important;
}

:deep(.flatpickr-day.selected),
:deep(.flatpickr-day.startRange),
:deep(.flatpickr-day.endRange),
:deep(.flatpickr-day.selected:focus),
:deep(.flatpickr-day.selected:hover) {
  background: #2563eb !important;
  border-color: #2563eb !important;
}

:deep(.flatpickr-day.today) {
  border-color: #2563eb !important;
}

:deep(.flatpickr-day:hover) {
  background: #eff6ff !important;
}

@keyframes modalSlide { from { opacity: 0; transform: translateY(12px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
</style>