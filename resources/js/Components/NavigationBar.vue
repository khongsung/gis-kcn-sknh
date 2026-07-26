<template>
  <header class="top-navbar">
    <!-- 1. BRAND LOGO (BÊN TRÁI) -->
    <div class="nav-brand">
      <Link href="/" class="brand-link">
        <div class="logo-box">
          <img 
            src="/storage/images/logo_HTKCN.png" 
            alt="Logo KCN" 
            class="logo-img" 
            @error="handleLogoError" 
          />
          <span v-if="imageError" class="logo-fallback">GIS</span>
        </div>
        <div class="brand-info">
          <h1 class="brand-title">KCN SONG KHÊ - NỘI HOÀNG</h1>
          <span class="brand-sub">Hệ Thống Quản Lý Hạ Tầng GIS</span>
        </div>
      </Link>
    </div>

    <!-- NÚT HAMBURGER BẤM MỞ MENU TRÊN MOBILE -->
    <button class="hamburger-btn" @click="isMobileMenuOpen = !isMobileMenuOpen" aria-label="Toggle Menu">
      <span v-if="!isMobileMenuOpen">☰</span>
      <span v-else>✕</span>
    </button>

    <!-- OVERLAY MỜ NỀN KHI MỞ MENU MOBILE -->
    <div v-if="isMobileMenuOpen" class="nav-overlay" @click="isMobileMenuOpen = false"></div>

    <!-- 2. KHUNG CHỨA MENU & AUTH -->
    <div class="nav-content" :class="{ 'mobile-open': isMobileMenuOpen }">
      <!-- DANH SÁCH MENU NẰM NGANG -->
      <nav class="nav-menu">
        <Link 
          href="/" 
          class="nav-link" 
          :class="{ active: currentPath === '/' || currentPath === '' }"
          @click="isMobileMenuOpen = false"
        >
          <span class="nav-icon">🗺️</span>
          <span class="nav-label">Trang chủ (Map)</span>
        </Link>

        <Link 
          href="/contact" 
          class="nav-link" 
          :class="{ active: currentPath.startsWith('/contact') }"
          @click="isMobileMenuOpen = false"
        >
          <span class="nav-icon">🌟</span>
          <span class="nav-label">Liên hệ Chủ đầu tư</span>
        </Link>
      </nav>

      <!-- 3. KHUNG USER / ĐĂNG NHẬP (BÊN PHẢI CÙNG) -->
      <div class="nav-auth">
        <!-- ĐÃ ĐĂNG NHẬP -->
        <template v-if="currentUser">
          <div class="user-profile-wrapper">
            <Link href="/admin/dashboard" class="user-profile-link" title="Truy cập Trang quản trị Admin">
              <div class="user-profile">
                <div class="avatar-circle">
                  {{ currentUser.name ? currentUser.name.charAt(0).toUpperCase() : 'U' }}
                </div>
                <div class="user-info">
                  <span class="user-name">{{ currentUser.name }}</span>
                  <span class="user-role">{{ currentUser.role === 'admin' ? '👑 Quản trị viên' : '🏢 Doanh nghiệp' }}</span>
                </div>
              </div>
            </Link>

            <button @click.prevent="logout" class="logout-btn" title="Đăng xuất khỏi hệ thống">
              🚪
            </button>
          </div>
        </template>

        <!-- CHƯA ĐĂNG NHẬP: HIỆN NÚT ĐĂNG NHẬP -->
        <template v-else>
          <Link href="/login" class="login-btn" @click="isMobileMenuOpen = false">
            🔑 Đăng nhập
          </Link>
        </template>
      </div>
    </div>
  </header>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3';

export default {
  name: 'NavigationBar',
  components: { Link },
  data() {
    return {
      isMobileMenuOpen: false,
      imageError: false
    };
  },
  computed: {
    currentPath() {
      return this.$page?.url || window.location.pathname;
    },

    currentUser() {
      return this.$page?.props?.auth?.user || null;
    },

    isAdmin() {
      return this.currentUser && this.currentUser.role === 'admin';
    }
  },
  methods: {
    handleLogoError() {
      this.imageError = true;
    },

    logout() {
      if (confirm('Anh có chắc chắn muốn đăng xuất khỏi hệ thống?')) {
        this.$inertia.post('/logout');
      }
    }
  }
};
</script>

<style scoped>
/* THANH TOP HEADER NẰM NGANG FULL WIDTH */
.top-navbar {
  width: 100vw;
  height: 60px;
  background: #0f172a;
  color: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  /* Thêm safe-area cho phần tai thỏ / notch nếu có */
  padding-top: env(safe-area-inset-top, 0px); 
  box-sizing: border-box;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
  z-index: 2000;
  position: relative;
  user-select: none;
}

.nav-brand {
  display: flex;
  align-items: center;
  flex-shrink: 0;
}

.brand-link {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
}

.logo-box {
  width: 36px;
  height: 36px;
  background: #2563eb;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  color: #fff;
  overflow: hidden;
}

.logo-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.brand-title {
  font-size: 14px;
  font-weight: 800;
  margin: 0;
  color: #38bdf8;
  letter-spacing: 0.5px;
}

.brand-sub {
  font-size: 10px;
  color: #94a3b8;
  display: block;
}

.hamburger-btn, .nav-overlay {
  display: none;
}

.nav-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex: 1;
  margin-left: 24px;
}

.nav-menu {
  display: flex;
  align-items: center;
  gap: 6px;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 7px 12px;
  color: #cbd5e1;
  text-decoration: none;
  font-size: 13px;
  font-weight: 500;
  border-radius: 6px;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.nav-link:hover {
  background: #1e293b;
  color: #ffffff;
}

.nav-link.active {
  background: #2563eb;
  color: #ffffff;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.35);
}

.admin-link {
  border: 1px dashed #3b82f6;
}

.badge-admin {
  font-size: 8px;
  background: #ef4444;
  color: #fff;
  padding: 1px 5px;
  border-radius: 6px;
  font-weight: 800;
  text-transform: uppercase;
}

/* USER AUTH BOX BÊN PHẢI */
.nav-auth {
  display: flex;
  align-items: center;
  margin-left: auto;
}

.login-btn {
  padding: 10px 16px;
  background: #2563eb;
  color: #ffffff;
  text-decoration: none;
  font-size: 14px;
  font-weight: bold;
  border-radius: 8px;
  transition: background 0.2s;
  white-space: nowrap;
  box-sizing: border-box;
}

.login-btn:hover {
  background: #1d4ed8;
}

.user-profile-wrapper {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #1e293b;
  padding: 4px 6px 4px 10px;
  border-radius: 20px;
  border: 1px solid #334155;
}

.user-profile-link {
  text-decoration: none;
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 8px;
}

.avatar-circle {
  width: 28px;
  height: 28px;
  background: #10b981;
  color: #ffffff;
  font-weight: 800;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  flex-shrink: 0;
}

.user-info {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-size: 12px;
  font-weight: bold;
  color: #f8fafc;
  white-space: nowrap;
}

.user-role {
  font-size: 9px;
  color: #38bdf8;
}

.logout-btn {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #ef4444;
  font-size: 14px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.logout-btn:hover {
  background: #ef4444;
  color: #ffffff;
  transform: scale(1.05);
}

/* RESPONSIVE MOBILE (XỬ LÝ DÀNH RIÊNG CHO IPHONE / SAFARI) */
@media (max-width: 992px) {
  .hamburger-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    color: #ffffff;
    font-size: 22px;
    cursor: pointer;
    z-index: 2002;
    width: 36px;
    height: 36px;
  }

  .nav-overlay {
    display: block;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(2px);
    z-index: 2000;
  }

  .nav-content {
    position: fixed;
    top: 0;
    right: -280px;
    width: 260px;
    /* 🌟 FIX 1: Chuyển 100vh sang 100dvh để tự co giãn theo thanh trình duyệt di động */
    height: 100vh;
    height: 100dvh; 
    background: #0f172a;
    flex-direction: column;
    justify-content: flex-start;
    align-items: stretch;
    margin-left: 0;
    /* 🌟 FIX 2: Thêm pb (padding-bottom) có tính Safe Area cho mép dưới iPhone */
    padding: 70px 16px calc(24px + env(safe-area-inset-bottom, 0px)) 16px;
    box-sizing: border-box;
    transition: right 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    z-index: 2001;
    box-shadow: -4px 0 20px rgba(0, 0, 0, 0.5);
    overflow-y: auto; /* Cho phép cuộn nếu màn hình quá ngắn */
  }

  .nav-content.mobile-open {
    right: 0;
  }

  .nav-menu {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
    margin-bottom: 20px;
  }

  .nav-link {
    padding: 10px 14px;
    font-size: 14px;
  }

  .nav-auth {
    margin-left: 0;
    margin-top: auto;
    padding-top: 20px;
    border-top: 1px solid #1e293b;
    /* 🌟 FIX 3: Khoảng cách an toàn bổ sung ở chân Menu */
    padding-bottom: calc(10px + env(safe-area-inset-bottom, 0px));
  }

  .user-profile-wrapper {
    width: 100%;
    justify-content: space-between;
    box-sizing: border-box;
  }

  .login-btn {
    text-align: center;
    width: 100%;
    display: block;
    padding: 12px 16px; /* Làm nút to ra hơn một chút để dễ bấm trên điện thoại */
  }
}
</style>