<template>
  <div class="webgis-page">
    <!-- 1. THANH NAVIGATION BAR NẰM NGANG TRÊN CÙNG -->
    <NavigationBar />

    <!-- 2. KHUNG NỘI DUNG CHÍNH (SIDEBAR + MAP) -->
    <div class="webgis-content">
      <!-- SIDEBAR ĐIỀU KHIỂN LAYER -->
      <LayerControlSidebar @change-layer="handleLayerChange" />

      <!-- KHUNG BẢN ĐỒ -->
      <div id="map" class="map-container">
        <!-- Nút bấm Định vị góc dưới bên phải màn hình -->
        <button class="locate-btn" @click="getCurrentLocation" title="Vị trí của tôi">
          🎯
        </button>
      </div>

      <!-- PANEL THÔNG TIN DOANH NGHIỆP KHI CLICK VÀO XƯỞNG -->
      <div v-if="selectedFactory" class="factory-info-panel">
        <div class="panel-header">
          <div>
            <span class="badge" :class="selectedFactory.status">
              {{ formatStatus(selectedFactory.status) }}
            </span>
            <h2 class="factory-code">Mã xưởng: {{ selectedFactory.code }}</h2>
          </div>
          <button class="close-btn" @click="selectedFactory = null">✕</button>
        </div>

        <div class="panel-body">
          <p><strong>Diện tích:</strong> {{ selectedFactory.area }} m²</p>
          <hr />
          <!-- NÚT CHỈ ĐƯỜNG TỚI NHÀ XƯỞNG -->
          <a 
            :href="getGoogleMapsUrl(selectedFactory.lat, selectedFactory.lng)" 
            target="_blank" 
            class="directions-btn-block"
          >
            🧭 Chỉ đường tới nhà xưởng này
          </a>
          <div>
            <h3>🏢 {{ selectedFactory.company_name }}</h3>
            <p><strong>Ngành nghề:</strong> {{ selectedFactory.company_industry || 'Đang cập nhật' }}</p>
            <p>
              <strong>SĐT:</strong> 
              <a :href="'tel:' + selectedFactory.company_phone" class="phone-link">{{ selectedFactory.company_phone || 'N/A' }}</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import L from 'leaflet';
import NavigationBar from '@/Components/NavigationBar.vue';
import LayerControlSidebar from '@/Components/LayerControlSidebar.vue';
import centerOfMass from '@turf/center-of-mass';
import pointOnFeature from '@turf/point-on-feature';

// Bảng tham chiếu lưu trữ các layer GeoJSON
const leafletLayers = {
  factories: null,
  wastewater: null,
  rainwater: null,
  plots: null,
  roads: null,
  greenery: null,
  waterBodies: null
};

export default {
  components: {
    NavigationBar,
    LayerControlSidebar
  },
  props: {
    factoriesData: Object,
    wastewaterManholesData: Object,
    rainwaterManholesData: Object,
    roadsData: Object,
    plotsData: Object,
    greeneryData: Object,  
    waterBodiesData: Object
  },
  data() {
    return {
      map: null,
      selectedFactory: null,
      roadsGeoJsonLayer: null,
      plotsGeoJsonLayer: null,
      factoriesGeoJsonLayer: null,
      greeneryGeoJsonLayer: null,
      waterGeoJsonLayer: null,
      currentLocationMarker: null,

      greeneryLabelMarkers: [],
      waterLabelMarkers: []
    };
  },
  mounted() {
    this.initMap();
  },
  methods: {
    initMap() {
      this.map = L.map('map', {
        zoomControl: false,
        preferCanvas: true,
        wheelDebounceTime: 40,
        wheelPxPerZoomLevel: 80
      }).setView([21.246624, 106.178020], 16);

      L.control.zoom({ position: 'bottomright' }).addTo(this.map);

      const tileOptions = {
        maxZoom: 20,
        maxNativeZoom: 19,
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        updateWhenIdle: true,
        updateWhenZooming: false,
        keepBuffer: 3
      };

      const googleSatellite = L.tileLayer('http://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        ...tileOptions,
        attribution: 'Google Satellite'
      });

      const googleSatelliteOnly = L.tileLayer('http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
        ...tileOptions,
        attribution: 'Google Satellite Clean'
      });

      const googleStreets = L.tileLayer('http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
        ...tileOptions,
        attribution: 'Google Streets'
      });

      googleSatelliteOnly.addTo(this.map);

      const baseMaps = {
        "🛰️ Ảnh vệ tinh": googleSatellite,
        "🛰️ Vệ tinh (Không nhãn)": googleSatelliteOnly,
        "🗺️ Bản đồ Đường phố": googleStreets
      };

      L.control.layers(baseMaps, null, {
        position: 'topright',
        collapsed: true
      }).addTo(this.map);

      this.addMapLegend();

      this.map.on('zoomend', () => {
        this.checkAllLabelsVisibility();
      });

      this.drawPlots();
      this.drawGreenery();
      this.drawWaterBodies();
      this.drawRoads();
      this.drawFactories();
      this.drawWastewater();
      this.drawRainwater();

      this.bringManholesToFront();
      this.checkAllLabelsVisibility();
    },

    bringManholesToFront() {
      if (leafletLayers.wastewater) leafletLayers.wastewater.bringToFront();
      if (leafletLayers.rainwater) leafletLayers.rainwater.bringToFront();
    },

    drawPlots() {
      if (!this.plotsData || !this.plotsData.features.length) return;

      leafletLayers.plots = L.geoJSON(this.plotsData, {
        interactive: true,
        style: () => ({
          fillColor: '#b45309',
          fillOpacity: 0.35,
          color: '#b45309',
          weight: 2,
          dashArray: '4, 4'
        }),
        onEachFeature: (feature, layer) => {
          const p = feature.properties;
          if (p.code) {
            layer.bindTooltip(p.code, {
              permanent: true,
              direction: 'center',
              className: 'plot-label'
            });
          }

          layer.bindPopup(`
            <div style="font-family: sans-serif; font-size: 13px; min-width: 180px;">
              <h4 style="margin: 0 0 6px 0; color: #b45309;">🗺️ Lô đất: ${p.code}</h4>
              <p style="margin: 3px 0;"><b>Diện tích:</b> ${p.total_area ? Number(p.total_area).toLocaleString('vi-VN') + ' m²' : '—'}</p>
              <p style="margin: 3px 0;"><b>Mô tả / Trạng thái:</b> ${p.description || 'Chưa có thông tin'}</p>
            </div>
          `);

          layer.on({
            mouseover: (e) => { e.target.setStyle({ fillOpacity: 0.6, weight: 3 }); },
            mouseout: (e) => { e.target.setStyle({ fillOpacity: 0.35, weight: 2 }); }
          });
        }
      }).addTo(this.map);

      this.plotsGeoJsonLayer = leafletLayers.plots;
    },

    drawGreenery() {
      if (!this.greeneryData || !this.greeneryData.features) return;
      this.greeneryLabelMarkers = [];

      leafletLayers.greenery = L.geoJSON(this.greeneryData, {
        interactive: true,
        style: {
          fillColor: '#22c55e',
          fillOpacity: 0.5,
          color: '#15803d',
          weight: 1.5
        },
        onEachFeature: (feature, layer) => {
          const p = feature.properties;
          if (p.code) {
            this.addPolygonLabel(feature, `🌳 ${p.code}`, 'greenery-label');
          }
          layer.bindPopup(`
            <div style="font-size: 13px; min-width: 170px;">
              <b style="color: #15803d;">🌳 Vùng Cây Xanh: ${p.code}</b><br>
              <b>Diện tích:</b> ${p.area ? Number(p.area).toLocaleString('vi-VN') + ' m²' : '—'}<br>
              <b>Mô tả:</b> ${p.description || 'Chưa cập nhật'}
            </div>
          `);
        }
      }).addTo(this.map);

      this.greeneryGeoJsonLayer = leafletLayers.greenery;
    },

    drawWaterBodies() {
      if (!this.waterBodiesData || !this.waterBodiesData.features) return;
      this.waterLabelMarkers = [];

      leafletLayers.waterBodies = L.geoJSON(this.waterBodiesData, {
        interactive: true,
        style: {
          fillColor: '#0284c7',
          fillOpacity: 0.6,
          color: '#0369a1',
          weight: 1.5
        },
        onEachFeature: (feature, layer) => {
          const p = feature.properties;
          if (p.code) {
            this.addPolygonLabel(feature, `🌊 ${p.code}`, 'water-label');
          }
          layer.bindPopup(`
            <div style="font-size: 13px; min-width: 170px;">
              <b style="color: #0284c7;">🌊 Mặt Nước / Hồ: ${p.code}</b><br>
              <b>Diện tích:</b> ${p.area ? Number(p.area).toLocaleString('vi-VN') + ' m²' : '—'}<br>
              <b>Ghi chú:</b> ${p.description || 'Chưa cập nhật'}
            </div>
          `);
        }
      }).addTo(this.map);

      this.waterGeoJsonLayer = leafletLayers.waterBodies;
    },

    drawRoads() {
      if (!this.roadsData || !this.roadsData.features.length) return;

      leafletLayers.roads = L.geoJSON(this.roadsData, {
        interactive: true,
        style: {
          color: '#FFE082',
          weight: 5,
          opacity: 0.85,
          fillOpacity: 0.85
        },
        onEachFeature: (feature, layer) => {
          const p = feature.properties;
          if (p.name) {
            layer.bindTooltip(p.name, {
              permanent: true,
              direction: 'center',
              className: 'street-name-sign'
            });
          }
          layer.bindPopup(`<b>Đường: ${p.name}</b><br>Lộ giới: ${p.right_of_way || '—'}m`);
        }
      }).addTo(this.map);

      this.roadsGeoJsonLayer = leafletLayers.roads;
    },

    drawFactories() {
      if (!this.factoriesData || !this.factoriesData.features) return;

      leafletLayers.factories = L.geoJSON(this.factoriesData, {
        interactive: true,
        style: (feature) => {
          const p = feature.properties;
          if (p.status === 'rented' && (p.company_color || p.color)) {
            const customColor = p.company_color || p.color;
            return {
              fillColor: customColor,
              color: customColor,
              fillOpacity: 0.65,
              weight: 2
            };
          }
          return this.getPolygonStyle(p.status, 0.6);
        },
        onEachFeature: (feature, layer) => {
          const p = feature.properties;
          const center = layer.getBounds().getCenter();
          p.lat = center.lat;
          p.lng = center.lng;

          if (p.code) {
            layer.bindTooltip(`🏭 ${p.code}`, {
              permanent: true,
              direction: 'center',
              className: 'factory-label'
            });
          }

          layer.on('click', (e) => {
            L.DomEvent.stopPropagation(e);
            this.selectedFactory = p; 
          });

          layer.on({
            mouseover: (e) => { e.target.setStyle({ fillOpacity: 0.8, weight: 3 }); },
            mouseout: (e) => { e.target.setStyle({ fillOpacity: 0.6, weight: 2 }); }
          });
        }
      }).addTo(this.map);

      this.factoriesGeoJsonLayer = leafletLayers.factories;
    },

    drawWastewater() {
      if (!this.wastewaterManholesData || !this.wastewaterManholesData.features) return;

      leafletLayers.wastewater = L.geoJSON(this.wastewaterManholesData, {
        interactive: true,
        pointToLayer: (feature, latlng) => {
          return L.circleMarker(latlng, {
            radius: 7,
            fillColor: '#800080',
            color: '#ffffff',
            weight: 1,
            opacity: 0.9,
            fillOpacity: 0.9
          });
        },
        onEachFeature: (feature, layer) => {
          const props = feature.properties;
          const lat = layer.getLatLng().lat;
          const lng = layer.getLatLng().lng;

          layer.bindPopup(`
            <div style="font-size: 13px; min-width: 180px;">
              <b style="color: #800080;">🧪 Hố Ga Nước Thải</b><br>
              <b>Mã:</b> ${props.code}<br>
              <b>DN:</b> ${props.company_name || 'Chưa gắn'}<br>
              <b>Độ sâu:</b> ${props.depth || 'N/A'} m<br>
              <hr style="margin: 6px 0; border: none; border-top: 1px dashed #ccc;">
              <a href="${this.getGoogleMapsUrl(lat, lng)}" target="_blank" class="directions-btn">
                🧭 Chỉ đường (Google Maps)
              </a>
            </div>
          `);
        }
      }).addTo(this.map);
    },

    drawRainwater() {
      if (!this.rainwaterManholesData || !this.rainwaterManholesData.features) return;

      leafletLayers.rainwater = L.geoJSON(this.rainwaterManholesData, {
        interactive: true,
        pointToLayer: (feature, latlng) => {
          return L.circleMarker(latlng, {
            radius: 6,
            fillColor: '#007bff',
            color: '#ffffff',
            weight: 1,
            opacity: 0.9,
            fillOpacity: 0.9
          });
        },
        onEachFeature: (feature, layer) => {
          const props = feature.properties;
          const lat = layer.getLatLng().lat;
          const lng = layer.getLatLng().lng;

          layer.bindPopup(`
            <div style="font-size: 13px; min-width: 180px;">
              <b style="color: #007bff;">🌧️ Hố Ga Nước Mưa</b><br>
              <b>Mã:</b> ${props.code}<br>
              <b>DN:</b> ${props.company_name || 'Chưa gắn'}<br>
              <b>Nắp:</b> ${props.cover_material || 'N/A'}<br>
              <hr style="margin: 6px 0; border: none; border-top: 1px dashed #ccc;">
              <a href="${this.getGoogleMapsUrl(lat, lng)}" target="_blank" class="directions-btn">
                🧭 Chỉ đường (Google Maps)
              </a>
            </div>
          `);
        }
      }).addTo(this.map);
    },

    addMapLegend() {
      const legend = L.control({ position: 'bottomleft' });

      legend.onAdd = () => {
        const div = L.DomUtil.create('div', 'custom-map-legend');
        div.innerHTML = `
          <div class="legend-header">📌 CHÚ THÍCH BẢN ĐỒ</div>
          <div class="legend-list">
            <div class="legend-item">
              <span class="legend-point rainwater-point"></span>
              <span class="legend-text">Hố ga nước mưa</span>
            </div>
            <div class="legend-item">
              <span class="legend-point wastewater-point"></span>
              <span class="legend-text">Hố ga nước thải</span>
            </div>
          </div>
        `;

        L.DomEvent.disableClickPropagation(div);
        L.DomEvent.disableScrollPropagation(div);

        return div;
      };

      legend.addTo(this.map);
    },

    checkAllLabelsVisibility() {
      if (!this.map) return;
      const currentZoom = this.map.getZoom();

      const layersToCheck = [
        { layer: this.roadsGeoJsonLayer, minZoom: 16 },
        { layer: this.plotsGeoJsonLayer, minZoom: 16 },
        { layer: this.factoriesGeoJsonLayer, minZoom: 18 }
      ];

      layersToCheck.forEach(item => {
        if (!item.layer) return;
        const show = currentZoom >= item.minZoom;
        item.layer.eachLayer(l => {
          if (l.getTooltip()) {
            if (show) l.openTooltip();
            else l.closeTooltip();
          }
        });
      });

      const MIN_ZOOM = 16;
      const show = currentZoom >= MIN_ZOOM;

      this.greeneryLabelMarkers.forEach(marker => {
        if (marker && marker.getTooltip()) {
          if (show) marker.openTooltip();
          else marker.closeTooltip();
        }
      });

      this.waterLabelMarkers.forEach(marker => {
        if (marker && marker.getTooltip()) {
          if (show) marker.openTooltip();
          else marker.closeTooltip();
        }
      });
    },

    handleLayerChange({ name, config }) {
      const layer = leafletLayers[name];
      if (!layer) return;

      if (config.visible) {
        if (!this.map.hasLayer(layer)) {
          this.map.addLayer(layer);
        }
      } else {
        if (this.map.hasLayer(layer)) {
          this.map.removeLayer(layer);
        }
        return;
      }

      layer.eachLayer((subLayer) => {
        if (subLayer.setStyle) {
          subLayer.setStyle({
            fillOpacity: config.opacity,
            opacity: config.opacity
          });
        }
      });

      this.bringManholesToFront();
    },

    getPolygonStyle(status, opacity = 0.6) {
      const baseStyle = { fillOpacity: opacity, opacity: opacity, weight: 2 };
      switch (status) {
        case 'rented': return { ...baseStyle, color: '#dc3545', fillColor: '#dc3545' };
        case 'available': return { ...baseStyle, color: '#28a745', fillColor: '#28a745' };
        case 'under_construction': return { ...baseStyle, color: '#ffc107', fillColor: '#ffc107' };
        default: return { ...baseStyle, color: '#6c757d', fillColor: '#6c757d' };
      }
    },

    formatStatus(status) {
      if (status === 'rented') return 'Đang hoạt động';
      if (status === 'available') return 'Đang trống / Cần đầu tư';
      return 'Đang xây dựng';
    },

    getCurrentLocation() {
      if (!navigator.geolocation) {
        alert("Trình duyệt của anh không hỗ trợ tính năng định vị GPS.");
        return;
      }

      navigator.geolocation.getCurrentPosition(
        (position) => {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;

          if (this.currentLocationMarker) {
            this.map.removeLayer(this.currentLocationMarker);
          }

          this.currentLocationMarker = L.circleMarker([lat, lng], {
            radius: 9,
            fillColor: '#007bff',
            color: '#ffffff',
            weight: 3,
            opacity: 1,
            fillOpacity: 1
          }).addTo(this.map);

          this.currentLocationMarker.bindPopup("<b>📍 Vị trí hiện tại của anh</b>").openPopup();
          this.map.setView([lat, lng], 17);
        },
        (error) => {
          switch (error.code) {
            case error.PERMISSION_DENIED: alert("Anh chưa cho phép truy cập vị trí GPS."); break;
            case error.POSITION_UNAVAILABLE: alert("Không thể xác định vị trí GPS."); break;
            case error.TIMEOUT: alert("Thời gian yêu cầu định vị quá hạn."); break;
            default: alert("Lỗi định vị không xác định."); break;
          }
        },
        { enableHighAccuracy: true }
      );
    },

    addPolygonLabel(feature, labelText, cssClass) {
      if (!feature || !feature.geometry || !labelText) return null;

      try {
        const pt = pointOnFeature(feature);
        const lat = pt.geometry.coordinates[1];
        const lng = pt.geometry.coordinates[0];

        const labelMarker = L.circleMarker([lat, lng], {
          interactive: false,
          radius: 0,
          opacity: 0,
          fillOpacity: 0
        }).addTo(this.map);

        labelMarker.bindTooltip(labelText, {
          permanent: true,
          direction: 'center',
          className: cssClass
        });

        if (cssClass === 'greenery-label') {
          this.greeneryLabelMarkers.push(labelMarker);
        } else if (cssClass === 'water-label') {
          this.waterLabelMarkers.push(labelMarker);
        }

        return labelMarker;
      } catch (error) {
        console.warn('Không thể tính trọng tâm Polygon bằng Turf:', error);
        return null;
      }
    },

    getGoogleMapsUrl(lat, lng) {
      return `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
    }
  }
};
</script>

<style scoped>
.webgis-page {
  display: flex;
  flex-direction: column;
  height: 100vh;
  height: 100dvh; /* Co giãn chuẩn theo thanh trình duyệt di động */
  width: 100vw;
  overflow: hidden;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

.webgis-content {
  display: flex;
  flex: 1;
  position: relative;
  overflow: hidden;
}

.map-container {
  flex: 1;
  height: 100%;
  width: 100%;
  z-index: 1;
}

.factory-info-panel {
  position: absolute;
  top: 20px;
  right: 20px;
  width: 320px;
  max-height: calc(100dvh - 100px);
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.2);
  z-index: 1000;
  padding: 18px;
  overflow-y: auto;
}

.panel-header { display: flex; justify-content: space-between; align-items: flex-start; }
.factory-code { margin: 5px 0 0 0; font-size: 16px; }
.close-btn { border: none; background: #f1f5f9; padding: 6px 10px; border-radius: 50%; cursor: pointer; font-weight: bold; }
.badge { display: inline-block; padding: 3px 8px; border-radius: 4px; color: #fff; font-size: 11px; font-weight: bold; }
.badge.rented { background-color: #dc3545; }
.badge.available { background-color: #28a745; }
.badge.under_construction { background-color: #ffc107; color: #000; }
.phone-link { color: #0d6efd; text-decoration: none; font-weight: bold; }

/* 🎯 NÚT ĐỊNH VỊ CÓ TÍNH TỚI VÙNG AN TOÀN SAFE-AREA */
.locate-btn {
  position: absolute;
  bottom: calc(90px + env(safe-area-inset-bottom, 0px));
  right: 12px;
  z-index: 1000;
  width: 42px;
  height: 42px;
  background: #ffffff;
  border: 2px solid rgba(0, 0, 0, 0.2);
  border-radius: 50%;
  font-size: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.3);
  transition: all 0.2s ease;
}

.locate-btn:hover { background: #f4f4f4; transform: scale(1.05); }
.locate-btn:active { transform: scale(0.95); }

@media (max-width: 768px) {
  .factory-info-panel {
    top: auto;
    bottom: 0;
    left: 0;
    right: 0;
    width: 100%;
    max-height: 60vh;
    border-radius: 16px 16px 0 0;
    box-sizing: border-box;
    /* Dán padding-bottom cẩn thận để không bị thanh Home Indicator che */
    padding-bottom: calc(18px + env(safe-area-inset-bottom, 0px));
  }
}

:deep(.directions-btn) {
  display: inline-block;
  width: 100%;
  text-align: center;
  padding: 6px 10px;
  background-color: #10b981;
  color: #ffffff !important;
  text-decoration: none;
  font-weight: bold;
  font-size: 12px;
  border-radius: 6px;
  box-sizing: border-box;
}

.directions-btn-block {
  display: block;
  text-align: center;
  padding: 10px;
  background-color: #10b981;
  color: #ffffff !important;
  text-decoration: none;
  font-weight: bold;
  font-size: 13px;
  border-radius: 8px;
  margin: 10px 0;
  box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3);
}

/* STYLES CHO CÁC LOẠI NHÃN TRÊN BẢN ĐỒ */
:deep(.greenery-label) {
  background-color: rgba(255, 255, 255, 0.9) !important; color: #15803d !important;
  font-weight: 700 !important; font-size: 10px !important; padding: 2px 5px !important;
  border-radius: 4px !important; border: 1px solid #22c55e !important; white-space: nowrap !important;
  pointer-events: none !important;
}
:deep(.greenery-label::before) { display: none !important; }

:deep(.water-label) {
  background-color: rgba(255, 255, 255, 0.9) !important; color: #0369a1 !important;
  font-weight: 700 !important; font-size: 10px !important; padding: 2px 5px !important;
  border-radius: 4px !important; border: 1px solid #0284c7 !important; white-space: nowrap !important;
  pointer-events: none !important;
}
:deep(.water-label::before) { display: none !important; }

:deep(.factory-label) {
  background-color: rgba(255, 255, 255, 0.9) !important; color: #166534 !important;
  font-weight: 700 !important; font-size: 11px !important; padding: 2px 6px !important;
  border-radius: 4px !important; border: 1px solid #22c55e !important; white-space: nowrap !important;
  pointer-events: none !important;
}
:deep(.factory-label::before) { display: none !important; }

:deep(.street-name-sign) {
  background-color: #0284c7 !important;
  color: #ffffff !important;
  font-weight: 700 !important;
  font-size: 8px !important;
  padding: 3px 8px !important;
  border-radius: 4px !important;
  border: 1.5px solid #ffffff !important;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3) !important;
  white-space: nowrap !important;
  text-transform: uppercase !important;
  pointer-events: none !important;
}
:deep(.street-name-sign::before) { display: none !important; }

:deep(.plot-label) {
  background-color: rgba(255, 255, 255, 0.85) !important;
  color: #0f172a !important;
  font-weight: 700 !important;
  font-size: 11px !important;
  padding: 2px 6px !important;
  border-radius: 4px !important;
  border: 1px solid #94a3b8 !important;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2) !important;
  white-space: nowrap !important;
  pointer-events: none !important;
}
</style>

<style>
/* 🌟 TOÀN BỘ CSS GLOBAL CHO CONTROL BẢN ĐỒ DƯỚI ĐÁY CÓ PADDING SAFE-AREA CHO IPHONE */
.leaflet-bottom.leaflet-left {
  z-index: 999 !important;
  margin-bottom: calc(15px + env(safe-area-inset-bottom, 0px)) !important;
  margin-left: 12px !important;
}

.leaflet-bottom.leaflet-right {
  z-index: 999 !important;
  margin-bottom: calc(15px + env(safe-area-inset-bottom, 0px)) !important;
  margin-right: 12px !important;
}

.custom-map-legend {
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(6px) !important;
  padding: 8px 12px !important;
  border-radius: 8px !important;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
  border: 1px solid #cbd5e1 !important;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
  min-width: 140px !important;
  display: block !important;
  visibility: visible !important;
}

.custom-map-legend .legend-header {
  font-size: 10px !important;
  font-weight: 800 !important;
  color: #0f172a !important;
  margin-bottom: 6px !important;
  border-bottom: 1px solid #e2e8f0 !important;
  padding-bottom: 4px !important;
  letter-spacing: 0.5px !important;
  text-transform: uppercase !important;
}

.custom-map-legend .legend-list {
  display: flex !important;
  flex-direction: column !important;
  gap: 5px !important;
}

.custom-map-legend .legend-item {
  display: flex !important;
  align-items: center !important;
  gap: 6px !important;
}

.custom-map-legend .legend-text {
  font-size: 10px !important;
  font-weight: 600 !important;
  color: #334155 !important;
}

.custom-map-legend .rainwater-point {
  width: 10px !important; height: 10px !important; border-radius: 50% !important;
  background-color: #007bff !important; border: 2px solid #ffffff !important;
  box-shadow: 0 0 4px rgba(0, 0, 0, 0.3) !important; display: inline-block !important;
}

.custom-map-legend .wastewater-point {
  width: 10px !important; height: 10px !important; border-radius: 50% !important;
  background-color: #800080 !important; border: 2px solid #ffffff !important;
  box-shadow: 0 0 4px rgba(0, 0, 0, 0.3) !important; display: inline-block !important;
}

/* BASEMAP CONTROL STYLING */
.leaflet-control-layers-base label {
  display: flex !important; align-items: center !important; gap: 6px !important;
  font-size: 12px !important; font-weight: 700 !important; color: #0f172a !important;
  cursor: pointer !important; padding: 3px 0 !important;
}

.leaflet-control-layers {
  border-radius: 8px !important;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2) !important;
  border: 1px solid #cbd5e1 !important;
}
</style>