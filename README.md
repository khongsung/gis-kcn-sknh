# 🗺️ Hệ Thống WebGIS Quản Lý Hạ Tầng KCN Song Khê - Nội Hoàng

Hệ thống quản lý không gian và thông tin hạ tầng kỹ thuật Khu công nghiệp Song Khê - Nội Hoàng trên nền tảng **WebGIS**. Hỗ trợ theo dõi, truy vấn không gian (PostGIS) và quản lý số hóa toàn bộ các lớp dữ liệu hạ tầng.

---

## 🚀 Công Nghệ Sử Dụng

- **Backend:** PHP 8.2+ / Laravel 11 / Eloquent ORM
- **Frontend:** Inertia.js / Vue.js 3 / Tailwind CSS
- **Database / GIS:** PostgreSQL / PostGIS Extension (ST_GeomFromGeoJSON, ST_AsGeoJSON)
- **Bản đồ:** OpenLayers / Leaflet / Mapbox GL
- **Kiểm thử (Testing):** PHPUnit / Feature Tests cho tất cả các Controller hạ tầng

---

## 🛠️ Các Lớp Dữ Liệu Hạ Tầng Quản Lý

- 🏢 **Lô đất & Doanh nghiệp:** Quản lý quy hoạch lô đất, liên kết đa công ty thuê (`Plot`, `Company`).
- 🏭 **Nhà xưởng:** Theo dõi trạng thái xưởng trống/đã thuê (`Factory`).
- 🛣️ **Đường giao thông:** Quản lý lộ giới, lòng đường, dữ liệu LineString (`Road`).
- 🌳 **Cây xanh & Thảm cỏ:** Quản lý diện tích dải cây xanh cách ly (`Greenery`).
- 💧 **Mặt nước & Hồ điều hòa:** Quản lý dung tích, diện tích vùng nước (`WaterBody`).
- 🕳️ **Hệ thống Hố ga:** Hố ga nước mưa (`RainwaterManhole`) & Hố ga nước thải (`WastewaterManhole`).

---

## 📦 Hướng Dẫn Cài Đặt Local

### 1. Clone dự án & Cài đặt dependencies
```bash
git clone [https://github.com/khongsung/gis-kcn-sknh.git](https://github.com/khongsung/gis-kcn-sknh.git)
cd gis-kcn-sknh

# Cài đặt PHP Packages
composer install

# Cài đặt Node Modules
npm install