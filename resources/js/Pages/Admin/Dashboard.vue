<template>
  <AdminLayout>
    <div class="dashboard-container">
      <!-- 1. HEADER TIÊU ĐỀ -->
      <div class="page-header">
        <div>
          <h2>📊 TỔNG QUAN HỆ THỐNG WEBGIS KCN</h2>
          <p>Thống kê toàn diện hạ tầng kỹ thuật, quỹ đất và tình hình hoạt động khu công nghiệp</p>
        </div>
        <div class="header-date">
          📅 Cập nhật: {{ currentDate }}
        </div>
      </div>

      <!-- 2. HÀNG THỐNG KÊ KCN LỚN (8 CARDS TOÀN DIỆN) -->
      <div class="stats-grid">
        <!-- 1. CÔNG TY -->
        <div class="stat-card border-blue">
          <div class="stat-icon bg-blue">🏢</div>
          <div class="stat-content">
            <span class="stat-title">Doanh Nghiệp</span>
            <span class="stat-value">{{ stats.companies_count || 0 }}</span>
            <span class="stat-desc">Đang hoạt động trong KCN</span>
          </div>
        </div>

        <!-- 2. NHÀ XƯỞNG -->
        <div class="stat-card border-green">
          <div class="stat-icon bg-green">🏭</div>
          <div class="stat-content">
            <span class="stat-title">Nhà Xưởng</span>
            <span class="stat-value">{{ stats.factories_count || 0 }}</span>
            <span class="stat-desc">{{ stats.rented_factories_count || 0 }} xưởng đã cho thuê</span>
          </div>
        </div>

        <!-- 3. LÔ ĐẤT QUY HOẠCH -->
        <div class="stat-card border-amber">
          <div class="stat-icon bg-amber">🗺️</div>
          <div class="stat-content">
            <span class="stat-title">Lô Đất Quy Hoạch</span>
            <span class="stat-value">{{ stats.plots_count || 0 }}</span>
            <span class="stat-desc">Phân khu hạ tầng đất</span>
          </div>
        </div>

        <!-- 4. ĐƯỜNG GIAO THÔNG -->
        <div class="stat-card border-indigo">
          <div class="stat-icon bg-indigo">🛣️</div>
          <div class="stat-content">
            <span class="stat-title">Đường Giao Thông</span>
            <span class="stat-value">{{ stats.roads_count || 0 }}</span>
            <span class="stat-desc">Tuyến trục chính & nội bộ</span>
          </div>
        </div>

        <!-- 5. CÂY XANH & THẢM CỎ -->
        <div class="stat-card border-emerald">
          <div class="stat-icon bg-emerald">🌳</div>
          <div class="stat-content">
            <span class="stat-title">Cây Xanh & Cỏ</span>
            <span class="stat-value">{{ stats.greenery_count || 0 }}</span>
            <span class="stat-desc">Khu vực thảm cỏ / cây xanh</span>
          </div>
        </div>

        <!-- 6. MẶT NƯỚC & HỒ -->
        <div class="stat-card border-sky">
          <div class="stat-icon bg-sky">🌊</div>
          <div class="stat-content">
            <span class="stat-title">Hồ Điều Hòa</span>
            <span class="stat-value">{{ stats.water_bodies_count || 0 }}</span>
            <span class="stat-desc">Vùng mặt nước / kênh mương</span>
          </div>
        </div>

        <!-- 7. HỐ GA NƯỚC THẢI -->
        <div class="stat-card border-purple">
          <div class="stat-icon bg-purple">🧪</div>
          <div class="stat-content">
            <span class="stat-title">Hố Ga Nước Thải</span>
            <span class="stat-value">{{ stats.wastewater_count || 0 }}</span>
            <span class="stat-desc">Điểm đấu nối xả thải</span>
          </div>
        </div>

        <!-- 8. HỐ GA NƯỚC MƯA -->
        <div class="stat-card border-cyan">
          <div class="stat-icon bg-cyan">🌧️</div>
          <div class="stat-content">
            <span class="stat-title">Hố Ga Nước Mưa</span>
            <span class="stat-value">{{ stats.rainwater_count || 0 }}</span>
            <span class="stat-desc">Điểm thu nước mặt</span>
          </div>
        </div>
      </div>

      <!-- 3. KHU VỰC BIỂU ĐỒ TRỰC QUAN (CHARTS) -->
      <div class="charts-grid">
        <!-- BIỂU ĐỒ CỘT: SỐ LƯỢNG ĐỐI TƯỢNG HẠ TẦNG -->
        <div class="chart-card">
          <div class="card-title">
            <h3>📈 Thống kê Tỷ trọng Đối tượng Hạ tầng GIS</h3>
            <span class="badge-tag">Hệ thống KCN</span>
          </div>
          <div class="chart-body">
            <!-- Biểu đồ cột vẽ bằng SVG Thuần Vue -->
            <svg class="bar-chart-svg" viewBox="0 0 500 200">
              <g v-for="(bar, index) in barChartData" :key="index">
                <!-- Cột SVG -->
                <rect 
                  :x="60 + index * 55" 
                  :y="160 - bar.height" 
                  width="32" 
                  :height="bar.height" 
                  :fill="bar.color"
                  rx="4"
                />
                <!-- Số lượng trên đỉnh cột -->
                <text 
                  :x="76 + index * 55" 
                  :y="152 - bar.height" 
                  text-anchor="middle" 
                  font-size="11" 
                  font-weight="bold" 
                  fill="#334155"
                >
                  {{ bar.value }}
                </text>
                <!-- Nhãn tên bên dưới -->
                <text 
                  :x="76 + index * 55" 
                  y="180" 
                  text-anchor="middle" 
                  font-size="10" 
                  fill="#64748b"
                >
                  {{ bar.label }}
                </text>
              </g>
              <!-- Đường kẻ đáy chart -->
              <line x1="40" y1="162" x2="480" y2="162" stroke="#cbd5e1" stroke-width="1" />
            </svg>
          </div>
        </div>

        <!-- BIỂU ĐỒ TRÒN (DONUT CHART): TỶ LỆ LẤP ĐẦY XƯỞNG -->
        <div class="chart-card">
          <div class="card-title">
            <h3>🍩 Tỷ lệ Tình trạng Cho thuê Nhà Xưởng</h3>
            <span class="badge-tag">Trạng thái</span>
          </div>
          <div class="donut-chart-container">
            <!-- Biểu đồ Donut SVG -->
            <svg class="donut-svg" viewBox="0 0 160 160">
              <circle cx="80" cy="80" r="60" fill="transparent" stroke="#f1f5f9" stroke-width="20" />
              <circle 
                cx="80" 
                cy="80" 
                r="60" 
                fill="transparent" 
                stroke="#10b981" 
                stroke-width="20"
                :stroke-dasharray="`${rentedFactoryPercent * 3.77} 377`"
                stroke-dashoffset="0"
                transform="rotate(-90 80 80)"
              />
              <text x="80" y="75" text-anchor="middle" font-size="20" font-weight="800" fill="#0f172a">
                {{ rentedFactoryPercent }}%
              </text>
              <text x="80" y="95" text-anchor="middle" font-size="10" fill="#64748b">
                Đã lấp đầy
              </text>
            </svg>

            <!-- Chú thích màu sắc -->
            <div class="chart-legend">
              <div class="legend-item">
                <span class="dot bg-emerald-dot"></span>
                <span>Đã cho thuê: <b>{{ stats.rented_factories_count || 0 }}</b></span>
              </div>
              <div class="legend-item">
                <span class="dot bg-slate-dot"></span>
                <span>Đang trống: <b>{{ availableFactoriesCount }}</b></span>
              </div>
              <div class="legend-item">
                <span class="dot bg-blue-dot"></span>
                <span>Tổng số xưởng: <b>{{ stats.factories_count || 0 }}</b></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 4. BẢNG DOANH NGHIỆP MỚI & THÔNG TIN HỆ THỐNG -->
      <div class="dashboard-tables">
        <div class="table-card">
          <div class="card-title">
            <h3>🏢 Doanh nghiệp đăng ký mới nhất</h3>
            <a href="/admin/companies" class="view-more">Xem tất cả →</a>
          </div>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>STT</th>
                  <th>Tên Công Ty</th>
                  <th>Mã Số Thuế</th>
                  <th>Ngành Nghề Sản Xuất</th>
                  <th>Số Điện Thoại</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(company, index) in latestCompanies" :key="company.id">
                  <td>{{ index + 1 }}</td>
                  <td><b class="company-name">🏢 {{ company.name }}</b></td>
                  <td><code class="tax-code">{{ company.tax_code }}</code></td>
                  <td>{{ company.industry || 'Đang cập nhật' }}</td>
                  <td>{{ company.phone || '—' }}</td>
                </tr>
                <tr v-if="!latestCompanies.length">
                  <td colspan="5" class="text-center text-muted">Chưa có dữ liệu doanh nghiệp.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </AdminLayout>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';

export default {
  name: 'Dashboard',
  components: {
    AdminLayout
  },
  props: {
    stats: {
      type: Object,
      default: () => ({
        companies_count: 0,
        factories_count: 0,
        rented_factories_count: 0,
        wastewater_count: 0,
        rainwater_count: 0,
        jobs_count: 0,
        plots_count: 0,
        roads_count: 0,
        greenery_count: 0,
        water_bodies_count: 0
      })
    },
    latestCompanies: {
      type: Array,
      default: () => []
    }
  },
  computed: {
    currentDate() {
      const d = new Date();
      return d.toLocaleDateString('vi-VN');
    },

    // Số xưởng còn trống
    availableFactoriesCount() {
      const total = this.stats.factories_count || 0;
      const rented = this.stats.rented_factories_count || 0;
      return Math.max(0, total - rented);
    },

    // Tỷ lệ lấp đầy nhà xưởng (%)
    rentedFactoryPercent() {
      const total = this.stats.factories_count || 0;
      const rented = this.stats.rented_factories_count || 0;
      if (!total) return 0;
      return Math.round((rented / total) * 100);
    },

    // Dữ liệu cột dựng biểu đồ SVG
    barChartData() {
      const maxVal = Math.max(
        this.stats.companies_count || 1,
        this.stats.factories_count || 1,
        this.stats.plots_count || 1,
        this.stats.roads_count || 1,
        this.stats.greenery_count || 1,
        this.stats.water_bodies_count || 1,
        this.stats.wastewater_count || 1,
        this.stats.rainwater_count || 1
      );

      // Chiều cao tối đa của cột SVG = 110px
      const calcH = (val) => Math.round(((val || 0) / maxVal) * 110) + 8;

      return [
        { label: 'C.Ty', value: this.stats.companies_count || 0, height: calcH(this.stats.companies_count), color: '#2563eb' },
        { label: 'Xưởng', value: this.stats.factories_count || 0, height: calcH(this.stats.factories_count), color: '#10b981' },
        { label: 'Lô đất', value: this.stats.plots_count || 0, height: calcH(this.stats.plots_count), color: '#f59e0b' },
        { label: 'Đường', value: this.stats.roads_count || 0, height: calcH(this.stats.roads_count), color: '#6366f1' },
        { label: 'Cây xanh', value: this.stats.greenery_count || 0, height: calcH(this.stats.greenery_count), color: '#059669' },
        { label: 'Hồ nước', value: this.stats.water_bodies_count || 0, height: calcH(this.stats.water_bodies_count), color: '#0284c7' },
        { label: 'N.Thải', value: this.stats.wastewater_count || 0, height: calcH(this.stats.wastewater_count), color: '#800080' },
        { label: 'N.Mưa', value: this.stats.rainwater_count || 0, height: calcH(this.stats.rainwater_count), color: '#06b6d4' }
      ];
    }
  }
};
</script>

<style scoped>
.dashboard-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

.page-header h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 800;
  color: #0f172a;
}

.page-header p {
  margin: 4px 0 0 0;
  font-size: 12px;
  color: #64748b;
}

.header-date {
  background: #ffffff;
  padding: 6px 14px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  box-shadow: 0 2px 8px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
}

/* LƯỚI CARD THỐNG KÊ (GRID RESPONSIVE 8 CARDS) */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
}

.stat-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
  border-left: 5px solid #cbd5e1;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
}

.stat-card.border-blue { border-left-color: #2563eb; }
.stat-card.border-green { border-left-color: #10b981; }
.stat-card.border-amber { border-left-color: #f59e0b; }
.stat-card.border-indigo { border-left-color: #6366f1; }
.stat-card.border-emerald { border-left-color: #059669; }
.stat-card.border-sky { border-left-color: #0284c7; }
.stat-card.border-purple { border-left-color: #800080; }
.stat-card.border-cyan { border-left-color: #06b6d4; }

.stat-icon {
  width: 46px;
  height: 46px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  flex-shrink: 0;
}

.bg-blue { background: #eff6ff; }
.bg-green { background: #ecfdf5; }
.bg-amber { background: #fffbeb; }
.bg-indigo { background: #eef2ff; }
.bg-emerald { background: #ecfdf5; }
.bg-sky { background: #e0f2fe; }
.bg-purple { background: #f3e8ff; }
.bg-cyan { background: #ecfeff; }

.stat-content {
  display: flex;
  flex-direction: column;
}

.stat-title {
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
}

.stat-value {
  font-size: 22px;
  font-weight: 800;
  color: #0f172a;
  margin: 2px 0;
}

.stat-desc {
  font-size: 11px;
  color: #94a3b8;
}

/* KHU VỰC BIỂU ĐỒ (CHARTS GRID) */
.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
  gap: 20px;
}

.chart-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 18px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.03);
}

.badge-tag {
  background: #f1f5f9;
  color: #475569;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
}

.bar-chart-svg {
  width: 100%;
  height: 200px;
}

.donut-chart-container {
  display: flex;
  align-items: center;
  justify-content: space-around;
  padding: 10px 0;
  flex-wrap: wrap;
  gap: 16px;
}

.donut-svg {
  width: 140px;
  height: 140px;
}

.chart-legend {
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: 12px;
  color: #334155;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
}

.bg-emerald-dot { background-color: #10b981; }
.bg-slate-dot { background-color: #cbd5e1; }
.bg-blue-dot { background-color: #2563eb; }

/* TABLE CARD */
.table-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 18px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
}

.card-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
}

.card-title h3 {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.view-more {
  font-size: 12px;
  color: #2563eb;
  text-decoration: none;
  font-weight: 600;
}

.table-responsive {
  width: 100%;
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 13px;
}

.data-table th {
  background: #f8fafc;
  padding: 10px 12px;
  color: #475569;
  font-weight: 700;
  border-bottom: 2px solid #e2e8f0;
  white-space: nowrap;
}

.data-table td {
  padding: 10px 12px;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
}

.company-name { color: #0f172a; }
.tax-code { background: #f1f5f9; color: #2563eb; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 11px; }
.text-center { text-align: center; }
.text-muted { color: #94a3b8; }
</style>