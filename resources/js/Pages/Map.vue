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
  boundaries: null,
  factories: null,
  wastewater: null,
  rainwater: null,
  plots: null,
  roads: null,
  greenery: null,
  waterBodies: null,
  lightingCabinets: null,
  lightingCables: null,
  lightingPoles: null,
  surroundingLocations: null
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
    waterBodiesData: Object,
    boundariesData: Object,
    lightingCabinetsData: Object,
    lightingCablesData: Object,
    lightingPolesData: Object,
    surroundingLocationsData: Object,
    zonesList: { type: Array, default: () => [] },
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
      waterLabelMarkers: [],

      // 🌟 Lưu trạng thái bật/tắt do người dùng chọn trên Sidebar
      userLayerVisibility: {
        lightingCabinets: false,
        lightingCables: false,
        lightingPoles: false,
        wastewater: false,
        rainwater: false,
        surroundingLocations: true
      },

      selectedZoneId: '1',
    };
  },
  mounted() {
    this.initMap();
    // Nếu có danh sách KCN, chọn sẵn KCN đầu tiên
    if (this.zonesList && this.zonesList.length > 0) {
      this.selectedZoneId = this.zonesList[0].id;
    }
  },
  methods: {
    initMap() {
      // 1. Xác định KCN mặc định đầu tiên
      const initialZone = this.zonesList && this.zonesList.length > 0 ? this.zonesList[0] : null;

      // 2. Lấy tọa độ động từ database (nếu không có thì mới lấy tọa độ fallback)
      const centerLat = initialZone?.lat ? parseFloat(initialZone.lat) : 21.246624;
      const centerLng = initialZone?.lng ? parseFloat(initialZone.lng) : 106.178020;

      const isMobile = window.innerWidth <= 768; // Kiểm tra nếu là điện thoại/màn hình nhỏ
      const baseZoom = initialZone?.default_zoom ? parseInt(initialZone.default_zoom) : 15;
      const targetZoom = isMobile ? Math.max(baseZoom - 1, 13) : baseZoom;
  
      // Màn hình điện thoại zoom nhỏ xuống 15 (hoặc 14.5), máy tính giữ nguyên 16
      const initialZoom = isMobile ? 15 : 16; 
      
      // Tọa độ tâm bản đồ KCN Song Khê - Nội Hoàng
      const initialCenter = [centerLat, centerLng];
      this.map = L.map('map', {
        zoomControl: false,
        preferCanvas: true,
        wheelDebounceTime: 40,
        wheelPxPerZoomLevel: 80,
        markerZoomAnimation: false
      }).setView(initialCenter, initialZoom);

      L.control.zoom({ position: 'bottomright' }).addTo(this.map);

      const tileOptions = {
        maxZoom: 20,
        maxNativeZoom: 19,
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        updateWhenIdle: true,
        updateWhenZooming: false,
        keepBuffer: 3
      };

      // const googleSatellite = L.tileLayer('http://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
      //   ...tileOptions,
      //   attribution: 'Google Satellite'
      // });

      const googleSatelliteOnly = L.tileLayer('http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
        ...tileOptions,
        attribution: 'Google Satellite Clean'
      });

      // const googleStreets = L.tileLayer('http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
      //   ...tileOptions,
      //   attribution: 'Google Streets'
      // });

      googleSatelliteOnly.addTo(this.map);
      // googleStreets.addTo(this.map);

      const baseMaps = {
        // "🛰️ Ảnh vệ tinh": googleSatellite,
        "🛰️ Vệ tinh (Không nhãn)": googleSatelliteOnly,
        // "🗺️ Bản đồ Đường phố": googleStreets
      };

      L.control.layers(baseMaps, null, {
        position: 'topright',
        collapsed: true
      }).addTo(this.map);

      this.addMapLegend();

      this.map.on('zoomend', this.handleMapZoomEnd);

      // 2. 🌟 TẠO TẤT CẢ CÁC PANE NGAY TẠI ĐÂY (TRƯỚC KHI VẼ BẤT KỲ LAYER NÀO)
      this.map.createPane('boundariesPane');
      this.map.getPane('boundariesPane').style.zIndex = 390;
      this.map.getPane('boundariesPane').style.pointerEvents = 'none';

      this.map.createPane('naturePane');
      this.map.getPane('naturePane').style.zIndex = 410;
      this.map.getPane('naturePane').style.pointerEvents = 'none';

      this.map.createPane('plotsPane');
      this.map.getPane('plotsPane').style.zIndex = 420;
      this.map.getPane('plotsPane').style.pointerEvents = 'none';

      this.map.createPane('roadsPane');
      this.map.getPane('roadsPane').style.zIndex = 430;
      this.map.getPane('roadsPane').style.pointerEvents = 'none';

      this.map.createPane('factoriesPane');
      this.map.getPane('factoriesPane').style.zIndex = 440;
      this.map.getPane('factoriesPane').style.pointerEvents = 'none';

      this.map.createPane('pointsPane');
      this.map.getPane('pointsPane').style.zIndex = 450;
      this.map.getPane('pointsPane').style.pointerEvents = 'none';

      this.map.createPane('lightingCablesPane');
      this.map.getPane('lightingCablesPane').style.zIndex = 435;
      this.map.getPane('lightingCablesPane').style.pointerEvents = 'none';

      this.map.createPane('lightingPointsPane');
      this.map.getPane('lightingPointsPane').style.zIndex = 460;
      this.map.getPane('lightingPointsPane').style.pointerEvents = 'none';

      this.map.createPane('surroundingPane');
      this.map.getPane('surroundingPane').style.zIndex = 550;
      this.map.getPane('surroundingPane').style.pointerEvents = 'none';

      this.drawBoundaries();
      this.drawPlots();
      this.drawGreenery();
      this.drawWaterBodies();
      this.drawRoads();
      this.drawFactories();
      this.drawWastewater();
      this.drawRainwater();
      this.drawLightingCables();
      this.drawLightingPoles();
      this.drawLightingCabinets();
      this.drawSurroundingLocations();

      if (leafletLayers.boundaries) leafletLayers.boundaries.bringToBack();
      this.bringManholesToFront();
      this.checkAllLabelsVisibility();
    },

    bringManholesToFront() {
      if (leafletLayers.wastewater) leafletLayers.wastewater.bringToFront();
      if (leafletLayers.rainwater) leafletLayers.rainwater.bringToFront();
    },

    // 🌟 TỔNG ĐIỀU PHỐI KHI PHÓNG TO / THU NHỎ BẢN ĐỒ
    handleMapZoomEnd() {
      clearTimeout(this.zoomDebounceTimer);
      this.zoomDebounceTimer = setTimeout(() => {
        this.updateRoadsWidth();           // 1. Co giãn bề rộng đường
        this.updateInfrastructureSizes();   // 2. Co giãn kích thước hố ga, cột đèn, cáp, tủ
        this.checkDetailLayersVisibility(); // 3. Tự ẩn khi zoom < 16, hiện khi zoom >= 16
        this.checkAllLabelsVisibility();    // 4. Bật/tắt nhãn chữ (lô đất, tên đường)

        // 🌟 KHÓA CHẶT TỌA ĐỘ TOOLTIP SURROUNDING NGAY KHI DỪNG ZOOM
        if (leafletLayers.surroundingLocations && this.map.hasLayer(leafletLayers.surroundingLocations)) {
          leafletLayers.surroundingLocations.eachLayer((layer) => {
            const tooltip = layer.getTooltip();
            if (tooltip && tooltip._container) {
              // Buộc Leaflet cập nhật lại tọa độ offset theo kích thước thực tế
              tooltip.update();
            }
          });
        }
      }, 50);

      console.log(this.map.getZoom());
    },

    /**
     * 🌟 BẮT SỰ KIỆN KHI NGƯỜI DÙNG ĐỔI KCN TRÊN NAVIGATIONBAR
     */
    handleZoneChange(zone) {
      if (!this.map || !zone) return;

      this.activeZoneId = zone.id;

      if (zone.lat && zone.lng) {
        const targetLat = parseFloat(zone.lat);
        const targetLng = parseFloat(zone.lng);
        const targetZoom = zone.default_zoom || 15;

        // Thực hiện hiệu ứng bay flyTo mượt mà
        this.map.flyTo([targetLat, targetLng], targetZoom, {
          animate: true,
          duration: 1.5
        });
      }
    },

    drawPlots() {
      if (!this.plotsData || !this.plotsData.features.length) return;

      leafletLayers.plots = L.geoJSON(this.plotsData, {
        interactive: true,
        style: () => ({
          fillColor: '#b45309',
          fillOpacity: 0.35,
          color: '#f55b02',
          // dashArray: '4, 4'
          weight: 1,
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
            mouseout: (e) => { e.target.setStyle({ fillOpacity: 0.4, weight: 1 }); }
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
        style: (feature) => {
          const widthInMeters = feature.properties.road_width || feature.properties.right_of_way || 10;
          
          return {
            color: '#FFE082',
            // 🌟 Tính weight theo mét thực tế từ Database
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

    getWeightInPixels(widthInMeters) {
      if (!this.map || !widthInMeters) return 3; // Mặc định 3px nếu không có dữ liệu
      
      const zoom = this.map.getZoom();
      const lat = this.map.getCenter().lat;
      
      // Tỉ lệ Mét/Pixel tại vĩ độ hiện tại
      const metersPerPixel = (156543.03392 * Math.cos((lat * Math.PI) / 180)) / Math.pow(2, zoom);
      
      // Trả về số Pixel (đảm bảo tối thiểu 1.5px để không bị biến mất khi zoom xa)
      return Math.max(3, widthInMeters / metersPerPixel);
    },

     // Hàm cập nhật nét vẽ khi Zoom
    updateRoadsWidth() {
      if (!this.roadsGeoJsonLayer) return;

      this.roadsGeoJsonLayer.eachLayer((layer) => {
        if (layer.feature && layer.setStyle) {
          const widthInMeters = layer.feature.properties.road_width || layer.feature.properties.right_of_way || 10;
          layer.setStyle({
            weight: this.getWeightInPixels(widthInMeters)
          });
        }
      });

      // 🌟 GỌI LUÔN CÁC HẠ TẦNG CÒN LẠI TẠI ĐÂY
      this.updateInfrastructureSizes();
    },

    // 1. Quy đổi bán kính (mét thực tế) thành pixel cho các điểm Point (Hố ga, Cột đèn)
    getPointRadiusInPixels(radiusInMeters, minPx = 2, maxPx = 6) {
      if (!this.map || !radiusInMeters) return minPx;
      const zoom = this.map.getZoom();
      const lat = this.map.getCenter().lat;
      const metersPerPixel = (156543.03392 * Math.cos((lat * Math.PI) / 180)) / Math.pow(2, zoom);
      
      const calcPx = radiusInMeters / metersPerPixel;
      return Math.min(maxPx, Math.max(minPx, calcPx));
    },

    // 2. Quy đổi kích thước hình vuông (mét thực tế) thành pixel cho Tủ điện
    getCabinetSizeInPixels(sizeInMeters = 1.8, minPx = 12, maxPx = 24) {
      if (!this.map) return minPx;
      const zoom = this.map.getZoom();
      const lat = this.map.getCenter().lat;
      const metersPerPixel = (156543.03392 * Math.cos((lat * Math.PI) / 180)) / Math.pow(2, zoom);
      
      const calcPx = sizeInMeters / metersPerPixel;
      return Math.round(Math.min(maxPx, Math.max(minPx, calcPx)));
    },

    updateInfrastructureSizes() {
      if (!this.map) return;

      // 1. Tính toán các kích thước chuẩn tại mức zoom hiện tại
      const wastewaterR = this.getPointRadiusInPixels(3.0, 3.5, 9.0);
      const rainwaterR  = this.getPointRadiusInPixels(2.6, 3.0, 8.5);
      const poleR       = this.getPointRadiusInPixels(2.4, 3.0, 8.0);
      const cableWeight = this.getWeightInPixels(1); // Tăng độ dày cáp lên tương đương 1m           // Cáp chiếu sáng (khoảng 0.6m thực tế)
      const cabSize     = this.getCabinetSizeInPixels(2.8, 12, 22); // Tủ điện

      // 2. Co giãn Hố ga nước thải
      if (leafletLayers.wastewater) {
        leafletLayers.wastewater.eachLayer(l => {
          if (l.setRadius) l.setRadius(wastewaterR);
        });
      }

      // 3. Co giãn Hố ga nước mưa
      if (leafletLayers.rainwater) {
        leafletLayers.rainwater.eachLayer(l => {
          if (l.setRadius) l.setRadius(rainwaterR);
        });
      }

      // 4. Co giãn Cột đèn chiếu sáng
      if (leafletLayers.lightingPoles) {
        leafletLayers.lightingPoles.eachLayer(l => {
          if (l.setRadius) l.setRadius(poleR);
        });
      }

      // 5. Co giãn Tuyến cáp chiếu sáng (LineString)
      if (leafletLayers.lightingCables) {
        leafletLayers.lightingCables.eachLayer(l => {
          if (l.setStyle) l.setStyle({ weight: Math.max(1.5, Math.min(4, cableWeight)) });
        });
      }

      // 6. Co giãn Icon Tủ điện (Point Marker)
      if (leafletLayers.lightingCabinets) {
        leafletLayers.lightingCabinets.eachLayer(l => {
          if (l.setIcon) {
            l.setIcon(L.divIcon({
              className: 'custom-cabinet-marker',
              html: `<div class="cabinet-icon-box" style="width: ${cabSize}px; height: ${cabSize}px; font-size: ${Math.round(cabSize * 0.55)}px;" title="${l.feature?.properties?.code || ''}">⚡</div>`,
              iconSize: [cabSize, cabSize],
              iconAnchor: [Math.round(cabSize / 2), Math.round(cabSize / 2)]
            }));
          }
        });
      }
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
      });

      this.factoriesGeoJsonLayer = leafletLayers.factories;
    },

    drawWastewater() {
      if (!this.wastewaterManholesData || !this.wastewaterManholesData.features) return;

      leafletLayers.wastewater = L.geoJSON(this.wastewaterManholesData, {
        interactive: true,
        pointToLayer: (feature, latlng) => {
          return L.circleMarker(latlng, {
            radius: this.getPointRadiusInPixels(1.5, 2, 5.5),
            fillColor: '#800080',
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

          layer.on({
            mouseover: (e) => { e.target.setStyle({ radius: 8, weight: 3 }); },
            mouseout: (e) => { e.target.setStyle({ radius: 6, weight: 2 }); }
          });
        }
      });
    },

    drawRainwater() {
      if (!this.rainwaterManholesData || !this.rainwaterManholesData.features) return;

      leafletLayers.rainwater = L.geoJSON(this.rainwaterManholesData, {
        interactive: true,
        pointToLayer: (feature, latlng) => {
          return L.circleMarker(latlng, {
            radius: this.getPointRadiusInPixels(1.3, 2, 5.0),
            fillColor: '#007bff',
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

          layer.on({
            mouseover: (e) => { e.target.setStyle({ radius: 8, weight: 3 }); },
            mouseout: (e) => { e.target.setStyle({ radius: 6, weight: 2 }); }
          });
        }
      });
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
            <div class="legend-item">
              <span style="border-top: 3px dashed #e11d48; width: 16px; display: inline-block;"></span>
              <span class="legend-text">Ranh giới quy hoạch</span>
            </div>
            <div class="legend-item">
              <span style="background-color: #d97706; width: 10px; height: 10px; border-radius: 2px; border: 1.5px solid #fff; display: inline-block;"></span>
              <span class="legend-text">Tủ điện chiếu sáng</span>
            </div>
            <div class="legend-item">
              <span style="background-color: #eab308; width: 10px; height: 10px; border-radius: 50%; border: 1.5px solid #fff; display: inline-block;"></span>
              <span class="legend-text">Cột đèn chiếu sáng</span>
            </div>
            <div class="legend-item">
              <span style="border-top: 3px solid #f59e0b; width: 16px; display: inline-block;"></span>
              <span class="legend-text">Tuyến cáp ngầm CS</span>
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
        { layer: this.roadsGeoJsonLayer, minZoom: 17, maxZoom: 20},
        { layer: this.plotsGeoJsonLayer, minZoom: 17, maxZoom: 18},
        { layer: this.factoriesGeoJsonLayer, minZoom: 18, maxZoom: 20}
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

      const MIN_ZOOM = 17;
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
      // Ghi nhớ người dùng chủ động Bật hay Tắt trên sidebar
      if (this.userLayerVisibility[name] !== undefined) {
        this.userLayerVisibility[name] = config.visible;
      }

      const layer = leafletLayers[name];
      if (!layer) return;

      if (config.visible) {
        if (name === 'surroundingLocations') {
          const isInRange = currentZoom >= 14 && currentZoom < 16;
          if (isInRange && !this.map.hasLayer(layer)) {
            this.map.addLayer(layer);
          }
        } else {
          // Chỉ thêm vào map nếu mức zoom hiện tại >= 16 (với các lớp chi tiết)
          const detailLayers = ['lightingCabinets', 'lightingCables', 'lightingPoles', 'wastewater', 'rainwater'];
          const currentZoom = this.map.getZoom();

          if (!detailLayers.includes(name) || currentZoom >= 16) {
            if (!this.map.hasLayer(layer)) {
              this.map.addLayer(layer);
            }
          }
          console.log(config);
          if (config.name === "greenery") {
            this.greeneryLabelMarkers.forEach(marker => {
              marker.openTooltip();
            });
          } else if (config.name === "waterBodies") {
            this.waterLabelMarkers.forEach(marker => {
              marker.openTooltip();
            });
          }
        }
      } else {
        if (this.map.hasLayer(layer)) {
          this.map.removeLayer(layer);
        }
        if (config.name === "greenery") {
          this.greeneryLabelMarkers.forEach(marker => {
            marker.closeTooltip();
          });
        } else if (config.name === "waterBodies") {
          this.waterLabelMarkers.forEach(marker => {
            marker.closeTooltip();
          });
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

    // 🌟 TỰ ĐỘNG ẨN KHI ZOOM < 16, HIỆN KHI ZOOM >= 16
    checkDetailLayersVisibility() {
      if (!this.map) return;
      const currentZoom = this.map.getZoom();
      const MIN_DETAIL_ZOOM = 16;

      // Danh sách 5 lớp hạ tầng chi tiết cần ẩn/hiện theo zoom
      const detailLayerKeys = [
        'lightingCabinets',
        'lightingCables',
        'lightingPoles',
        'wastewater',
        'rainwater'
      ];

      detailLayerKeys.forEach((key) => {
        const layer = leafletLayers[key];
        const userWantsVisible = this.userLayerVisibility[key];

        if (!layer) return;

        // Nếu người dùng BẬT trên sidebar VÀ zoom >= 16 -> HIỂN THỊ
        if (userWantsVisible && currentZoom >= MIN_DETAIL_ZOOM) {
          if (!this.map.hasLayer(layer)) {
            this.map.addLayer(layer);
          }
        } else {
          // Nếu zoom < 16 hoặc người dùng đã TẮT ở sidebar -> ẨN ĐI
          if (this.map.hasLayer(layer)) {
            this.map.removeLayer(layer);
          }
        }
      });

      // 2. 🌟 LỚP VÙNG LÂN CẬN (Zoom < 16 mới HIỆN, Zoom >= 16 thì ẨN)
      const surroundingLayer = leafletLayers.surroundingLocations;
      const userWantsSurrounding = this.userLayerVisibility.surroundingLocations ?? true;

      if (surroundingLayer) {
        const isInSurroundingZoomRange = currentZoom >= 14 && currentZoom < 16;
        if (userWantsSurrounding && isInSurroundingZoomRange) {
          if (!this.map.hasLayer(surroundingLayer)) {
            this.map.addLayer(surroundingLayer);
          }
        } else {
          if (this.map.hasLayer(surroundingLayer)) {
            this.map.removeLayer(surroundingLayer);
          }
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

    drawBoundaries() {
      if (!this.map || !this.boundariesData) return;

      // 🌟 Kiểm tra nếu pane chưa tồn tại thì tự động tạo luôn
      if (!this.map.getPane('boundariesPane')) {
        this.map.createPane('boundariesPane');
        this.map.getPane('boundariesPane').style.zIndex = 390;
        this.map.getPane('boundariesPane').style.pointerEvents = 'none';
      }

      let geoData = this.boundariesData;
      if (typeof geoData === 'string') {
        try {
          geoData = JSON.parse(geoData);
        } catch (e) {
          console.error("Lỗi parse JSON boundariesData:", e);
          return;
        }
      }

      if (!geoData.features || !geoData.features.length) return;

      leafletLayers.boundaries = L.geoJSON(geoData, {
        pane: 'boundariesPane',
        interactive: false,
        style: (feature) => {
          const p = feature.properties || {};
          return {
            pane: 'boundariesPane',
            color: p.fill_color || '#e11d48',
            weight: 3.5,
            dashArray: '10, 6',
            opacity: 0.95
          };
        }
      }).addTo(this.map);
    },

    // ⚡ 1. VẼ TỦ ĐIỆN CHIẾU SÁNG (Point - Hình vuông cam nổi bật)
    drawLightingCabinets() {
      const initCabSize = this.getCabinetSizeInPixels(1.8, 12, 22);

      if (!this.lightingCabinetsData?.features?.length) return;

      leafletLayers.lightingCabinets = L.geoJSON(this.lightingCabinetsData, {
        pane: 'lightingPointsPane',
        interactive: true,
        pointToLayer: (feature, latlng) => {
          // Dùng DivIcon hình hộp điện thu nhỏ
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
              <p style="margin: 3px 0;"><b>Tên tủ:</b> ${p.name}</p>
              <p style="margin: 3px 0;"><b>Công suất:</b> ${p.capacity_kva ? p.capacity_kva + ' kVA' : '—'}</p>
              <p style="margin: 3px 0;"><b>Chế độ điều khiển:</b> ${p.control_type === 'timer' ? '⏱️ Hẹn giờ' : (p.control_type === 'photocell' ? '☀️ Cảm biến quang' : '📡 Smart IoT')}</p>
              <p style="margin: 3px 0;"><b>Nguồn cấp TBA:</b> ${p.power_source || '—'}</p>
              <p style="margin: 3px 0;"><b>Trạng thái:</b> ${p.status === 'active' ? '🟢 Đang hoạt động' : '🔴 Ngừng cấp điện'}</p>
              <hr style="margin: 6px 0; border: none; border-top: 1px dashed #cbd5e1;">
              <a href="${this.getGoogleMapsUrl(lat, lng)}" target="_blank" class="directions-btn">
                🧭 Chỉ đường tới tủ điện
              </a>
            </div>
          `);
        }
      });
    },

    // 〰️ 2. VẼ TUYẾN CÁP CHIẾU SÁNG (LineString - Nét liền màu cam/vàng)
    drawLightingCables() {
      if (!this.lightingCablesData?.features?.length) return;

      // 🌟 Dùng SVG renderer riêng cho pane cáp để click xuyên thấu chính xác
      const cableRenderer = L.svg({ pane: 'lightingCablesPane' });

      leafletLayers.lightingCables = L.geoJSON(this.lightingCablesData, {
        pane: 'lightingCablesPane',
        renderer: cableRenderer,
        interactive: true,
        style: (feature) => {
          const isFault = feature.properties.status === 'fault';
          return {
            pane: 'lightingCablesPane',
            color: isFault ? '#ef4444' : '#f59e0b',
            weight: Math.max(1.5, Math.min(4, this.getWeightInPixels(0.6))), // Tăng lên 4px để dễ click
            dashArray: feature.properties.installation_type === 'overhead' ? '6, 6' : null,
            opacity: 0.95
          };
        },
        onEachFeature: (feature, layer) => {
          const p = feature.properties;

          // Popup thông tin tuyến cáp
          layer.bindPopup(`
            <div style="font-size: 13px; min-width: 210px; font-family: sans-serif;">
              <h4 style="margin: 0 0 6px 0; color: #f59e0b;">〰️ TUYẾN CÁP CHIẾU SÁNG</h4>
              <p style="margin: 3px 0;"><b>Mã lộ:</b> <span style="color:#0284c7; font-weight:bold;">${p.code}</span></p>
              <p style="margin: 3px 0;"><b>Tên tuyến:</b> ${p.name}</p>
              <p style="margin: 3px 0;"><b>Quy cách:</b> ${p.cable_spec || 'Cu/XLPE 4x16mm2'}</p>
              <p style="margin: 3px 0;"><b>Lắp đặt:</b> ${p.installation_type === 'underground' ? '🚇 Cáp ngầm hào' : '🪢 Cáp treo'}</p>
              <p style="margin: 3px 0;"><b>Chiều dài đo được:</b> <b style="color:#0284c7;">${p.length_m || '—'} m</b></p>
              <p style="margin: 3px 0;"><b>Tình trạng:</b> ${p.status === 'normal' ? '🟢 Bình thường' : '🔴 Sự cố đứt/chập'}</p>
            </div>
          `);

          // Hiệu ứng rê chuột sáng viền và đổi con trỏ pointer
          layer.on({
            mouseover: (e) => { 
              e.target.setStyle({ weight: 6, color: '#fbbf24', opacity: 1 }); 
            },
            mouseout: (e) => { 
              const isFault = feature.properties.status === 'fault';
              e.target.setStyle({ weight: 4, color: isFault ? '#ef4444' : '#f59e0b', opacity: 0.95 }); 
            }
          });
        }
      });
    },

    // 💡 3. VẼ CỘT ĐÈN CHIẾU SÁNG (Point - Viên tròn vàng phát sáng)
    drawLightingPoles() {
      if (!this.lightingPolesData?.features?.length) return;

      // 🌟 Dùng SVG renderer riêng cho cột đèn
      const poleRenderer = L.svg({ pane: 'lightingPointsPane' });

      leafletLayers.lightingPoles = L.geoJSON(this.lightingPolesData, {
        pane: 'lightingPointsPane',
        renderer: poleRenderer,
        interactive: true,
        pointToLayer: (feature, latlng) => {
          const isBroken = feature.properties.status === 'broken';
          return L.circleMarker(latlng, {
            pane: 'lightingPointsPane',
            renderer: poleRenderer,
            interactive: true, // 🌟 Bắt buộc phải có trong circleMarker để nhận click
            radius: this.getPointRadiusInPixels(1.2, 1.8, 5.0),         // Tăng từ 4.5px lên 6px để dễ bấm trúng
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
              <p style="margin: 3px 0;"><b>Chiều cao:</b> ${p.height_m || 8} m (${p.arm_type || 'Cần đơn'})</p>
              <p style="margin: 3px 0;"><b>Loại bóng:</b> ${p.lamp_type || 'LED'} - <b>${p.wattage || 150}W</b></p>
              <p style="margin: 3px 0;"><b>Chất liệu:</b> ${p.pole_material || 'Thép mạ kẽm'}</p>
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
    },

    // 📍 VẼ CÁC VÙNG PHỤ CẬN LÂN CẬN (DÙNG CHÍNH CIRCLEMARKER ĐỂ KHÓA TỌA ĐỘ 100%)
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

          // 1. Tạo chấm tròn chuẩn toạ độ
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

          // 2. Nội dung nhãn
          const tooltipContent = `
            <div class="surrounding-tag" style="background-color: ${bgColor}; color: ${textColor}; border-color: ${bgColor};">
              <span>${icon} ${name}</span>
            </div>
          `;

          // 3. Gắn Tooltip: 🌟 ÉP NẰM CHUNG PANE VỚI CIRCLEMARKER
          marker.bindTooltip(tooltipContent, {
            pane: 'surroundingPane',  // 🌟 BẮT BUỘC: Đưa tooltip về chung một hệ quy chiếu với marker
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
  bottom: calc(105px + env(safe-area-inset-bottom, 0px));
  right: 18px;
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
:deep(.leaflet-control-attribution){
  display: none;
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

/* ICON TỦ ĐIỆN CHIẾU SÁNG */
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

/* Giải phóng vùng đệm pane chiếu sáng để click xuyên qua khoảng trống */
.leaflet-lightingCablesPane-pane,
.leaflet-lightingPointsPane-pane {
  pointer-events: none !important;
}

/* Kích hoạt click trên chính nét vẽ cáp và hình tròn bóng đèn */
.leaflet-lightingCablesPane-pane svg path,
.leaflet-lightingPointsPane-pane svg path,
.leaflet-lightingPointsPane-pane .leaflet-interactive {
  pointer-events: auto !important;
  cursor: pointer !important;
}

/* =============================================================================
 * CSS FIX TRÔI TOOLTIP: DÙNG CƠ CHẾ POSITION CỦA LEAFLET
 * ============================================================================= */

.leaflet-tooltip.surrounding-leaflet-tooltip {
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
  padding: 0 !important;
  margin: 0 !important;
  pointer-events: auto !important;
  cursor: pointer !important;
}

/* Chóp tam giác trỏ xuống */
.leaflet-tooltip-top.surrounding-leaflet-tooltip::before {
  border-top-color: inherit !important;
  border-width: 6px 5px 0 !important;
  bottom: -6px !important;
  margin-left: -5px !important;
}

/* Thân nhãn bong bóng: CHỈ DÙNG display inline-flex thông thường */
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
  /* ⚠️ TUYỆT ĐỐI KHÔNG để transform, position: absolute, margin âm ở đây */
}
</style>