<template>
  <div>
    <!-- Nút bật/tắt Menu điều khiển trên Mobile (Cố định góc trên bên trái) -->
    <button class="mobile-toggle-btn" @click="isOpen = !isOpen">
      <span v-if="!isOpen">🗺️ Đổi lớp bản đồ ▾</span>
      <span v-else>✕ Đóng menu ▴</span>
    </button>

    <!-- Overlay mờ nền khi mở menu trên Mobile -->
    <div v-if="isOpen" class="sidebar-overlay" @click="isOpen = false"></div>

    <div class="layer-sidebar" :class="{ 'is-open': isOpen }">
      <div class="sidebar-header">
        <h3>🗺️ Quản lý Lớp Bản đồ</h3>
        <button class="close-mobile-btn" @click="isOpen = false">✕</button>
      </div>

      <div class="layer-list">
        <!-- 🌟 MỤC TỔNG: BẬT/TẮT VÀ TĂNG GIẢM OPACITY TOÀN BỘ LAYERS -->
        <div class="layer-item master-layer">
          <div class="layer-title">
            <label>
              <input 
                type="checkbox" 
                v-model="masterLayer.visible" 
                @change="toggleAllLayers"
              />
              <b class="master-text">🌐 Tất cả các lớp</b>
            </label>
          </div>
          <div class="opacity-control" v-if="masterLayer.visible">
            <div class="opacity-label">
              <span>Độ trong suốt chung</span>
              <b>{{ Math.round(masterLayer.opacity * 100) }}%</b>
            </div>
            <input 
              type="range" 
              min="0" 
              max="1" 
              step="0.05" 
              v-model.number="masterLayer.opacity" 
              @input="changeActiveOpacity"
            />
          </div>
        </div>

        <div class="divider-container">
          <span class="divider-text">LỚP DỮ LIỆU CHUYÊN CƠ</span>
        </div>

        <!-- 🌟 DANH SÁCH LỚP DỮ LIỆU ĐƯỢC RENDER TỰ ĐỘNG BẰNG V-FOR -->
        <div 
          v-for="item in layerConfig" 
          :key="item.key" 
          class="layer-item"
          :class="{ 'is-active': layers[item.key].visible }"
        >
          <div class="layer-title">
            <label>
              <input 
                type="checkbox" 
                v-model="layers[item.key].visible" 
                @change="updateLayer(item.key)"
              />
              <span class="layer-badge" :style="{ backgroundColor: item.badgeColor }"></span>
              <b class="layer-name" :style="{ color: item.textColor || '#0f172a' }">
                {{ item.icon }} {{ item.label }}
              </b>
            </label>
          </div>

          <div class="opacity-control" v-if="layers[item.key].visible">
            <div class="opacity-label">
              <span>Độ rõ hiển thị</span>
              <b>{{ Math.round(layers[item.key].opacity * 100) }}%</b>
            </div>
            <input 
              type="range" 
              min="0" 
              max="1" 
              step="0.05" 
              v-model.number="layers[item.key].opacity" 
              @input="updateLayer(item.key)"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'LayerControlSidebar',
  data() {
    return {
      isOpen: false, // Trạng thái đóng/mở sidebar trên mobile
      
      // Cấu hình Nút Tổng Master
      masterLayer: {
        visible: true,
        opacity: 0.7
      },

      // Danh mục hiển thị và màu sắc nhận diện các Layer
      layerConfig: [
        { key: 'plots', label: 'Lô đất quy hoạch', icon: '🏢', badgeColor: '#b45309', textColor: '#b45309' },
        { key: 'factories', label: 'Nhà xưởng KCN', icon: '🏭', badgeColor: '#166534', textColor: '#166534' },
        { key: 'wastewater', label: 'Hố ga Nước thải', icon: '🧪', badgeColor: '#800080', textColor: '#800080' },
        { key: 'rainwater', label: 'Hố ga Nước mưa', icon: '🌧️', badgeColor: '#0284c7', textColor: '#0284c7' },
        { key: 'roads', label: 'Đường giao thông', icon: '🛣️', badgeColor: '#f59e0b', textColor: '#d97706' },
        { key: 'greenery', label: 'Cây xanh & Thảm cỏ', icon: '🌳', badgeColor: '#16a34a', textColor: '#15803d' },
        { key: 'waterBodies', label: 'Mặt nước & Hồ điều hòa', icon: '🌊', badgeColor: '#0284c7', textColor: '#0369a1' }
      ],

      // Trạng thái Bật/Tắt và Độ rõ (Opacity) mặc định
      layers: {
        plots: { visible: true, opacity: 0.35 },
        factories: { visible: true, opacity: 0.6 },
        wastewater: { visible: true, opacity: 0.9 },
        rainwater: { visible: true, opacity: 0.9 },
        roads: { visible: true, opacity: 0.85 },
        greenery: { visible: true, opacity: 0.5 },    
        waterBodies: { visible: true, opacity: 0.6 }
      }
    };
  },
  methods: {
    // 1. CẬP NHẬT TỪNG LỚP CON RIÊNG LẺ
    updateLayer(layerName) {
      this.$emit('change-layer', {
        name: layerName,
        config: { ...this.layers[layerName] }
      });
      this.syncMasterCheckbox();
    },

    // 2. BẬT / TẮT TOÀN BỘ LỚP KHI TÍCH/BỎ TÍCH NÚT TỔNG
    toggleAllLayers() {
      const isVisible = this.masterLayer.visible;

      Object.keys(this.layers).forEach(key => {
        this.layers[key].visible = isVisible;
        this.$emit('change-layer', {
          name: key,
          config: { ...this.layers[key] }
        });
      });
    },

    // 3. THAY ĐỔI OPACITY TỔNG
    changeActiveOpacity() {
      const newOpacity = this.masterLayer.opacity;

      Object.keys(this.layers).forEach(key => {
        if (this.layers[key].visible) {
          this.layers[key].opacity = newOpacity;
          this.$emit('change-layer', {
            name: key,
            config: { ...this.layers[key] }
          });
        }
      });
    },

    // 4. LOGIC ĐỒNG BỘ TRẠNG THÁI NÚT TỔNG (MASTER CHECKBOX)
    syncMasterCheckbox() {
      const allChildLayersVisible = Object.values(this.layers).every(layer => layer.visible);
      this.masterLayer.visible = allChildLayersVisible;
    }
  }
};
</script>

<style scoped>
/* PC / Desktop Style */
.mobile-toggle-btn, .close-mobile-btn, .sidebar-overlay {
  display: none;
}

.layer-sidebar {
  width: 290px;
  height: 100%;
  background: #ffffff;
  box-shadow: 2px 0 12px rgba(0,0,0,0.08);
  z-index: 1000;
  display: flex;
  flex-direction: column;
  user-select: none;
}

.sidebar-header {
  padding: 16px 20px;
  background: #0f172a;
  color: #ffffff;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.sidebar-header h3 {
  margin: 0;
  font-size: 15px;
  font-weight: 800;
  letter-spacing: 0.3px;
}

.layer-list {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

/* Nổi bật mục Master Layer */
.layer-item.master-layer {
  background: #eff6ff;
  border: 1.5px solid #3b82f6;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.12);
}

.master-text {
  color: #1d4ed8;
  font-size: 13.5px;
}

.divider-container {
  display: flex;
  align-items: center;
  margin: 6px 0;
}

.divider-text {
  font-size: 10px;
  font-weight: 800;
  color: #94a3b8;
  letter-spacing: 0.8px;
}

.layer-item {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 12px;
  transition: all 0.2s ease;
}

.layer-item:hover {
  border-color: #cbd5e1;
  box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}

.layer-item.is-active {
  background: #ffffff;
}

.layer-title label {
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
}

.layer-badge {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
  flex-shrink: 0;
}

.layer-name {
  font-size: 13px;
}

/* CONTROL OPACITY STYLING */
.opacity-control {
  margin-top: 10px;
  padding-top: 8px;
  border-top: 1px dashed #e2e8f0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.opacity-label {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 11px;
  color: #64748b;
}

.opacity-control input[type="range"] {
  width: 100%;
  height: 5px;
  border-radius: 3px;
  accent-color: #2563eb;
  cursor: pointer;
}

/* 📱 RESPONSIVE MOBILE STYLE */
@media (max-width: 768px) {
  .mobile-toggle-btn {
    display: flex;
    align-items: center;
    position: absolute;
    top: 72px;
    left: 12px;
    z-index: 1001;
    background: #0f172a;
    color: #38bdf8;
    border: 1px solid #38bdf8;
    padding: 7px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    cursor: pointer;
  }

  .close-mobile-btn {
    display: block;
    background: transparent;
    border: none;
    color: #94a3b8;
    font-size: 16px;
    cursor: pointer;
  }

  .sidebar-overlay {
    display: block;
    position: fixed;
    top: 60px; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0);
    backdrop-filter: blur(0px);
    z-index: 1001;
  }

  .layer-sidebar {
    position: fixed;
    top: 60px;
    left: 0;
    right: 0;
    width: 100vw;
    height: auto;
    max-height: 0;
    overflow: hidden;
    background: #ffffff;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    z-index: 1002;
    transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 0 0 16px 16px;
  }

  .layer-sidebar.is-open {
    max-height: 220px;
  }

  .sidebar-header {
    padding: 10px 16px;
    background: #0f172a;
  }

  .divider-container {
    display: none;
  }

  .layer-list {
    display: flex;
    flex-direction: row;
    gap: 10px;
    padding: 12px 16px;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    scroll-snap-type: x mandatory;
  }

  .layer-list::-webkit-scrollbar {
    display: none;
  }

  .layer-item {
    flex: 0 0 180px;
    margin-bottom: 0;
    padding: 10px;
    scroll-snap-align: start;
  }

  .layer-title label {
    font-size: 12px;
    white-space: nowrap;
  }
}
</style>