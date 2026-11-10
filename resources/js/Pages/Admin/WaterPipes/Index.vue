<template>
  <AdminLayout>
    <div class="water-pipes-page">
      <!-- 1. HEADER TIÊU ĐỀ TRANG VÀ NÚT TẠO MỚI -->
      <div class="page-header">
        <div>
          <h2>🚰 QUẢN LÝ ĐƯỜNG ỐNG THOÁT NƯỚC</h2>
          <p>Mạng lưới cống thu gom nước thải tự chảy và thoát nước mưa liên kết hố ga (MultiLineString) trên bản đồ KCN</p>
        </div>
        <button class="btn-primary" @click="openCreateModal">
          ➕ Thêm Tuyến Cống Mới
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
              placeholder="Tìm theo Mã cống, Tên cống, Tuyến đường..." 
            />
          </div>

          <!-- Lọc nhanh theo KCN và Loại cống -->
          <div class="filter-selects">
            <select v-model="filterZoneId" class="select-control">
              <option value="">-- Tất cả KCN --</option>
              <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.name }}</option>
            </select>

            <select v-model="filterType" class="select-control">
              <option value="">-- Tất cả hệ thống --</option>
              <option value="wastewater">Thu gom Nước thải</option>
              <option value="rainwater">Thoát Nước mưa</option>
            </select>
          </div>

          <div class="stats-mini">
            <span class="stat-item">
              🚰 Tổng đoạn: <b>{{ pipes.total || pipeList.length }}</b>
            </span>
            <span class="stat-item highlight-waste">
              🧪 Nước thải: <b>{{ totalWastePipes }}</b>
            </span>
            <span class="stat-item highlight-rain">
              🌧️ Nước mưa: <b>{{ totalRainPipes }}</b>
            </span>
          </div>
        </div>
      </div>

      <!-- 3. BẢNG DANH SÁCH ĐƯỜNG ỐNG -->
      <div class="table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>STT</th>
                <th>Mã / Tên Cống</th>
                <th>Hệ Thống</th>
                <th>Tuyến Đường / KCN</th>
                <th>Tiết Diện / Vật Liệu</th>
                <th>Hố Ga Liên Kết</th>
                <th>Độ Dốc (i%)</th>
                <th>Định Vị GIS</th>
                <th class="text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(p, index) in filteredPipes" :key="p.id">
                <td>{{ ((pipes.current_page || 1) - 1) * (pipes.per_page || 15) + index + 1 }}</td>
                <td>
                  <b class="pipe-code">{{ p.code }}</b>
                  <div class="text-muted" v-if="p.name">{{ p.name }}</div>
                </td>
                <td>
                  <span 
                    class="badge-type" 
                    :class="p.pipe_type === 'wastewater' ? 'type-waste' : 'type-rain'"
                  >
                    {{ p.pipe_type === 'wastewater' ? '🧪 Nước thải' : '🌧️ Nước mưa' }}
                  </span>
                </td>
                <td>
                  <span>{{ p.road?.name || '—' }}</span>
                  <div class="text-muted" v-if="p.zone">{{ p.zone.name }}</div>
                </td>
                <td>
                  <span class="badge-width" v-if="p.cross_section === 'circular'">D{{ p.diameter || '—' }} mm</span>
                  <span class="badge-width alt" v-else>Hộp {{ p.width }}x{{ p.height }}</span>
                  <div class="text-muted">{{ p.material || 'BTCT' }}</div>
                </td>
                <td>
                  <div class="manhole-flow">
                    <span class="badge-mh" :title="getManholeCode(p.pipe_type, p.start_manhole_id)">
                      {{ getManholeCode(p.pipe_type, p.start_manhole_id) || 'HG Đầu' }}
                    </span>
                    <span class="arrow-mh">➔</span>
                    <span class="badge-mh" :title="getManholeCode(p.pipe_type, p.end_manhole_id)">
                      {{ getManholeCode(p.pipe_type, p.end_manhole_id) || 'HG Cuối' }}
                    </span>
                  </div>
                </td>
                <td>
                  <b class="text-slope" v-if="p.slope !== null && p.slope !== undefined">{{ p.slope }}%</b>
                  <span class="text-muted" v-else>—</span>
                </td>
                <td>
                  <span v-if="p.geom_wkt || p.geojson || p.geom" class="badge-gis">📍 MultiLineString</span>
                  <span v-else class="text-muted">Chưa có tọa độ</span>
                </td>
                <td class="text-center actions-cell">
                  <button class="btn-icon btn-edit" @click="openEditModal(p)" title="Sửa thông tin">✏️</button>
                  <button class="btn-icon btn-delete" @click="deletePipe(p)" title="Xóa đoạn cống">🗑️</button>
                </td>
              </tr>
              <tr v-if="!filteredPipes.length">
                <td colspan="9" class="text-center text-muted">
                  {{ searchQuery || filterZoneId || filterType ? 'Không tìm thấy đoạn cống phù hợp với bộ lọc.' : 'Chưa có dữ liệu đường ống.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- PHÂN TRANG (PAGINATION) -->
        <div class="pagination-wrapper" v-if="pipes.links && pipes.links.length > 3">
          <div class="pagination-info text-muted">
            Hiển thị trang <b>{{ pipes.current_page }}</b> / <b>{{ pipes.last_page }}</b> (Tổng <b>{{ pipes.total }}</b> tuyến cống)
          </div>
          <div class="pagination-links">
            <template v-for="(link, lIdx) in pipes.links" :key="lIdx">
              <span 
                v-if="!link.url" 
                class="page-link disabled" 
                v-html="link.label"
              ></span>
              <a 
                v-else 
                :href="link.url" 
                @click.prevent="goToPage(link.url)" 
                class="page-link" 
                :class="{ active: link.active }" 
                v-html="link.label"
              ></a>
            </template>
          </div>
        </div>
      </div>

      <!-- 4. MODAL THÊM / SỬA TUYẾN CỐNG (RESPONSIVE CHUẨN) -->
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content modal-lg">
          <div class="modal-header">
            <h3>{{ isEditMode ? '✏️ Cập nhật Tuyến Cống' : '➕ Thêm Tuyến Cống Mới' }}</h3>
            <button class="close-btn" @click="closeModal">✕</button>
          </div>

          <form @submit.prevent="savePipe" class="modal-form">
            <!-- HÀNG 1: LOẠI HỆ THỐNG & KCN & TUYẾN ĐƯỜNG -->
            <div class="form-row">
              <div class="form-group third">
                <label>Hệ thống thoát nước <span class="required">*</span></label>
                <select v-model="form.pipe_type" @change="onPipeTypeChange" required>
                  <option value="wastewater">🧪 Thu gom Nước thải (Tự chảy)</option>
                  <option value="rainwater">🌧️ Thoát Nước mưa</option>
                </select>
              </div>

              <div class="form-group third">
                <label>Khu công nghiệp</label>
                <select v-model="form.zone_id">
                  <option value="">-- Chọn KCN --</option>
                  <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.name }}</option>
                </select>
              </div>

              <div class="form-group third">
                <label>Tuyến đường (Road)</label>
                <select v-model="form.road_id">
                  <option value="">-- Chọn đường chạy qua --</option>
                  <option v-for="r in availableRoads" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
              </div>
            </div>

            <!-- HÀNG 2: MÃ CỐNG & TÊN CỐNG & VẬT LIỆU -->
            <div class="form-row">
              <div class="form-group third">
                <label>Mã đoạn cống <span class="required">*</span></label>
                <input 
                  type="text" 
                  v-model="form.code" 
                  required 
                  placeholder="VD: NT-D1-01 hoặc NM-TRUC-02" 
                  :class="{ 'input-error': form.errors.code }"
                />
                <small v-if="form.errors.code" class="error-text">{{ form.errors.code }}</small>
              </div>

              <div class="form-group third">
                <label>Tên tuyến / Mô tả</label>
                <input 
                  type="text" 
                  v-model="form.name" 
                  placeholder="VD: Đoạn từ HG1 đến HG2" 
                />
              </div>

              <div class="form-group third">
                <label>Vật liệu cống</label>
                <input type="text" v-model="form.material" placeholder="HDPE 2 vách, BTCT..." />
              </div>
            </div>

            <!-- HÀNG 3: HÌNH HỌC & KÍCH THƯỚC TIẾT DIỆN -->
            <div class="form-row">
              <div class="form-group third">
                <label>Tiết diện cống</label>
                <select v-model="form.cross_section">
                  <option value="circular">Cống tròn</option>
                  <option value="box">Cống hộp</option>
                  <option value="open_channel">Mương hở</option>
                </select>
              </div>

              <div class="form-group two-thirds" v-if="form.cross_section === 'circular'">
                <label>Đường kính trong D (mm)</label>
                <input type="number" v-model.number="form.diameter" placeholder="300, 400, 600, 800..." />
              </div>
              <template v-else>
                <div class="form-group third">
                  <label>Chiều rộng B (mm)</label>
                  <input type="number" v-model.number="form.width" placeholder="1000" />
                </div>
                <div class="form-group third">
                  <label>Chiều cao H (mm)</label>
                  <input type="number" v-model.number="form.height" placeholder="1200" />
                </div>
              </template>
            </div>

            <!-- 🌟 HÀNG 4: DANH SÁCH CÁC NHÁNH CỐNG (MỖI NHÁNH CÓ HỐ GA ĐẦU - CUỐI & TỰ ĐỘNG LẤY TỌA ĐỘ, CỐT ĐÁY) -->
            <div class="form-group">
              <div class="branch-header">
                <div>
                  <label class="font-bold">Các nhánh tuyến cống (MultiLineString)</label>
                  <p class="help-text-sub">Mỗi nhánh chọn Hố ga đầu và Hố ga cuối để hệ thống tự động nối tọa độ và nạp cốt đáy</p>
                </div>
                <button type="button" class="btn-add-branch" @click="addBranch">
                  ➕ Thêm nhánh rẽ
                </button>
              </div>

              <div class="branches-container">
                <div v-for="(b, bIdx) in branchInputs" :key="bIdx" class="branch-item">
                  <div class="branch-label-row">
                    <span class="branch-badge">Nhánh #{{ bIdx + 1 }} {{ bIdx === 0 ? '(Nhánh chính)' : '(Nhánh rẽ)' }}</span>
                    <button 
                      v-if="branchInputs.length > 1" 
                      type="button" 
                      class="btn-remove-branch" 
                      @click="removeBranch(bIdx)"
                    >
                      ✕ Xóa nhánh
                    </button>
                  </div>

                  <!-- Chọn 2 hố ga cho nhánh: Vừa gõ tìm kiếm vừa có Dropdown -->
                  <div class="form-row branch-inputs-row">
                    <!-- HỐ GA ĐẦU -->
                    <div class="form-group quarter">
                      <div class="flex-between">
                        <label class="sub-label">Hố ga đầu (Thượng lưu)</label>
                        <span 
                          v-if="b.start_manhole_code" 
                          class="btn-clear-mh" 
                          @click="clearBranchManhole(b, true)" 
                          title="Xóa chọn lại"
                        >✕</span>
                      </div>
                      <input 
                        type="text"
                        list="manholesDatalist"
                        v-model="b.start_manhole_code"
                        @input="onManholeInput(b, true)"
                        placeholder="Gõ mã hoặc click chọn..."
                        class="mh-search-input"
                      />
                    </div>

                    <div class="form-group quarter">
                      <label class="sub-label">Cốt đáy đầu (m)</label>
                      <input 
                        type="number" 
                        step="0.001" 
                        v-model="b.invert_start" 
                        @input="onBranchElevationInput(b)"
                        placeholder="VD: 4.850" 
                      />
                    </div>

                    <!-- HỐ GA CUỐI -->
                    <div class="form-group quarter">
                      <div class="flex-between">
                        <label class="sub-label">Hố ga cuối (Hạ lưu)</label>
                        <span 
                          v-if="b.end_manhole_code" 
                          class="btn-clear-mh" 
                          @click="clearBranchManhole(b, false)" 
                          title="Xóa chọn lại"
                        >✕</span>
                      </div>
                      <input 
                        type="text"
                        list="manholesDatalist"
                        v-model="b.end_manhole_code"
                        @input="onManholeInput(b, false)"
                        placeholder="Gõ mã hoặc click chọn..."
                        class="mh-search-input"
                      />
                    </div>

                    <div class="form-group quarter">
                      <label class="sub-label">Cốt đáy cuối (m)</label>
                      <input 
                        type="number" 
                        step="0.001" 
                        v-model="b.invert_end" 
                        @input="onBranchElevationInput(b)"
                        placeholder="VD: 4.620" 
                      />
                    </div>
                  </div>

                  <!-- Tọa độ nối tự động hoặc nhập tùy chỉnh -->
                  <div class="form-group" style="margin-top: 6px;">
                    <div class="geojson-input-wrapper">
                      <span class="prefix-tag">[</span>
                      <textarea 
                        v-model="b.text" 
                        @input="onCoordinatesChange"
                        rows="2"
                        :class="{ 'input-error': b.text && !validateSingleBranch(b.text) }"
                        placeholder="Tọa độ tự động điền khi chọn 2 hố ga, hoặc nhập: 21.246, 106.177; 21.248, 106.179"
                      ></textarea>
                      <span class="suffix-tag">]</span>
                    </div>
                  </div>
                </div>
              </div>

              <small v-if="hasBranchError" class="error-text">
                ⚠️ Mỗi nhánh cần tối thiểu 2 điểm tọa độ hợp lệ, phân cách bằng dấu chấm phẩy (;)
              </small>
              <small v-else class="help-text">
                💡 Khi chọn 2 hố ga, hệ thống sẽ tự động bắt cặp tọa độ và nạp cốt đáy. Nếu hố ga chưa có cốt, hãy nhập trực tiếp vào ô cốt đáy ở trên để hệ thống lưu và cập nhật ngược lại hố ga.
              </small>
            </div>

            <!-- HÀNG 5: ĐỘ DỐC THỦY LỰC TÍNH TOÁN & TRẠNG THÁI -->
            <div class="form-row">
              <div class="form-group half">
                <div class="flex-between">
                  <label>Độ dốc thủy lực i (%)</label>
                  <span class="calc-tag" v-if="calculateTotalLength() > 0">
                    Chiều dài L ≈ {{ Math.round(calculateTotalLength()) }}m
                  </span>
                </div>
                <input 
                  type="number" 
                  step="0.001" 
                  v-model.number="form.slope" 
                  placeholder="Tự động tính từ cốt đáy và chiều dài" 
                  :class="{ 'slope-warning': form.slope < 0 }"
                />
                <small v-if="form.slope < 0" class="error-text">
                  ⚠️ Cảnh báo: Dốc ngược (cốt cuối cao hơn cốt đầu)!
                </small>
              </div>

              <div class="form-group half">
                <label>Trạng thái vận hành</label>
                <select v-model="form.status">
                  <option value="active">Hoạt động bình thường</option>
                  <option value="clogged">Bị nghẽn / lắng cặn bùn</option>
                  <option value="damaged">Sụt lún / vỡ nứt</option>
                  <option value="maintenance">Đang bảo trì / nạo vét</option>
                </select>
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
                {{ isEditMode ? 'Cập nhật' : 'Tạo mới' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- 🌟 KHO DỮ LIỆU TÌM KIẾM HỐ GA (DATALIST NATIVE) -->
      <datalist id="manholesDatalist">
        <option 
          v-for="m in currentManholes" 
          :key="m.id" 
          :value="m.code"
        >
          {{ m.code }} - Cốt đáy: {{ m.bottom_depth !== null && m.bottom_depth !== undefined ? m.bottom_depth + 'm' : 'Chưa có' }}
        </option>
      </datalist>

    </div>
  </AdminLayout>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Inertia } from '@inertiajs/inertia';

export default {
  name: 'WaterPipesIndex',
  components: { AdminLayout },
  props: {
    pipes: { 
      type: [Object, Array], 
      default: () => ({ data: [], total: 0 }) 
    },
    zones: { type: Array, default: () => [] },
    roads: { type: Array, default: () => [] },
    wastewaterManholes: { type: Array, default: () => [] },
    rainwaterManholes: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) }
  },
  data() {
    return {
      showModal: false,
      isEditMode: false,
      selectedId: null,
      searchQuery: this.filters?.search || '',
      filterZoneId: this.filters?.zone_id || '',
      filterType: this.filters?.pipe_type || '',
      branchInputs: [
        {
          start_manhole_id: '',
          start_manhole_code: '',
          end_manhole_id: '',
          end_manhole_code: '',
          invert_start: '',
          invert_end: '',
          text: ''
        }
      ],
      form: this.$inertia.form({
        zone_id: '',
        road_id: '',
        pipe_type: 'wastewater',
        code: '',
        name: '',
        start_manhole_id: '',
        end_manhole_id: '',
        cross_section: 'circular',
        diameter: 300,
        width: '',
        height: '',
        material: 'HDPE 2 vách',
        invert_start_elevation: '',
        invert_end_elevation: '',
        slope: '',
        status: 'active',
        geom: ''
      })
    };
  },
  computed: {
    // Helper an toàn trích xuất mảng danh sách từ object phân trang Laravel
    pipeList() {
      if (Array.isArray(this.pipes)) return this.pipes;
      return this.pipes?.data || [];
    },

    totalWastePipes() {
      return this.pipeList.filter(p => p.pipe_type === 'wastewater').length;
    },
    totalRainPipes() {
      return this.pipeList.filter(p => p.pipe_type === 'rainwater').length;
    },

    availableRoads() {
      if (!this.form.zone_id) return this.roads;
      return this.roads.filter(r => r.zone_id == this.form.zone_id);
    },

    // Danh sách hố ga tương ứng với loại cống và KCN đang chọn
    currentManholes() {
      let list = this.form.pipe_type === 'wastewater' 
        ? this.wastewaterManholes 
        : this.rainwaterManholes;

      if (this.form.zone_id) {
        list = list.filter(m => m.zone_id == this.form.zone_id);
      }
      return list;
    },

    filteredPipes() {
      return this.pipeList.filter(p => {
        const query = this.searchQuery.toLowerCase().trim();
        const matchQuery = !query || 
          (p.code && p.code.toLowerCase().includes(query)) ||
          (p.name && p.name.toLowerCase().includes(query)) ||
          (p.road?.name && p.road.name.toLowerCase().includes(query));

        const matchZone = !this.filterZoneId || p.zone_id == this.filterZoneId;
        const matchType = !this.filterType || p.pipe_type === this.filterType;

        return matchQuery && matchZone && matchType;
      });
    },

    hasBranchError() {
      const activeBranches = this.branchInputs.filter(b => b.text && b.text.trim() !== '');
      if (!activeBranches.length) return false;
      return activeBranches.some(b => !this.validateSingleBranch(b.text));
    },

    isFormValid() {
      const isCodeValid = this.form.code && this.form.code.trim() !== '';
      return isCodeValid && !this.hasBranchError;
    }
  },
  methods: {
    goToPage(url) {
      if (url) {
        Inertia.get(url, {}, { preserveState: true, replace: true });
      }
    },

    getManholeCode(pipeType, manholeId) {
      if (!manholeId) return '';
      const list = pipeType === 'wastewater' ? this.wastewaterManholes : this.rainwaterManholes;
      const found = list.find(m => m.id == manholeId);
      return found ? found.code : `#${manholeId}`;
    },

    onPipeTypeChange() {
      // Khi đổi loại cống, xóa các hố ga đã chọn để người dùng chọn lại đúng bảng
      this.branchInputs.forEach(b => {
        b.start_manhole_id = '';
        b.end_manhole_id = '';
        b.invert_start = '';
        b.invert_end = '';
        b.text = '';
      });
      this.form.start_manhole_id = '';
      this.form.end_manhole_id = '';
      this.form.invert_start_elevation = '';
      this.form.invert_end_elevation = '';
      this.form.slope = '';
      this.form.geom = '';
    },

    validateSingleBranch(text) {
      if (!text || text.trim() === '') return true;
      const pointStrings = text.split(';').map(p => p.trim()).filter(p => p !== '');
      if (pointStrings.length < 2) return false;

      for (let pStr of pointStrings) {
        const parts = pStr.split(',').map(item => parseFloat(item.trim()));
        if (parts.length !== 2 || isNaN(parts[0]) || isNaN(parts[1])) return false;
      }
      return true;
    },

    addBranch() {
      this.branchInputs.push({
        start_manhole_id: '',
        start_manhole_code: '',
        end_manhole_id: '',
        end_manhole_code: '',
        invert_start: '',
        invert_end: '',
        text: ''
      });
    },

    removeBranch(index) {
      this.branchInputs.splice(index, 1);
      this.onCoordinatesChange();
    },

    // 🌟 Xử lý khi chọn hố ga đầu hoặc cuối của nhánh
    onBranchManholeChange(branch, isStart) {
      const startMh = this.currentManholes.find(m => m.id == branch.start_manhole_id);
      const endMh = this.currentManholes.find(m => m.id == branch.end_manhole_id);

      // 1. Tự động lấy cốt đáy của hố ga
      if (startMh && startMh.elevation !== null && startMh.elevation !== undefined) {
        branch.invert_start = startMh.elevation;
      }
      if (endMh && endMh.elevation !== null && endMh.elevation !== undefined) {
        branch.invert_end = endMh.elevation;
      }

      // 2. Tự động lấy tọa độ của 2 hố ga nối thành đường
      if (startMh && endMh && startMh.lat && endMh.lat) {
        branch.text = `${startMh.lat}, ${startMh.lng}; ${endMh.lat}, ${endMh.lng}`;
      }

      // Đồng bộ nhánh chính (#1) vào form
      if (this.branchInputs[0] === branch) {
        this.form.start_manhole_id = branch.start_manhole_id || '';
        this.form.end_manhole_id = branch.end_manhole_id || '';
        this.form.invert_start_elevation = branch.invert_start || '';
        this.form.invert_end_elevation = branch.invert_end || '';
      }

      this.onCoordinatesChange();
    },

    // 🌟 Xử lý khi người dùng gõ mã hoặc click chọn từ dropdown datalist
    onManholeInput(branch, isStart) {
      const inputCode = isStart 
        ? branch.start_manhole_code?.trim().toUpperCase() 
        : branch.end_manhole_code?.trim().toUpperCase();

      // Tìm hố ga khớp mã trong danh sách hố ga hiện tại
      const found = this.currentManholes.find(m => m.code.toUpperCase() === inputCode);

      if (found) {
        if (isStart) {
          branch.start_manhole_id = found.id;
          branch.start_manhole_code = found.code;
          if (found.bottom_depth !== null && found.bottom_depth !== undefined) {
            branch.invert_start = found.bottom_depth;
          }
        } else {
          branch.end_manhole_id = found.id;
          branch.end_manhole_code = found.code;
          if (found.bottom_depth !== null && found.bottom_depth !== undefined) {
            branch.invert_end = found.bottom_depth;
          }
        }

        // Tự động kiểm tra và nối tọa độ nếu cả 2 hố ga đều đã khớp
        const startMh = this.currentManholes.find(m => m.id == branch.start_manhole_id);
        const endMh = this.currentManholes.find(m => m.id == branch.end_manhole_id);

        if (startMh && endMh && startMh.lat && endMh.lat) {
          branch.text = `${startMh.lat}, ${startMh.lng}; ${endMh.lat}, ${endMh.lng}`;
        }

        // Nếu là nhánh chính #1, đồng bộ ra form cha
        if (this.branchInputs[0] === branch) {
          if (isStart) {
            this.form.start_manhole_id = found.id;
            this.form.invert_start_elevation = branch.invert_start;
          } else {
            this.form.end_manhole_id = found.id;
            this.form.invert_end_elevation = branch.invert_end;
          }
        }

        this.onCoordinatesChange();
      } else {
        // Nếu chưa gõ xong hoặc không khớp, chỉ tạm thời set ID về rỗng
        if (isStart) branch.start_manhole_id = '';
        else branch.end_manhole_id = '';
      }
    },

    // Xóa nhanh mã hố ga để chọn lại
    clearBranchManhole(branch, isStart) {
      if (isStart) {
        branch.start_manhole_id = '';
        branch.start_manhole_code = '';
        branch.invert_start = '';
        if (this.branchInputs[0] === branch) {
          this.form.start_manhole_id = '';
          this.form.invert_start_elevation = '';
        }
      } else {
        branch.end_manhole_id = '';
        branch.end_manhole_code = '';
        branch.invert_end = '';
        if (this.branchInputs[0] === branch) {
          this.form.end_manhole_id = '';
          this.form.invert_end_elevation = '';
        }
      }
      this.onCoordinatesChange();
    },

    // Khi người dùng tự nhập cốt đáy trên nhánh
    onBranchElevationInput(branch) {
      if (this.branchInputs[0] === branch) {
        this.form.invert_start_elevation = branch.invert_start;
        this.form.invert_end_elevation = branch.invert_end;
      }
      this.autoCalculateSlope();
    },

    // Tính khoảng cách giữa 2 điểm WGS-84 (mét)
    calculateDistance(lat1, lon1, lat2, lon2) {
      const R = 6371000;
      const dLat = (lat2 - lat1) * Math.PI / 180;
      const dLon = (lon2 - lon1) * Math.PI / 180;
      const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      return R * c;
    },

    calculateTotalLength() {
      if (!this.branchInputs || !this.branchInputs.length) return 0;
      const firstBranchText = this.branchInputs[0]?.text;
      if (!firstBranchText) return 0;

      const points = firstBranchText.split(';')
        .map(p => p.trim())
        .filter(p => p !== '')
        .map(p => {
          const parts = p.split(',').map(item => parseFloat(item.trim()));
          let lat = parts[0] < parts[1] ? parts[0] : parts[1];
          let lng = parts[0] < parts[1] ? parts[1] : parts[0];
          return { lat, lng };
        });

      if (points.length < 2) return 0;

      let totalDist = 0;
      for (let i = 0; i < points.length - 1; i++) {
        if (!isNaN(points[i].lat) && !isNaN(points[i + 1].lat)) {
          totalDist += this.calculateDistance(
            points[i].lat, points[i].lng,
            points[i + 1].lat, points[i + 1].lng
          );
        }
      }
      return totalDist;
    },

    // 🌟 Tự động tính độ dốc thủy lực i (%)
    autoCalculateSlope() {
      const zStart = parseFloat(this.form.invert_start_elevation);
      const zEnd = parseFloat(this.form.invert_end_elevation);
      const length = this.calculateTotalLength();

      if (!isNaN(zStart) && !isNaN(zEnd) && length > 0) {
        const deltaH = zStart - zEnd;
        const slopeValue = (deltaH / length) * 100;
        this.form.slope = parseFloat(slopeValue.toFixed(3));
      }
    },

    // 🌟 Đóng gói tất cả các nhánh thành GeoJSON MultiLineString chuẩn
    onCoordinatesChange() {
      const validLines = [];

      for (let b of this.branchInputs) {
        if (!b.text || b.text.trim() === '') continue;
        if (!this.validateSingleBranch(b.text)) continue;

        const pointStrings = b.text.split(';').map(p => p.trim()).filter(p => p !== '');
        const lineCoords = pointStrings.map(pStr => {
          const parts = pStr.split(',').map(item => parseFloat(item.trim()));
          let lat, lng;
          if (parts[0] < parts[1]) {
            lat = parts[0];
            lng = parts[1];
          } else {
            lng = parts[0];
            lat = parts[1];
          }
          return [lng, lat]; // GeoJSON chuẩn là [Lng, Lat]
        });

        if (lineCoords.length >= 2) {
          validLines.push(lineCoords);
        }
      }

      if (validLines.length > 0) {
        this.form.geom = JSON.stringify({
          type: 'MultiLineString',
          coordinates: validLines
        });
      } else {
        this.form.geom = '';
      }

      this.autoCalculateSlope();
    },

    extractBranches(pipe) {
      // Lấy mã code hố ga từ ID
      const startCode = this.getManholeCode(pipe.pipe_type, pipe.start_manhole_id);
      const endCode = this.getManholeCode(pipe.pipe_type, pipe.end_manhole_id);

      // 1. Phục hồi từ chuỗi WKT
      if (pipe.geom_wkt) {
        const wkt = pipe.geom_wkt.trim();
        if (wkt.startsWith('MULTILINESTRING')) {
          const content = wkt.replace(/^MULTILINESTRING\s*\(/i, '').slice(0, -1);
          const lines = content.split(/\),\s*\(/).map(l => l.replace(/[()]/g, '').trim());
          return lines.map((lineStr, idx) => {
            const points = lineStr.split(',').map(pt => pt.trim().split(/\s+/));
            return {
              start_manhole_id: idx === 0 ? (pipe.start_manhole_id || '') : '',
              start_manhole_code: idx === 0 ? startCode : '',
              end_manhole_id: idx === 0 ? (pipe.end_manhole_id || '') : '',
              end_manhole_code: idx === 0 ? endCode : '',
              invert_start: idx === 0 ? (pipe.invert_start_elevation || '') : '',
              invert_end: idx === 0 ? (pipe.invert_end_elevation || '') : '',
              text: points.map(p => `${p[1]}, ${p[0]}`).join('; ')
            };
          });
        }
      }

      // 2. Phục hồi từ GeoJSON
      const geomData = pipe.geojson || pipe.geom;
      if (geomData) {
        let parsed = geomData;
        if (typeof geomData === 'string') {
          try { parsed = JSON.parse(geomData); } catch (e) { return [{ text: '' }]; }
        }

        if (parsed && parsed.type === 'MultiLineString' && Array.isArray(parsed.coordinates)) {
          return parsed.coordinates.map((line, idx) => ({
            start_manhole_id: idx === 0 ? (pipe.start_manhole_id || '') : '',
            start_manhole_code: idx === 0 ? startCode : '',
            end_manhole_id: idx === 0 ? (pipe.end_manhole_id || '') : '',
            end_manhole_code: idx === 0 ? endCode : '',
            invert_start: idx === 0 ? (pipe.invert_start_elevation || '') : '',
            invert_end: idx === 0 ? (pipe.invert_end_elevation || '') : '',
            text: line.map(pt => `${pt[1]}, ${pt[0]}`).join('; ')
          }));
        }
      }

      return [{
        start_manhole_id: pipe.start_manhole_id || '',
        start_manhole_code: startCode,
        end_manhole_id: pipe.end_manhole_id || '',
        end_manhole_code: endCode,
        invert_start: pipe.invert_start_elevation || '',
        invert_end: pipe.invert_end_elevation || '',
        text: ''
      }];
    },

    openCreateModal() {
      this.isEditMode = false;
      this.selectedId = null;
      this.form.reset();
      this.form.clearErrors();
      this.branchInputs = [{
        start_manhole_id: '',
        end_manhole_id: '',
        invert_start: '',
        invert_end: '',
        text: ''
      }];
      this.form.pipe_type = 'wastewater';
      this.form.cross_section = 'circular';
      this.form.diameter = 300;
      this.form.material = 'HDPE 2 vách';
      this.form.status = 'active';
      if (this.zones.length) this.form.zone_id = this.zones[0].id;
      this.showModal = true;
    },

    openEditModal(p) {
      this.isEditMode = true;
      this.selectedId = p.id;
      this.form.clearErrors();

      this.form.zone_id = p.zone_id || '';
      this.form.road_id = p.road_id || '';
      this.form.pipe_type = p.pipe_type || 'wastewater';
      this.form.code = p.code || '';
      this.form.name = p.name || '';
      this.form.start_manhole_id = p.start_manhole_id || '';
      this.form.end_manhole_id = p.end_manhole_id || '';
      this.form.cross_section = p.cross_section || 'circular';
      this.form.diameter = p.diameter || '';
      this.form.width = p.width || '';
      this.form.height = p.height || '';
      this.form.material = p.material || '';
      this.form.invert_start_elevation = p.invert_start_elevation || '';
      this.form.invert_end_elevation = p.invert_end_elevation || '';
      this.form.slope = p.slope || '';
      this.form.status = p.status || 'active';

      this.branchInputs = this.extractBranches(p);
      this.onCoordinatesChange();

      this.showModal = true;
    },

    closeModal() {
      this.showModal = false;
      this.form.reset();
      this.form.clearErrors();
    },

    savePipe() {
      this.onCoordinatesChange();
      if (this.isEditMode) {
        this.form.put(`/admin/water-pipes/${this.selectedId}`, {
          onSuccess: () => this.closeModal()
        });
      } else {
        this.form.post('/admin/water-pipes', {
          onSuccess: () => this.closeModal()
        });
      }
    },

    deletePipe(p) {
      if (confirm(`Anh có chắc chắn muốn XÓA đoạn cống [${p.code}] không?`)) {
        this.$inertia.delete(`/admin/water-pipes/${p.id}`);
      }
    }
  }
};
</script>

<style scoped>
.water-pipes-page { display: flex; flex-direction: column; gap: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
.page-header h2 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; }
.page-header p { margin: 4px 0 0 0; font-size: 12px; color: #64748b; }

.btn-primary { background: #2563eb; color: #fff; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }
.btn-primary:hover { background: #1d4ed8; }
.btn-secondary { background: #e2e8f0; color: #334155; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }

/* FILTER & STATS CARD */
.filter-card { background: #fff; border-radius: 12px; padding: 14px 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.filter-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; }
.search-box { display: flex; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; flex: 1; min-width: 240px; }
.search-icon { margin-right: 8px; font-size: 14px; }
.search-box input { border: none; background: transparent; outline: none; width: 100%; font-size: 13px; color: #0f172a; }

.filter-selects { display: flex; gap: 8px; flex-wrap: wrap; }
.select-control { border: 1px solid #cbd5e1; background: #f8fafc; border-radius: 8px; padding: 6px 10px; font-size: 13px; color: #334155; outline: none; }

.stats-mini { display: flex; gap: 10px; font-size: 12px; color: #475569; flex-wrap: wrap; }
.stat-item { background: #f1f5f9; padding: 6px 12px; border-radius: 6px; }
.stat-item.highlight-waste { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.stat-item.highlight-rain { background: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd; }

/* TABLE CARD */
.table-card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
.table-responsive { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
.data-table th { background: #f8fafc; padding: 12px; color: #475569; font-weight: 700; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
.data-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }

.pipe-code { color: #2563eb; font-size: 13px; }
.badge-type { padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; display: inline-block; }
.type-waste { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.type-rain { background: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd; }

.badge-width { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
.badge-width.alt { background: #fdf4ff; color: #86198f; border-color: #f5d0fe; }
.badge-gis { background: #ecfdf5; color: #047857; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }

.manhole-flow { display: flex; align-items: center; gap: 4px; }
.badge-mh { background: #f1f5f9; border: 1px solid #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 700; color: #334155; }
.arrow-mh { font-size: 10px; color: #94a3b8; }
.text-slope { color: #059669; font-family: monospace; font-size: 13px; }
.text-muted { color: #94a3b8; font-size: 12px; }

.actions-cell { display: flex; justify-content: center; gap: 8px; }
.btn-icon { border: none; background: #f1f5f9; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.btn-edit:hover { background: #dbeafe; }
.btn-delete:hover { background: #fee2e2; }

/* PAGINATION STYLES */
.pagination-wrapper { display: flex; justify-content: space-between; align-items: center; margin-top: 16px; flex-wrap: wrap; gap: 12px; }
.pagination-links { display: flex; gap: 4px; }
.page-link { padding: 6px 12px; border: 1px solid #cbd5e1; background: #fff; color: #334155; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; }
.page-link:hover { background: #f1f5f9; }
.page-link.active { background: #2563eb; color: #fff; border-color: #2563eb; }
.page-link.disabled { color: #94a3b8; cursor: not-allowed; background: #f8fafc; border-color: #e2e8f0; }

/* BRANCHES INPUT CONTAINER */
.branch-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.btn-add-branch { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; }
.btn-add-branch:hover { background: #dbeafe; }
.help-text-sub { margin: 2px 0 0 0; font-size: 11px; color: #64748b; font-weight: normal; }

.branches-container { display: flex; flex-direction: column; gap: 12px; }
.branch-item { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; }
.branch-label-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.branch-badge { font-size: 11px; font-weight: 700; color: #2563eb; background: #eff6ff; border: 1px solid #bfdbfe; padding: 2px 8px; border-radius: 4px; }
.btn-remove-branch { background: transparent; border: none; color: #dc2626; font-size: 11px; font-weight: 600; cursor: pointer; }

.branch-inputs-row { margin-bottom: 6px; }
.sub-label { font-size: 11px !important; color: #64748b !important; }

.geojson-input-wrapper { display: flex; align-items: flex-start; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; font-family: monospace; font-size: 13px; }
.geojson-input-wrapper:focus-within { border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1); }
.prefix-tag, .suffix-tag { background: #e2e8f0; color: #475569; padding: 8px 10px; font-weight: 600; user-select: none; }
.geojson-input-wrapper textarea { border: none !important; box-shadow: none !important; padding: 8px 10px; flex: 1; outline: none; background: transparent; font-family: monospace; font-weight: 600; color: #0f172a; resize: vertical; }

/* MODAL STYLES */
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 3000; display: flex; align-items: center; justify-content: center; padding: 16px; }
.modal-content.modal-lg { background: #fff; border-radius: 14px; width: 100%; max-width: 760px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
.modal-header { padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
.modal-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: #0f172a; }
.close-btn { border: none; background: transparent; font-size: 18px; cursor: pointer; color: #64748b; }
.modal-form { padding: 20px; display: flex; flex-direction: column; gap: 14px; overflow-y: auto; }

.form-row { display: flex; gap: 10px; flex-wrap: wrap; }
.half { flex: 1; min-width: 220px; }
.third { flex: 1; min-width: 180px; }
.two-thirds { flex: 2; min-width: 280px; }
.quarter { flex: 1; min-width: 140px; }

.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 13px; font-weight: 600; color: #334155; }
.required { color: #dc2626; }
.form-group input, .form-group select, .form-group textarea { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 100%; box-sizing: border-box; background: #fff; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #2563eb; outline: none; }

.flex-between { display: flex; justify-content: space-between; align-items: center; }
.calc-tag { font-size: 11px; color: #0284c7; background: #e0f2fe; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
.slope-warning { border-color: #ef4444 !important; background-color: #fef2f2 !important; color: #b91c1c !important; font-weight: bold; }

.input-error { border-color: #dc2626 !important; background-color: #fef2f2 !important; }
.error-text { font-size: 11px; color: #dc2626; margin-top: 2px; display: block; }
.help-text { font-size: 11px; color: #64748b; margin-top: 4px; display: block; line-height: 1.4; }

.modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #f1f5f9; }
.btn-primary:disabled, .btn-disabled { background-color: #94a3b8 !important; cursor: not-allowed !important; opacity: 0.7; }

/* RESPONSIVE (< 768px) */
@media (max-width: 768px) {
  .half, .third, .two-thirds, .quarter { flex: 100%; min-width: 100%; }
  .filter-row { flex-direction: column; align-items: stretch; }
  .filter-selects { width: 100%; }
  .select-control { flex: 1; }
  .stats-mini { justify-content: space-between; }
  .pagination-wrapper { flex-direction: column; align-items: center; gap: 8px; }
}

.btn-clear-mh {
  cursor: pointer;
  color: #94a3b8;
  font-size: 11px;
  font-weight: bold;
  padding: 0 4px;
  border-radius: 4px;
}
.btn-clear-mh:hover {
  color: #ef4444;
  background: #fee2e2;
}

.mh-search-input {
  font-family: monospace;
  font-weight: 600;
  color: #0f172a;
}
.mh-search-input::placeholder {
  font-family: inherit;
  font-weight: normal;
  color: #94a3b8;
  font-size: 12px;
}
</style>