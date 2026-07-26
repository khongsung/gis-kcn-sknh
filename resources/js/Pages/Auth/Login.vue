<template>
  <div class="login-page">
    <div class="login-card">
      <div class="card-header">
        <div class="logo-circle">🗺️</div>
        <h2>ĐĂNG NHẬP HỆ THỐNG</h2>
        <p>Quản lý hạ tầng KCN Song Khê - Nội Hoàng</p>
      </div>

      <!-- BÁO LỖI CHUNG HOẶC THÔNG BÁO TỪ BACKEND -->
      <div v-if="form.errors.email && !clientErrors.email" class="alert-error">
        ⚠️ {{ form.errors.email }}
      </div>

      <form @submit.prevent="submit" class="login-form" novalidate>
        <!-- 1. Ô NHẬP EMAIL -->
        <div class="form-group" :class="{ 'has-error': clientErrors.email || form.errors.email }">
          <label for="email">📧 Email đăng nhập</label>
          <input 
            type="email" 
            id="email" 
            v-model="form.email" 
            placeholder="admin@songkhenoihoang.vn" 
            :disabled="form.processing"
            @blur="validateEmail"
            @input="clearFieldError('email')"
          />
          <span v-if="clientErrors.email" class="error-msg">{{ clientErrors.email }}</span>
          <span v-else-if="form.errors.email" class="error-msg">{{ form.errors.email }}</span>
        </div>

        <!-- 2. Ô NHẬP MẬT KHẨU (KÈM NÚT ẨN/HIỆN MẬT KHẨU) -->
        <div class="form-group" :class="{ 'has-error': clientErrors.password || form.errors.password }">
          <label for="password">🔑 Mật khẩu</label>
          <div class="password-input-wrapper">
            <input 
              :type="showPassword ? 'text' : 'password'" 
              id="password" 
              v-model="form.password" 
              placeholder="••••••••" 
              :disabled="form.processing"
              @blur="validatePassword"
              @input="clearFieldError('password')"
            />
            <button 
              type="button" 
              class="toggle-password-btn" 
              @click="showPassword = !showPassword"
              tabindex="-1"
              title="Ẩn/Hiện mật khẩu"
            >
              {{ showPassword ? '👁️' : '🙈' }}
            </button>
          </div>
          <span v-if="clientErrors.password" class="error-msg">{{ clientErrors.password }}</span>
          <span v-else-if="form.errors.password" class="error-msg">{{ form.errors.password }}</span>
        </div>

        <!-- 3. TÙY CHỌN GHI NHỚ -->
        <div class="form-options">
          <label class="remember-me">
            <input type="checkbox" v-model="form.remember" :disabled="form.processing" />
            <span>Ghi nhớ đăng nhập</span>
          </label>
        </div>

        <!-- 4. NÚT SUBMIT -->
        <button type="submit" class="submit-btn" :disabled="form.processing || hasClientErrors">
          <span v-if="!form.processing">ĐĂNG NHẬP</span>
          <span v-else class="loading-state">
            <span class="spinner"></span> Đang xác thực...
          </span>
        </button>

        <div class="card-footer">
          <a href="/" class="back-home">← Quay lại Bản đồ KCN</a>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Login',
  data() {
    return {
      showPassword: false,
      form: this.$inertia.form({
        email: '',
        password: '',
        remember: false,
      }),
      clientErrors: {
        email: '',
        password: ''
      }
    };
  },
  computed: {
    hasClientErrors() {
      return !!(this.clientErrors.email || this.clientErrors.password);
    }
  },
  methods: {
    validateEmail() {
      if (!this.form.email) {
        this.clientErrors.email = 'Vui lòng nhập địa chỉ email.';
        return false;
      }
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(this.form.email)) {
        this.clientErrors.email = 'Địa chỉ email không đúng định dạng.';
        return false;
      }
      this.clientErrors.email = '';
      return true;
    },

    validatePassword() {
      if (!this.form.password) {
        this.clientErrors.password = 'Vui lòng nhập mật khẩu.';
        return false;
      }
      if (this.form.password.length < 6) {
        this.clientErrors.password = 'Mật khẩu phải có ít nhất 6 ký tự.';
        return false;
      }
      this.clientErrors.password = '';
      return true;
    },

    clearFieldError(field) {
      this.clientErrors[field] = '';
      if (this.form.errors[field]) {
        this.form.clearErrors(field);
      }
    },

    submit() {
      const isEmailValid = this.validateEmail();
      const isPasswordValid = this.validatePassword();

      if (!isEmailValid || !isPasswordValid) return;

      this.form.post('/login', {
        onError: (errors) => {
          // Xóa mật khẩu khi gõ sai
          this.form.reset('password');
          // Bắt buộc Inertia cập nhật lại CSRF Token mới từ Session
          this.$inertia.reload({ only: ['errors', 'flash'] });
        },
        onFinish: () => {
          if (!this.form.hasErrors) {
            this.form.reset('password');
          }
        },
      });
    }
  }
};
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  min-height: 100dvh; /* Co giãn chuẩn trên khung hình iPhone Safari */
  width: 100vw;
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  box-sizing: border-box;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

.login-card {
  width: 100%;
  max-width: 400px;
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
  padding: 32px 24px;
  box-sizing: border-box;
}

.card-header { text-align: center; margin-bottom: 24px; }
.logo-circle { 
  width: 52px; 
  height: 52px; 
  background: #2563eb; 
  border-radius: 50%; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  font-size: 26px; 
  margin: 0 auto 12px auto; 
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35); 
}

.card-header h2 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; letter-spacing: 0.5px; }
.card-header p { margin: 6px 0 0 0; font-size: 12px; color: #64748b; }

.alert-error { 
  background: #fef2f2; 
  border: 1px solid #fca5a5; 
  color: #dc2626; 
  padding: 10px 12px; 
  border-radius: 8px; 
  font-size: 13px; 
  font-weight: 500;
  margin-bottom: 18px; 
  text-align: center; 
}

.form-group { margin-bottom: 18px; position: relative; }
.form-group label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; }

.form-group input { 
  width: 100%; 
  padding: 10px 14px; 
  border: 1px solid #cbd5e1; 
  border-radius: 8px; 
  font-size: 14px; 
  box-sizing: border-box; 
  transition: all 0.2s ease;
  background-color: #f8fafc;
}

.form-group input:focus { 
  outline: none; 
  border-color: #2563eb; 
  background-color: #ffffff;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); 
}

/* Style cho trạng thái Input bị lỗi */
.form-group.has-error input {
  border-color: #ef4444 !important;
  background-color: #fff5f5;
}

.form-group.has-error input:focus {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
}

.error-msg {
  display: block;
  color: #ef4444;
  font-size: 12px;
  font-weight: 500;
  margin-top: 5px;
}

/* Mật khẩu & Nút Show/Hide */
.password-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.password-input-wrapper input {
  padding-right: 42px;
}

.toggle-password-btn {
  position: absolute;
  right: 8px;
  background: transparent;
  border: none;
  font-size: 16px;
  cursor: pointer;
  padding: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  opacity: 0.7;
  transition: opacity 0.2s;
}

.toggle-password-btn:hover {
  opacity: 1;
}

.form-options { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; }
.remember-me { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #475569; cursor: pointer; user-select: none; }
.remember-me input { cursor: pointer; width: 16px; height: 16px; accent-color: #2563eb; }

.submit-btn { 
  width: 100%; 
  padding: 12px; 
  background: #2563eb; 
  color: #ffffff; 
  border: none; 
  border-radius: 8px; 
  font-size: 14px; 
  font-weight: bold; 
  cursor: pointer; 
  transition: background 0.2s, transform 0.1s;
}

.submit-btn:hover:not(:disabled) { background: #1d4ed8; }
.submit-btn:active:not(:disabled) { transform: scale(0.99); }
.submit-btn:disabled { background: #94a3b8; cursor: not-allowed; }

/* Trạng thái Loading spinner */
.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.spinner {
  width: 14px;
  height: 14px;
  border: 2px solid #ffffff;
  border-bottom-color: transparent;
  border-radius: 50%;
  display: inline-block;
  animation: rotation 1s linear infinite;
}

@keyframes rotation {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.card-footer { margin-top: 22px; text-align: center; }
.back-home { color: #64748b; text-decoration: none; font-size: 13px; font-weight: 600; transition: color 0.2s; }
.back-home:hover { color: #2563eb; }

@media (max-width: 480px) {
  .login-card { padding: 28px 18px; }
}
</style>