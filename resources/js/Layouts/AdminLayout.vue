<template>
  <div class="admin-page">
    <!-- 1. TOP HEADER (NAVIGATION BAR CHUNG) -->
    <NavigationBar />

    <!-- 2. THÂN TRANG BÊN DƯỚI -->
    <div class="admin-body">
      <!-- NÚT BẤM MỞ SIDEBAR TRÊN MOBILE -->
      <button class="mobile-sidebar-toggle" @click="isSidebarOpen = !isSidebarOpen">
        {{ isSidebarOpen ? '✕ Đóng Menu Quản trị' : '☰ Menu Quản trị Admin' }}
      </button>

      <!-- OVERLAY MỜ MOBILE -->
      <div v-if="isSidebarOpen" class="sidebar-overlay" @click="isSidebarOpen = false"></div>

      <!-- SIDEBAR QUẢN TRỊ BÊN TRÁI -->
      <aside class="admin-sidebar" :class="{ 'mobile-open': isSidebarOpen }">
        <div class="sidebar-user-card">
          <div class="user-avatar-lg">
            {{ user?.name ? user.name.charAt(0).toUpperCase() : 'A' }}
          </div>
          <div class="user-details">
            <span class="user-name-text">{{ user?.name || 'Tài khoản' }}</span>
            <span class="user-role-badge" :class="user?.role">
              {{ user?.role === 'admin' ? 'Quản trị viên KCN' : 'Đại diện Doanh nghiệp' }}
            </span>
          </div>
        </div>

        <nav class="sidebar-menu">
          <div class="menu-label">DANH MỤC QUẢN LÝ</div>

          <a href="/admin/dashboard" class="menu-item" :class="{ active: currentPath === '/admin/dashboard' }">
            <span class="menu-icon">📊</span>
            <span>Tổng quan (Dashboard)</span>
          </a>

          <!-- CHỈ ADMIN MỚI NHÌN THẤY CÁC MENU NÀY -->
          <template v-if="isAdmin">
            <a href="/admin/users" class="menu-item" :class="{ active: currentPath.startsWith('/admin/users') }">
              <span class="menu-icon">👥</span>
              <span>Quản lý User</span>
            </a>

            <a href="/admin/plots" class="menu-item" :class="{ active: currentPath.startsWith('/admin/plots') }">
              <span class="menu-icon">🗺️</span>
              <span>Quản lý Lô đất</span>
            </a>

            <a href="/admin/companies" class="menu-item" :class="{ active: currentPath.startsWith('/admin/companies') }">
              <span class="menu-icon">🏢</span>
              <span>Quản lý Công ty</span>
            </a>

            <a href="/admin/factories" class="menu-item" :class="{ active: currentPath.startsWith('/admin/factories') }">
              <span class="menu-icon">🏭</span>
              <span>Quản lý Nhà xưởng</span>
            </a>

            <a href="/admin/rainwater-manholes" class="menu-item" :class="{ active: currentPath.startsWith('/admin/rainwater-manholes') }">
              <span class="menu-icon">🌧️</span>
              <span>Hố ga Nước mưa</span>
            </a>

            <a href="/admin/wastewater-manholes" class="menu-item" :class="{ active: currentPath.startsWith('/admin/wastewater-manholes') }">
              <span class="menu-icon">🧪</span>
              <span>Hố ga Nước thải</span>
            </a>

            <a href="/admin/roads" class="menu-item" :class="{ active: currentPath.startsWith('/admin/roads') }">
              <span class="menu-icon">🛣️</span>
              <span>Đường giao thông</span>
            </a>

            <a href="/admin/greenery" class="menu-item" :class="{ active: currentPath.startsWith('/admin/greenery') }">
              <span class="icon">🌳</span>
              <span class="label">Quản lý Cây Xanh</span>
            </a>

            <a href="/admin/water-bodies" class="menu-item" :class="{ active: currentPath.startsWith('/admin/water-bodies') }">
              <span class="icon">🌊</span>
              <span class="label">Quản lý Mặt Nước</span>
            </a>
          </template>

          <!-- MENU DÀNH CHO CẢ ADMIN VÀ USER DOANH NGHIỆP -->
          <!-- <a href="/admin/recruitments" class="menu-item" :class="{ active: currentPath.startsWith('/admin/recruitments') }">
            <span class="menu-icon">💼</span>
            <span>Đăng tin tuyển dụng</span>
          </a> -->
        </nav>
      </aside>

      <!-- MAIN CONTENT SECTION (BÊN PHẢI) -->
      <main class="admin-main">
        <slot />
      </main>
    </div>
  </div>
</template>

<script>
import NavigationBar from '@/Components/NavigationBar.vue';

export default {
  name: 'AdminLayout',
  components: {
    NavigationBar
  },
  data() {
    return {
      isSidebarOpen: false,
      currentPath: window.location.pathname
    };
  },
  computed: {
    user() {
      return this.$page.props.auth?.user || null;
    },
    isAdmin() {
      return this.user && this.user.role === 'admin';
    }
  }
};
</script>

<style scoped>
.admin-page {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background-color: #f1f5f9;
}

.admin-body {
  display: flex;
  flex: 1;
  position: relative;
}

/* SIDEBAR CỘT BÊN TRÁI */
.admin-sidebar {
  width: 260px;
  background: #ffffff;
  border-right: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  box-shadow: 2px 0 10px rgba(0,0,0,0.02);
}

.sidebar-user-card {
  padding: 20px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar-lg {
  width: 42px;
  height: 42px;
  background: #2563eb;
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 18px;
  flex-shrink: 0;
}

.user-details {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.user-name-text {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-role-badge {
  font-size: 10px;
  font-weight: 600;
  display: inline-block;
  padding: 2px 6px;
  border-radius: 4px;
  margin-top: 3px;
}

.user-role-badge.admin {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fca5a5;
}

.user-role-badge.company {
  background: #eff6ff;
  color: #2563eb;
  border: 1px solid #93c5fd;
}

.sidebar-menu {
  padding: 16px 12px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.menu-label {
  font-size: 10px;
  font-weight: 800;
  color: #94a3b8;
  letter-spacing: 0.5px;
  padding: 8px 12px;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 14px;
  color: #475569;
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.menu-item:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.menu-item.active {
  background: #2563eb;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
}

.menu-icon {
  font-size: 16px;
}

/* MAIN CONTENT BÊN PHẢI */
.admin-main {
  flex: 1;
  padding: 24px;
  box-sizing: border-box;
  overflow-y: auto;
}

.mobile-sidebar-toggle, .sidebar-overlay {
  display: none;
}

/* RESPONSIVE MOBILE (< 992px) */
@media (max-width: 992px) {
  .admin-body {
    flex-direction: column;
  }

  .mobile-sidebar-toggle {
    display: block;
    width: 100%;
    padding: 12px;
    background: #1e293b;
    color: #38bdf8;
    border: none;
    font-weight: bold;
    font-size: 13px;
    cursor: pointer;
    text-align: center;
  }

  .sidebar-overlay {
    display: block;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1001;
  }

  .admin-sidebar {
    position: fixed;
    top: 0;
    left: -280px;
    width: 270px;
    height: 100vh;
    z-index: 1002;
    transition: left 0.3s ease-in-out;
    box-shadow: 4px 0 20px rgba(0,0,0,0.2);
  }

  .admin-sidebar.mobile-open {
    left: 0;
  }

  .admin-main {
    padding: 16px;
  }
}
</style>