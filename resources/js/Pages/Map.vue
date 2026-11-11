<template>
  <div class="webgis-page">
    <!-- 1. THANH NAVIGATION BAR NẰM NGANG TRÊN CÙNG -->
    <NavigationBar
      :zones="zonesList"
      :current-zone-id="activeZoneId"
      @change-zone="handleZoneChange"
    />

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
        <!-- HEADER PANEL -->
        <div class="panel-header">
          <div class="header-left">
            <div class="header-badges">
              <span class="badge" :class="selectedFactory.status">
                {{ formatStatus(selectedFactory.status) }}
              </span>
              <span v-if="selectedFactory.plot_code" class="badge-plot">
                Lô {{ selectedFactory.plot_code }}
              </span>
            </div>
            <h3 class="factory-code">Xưởng: {{ selectedFactory.code }}</h3>
          </div>

          <!-- KHỐI NÚT THAO TÁC GÓC PHẢI -->
          <div class="header-actions">
            <a 
              v-if="selectedFactory.lat && selectedFactory.lng"
              :href="getGoogleMapsUrl(selectedFactory.lat, selectedFactory.lng)" 
              target="_blank" 
              class="icon-nav-btn"
              title="Chỉ đường Google Maps"
            >
              🧭
            </a>
            <button class="close-btn" @click="selectedFactory = null">✕</button>
          </div>
        </div>

        <!-- BODY PANEL -->
        <div class="panel-body">
          <!-- KHỐI DOANH NGHIỆP -->
          <div class="company-block">
            <div class="company-title-row">
              <span 
                class="color-indicator" 
                :style="{ backgroundColor: selectedFactory.company_color || '#2563eb' }"
              ></span>
              <h4 class="company-name">{{ selectedFactory.company_name || 'Đang trống / Chưa thuê' }}</h4>
            </div>

            <div class="meta-tags">
              <span class="tag tag-country">
                {{ getFlag(selectedFactory.company_country) }} {{ selectedFactory.company_country || 'Việt Nam' }}
              </span>
              <span v-if="selectedFactory.zone_name" class="tag tag-zone">
                🏭 {{ selectedFactory.zone_name }}
              </span>
            </div>
          </div>

          <!-- LƯỚI THÔNG TIN CHI TIẾT (COMPACT GRID) -->
          <div class="info-grid">
            <div class="info-item">
              <span class="label">Diện tích xưởng:</span>
              <span class="value text-highlight">{{ formatNumber(selectedFactory.area) }} m²</span>
            </div>

            <div class="info-item" v-if="selectedFactory.company_total_leased_area">
              <span class="label">Tổng đất thuê:</span>
              <span class="value">{{ formatNumber(selectedFactory.company_total_leased_area) }} m²</span>
            </div>

            <div class="info-item">
              <span class="label">Mã số thuế:</span>
              <span class="value font-mono">{{ selectedFactory.company_tax_code || '—' }}</span>
            </div>

            <div class="info-item" v-if="selectedFactory.company_irc_number && selectedFactory.company_irc_number !== '—'">
              <span class="label">Số GCNĐT (IRC):</span>
              <span class="value font-mono">{{ selectedFactory.company_irc_number }}</span>
            </div>

            <div class="info-item full-width" v-if="selectedFactory.company_main_industry || selectedFactory.company_industry">
              <span class="label">Ngành nghề:</span>
              <span class="value">{{ selectedFactory.company_main_industry || selectedFactory.company_industry }}</span>
            </div>

            <div class="info-item full-width" v-if="selectedFactory.company_lease_start_date && selectedFactory.company_lease_end_date">
              <span class="label">Thời hạn thuê:</span>
              <span class="value">{{ selectedFactory.company_lease_start_date }} ➔ {{ selectedFactory.company_lease_end_date }}</span>
            </div>
          </div>

          <!-- LIÊN HỆ & GHI CHÚ -->
          <div class="contact-footer">
            <div class="contact-row">
              <a v-if="selectedFactory.company_phone && selectedFactory.company_phone !== '—'" :href="'tel:' + selectedFactory.company_phone" class="contact-link phone">
                📞 {{ selectedFactory.company_phone }}
              </a>
              <a v-if="selectedFactory.company_email && selectedFactory.company_email !== '—'" :href="'mailto:' + selectedFactory.company_email" class="contact-link email">
                ✉️ {{ selectedFactory.company_email }}
              </a>
            </div>
            <p v-if="selectedFactory.company_notes" class="notes-text">
              <b>Ghi chú:</b> {{ selectedFactory.company_notes }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import L from 'leaflet';
import 'leaflet-polylinedecorator';
import NavigationBar from '@/Components/NavigationBar.vue';
import LayerControlSidebar from '@/Components/LayerControlSidebar.vue';
import pointOnFeature from '@turf/point-on-feature';
// [UNUSED] import centerOfMass from '@turf/center-of-mass'; // Đã thay thế bằng pointOnFeature để luôn đảm bảo nhãn nằm trong đa giác

// Bảng tham chiếu các layer GeoJSON toàn cục của Leaflet
const leafletLayers = {
  boundaries: null,
  factories: null,
  wastewater: null,
  rainwater: null,
  plots: null,
  companyLands: null,
  roads: null,
  greenery: null,
  waterBodies: null,
  lightingSystem: null,
  surroundingLocations: null
};

// Cấu hình các Pane và thứ tự zIndex hiển thị chuẩn GIS
const PANE_CONFIGS = [
  { name: 'boundariesPane',     zIndex: 390 },
  { name: 'naturePane',         zIndex: 410 },
  { name: 'plotsPane',          zIndex: 420 },
  { name: 'companyLandsPane',   zIndex: 425 },
  { name: 'roadsPane',          zIndex: 430 },
  { name: 'pipesPane',          zIndex: 432 },
  { name: 'lightingCablesPane', zIndex: 435 },
  { name: 'factoriesPane',      zIndex: 440 },
  { name: 'pointsPane',         zIndex: 450 },
  { name: 'lightingPointsPane', zIndex: 460 },
  { name: 'surroundingPane',    zIndex: 550 }
];

export default {
  name: 'MapComponent',
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
    companyLandsData: Object,
    greeneryData: Object,  
    waterBodiesData: Object,
    boundariesData: Object,
    lightingCabinetsData: Object,
    lightingCablesData: Object,
    lightingPolesData: Object,
    surroundingLocationsData: Object,
    drainagePipesData: Object,
    zonesList: { type: Array, default: () => [] }
  },
  data() {
    return {
      map: null,
      selectedFactory: null,
      activeZoneId: '1',
      // [UNUSED] selectedZoneId: '1', // Trùng lặp với activeZoneId truyền vào NavigationBar

      // Tham chiếu các layer quản lý nhãn / tooltip
      roadsGeoJsonLayer: null,
      plotsGeoJsonLayer: null,
      companyLandsGeoJsonLayer: null,
      factoriesGeoJsonLayer: null,
      greeneryGeoJsonLayer: null,
      waterGeoJsonLayer: null,
      currentLocationMarker: null,

      greeneryLabelMarkers: [],
      waterLabelMarkers: [],
      zoomDebounceTimer: null,

      // Trạng thái bật/tắt layer do người dùng điều khiển ở Sidebar
      userLayerVisibility: {
        lightingSystem: false,
        wastewater: false,
        rainwater: false,
        companyLands: false,
        surroundingLocations: true
      }
    };
  },
  mounted() {
    this.initMap();
    if (this.zonesList && this.zonesList.length > 0) {
      this.activeZoneId = this.zonesList[0].id;
    }
  },
  beforeUnmount() {
    // Thu hồi tài nguyên và timer khi unmount component
    if (this.zoomDebounceTimer) {
      clearTimeout(this.zoomDebounceTimer);
    }
    if (this.map) {
      this.map.remove();
      this.map = null;
    }
  },
  methods: {
    // =========================================================================
    // 1. KHỞI TẠO BẢN ĐỒ & PANE QUẢN LÝ TẦNG LỚP
    // =========================================================================
    initMap() {
      const initialZone = this.zonesList && this.zonesList.length > 0 ? this.zonesList[0] : null;

      // Toạ độ mặc định (KCN Song Khê - Nội Hoàng, Bắc Giang)
      const centerLat = initialZone?.lat ? parseFloat(initialZone.lat) : 21.246624;
      const centerLng = initialZone?.lng ? parseFloat(initialZone.lng) : 106.178020;

      const isMobile = window.innerWidth <= 768;
      const baseZoom = initialZone?.default_zoom ? parseInt(initialZone.default_zoom) : 16;
      const initialZoom = isMobile ? Math.max(baseZoom - 1, 14) : baseZoom;

      this.map = L.map('map', {
        zoomControl: false,
        preferCanvas: true,
        wheelDebounceTime: 40,
        wheelPxPerZoomLevel: 80,
        markerZoomAnimation: false
      }).setView([centerLat, centerLng], initialZoom);

      L.control.zoom({ position: 'bottomright' }).addTo(this.map);

      // Thêm BaseMap vệ tinh Google
      const tileOptions = {
        maxZoom: 20,
        maxNativeZoom: 19,
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        updateWhenIdle: true,
        updateWhenZooming: false,
        keepBuffer: 3
      };

      const googleSatelliteOnly = L.tileLayer('http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
        ...tileOptions,
        attribution: 'Google Satellite Clean'
      });
      googleSatelliteOnly.addTo(this.map);

      L.control.layers({ "🛰️ Vệ tinh (Không nhãn)": googleSatelliteOnly }, null, {
        position: 'topright',
        collapsed: true
      }).addTo(this.map);

      this.addMapLegend();
      this.map.on('zoomend', this.handleMapZoomEnd);

      // Khởi tạo các Pane hiển thị theo cấu hình PANE_CONFIGS
      PANE_CONFIGS.forEach(pane => {
        this.map.createPane(pane.name);
        const paneEl = this.map.getPane(pane.name);
        paneEl.style.zIndex = pane.zIndex;
        paneEl.style.pointerEvents = 'none'; // Giải phóng click xuyên qua các khoảng trống
      });

      // Vẽ toàn bộ các lớp dữ liệu
      this.drawBoundaries();
      this.drawPlots();
      this.drawCompanyLands();
      this.drawLandscape('greenery');
      this.drawLandscape('waterBodies');
      this.drawRoads();
      this.drawFactories();
      this.drawWastewater();
      this.drawRainwater();
      this.drawLightingSystem();
      this.drawSurroundingLocations();

      if (leafletLayers.boundaries) leafletLayers.boundaries.bringToBack();
      this.bringManholesToFront();
      this.checkAllLabelsVisibility();
    },

    bringManholesToFront() {
      if (leafletLayers.wastewater) leafletLayers.wastewater.bringToFront();
      if (leafletLayers.rainwater) leafletLayers.rainwater.bringToFront();
    },

    // =========================================================================
    // 2. TÍNH TOÁN KÍCH THƯỚC ĐỘ PHÂN GIẢI MẶT ĐẤT (METERS -> PIXELS)
    // =========================================================================
    /**
     * Quy đổi kích thước thực tế (mét) sang kích thước hiển thị trên màn hình (pixels)
     */
    convertMetersToPixels(meters, minPx = 2, maxPx = 20) {
      if (!this.map || !meters) return minPx;
      const zoom = this.map.getZoom();
      const lat = this.map.getCenter().lat;
      const metersPerPixel = (156543.03392 * Math.cos((lat * Math.PI) / 180)) / Math.pow(2, zoom);
      const calcPx = meters / metersPerPixel;
      return Math.min(maxPx, Math.max(minPx, calcPx));
    },

    getWeightInPixels(widthInMeters) {
      return this.convertMetersToPixels(widthInMeters, 3, 40);
    },

    getPointRadiusInPixels(radiusInMeters, minPx = 2, maxPx = 6) {
      return this.convertMetersToPixels(radiusInMeters, minPx, maxPx);
    },

    getCabinetSizeInPixels(sizeInMeters = 1.8, minPx = 12, maxPx = 24) {
      return Math.round(this.convertMetersToPixels(sizeInMeters, minPx, maxPx));
    },

    getPipeWeightInPixels(feature) {
      if (!this.map) return 2.5;
      const p = feature.properties || {};
      let sizeInMeters = 0.35; // Mặc định D350

      if (p.cross_section === 'box' && p.width) {
        sizeInMeters = p.width / 1000;
      } else if (p.diameter) {
        sizeInMeters = p.diameter / 1000;
      }
      return this.convertMetersToPixels(sizeInMeters, 2.5, 14);
    },

    // =========================================================================
    // 3. XỬ LÝ SỰ KIỆN CO GIÃN THEO MỨC ZOOM
    // =========================================================================
    handleMapZoomEnd() {
      clearTimeout(this.zoomDebounceTimer);
      this.zoomDebounceTimer = setTimeout(() => {
        this.updateRoadsWidth();
        this.updateInfrastructureSizes();
        this.checkDetailLayersVisibility();
        this.checkAllLabelsVisibility();

        if (leafletLayers.surroundingLocations && this.map.hasLayer(leafletLayers.surroundingLocations)) {
          leafletLayers.surroundingLocations.eachLayer((layer) => {
            const tooltip = layer.getTooltip();
            if (tooltip && tooltip._container) tooltip.update();
          });
        }
      }, 50);
    },

    updateRoadsWidth() {
      if (!this.roadsGeoJsonLayer) return;
      this.roadsGeoJsonLayer.eachLayer((layer) => {
        if (layer.feature && layer.setStyle) {
          const widthInMeters = layer.feature.properties.road_width || layer.feature.properties.right_of_way || 10;
          layer.setStyle({ weight: this.getWeightInPixels(widthInMeters) });
        }
      });
      this.updateInfrastructureSizes();
    },

    updateInfrastructureSizes() {
      if (!this.map) return;

      const wastewaterR = this.getPointRadiusInPixels(3.0, 3.5, 9.0);
      const rainwaterR  = this.getPointRadiusInPixels(2.6, 3.0, 8.5);
      const poleR       = this.getPointRadiusInPixels(2.4, 3.0, 8.0);
      const cableWeight = this.getWeightInPixels(0.3);
      const cabSize     = this.getCabinetSizeInPixels(2.8, 12, 22);

      // Co giãn hệ thống Nước thải
      if (leafletLayers.wastewater) {
        leafletLayers.wastewater.eachLayer((subGroup) => {
          if (subGroup.eachLayer) {
            subGroup.eachLayer((layer) => {
              if (layer.setRadius) layer.setRadius(wastewaterR);
              if (layer.setStyle && layer.feature?.geometry?.type?.includes('Line')) {
                layer.setStyle({ weight: Math.max(3, this.getPipeWeightInPixels(layer.feature) + 1.5) });
              }
            });
          }
        });
      }

      // Co giãn hệ thống Nước mưa
      if (leafletLayers.rainwater) {
        leafletLayers.rainwater.eachLayer((subGroup) => {
          if (subGroup.eachLayer) {
            subGroup.eachLayer((layer) => {
              if (layer.setRadius) layer.setRadius(rainwaterR);
              if (layer.setStyle && layer.feature?.geometry?.type?.includes('Line')) {
                layer.setStyle({ weight: Math.max(3.5, this.getPipeWeightInPixels(layer.feature) + 2) });
              }
            });
          }
        });
      }

      // Co giãn hệ thống Chiếu sáng
      if (leafletLayers.lightingSystem) {
        leafletLayers.lightingSystem.eachLayer((subGroup) => {
          if (subGroup.eachLayer) {
            subGroup.eachLayer((l) => {
              if (l.setRadius) l.setRadius(poleR);
              if (l.setStyle && l.feature?.geometry?.type?.includes('Line')) {
                l.setStyle({ weight: Math.max(1, Math.min(2, cableWeight)) });
              }
              if (l.setIcon && l.feature?.geometry?.type === 'Point') {
                l.setIcon(L.divIcon({
                  className: 'custom-cabinet-marker',
                  html: `<div class="cabinet-icon-box" style="width: ${cabSize}px; height: ${cabSize}px; font-size: ${Math.round(cabSize * 0.55)}px;" title="${l.feature?.properties?.code || ''}">⚡</div>`,
                  iconSize: [cabSize, cabSize],
                  iconAnchor: [Math.round(cabSize / 2), Math.round(cabSize / 2)]
                }));
              }
            });
          }
        });
      }
    },

    handleZoneChange(zone) {
      if (!this.map || !zone) return;
      this.activeZoneId = zone.id;

      if (zone.lat && zone.lng) {
        this.map.flyTo([parseFloat(zone.lat), parseFloat(zone.lng)], zone.default_zoom || 15, {
          animate: true,
          duration: 1.5
        });
      }
    },

    // =========================================================================
    // 4. VẼ CÁC LỚP BẢN ĐỒ (LAYERS BUILDER)
    // =========================================================================
    drawBoundaries() {
      if (!this.map || !this.boundariesData) return;
      let geoData = this.boundariesData;
      if (typeof geoData === 'string') {
        try { geoData = JSON.parse(geoData); } catch (e) { return; }
      }
      if (!geoData.features?.length) return;

      leafletLayers.boundaries = L.geoJSON(geoData, {
        pane: 'boundariesPane',
        interactive: false,
        style: (feature) => ({
          pane: 'boundariesPane',
          color: feature.properties?.fill_color || '#e11d48',
          weight: 3.5,
          dashArray: '10, 6',
          opacity: 0.95
        })
      }).addTo(this.map);
    },

    drawPlots() {
      if (!this.plotsData?.features?.length) return;

      leafletLayers.plots = L.geoJSON(this.plotsData, {
        interactive: true,
        style: (feature) => {
          const p = feature.properties || {};
          const plotColor = p.color || (p.land_type === 'commercial' ? '#e11d48' : '#b45309');
          return {
            fillColor: plotColor,
            fillOpacity: 0.38,
            color: plotColor,
            weight: 1.5
          };
        },
        onEachFeature: (feature, layer) => {
          const p = feature.properties;
          if (p.code) {
            layer.bindTooltip(p.code, {
              permanent: true,
              direction: 'center',
              className: 'plot-label'
            });
          }

          const landTypeName = p.land_type === 'commercial' ? '🏬 Đất TMDV' : '🏭 Đất Công nghiệp';
          const plotColor = p.color || (p.land_type === 'commercial' ? '#e11d48' : '#b45309');

          layer.bindPopup(`
            <div style="font-family: sans-serif; font-size: 13px; min-width: 200px;">
              <h4 style="margin: 0 0 6px 0; color: ${plotColor};">🗺️ Lô đất: ${p.code}</h4>
              <p style="margin: 3px 0;"><b>Loại đất:</b> <span style="font-weight: 700; color: ${plotColor};">${landTypeName}</span></p>
              <p style="margin: 3px 0;"><b>Diện tích:</b> ${p.total_area ? Number(p.total_area).toLocaleString('vi-VN') + ' m²' : '—'}</p>
              <p style="margin: 3px 0;"><b>Mô tả:</b> ${p.description || 'Chưa có thông tin'}</p>
            </div>
          `);

          layer.on({
            mouseover: (e) => { e.target.setStyle({ fillOpacity: 0.65, weight: 3 }); },
            mouseout: (e) => { e.target.setStyle({ fillOpacity: 0.38, weight: 1.5 }); }
          });
        }
      }).addTo(this.map);

      this.plotsGeoJsonLayer = leafletLayers.plots;
    },

    drawCompanyLands() {
      const data = this.companyLandsData || this.$page?.props?.companyLandsData;
      if (!data?.features?.length) return;

      const landRenderer = L.svg({ pane: 'companyLandsPane' });

      leafletLayers.companyLands = L.geoJSON(data, {
        pane: 'companyLandsPane',
        renderer: landRenderer,
        interactive: true,
        style: (feature) => {
          const p = feature.properties || {};
          const compColor = '#003099';
          return {
            pane: 'companyLandsPane',
            fillColor: compColor,
            fillOpacity: 0.55,
            color: compColor,
            weight: 2,
            dashArray: p.status === 'under_construction' ? '5, 5' : null,
            interactive: true
          };
        },
        onEachFeature: (feature, layer) => {
          const p = feature.properties;
          if (p.land_code) {
            layer.bindTooltip(`🏢 ${p.company_name || ''}`, {
              pane: 'tooltipPane',
              permanent: true,
              direction: 'center',
              className: 'company-land-label',
              interactive: false
            });
          }

          const leaseEnd = p.lease_end_date ? new Date(p.lease_end_date).toLocaleDateString('vi-VN') : '---';
          const areaDisplay = p.leased_area 
            ? Number(p.leased_area).toLocaleString('vi-VN') + ' m²' 
            : (p.calculated_area ? Number(p.calculated_area).toLocaleString('vi-VN') + ' m²' : '—');

          layer.bindPopup(`
            <div style="font-family: sans-serif; font-size: 13px; min-width: 220px;">
              <h4 style="margin: 0 0 6px 0; color: ${p.company_color || '#0f172a'};">
                🏢 ${p.company_name || 'Đất Doanh Nghiệp'}
              </h4>
              <p style="margin: 3px 0;"><b>Mã thửa đất:</b> <span style="font-family: monospace; font-weight: bold; color: #0284c7;">${p.land_code}</span></p>
              <p style="margin: 3px 0;"><b>Thuộc lô QH:</b> ${p.plot_code ? 'Lô ' + p.plot_code : 'Chưa gắn'}</p>
              <p style="margin: 3px 0;"><b>Số bìa đỏ:</b> <span style="color: #b45309; font-weight: bold;">${p.certificate_number || 'Đang cấp đổi'}</span></p>
              <p style="margin: 3px 0;"><b>Thời hạn thuê đến:</b> <b>${leaseEnd}</b></p>
              <p style="margin: 3px 0;"><b>Diện tích:</b> <b style="color: #059669;">${areaDisplay}</b></p>
              <p style="margin: 3px 0;"><b>Hiện trạng:</b> ${p.status === 'active' ? '🟢 Đang sản xuất' : '🟡 Đang xây dựng'}</p>
            </div>
          `);

          layer.on({
            mouseover: (e) => { e.target.setStyle({ fillOpacity: 0.8, weight: 3.5 }); },
            mouseout: (e) => { e.target.setStyle({ fillOpacity: 0.55, weight: 2 }); }
          });
        }
      });

      this.companyLandsGeoJsonLayer = leafletLayers.companyLands;
    },

    /**
     * 🌟 Hàm dùng chung vẽ cảnh quan môi trường (Cây xanh, Mặt nước)
     */
    drawLandscape(type) {
      const isGreenery = type === 'greenery';
      const data = isGreenery ? this.greeneryData : this.waterBodiesData;
      if (!data?.features) return;

      const config = isGreenery ? {
        fillColor: '#22c55e',
        color: '#15803d',
        icon: '🌳',
        title: 'Vùng Cây Xanh',
        labelClass: 'greenery-label',
        targetLayerKey: 'greenery'
      } : {
        fillColor: '#0284c7',
        color: '#0369a1',
        icon: '🌊',
        title: 'Mặt Nước / Hồ Điều Hòa',
        labelClass: 'water-label',
        targetLayerKey: 'waterBodies'
      };

      if (isGreenery) this.greeneryLabelMarkers = [];
      else this.waterLabelMarkers = [];

      leafletLayers[config.targetLayerKey] = L.geoJSON(data, {
        interactive: true,
        style: {
          fillColor: config.fillColor,
          fillOpacity: isGreenery ? 0.5 : 0.6,
          color: config.color,
          weight: 1.5
        },
        onEachFeature: (feature, layer) => {
          const p = feature.properties;
          if (p.code) {
            this.addPolygonLabel(feature, `${config.icon} ${p.code}`, config.labelClass);
          }
          layer.bindPopup(`
            <div style="font-size: 13px; min-width: 170px;">
              <b style="color: ${config.color};">${config.icon} ${config.title}: ${p.code}</b><br>
              <b>Diện tích:</b> ${p.area ? Number(p.area).toLocaleString('vi-VN') + ' m²' : '—'}<br>
              <b>Mô tả:</b> ${p.description || 'Chưa cập nhật'}
            </div>
          `);
        }
      }).addTo(this.map);

      if (isGreenery) this.greeneryGeoJsonLayer = leafletLayers.greenery;
      else this.waterGeoJsonLayer = leafletLayers.waterBodies;
    },

    drawRoads() {
      if (!this.roadsData?.features?.length) return;

      leafletLayers.roads = L.geoJSON(this.roadsData, {
        interactive: true,
        style: (feature) => {
          const widthInMeters = feature.properties.road_width || feature.properties.right_of_way || 10;
          return {
            color: '#FFE082',
            weight: this.getWeightInPixels(widthInMeters),
            opacity: 0.85
          };
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
          layer.bindPopup(`<b>Tên đường: ${p.name}</b><br>Rộng: ${p.road_width || p.right_of_way || '—'}m`);
        }
      }).addTo(this.map);

      this.roadsGeoJsonLayer = leafletLayers.roads;
    },

    drawFactories() {
      if (!this.factoriesData?.features) return;

      const factoryRenderer = L.svg({ pane: 'factoriesPane' });

      leafletLayers.factories = L.geoJSON(this.factoriesData, {
        pane: 'factoriesPane',
        renderer: factoryRenderer,
        interactive: true,
        style: (feature) => {
          const p = feature.properties;
          if (p.status === 'rented' && (p.company_color || p.color)) {
            const customColor = p.company_color || p.color;
            return {
              pane: 'factoriesPane',
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
      });

      this.factoriesGeoJsonLayer = leafletLayers.factories;
    },

    drawWastewater() {
      leafletLayers.wastewater = this.createDrainageSystemLayer({
        pipeType: 'wastewater',
        pipeTitle: '🧪 TUYẾN THU GOM NƯỚC THẢI',
        pipeColor: '#9333ea',
        arrowFillColor: '#581c87',
        defaultMaterial: 'HDPE 2 vách',
        manholesData: this.wastewaterManholesData,
        manholeTitle: '🧪 Hố Ga Nước Thải',
        manholeColor: '#800080',
        manholeRadius: this.getPointRadiusInPixels(1.5, 2, 5.5),
        extraFieldLabel: 'Độ sâu',
        extraFieldKey: 'depth'
      });
    },

    drawRainwater() {
      leafletLayers.rainwater = this.createDrainageSystemLayer({
        pipeType: 'rainwater',
        pipeTitle: '🌧️ TUYẾN THOÁT NƯỚC MƯA',
        pipeColor: '#0284c7',
        arrowFillColor: '#0369a1',
        defaultMaterial: 'BTCT',
        manholesData: this.rainwaterManholesData,
        manholeTitle: '🌧️ Hố Ga Nước Mưa',
        manholeColor: '#007bff',
        manholeRadius: this.getPointRadiusInPixels(1.3, 2, 5.0),
        extraFieldLabel: 'Nắp',
        extraFieldKey: 'cover_material'
      });
    },

    /**
     * 🌟 Hàm dùng chung khởi tạo Hệ thống Thoát nước (Cống + Mũi tên MultiLine + Hố ga)
     */
    createDrainageSystemLayer(config) {
      const layers = [];
      const pipeRenderer = L.svg({ pane: 'pipesPane' });
      const manholeRenderer = L.svg({ pane: 'pointsPane' });

      // 1. Tuyến đường cống
      const filteredPipes = {
        type: 'FeatureCollection',
        features: (this.drainagePipesData?.features || []).filter(
          (f) => f.properties.pipe_type === config.pipeType
        )
      };

      if (filteredPipes.features.length) {
        const pipeLayer = L.geoJSON(filteredPipes, {
          pane: 'pipesPane',
          renderer: pipeRenderer,
          interactive: true,
          style: (feature) => ({
            pane: 'pipesPane',
            color: config.pipeColor,
            weight: this.getPipeWeightInPixels(feature),
            opacity: 0.95,
            lineCap: 'round',
            lineJoin: 'round'
          }),
          onEachFeature: (feature, layer) => {
            const p = feature.properties;
            const spec = p.cross_section === 'circular'
              ? `D${p.diameter || '---'} mm`
              : `Hộp ${p.width}x${p.height} mm`;

            layer.bindPopup(`
              <div style="font-size: 13px; min-width: 210px; font-family: sans-serif; line-height: 1.45;">
                <h4 style="margin: 0 0 6px 0; color: ${config.pipeColor};">${config.pipeTitle}</h4>
                <p style="margin: 3px 0;"><b>Mã cống:</b> <span style="color:#0284c7; font-weight:bold;">${p.code}</span></p>
                <p style="margin: 3px 0;"><b>Tên đoạn:</b> ${p.name || '---'}</p>
                <p style="margin: 3px 0;"><b>Quy cách:</b> ${spec} (${p.material || config.defaultMaterial})</p>
                <p style="margin: 3px 0;"><b>Dòng chảy:</b> ${p.start_manhole_code || '---'} ➔ ${p.end_manhole_code || '---'}</p>
                <p style="margin: 3px 0;"><b>Cốt đáy:</b> ${p.invert_start_elevation || '---'}m ➔ ${p.invert_end_elevation || '---'}m</p>
                <p style="margin: 3px 0;"><b>Độ dốc i:</b> <b style="color:#059669;">${p.slope ? p.slope + '%' : '---'}</b></p>
                <p style="margin: 3px 0;"><b>Chiều dài:</b> ${p.length_m ? p.length_m + ' m' : '---'}</p>
              </div>
            `);

            layer.on({
              mouseover: (e) => { e.target.setStyle({ weight: this.getPipeWeightInPixels(feature) + 4 }); },
              mouseout: (e) => { e.target.setStyle({ weight: this.getPipeWeightInPixels(feature) + 1 }); }
            });
          }
        });

        layers.push(pipeLayer);

        // 2. Mũi tên dòng chảy (Hỗ trợ MultiLineString, bỏ đoạn < 30m)
        if (typeof L.polylineDecorator === 'function') {
          const arrowDecors = [];

          pipeLayer.eachLayer((pLayer) => {
            if (!(pLayer instanceof L.Polyline)) return;
            const rawLatLngs = pLayer.getLatLngs();
            if (!rawLatLngs || !rawLatLngs.length) return;

            const subSegments = Array.isArray(rawLatLngs[0]) ? rawLatLngs : [rawLatLngs];

            subSegments.forEach((segment) => {
              if (!segment || segment.length < 2) return;

              let segLength = 0;
              for (let i = 0; i < segment.length - 1; i++) {
                segLength += segment[i].distanceTo(segment[i + 1]);
              }

              // 🛑 Đoạn ngắn hơn 30m: bỏ qua hoàn toàn
              if (segLength < 30) return;

              const patternConfig = segLength < 50
                ? [{
                    offset: '50%',
                    repeat: 0,
                    symbol: L.Symbol.arrowHead({
                      pixelSize: 8,
                      polygon: true,
                      pathOptions: {
                        pane: 'pipesPane',
                        fillColor: config.arrowFillColor,
                        fillOpacity: 1,
                        color: '#ffffff',
                        weight: 1
                      }
                    })
                  }]
                : [{
                    offset: '25m',
                    repeat: '50m',
                    symbol: L.Symbol.arrowHead({
                      pixelSize: 8,
                      polygon: true,
                      pathOptions: {
                        pane: 'pipesPane',
                        fillColor: config.arrowFillColor,
                        fillOpacity: 1,
                        color: '#ffffff',
                        weight: 1
                      }
                    })
                  }];

              arrowDecors.push(L.polylineDecorator(segment, { patterns: patternConfig }));
            });
          });

          if (arrowDecors.length) {
            layers.push(L.featureGroup(arrowDecors));
          }
        }
      }

      // 3. Hố ga
      if (config.manholesData?.features?.length) {
        const manholeLayer = L.geoJSON(config.manholesData, {
          pane: 'pointsPane',
          renderer: manholeRenderer,
          interactive: true,
          pointToLayer: (feature, latlng) => {
            return L.circleMarker(latlng, {
              pane: 'pointsPane',
              renderer: manholeRenderer,
              interactive: true,
              radius: config.manholeRadius,
              fillColor: config.manholeColor,
              color: '#ffffff',
              weight: 1.5,
              opacity: 0.9,
              fillOpacity: 0.9
            });
          },
          onEachFeature: (feature, layer) => {
            const props = feature.properties;
            const lat = layer.getLatLng().lat;
            const lng = layer.getLatLng().lng;

            layer.bindPopup(`
              <div style="font-size: 13px; min-width: 190px; font-family: sans-serif; line-height: 1.45;">
                <b style="color: ${config.manholeColor};">${config.manholeTitle}</b><br>
                <b>Mã:</b> <span style="font-family:monospace; font-weight:bold;">${props.code}</span><br>
                <b>DN:</b> ${props.company_name || 'Chưa gắn'}<br>
                <b>${config.extraFieldLabel}:</b> ${props[config.extraFieldKey] || 'N/A'}<br>
                <hr style="margin: 6px 0; border: none; border-top: 1px dashed #ccc;">
                <a href="${this.getGoogleMapsUrl(lat, lng)}" target="_blank" class="directions-btn">
                  🧭 Chỉ đường (Google Maps)
                </a>
              </div>
            `);

            layer.on('click', (e) => { L.DomEvent.stopPropagation(e); });
            layer.on({
              mouseover: (e) => { e.target.setStyle({ radius: 8, weight: 3 }); },
              mouseout: (e) => { e.target.setStyle({ radius: 6, weight: 2 }); }
            });
          }
        });

        layers.push(manholeLayer);
      }

      return L.featureGroup(layers);
    },

    drawLightingSystem() {
      const layers = [];
      const initCabSize = this.getCabinetSizeInPixels(1.8, 12, 22);

      // 1. Cáp chiếu sáng
      if (this.lightingCablesData?.features?.length) {
        const cableRenderer = L.svg({ pane: 'lightingCablesPane' });
        const cablesLayer = L.geoJSON(this.lightingCablesData, {
          pane: 'lightingCablesPane',
          renderer: cableRenderer,
          interactive: true,
          style: (feature) => {
            const isFault = feature.properties.status === 'fault';
            return {
              pane: 'lightingCablesPane',
              color: isFault ? '#ef4444' : '#f59e0b',
              weight: Math.max(1.5, Math.min(4, this.getWeightInPixels(0.6))),
              dashArray: feature.properties.installation_type === 'overhead' ? '6, 6' : null,
              opacity: 0.95
            };
          },
          onEachFeature: (feature, layer) => {
            const p = feature.properties;
            layer.bindPopup(`
              <div style="font-size: 13px; min-width: 210px; font-family: sans-serif;">
                <h4 style="margin: 0 0 6px 0; color: #f59e0b;">〰️ TUYẾN CÁP CHIẾU SÁNG</h4>
                <p style="margin: 3px 0;"><b>Mã lộ:</b> <span style="color:#0284c7; font-weight:bold;">${p.code}</span></p>
                <p style="margin: 3px 0;"><b>Tên tuyến:</b> ${p.name || '---'}</p>
                <p style="margin: 3px 0;"><b>Quy cách:</b> ${p.cable_spec || 'Cu/XLPE 4x16mm2'}</p>
                <p style="margin: 3px 0;"><b>Lắp đặt:</b> ${p.installation_type === 'underground' ? '🚇 Cáp ngầm hào' : '🪢 Cáp treo'}</p>
                <p style="margin: 3px 0;"><b>Chiều dài:</b> <b style="color:#0284c7;">${p.length_m || '—'} m</b></p>
                <p style="margin: 3px 0;"><b>Tình trạng:</b> ${p.status === 'normal' ? '🟢 Bình thường' : '🔴 Sự cố'}</p>
              </div>
            `);

            layer.on({
              mouseover: (e) => { e.target.setStyle({ weight: 6, color: '#fbbf24', opacity: 1 }); },
              mouseout: (e) => {
                const isFault = feature.properties.status === 'fault';
                e.target.setStyle({ weight: 4, color: isFault ? '#ef4444' : '#f59e0b', opacity: 0.95 });
              }
            });
          }
        });
        layers.push(cablesLayer);
      }

      // 2. Cột đèn chiếu sáng
      if (this.lightingPolesData?.features?.length) {
        const poleRenderer = L.svg({ pane: 'lightingPointsPane' });
        const polesLayer = L.geoJSON(this.lightingPolesData, {
          pane: 'lightingPointsPane',
          renderer: poleRenderer,
          interactive: true,
          pointToLayer: (feature, latlng) => {
            const isBroken = feature.properties.status === 'broken';
            return L.circleMarker(latlng, {
              pane: 'lightingPointsPane',
              renderer: poleRenderer,
              interactive: true,
              radius: this.getPointRadiusInPixels(1.2, 1.8, 5.0),
              fillColor: isBroken ? '#ef4444' : '#eab308',
              color: '#ffffff',
              weight: 2,
              opacity: 1,
              fillOpacity: 0.95
            });
          },
          onEachFeature: (feature, layer) => {
            const p = feature.properties;
            const lat = layer.getLatLng().lat;
            const lng = layer.getLatLng().lng;

            layer.bindPopup(`
              <div style="font-size: 13px; min-width: 190px; font-family: sans-serif;">
                <h4 style="margin: 0 0 6px 0; color: #ca8a04;">💡 CỘT ĐÈN CHIẾU SÁNG</h4>
                <p style="margin: 3px 0;"><b>Mã cột:</b> <span style="color:#ca8a04; font-weight:bold;">${p.code}</span></p>
                <p style="margin: 3px 0;"><b>Chiều cao:</b> ${p.height_m || 8} m</p>
                <p style="margin: 3px 0;"><b>Loại bóng:</b> ${p.lamp_type || 'LED'} - <b>${p.wattage || 150}W</b></p>
                <p style="margin: 3px 0;"><b>Trạng thái:</b> ${p.status === 'working' ? '🟢 Sáng tốt' : (p.status === 'broken' ? '🔴 Hỏng bóng' : '🟡 Bảo dưỡng')}</p>
                <hr style="margin: 6px 0; border: none; border-top: 1px dashed #cbd5e1;">
                <a href="${this.getGoogleMapsUrl(lat, lng)}" target="_blank" class="directions-btn">
                  🧭 Chỉ đường tới cột đèn
                </a>
              </div>
            `);

            layer.on({
              mouseover: (e) => { e.target.setStyle({ radius: 8, weight: 3 }); },
              mouseout: (e) => { e.target.setStyle({ radius: 6, weight: 2 }); }
            });
          }
        });
        layers.push(polesLayer);
      }

      // 3. Tủ điện chiếu sáng
      if (this.lightingCabinetsData?.features?.length) {
        const cabinetsLayer = L.geoJSON(this.lightingCabinetsData, {
          pane: 'lightingPointsPane',
          interactive: true,
          pointToLayer: (feature, latlng) => {
            return L.marker(latlng, {
              pane: 'lightingPointsPane',
              icon: L.divIcon({
                className: 'custom-cabinet-marker',
                html: `<div class="cabinet-icon-box" style="width: ${initCabSize}px; height: ${initCabSize}px; font-size: ${Math.round(initCabSize * 0.55)}px;" title="${feature.properties.code}">⚡</div>`,
                iconSize: [initCabSize, initCabSize],
                iconAnchor: [Math.round(initCabSize / 2), Math.round(initCabSize / 2)]
              })
            });
          },
          onEachFeature: (feature, layer) => {
            const p = feature.properties;
            const lat = layer.getLatLng().lat;
            const lng = layer.getLatLng().lng;

            layer.bindPopup(`
              <div style="font-size: 13px; min-width: 200px; font-family: sans-serif;">
                <h4 style="margin: 0 0 6px 0; color: #d97706;">⚡ TỦ ĐIỆN CHIẾU SÁNG</h4>
                <p style="margin: 3px 0;"><b>Mã tủ:</b> <span style="color:#d97706; font-weight:bold;">${p.code}</span></p>
                <p style="margin: 3px 0;"><b>Tên tủ:</b> ${p.name || '---'}</p>
                <p style="margin: 3px 0;"><b>Công suất:</b> ${p.capacity_kva ? p.capacity_kva + ' kVA' : '—'}</p>
                <p style="margin: 3px 0;"><b>Trạng thái:</b> ${p.status === 'active' ? '🟢 Hoạt động' : '🔴 Ngừng cấp'}</p>
                <hr style="margin: 6px 0; border: none; border-top: 1px dashed #cbd5e1;">
                <a href="${this.getGoogleMapsUrl(lat, lng)}" target="_blank" class="directions-btn">
                  🧭 Chỉ đường tới tủ điện
                </a>
              </div>
            `);
          }
        });
        layers.push(cabinetsLayer);
      }

      leafletLayers.lightingSystem = L.featureGroup(layers);
    },

    drawSurroundingLocations() {
      if (!this.surroundingLocationsData?.features?.length) return;

      leafletLayers.surroundingLocations = L.geoJSON(this.surroundingLocationsData, {
        pane: 'surroundingPane',
        interactive: true,
        pointToLayer: (feature, latlng) => {
          const p = feature.properties;
          const bgColor = p.bg_color || '#1e293b';
          const textColor = p.text_color || '#ffffff';
          const icon = p.icon || '📍';
          const name = p.name || p.code;

          const marker = L.circleMarker(latlng, {
            pane: 'surroundingPane',
            interactive: true,
            radius: 0,
            fillColor: bgColor,
            color: '#ffffff',
            weight: 0,
            opacity: 1,
            fillOpacity: 1
          });

          const tooltipContent = `
            <div class="surrounding-tag" style="background-color: ${bgColor}; color: ${textColor}; border-color: ${bgColor};">
              <span>${icon} ${name}</span>
            </div>
          `;

          marker.bindTooltip(tooltipContent, {
            pane: 'surroundingPane',
            permanent: true,
            direction: 'top',
            className: 'surrounding-leaflet-tooltip',
            offset: [0, -6]
          });

          return marker;
        },
        onEachFeature: (feature, layer) => {
          const p = feature.properties;
          const lat = layer.getLatLng().lat;
          const lng = layer.getLatLng().lng;

          layer.bindPopup(`
            <div style="font-size: 13px; min-width: 200px; font-family: sans-serif;">
              <h4 style="margin: 0 0 6px 0; color: ${p.bg_color || '#0f172a'};">
                ${p.icon || '📍'} ${p.name}
              </h4>
              <p style="margin: 3px 0;"><b>Mã mốc:</b> <span style="font-family: monospace; font-weight: bold;">${p.code}</span></p>
              <p style="margin: 3px 0;"><b>Phân loại:</b> ${p.category || 'Vùng phụ cận'}</p>
              <p style="margin: 3px 0;"><b>Mô tả:</b> ${p.description || 'Chưa cập nhật'}</p>
              <hr style="margin: 6px 0; border: none; border-top: 1px dashed #cbd5e1;">
              <a href="${this.getGoogleMapsUrl(lat, lng)}" target="_blank" class="directions-btn">
                🧭 Chỉ đường tới khu vực này
              </a>
            </div>
          `);
        }
      });

      const initialZoom = this.map.getZoom();
      if (initialZoom >= 14 && initialZoom < 16) {
        leafletLayers.surroundingLocations.addTo(this.map);
      }
    },

    // =========================================================================
    // 5. CÁC HÀM TIỆN ÍCH & ĐIỀU KHIỂN HIỂN THỊ
    // =========================================================================
    addPolygonLabel(feature, labelText, cssClass) {
      if (!feature?.geometry || !labelText) return null;

      try {
        const pt = pointOnFeature(feature);
        const [lng, lat] = pt.geometry.coordinates;

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

    checkAllLabelsVisibility() {
      if (!this.map) return;
      const currentZoom = this.map.getZoom();

      const layersToCheck = [
        { layer: this.roadsGeoJsonLayer, minZoom: 17, maxZoom: 20 },
        { layer: this.plotsGeoJsonLayer, minZoom: 17, maxZoom: 18 },
        { layer: this.companyLandsGeoJsonLayer, minZoom: 18, maxZoom: 20 },
        { layer: this.factoriesGeoJsonLayer, minZoom: 18, maxZoom: 20 }
      ];

      layersToCheck.forEach(item => {
        if (!item.layer) return;
        const show = currentZoom >= item.minZoom && currentZoom < item.maxZoom;
        item.layer.eachLayer(l => {
          if (l.getTooltip()) {
            if (show) l.openTooltip();
            else l.closeTooltip();
          }
        });
      });

      const showLandscape = currentZoom >= 17;
      [...this.greeneryLabelMarkers, ...this.waterLabelMarkers].forEach(marker => {
        if (marker?.getTooltip()) {
          if (showLandscape) marker.openTooltip();
          else marker.closeTooltip();
        }
      });
    },

    handleLayerChange({ name, config }) {
      if (this.userLayerVisibility[name] !== undefined) {
        this.userLayerVisibility[name] = config.visible;
      }

      const layer = leafletLayers[name];
      if (!layer) return;

      const currentZoom = this.map.getZoom();

      if (config.visible) {
        if (name === 'surroundingLocations') {
          const isInRange = currentZoom >= 14 && currentZoom < 16;
          if (isInRange && !this.map.hasLayer(layer)) {
            this.map.addLayer(layer);
          }
        } else {
          const detailLayers = ['lightingSystem', 'wastewater', 'rainwater'];
          if (!detailLayers.includes(name) || currentZoom >= 16) {
            if (!this.map.hasLayer(layer)) {
              this.map.addLayer(layer);
            }
          }

          if (config.name === "greenery") {
            this.greeneryLabelMarkers.forEach(marker => marker.openTooltip());
          } else if (config.name === "waterBodies") {
            this.waterLabelMarkers.forEach(marker => marker.openTooltip());
          }
        }
      } else {
        if (this.map.hasLayer(layer)) {
          this.map.removeLayer(layer);
        }
        if (config.name === "greenery") {
          this.greeneryLabelMarkers.forEach(marker => marker.closeTooltip());
        } else if (config.name === "waterBodies") {
          this.waterLabelMarkers.forEach(marker => marker.closeTooltip());
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
      this.checkAllLabelsVisibility();
    },

    checkDetailLayersVisibility() {
      if (!this.map) return;
      const currentZoom = this.map.getZoom();
      const MIN_DETAIL_ZOOM = 16;

      const detailLayerKeys = ['lightingSystem', 'wastewater', 'rainwater'];

      detailLayerKeys.forEach((key) => {
        const layer = leafletLayers[key];
        const userWantsVisible = this.userLayerVisibility[key];
        if (!layer) return;

        if (userWantsVisible && currentZoom >= MIN_DETAIL_ZOOM) {
          if (!this.map.hasLayer(layer)) this.map.addLayer(layer);
        } else {
          if (this.map.hasLayer(layer)) this.map.removeLayer(layer);
        }
      });

      // Lớp phụ cận ngoài ranh giới KCN
      const surroundingLayer = leafletLayers.surroundingLocations;
      const userWantsSurrounding = this.userLayerVisibility.surroundingLocations ?? true;

      if (surroundingLayer) {
        const isInSurroundingZoomRange = currentZoom >= 14 && currentZoom < 16;
        if (userWantsSurrounding && isInSurroundingZoomRange) {
          if (!this.map.hasLayer(surroundingLayer)) this.map.addLayer(surroundingLayer);
        } else {
          if (this.map.hasLayer(surroundingLayer)) this.map.removeLayer(surroundingLayer);
        }
      }
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
        alert("Trình duyệt không hỗ trợ tính năng định vị GPS.");
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

          this.currentLocationMarker.bindPopup("<b>📍 Vị trí hiện tại của bạn</b>").openPopup();
          this.map.setView([lat, lng], 17);
        },
        (error) => {
          switch (error.code) {
            case error.PERMISSION_DENIED: alert("Chưa cấp quyền truy cập GPS."); break;
            case error.POSITION_UNAVAILABLE: alert("Không thể xác định vị trí GPS."); break;
            case error.TIMEOUT: alert("Thời gian định vị quá hạn."); break;
            default: alert("Lỗi định vị không xác định."); break;
          }
        },
        { enableHighAccuracy: true }
      );
    },

    addMapLegend() {
      const legend = L.control({ position: 'bottomleft' });

      legend.onAdd = () => {
        const div = L.DomUtil.create('div', 'custom-map-legend collapsed');
        div.id = 'map-legend-box';

        div.innerHTML = `
          <div class="legend-header" id="legend-toggle-btn" title="Click để ẩn/hiện chú thích">
            <div class="legend-header-title">
              <span class="legend-header-icon">📌</span>
              <span class="legend-title-text">CHÚ THÍCH BẢN ĐỒ</span>
            </div>
            <span class="legend-toggle-arrow">▲</span>
          </div>

          <div class="legend-content">
            <div class="legend-group-title">QUY HOẠCH & XÂY DỰNG</div>
            <div class="legend-item"><span class="legend-line boundary-line"></span><span class="legend-text">Ranh giới quy hoạch</span></div>
            <div class="legend-item"><span class="legend-box" style="background-color: #b45309; opacity: 0.7; border: 1px solid #f55b02;"></span><span class="legend-text">Đất công nghiệp</span></div>
            <div class="legend-item"><span class="legend-box" style="background-color: #e11d48; opacity: 0.7; border: 1px solid #be123c;"></span><span class="legend-text">Đất thương mại dịch vụ</span></div>
            <div class="legend-item"><span class="legend-box" style="background-color: #003099; opacity: 0.6; border: 1.5px solid #003099;"></span><span class="legend-text">Đất doanh nghiệp thuê</span></div>

            <div class="legend-group-title">GIAO THÔNG & CẢNH QUAN</div>
            <div class="legend-item"><span class="legend-line road-line"></span><span class="legend-text">Đường nội bộ KCN</span></div>
            <div class="legend-item"><span class="legend-box greenery-box"></span><span class="legend-text">Khuôn viên cây xanh</span></div>
            <div class="legend-item"><span class="legend-box water-box"></span><span class="legend-text">Mặt nước / Kênh điều hòa</span></div>

            <div class="legend-group-title">HỆ THỐNG THOÁT NƯỚC</div>
            <div class="legend-item"><span class="legend-point rainwater-point"></span><span class="legend-line rain-pipe-line"></span><span class="legend-text">Cống & Hố ga nước mưa</span></div>
            <div class="legend-item"><span class="legend-point wastewater-point"></span><span class="legend-line waste-pipe-line"></span><span class="legend-text">Cống & Hố ga nước thải</span></div>

            <div class="legend-group-title">HỆ THỐNG CHIẾU SÁNG</div>
            <div class="legend-item"><span class="legend-icon-mini">⚡</span><span class="legend-text">Tủ điện chiếu sáng</span></div>
            <div class="legend-item"><span class="legend-point pole-point"></span><span class="legend-text">Cột đèn chiếu sáng LED</span></div>
            <div class="legend-item"><span class="legend-line cable-line"></span><span class="legend-text">Tuyến cáp điện ngầm</span></div>
          </div>
        `;

        L.DomEvent.disableClickPropagation(div);
        L.DomEvent.disableScrollPropagation(div);

        const toggleBtn = div.querySelector('#legend-toggle-btn');
        L.DomEvent.on(toggleBtn, 'click', (e) => {
          L.DomEvent.stopPropagation(e);
          div.classList.toggle('collapsed');
        });

        return div;
      };

      legend.addTo(this.map);
    },

    getFlag(country) {
      const flags = {
        'Hàn Quốc': '🇰🇷',
        'Trung Quốc': '🇨🇳',
        'Việt Nam': '🇻🇳',
        'Đài Loan': '🇹🇼',
        'Nhật Bản': '🇯🇵',
        'Singapore': '🇸🇬'
      };
      return flags[country] || '🌐';
    },

    formatNumber(val) {
      if (!val) return '0';
      return Number(val).toLocaleString('vi-VN');
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
  height: 100dvh;
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
  position: relative;
  z-index: 1;
}

/* Nút định vị ghim góc dưới bên phải */
.locate-btn {
  position: absolute;
  bottom: 98px;
  right: 24px;
  z-index: 1000;
  width: 36px;
  height: 36px;
  background-color: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18);
  transition: all 0.2s ease;
  user-select: none;
  outline: none;
}

.locate-btn:hover {
  background-color: #f8fafc;
  border-color: #94a3b8;
  transform: scale(1.08);
}

.locate-btn:active {
  transform: scale(0.94);
  background-color: #eff6ff;
  border-color: #2563eb;
}

@media (max-width: 640px) {
  .locate-btn {
    bottom: 28px;
    right: 14px;
    width: 42px;
    height: 42px;
    font-size: 18px;
  }
}

/* Panel thông tin chi tiết nhà xưởng */
.factory-info-panel {
  position: absolute;
  top: 16px;
  right: 16px;
  width: 330px;
  max-width: calc(100vw - 32px);
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.16);
  z-index: 2000;
  overflow: hidden;
  font-family: inherit;
  border: 1px solid #e2e8f0;
  font-size: 13px;
  color: #334155;
}

.panel-header {
  padding: 10px 14px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.header-left {
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.header-badges {
  display: flex;
  gap: 6px;
}
.badge {
  font-size: 11px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 4px;
  text-transform: uppercase;
}
.badge.rented, .badge.active { background: #dcfce7; color: #15803d; }
.badge.empty, .badge.vacant { background: #fee2e2; color: #b91c1c; }
.badge-plot {
  font-size: 11px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 4px;
  background: #fef3c7;
  color: #b45309;
}
.factory-code {
  margin: 0;
  font-size: 14.5px;
  font-weight: 800;
  color: #0f172a;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}
.icon-nav-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: 6px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  font-size: 15px;
  text-decoration: none;
  cursor: pointer;
  transition: transform 0.15s, background 0.15s;
}
.icon-nav-btn:hover {
  background: #dbeafe;
  transform: scale(1.08);
}
.close-btn {
  border: none;
  background: #f1f5f9;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  font-size: 13px;
  cursor: pointer;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
}
.close-btn:hover { background: #e2e8f0; color: #0f172a; }

.panel-body {
  padding: 12px 14px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 75vh;
  overflow-y: auto;
}

.company-block {
  display: flex;
  flex-direction: column;
  gap: 4px;
  border-bottom: 1px dashed #e2e8f0;
  padding-bottom: 8px;
}
.company-title-row {
  display: flex;
  align-items: center;
  gap: 8px;
}
.color-indicator {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}
.company-name {
  margin: 0;
  font-size: 13.5px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.35;
}
.meta-tags {
  display: flex;
  gap: 6px;
  margin-left: 18px;
  flex-wrap: wrap;
}
.tag {
  font-size: 11px;
  padding: 2px 7px;
  border-radius: 4px;
  background: #f1f5f9;
  color: #475569;
}
.tag-country { background: #eff6ff; color: #1e40af; font-weight: 600; }

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 6px 10px;
  background: #fafafa;
  padding: 8px 10px;
  border-radius: 8px;
  border: 1px solid #f1f5f9;
}
.info-item {
  display: flex;
  flex-direction: column;
  line-height: 1.35;
}
.info-item.full-width {
  grid-column: span 2;
}
.label {
  font-size: 11px;
  color: #64748b;
  font-weight: 600;
}
.value {
  font-size: 12.5px;
  color: #1e293b;
  font-weight: 600;
}
.text-highlight { color: #047857; font-weight: 800; }
.font-mono { font-family: monospace; font-size: 12px; }

.contact-footer {
  display: flex;
  flex-direction: column;
  gap: 6px;
  border-top: 1px solid #f1f5f9;
  padding-top: 8px;
}
.contact-row {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}
.contact-link {
  font-size: 12px;
  color: #2563eb;
  text-decoration: none;
  font-weight: 600;
}
.contact-link:hover { text-decoration: underline; }
.notes-text {
  margin: 0;
  font-size: 11.5px;
  color: #475569;
  line-height: 1.4;
  background: #fffbeb;
  padding: 6px 8px;
  border-radius: 6px;
  border: 1px solid #fef3c7;
}

@media (max-width: 640px) {
  .factory-info-panel {
    top: auto;
    bottom: 12px;
    left: 8px;
    right: 8px;
    width: auto;
    max-width: none;
    border-radius: 14px;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.18);
  }
  .panel-body {
    max-height: 52vh;
  }
}

/* Các nhãn bản đồ Leaflet */
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
  font-weight: 700 !important; font-size: 8px !important; padding: 2px 6px !important;
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
/* CSS TOÀN CỤC CHO CÁC CONTROLS VÀ TOOLTIPS LEAFLET */
.leaflet-bottom.leaflet-left {
  z-index: 999 !important;
  margin-left: 12px !important;
  margin-bottom: calc(15px + env(safe-area-inset-bottom, 0px)) !important;
}

.leaflet-bottom.leaflet-right {
  z-index: 999 !important;
  margin-right: 12px !important;
  margin-bottom: calc(15px + env(safe-area-inset-bottom, 0px)) !important;
}

/* Bảng chú thích bản đồ (Map Legend) */
.custom-map-legend {
  background: rgba(255, 255, 255, 0.96) !important;
  backdrop-filter: blur(10px) !important;
  -webkit-backdrop-filter: blur(10px) !important;
  border-radius: 12px !important;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25) !important;
  border: 1px solid rgba(203, 213, 225, 0.9) !important;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
  width: 230px !important;
  max-width: calc(100vw - 80px) !important;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
  overflow: hidden !important;
  user-select: none !important;
  box-sizing: border-box !important;
}

.custom-map-legend.collapsed {
  width: auto !important;
  border-radius: 24px !important;
  background: rgba(255, 255, 255, 0.94) !important;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.22) !important;
}

.custom-map-legend.collapsed .legend-content {
  display: none !important;
}

.custom-map-legend.collapsed .legend-header {
  border-bottom: none !important;
  padding: 8px 14px !important;
  background: transparent !important;
}

.custom-map-legend.collapsed .legend-toggle-arrow {
  transform: rotate(180deg) !important;
}

.legend-header {
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  min-height: 40px !important;
  padding: 8px 14px !important;
  background: #f8fafc !important;
  border-bottom: 1px solid #e2e8f0 !important;
  cursor: pointer !important;
  -webkit-tap-highlight-color: transparent !important;
}

.legend-header:active { background: #f1f5f9 !important; }

.legend-header-title {
  display: flex !important;
  align-items: center !important;
  gap: 7px !important;
}

.legend-header-icon { font-size: 14px !important; line-height: 1 !important; }
.legend-title-text {
  font-size: 11px !important;
  font-weight: 800 !important;
  color: #0f172a !important;
  letter-spacing: 0.5px !important;
  text-transform: uppercase !important;
  white-space: nowrap !important;
}

.legend-toggle-arrow {
  font-size: 10px !important;
  color: #64748b !important;
  transition: transform 0.25s ease !important;
  margin-left: 8px !important;
}

.legend-content {
  padding: 10px 14px 14px 14px !important;
  max-height: 48vh !important;
  overflow-y: auto !important;
  -webkit-overflow-scrolling: touch !important;
  display: flex !important;
  flex-direction: column !important;
  gap: 6px !important;
}

.legend-content::-webkit-scrollbar { width: 4px !important; }
.legend-content::-webkit-scrollbar-thumb { background: #cbd5e1 !important; border-radius: 4px !important; }

.legend-group-title {
  font-size: 9px !important;
  font-weight: 800 !important;
  color: #94a3b8 !important;
  text-transform: uppercase !important;
  letter-spacing: 0.6px !important;
  margin-top: 6px !important;
  margin-bottom: 2px !important;
  border-bottom: 1px dashed #e2e8f0 !important;
  padding-bottom: 2px !important;
}
.legend-group-title:first-child { margin-top: 0 !important; }

.legend-item {
  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
  font-size: 11px !important;
  font-weight: 600 !important;
  color: #334155 !important;
  line-height: 1.3 !important;
}
.legend-text { white-space: nowrap !important; }

.legend-line { display: inline-block !important; width: 16px !important; height: 0 !important; flex-shrink: 0 !important; }
.legend-box { display: inline-block !important; width: 12px !important; height: 10px !important; border-radius: 2px !important; flex-shrink: 0 !important; }
.legend-point { border-radius: 50% !important; border: 1.5px solid #ffffff !important; box-shadow: 0 0 3px rgba(0, 0, 0, 0.35) !important; flex-shrink: 0 !important; }
.legend-icon-mini { display: inline-flex !important; align-items: center !important; justify-content: center !important; width: 14px !important; font-size: 12px !important; flex-shrink: 0 !important; }

/* Ký hiệu màu sắc Legend */
.boundary-line { border-top: 2.5px dashed #e11d48 !important; }
.road-line { border-top: 4px solid #FFE082 !important; }
.greenery-box { background-color: #22c55e !important; opacity: 0.75 !important; border: 1px solid #15803d !important; }
.water-box { background-color: #0284c7 !important; opacity: 0.75 !important; border: 1px solid #0369a1 !important; }
.rainwater-point { width: 9px !important; height: 9px !important; background-color: #007bff !important; }
.rain-pipe-line { border-top: 2.5px solid #0284c7 !important; }
.wastewater-point { width: 9px !important; height: 9px !important; background-color: #800080 !important; }
.waste-pipe-line { border-top: 2.5px solid #9333ea !important; }
.pole-point { width: 8px !important; height: 8px !important; background-color: #eab308 !important; }
.cable-line { border-top: 2.5px solid #f59e0b !important; }

@media (max-width: 768px) {
  .leaflet-bottom.leaflet-left {
    margin-left: 8px !important;
    margin-bottom: calc(10px + env(safe-area-inset-bottom, 0px)) !important;
  }
  .custom-map-legend.collapsed { border-radius: 20px !important; }
  .custom-map-legend.collapsed .legend-header { padding: 2px 5px !important; min-height: 34px !important; }
  .custom-map-legend.collapsed .legend-title-text { font-size: 10px !important; }
  .custom-map-legend:not(.collapsed) { width: 215px !important; max-height: 40vh !important; }
  .legend-content { max-height: 32vh !important; padding: 8px 11px 12px 11px !important; gap: 5px !important; }
  .legend-item { font-size: 10.5px !important; gap: 7px !important; }
}

/* BaseMap Control Styling */
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

/* Icon Tủ điện chiếu sáng */
.cabinet-icon-box {
  background-color: #d97706;
  color: #ffffff;
  border: 2px solid #ffffff;
  border-radius: 4px;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: bold;
  box-shadow: 0 2px 6px rgba(0,0,0,0.35);
  cursor: pointer;
}

/* Giải phóng các Pane để chuột không bị chặn */
.leaflet-lightingCablesPane-pane,
.leaflet-lightingPointsPane-pane,
.leaflet-pipesPane-pane,
.leaflet-pane.leaflet-companyLandsPane-pane,
.leaflet-pane.leaflet-roadsPane-pane {
  pointer-events: none !important;
}

.leaflet-lightingCablesPane-pane svg path,
.leaflet-lightingPointsPane-pane svg path,
.leaflet-lightingPointsPane-pane .leaflet-interactive,
.leaflet-pipesPane-pane svg path,
.leaflet-pane.leaflet-companyLandsPane-pane svg path,
.leaflet-pane.leaflet-roadsPane-pane svg path {
  pointer-events: auto !important;
  cursor: pointer !important;
}

/* Tooltip Vùng lân cận phụ cận */
.leaflet-tooltip.surrounding-leaflet-tooltip {
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
  padding: 0 !important;
  margin: 0 !important;
  pointer-events: auto !important;
  cursor: pointer !important;
}
.leaflet-tooltip-top.surrounding-leaflet-tooltip::before {
  border-top-color: inherit !important;
  border-width: 6px 5px 0 !important;
  bottom: -6px !important;
  margin-left: -5px !important;
}
.surrounding-tag {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 10px;
  border-radius: 16px;
  font-size: 11px;
  border: 1.5px solid rgba(255, 255, 255, 0.95);
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.4);
  white-space: nowrap;
  user-select: none;
  line-height: 1.2;
}

:deep(.company-land-label) {
  background-color: rgba(255, 255, 255, 0.92) !important;
  color: #0f172a !important;
  font-weight: 700 !important;
  font-size: 9px !important;
  line-height: 1.2 !important;
  text-align: center !important;
  padding: 3px 6px !important;
  border-radius: 4px !important;
  border: 1px solid #cbd5e1 !important;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2) !important;
  pointer-events: none !important;
  white-space: nowrap !important;
}
:deep(.company-land-label::before) { display: none !important; }

.leaflet-control-attribution {
  display: none !important;
}
</style>