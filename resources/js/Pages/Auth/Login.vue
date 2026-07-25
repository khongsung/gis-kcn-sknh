<template>
  <div class="login-page">
    <div class="login-card">
      <div class="card-header">
        <div class="logo-circle">🗺️</div>
        <h2>ĐĂNG NHẬP HỆ THỐNG</h2>
        <p>Quản lý hạ tầng KCN Song Khê - Nội Hoàng</p>
      </div>

      <form @submit.prevent="submit" class="login-form">
        <div v-if="form.errors.email" class="alert-error">
          {{ form.errors.email }}
        </div>

        <div class="form-group">
          <label for="email">📧 Email đăng nhập</label>
          <input 
            type="email" 
            id="email" 
            v-model="form.email" 
            placeholder="admin@songkhenoihoang.vn" 
            required 
            :disabled="form.processing"
          />
        </div>

        <div class="form-group">
          <label for="password">🔑 Mật khẩu</label>
          <input 
            type="password" 
            id="password" 
            v-model="form.password" 
            placeholder="••••••••" 
            required 
            :disabled="form.processing"
          />
        </div>

        <div class="form-options">
          <label class="remember-me">
            <input type="checkbox" v-model="form.remember" />
            <span>Ghi nhớ đăng nhập</span>
          </label>
        </div>

        <button type="submit" class="submit-btn" :disabled="form.processing">
          <span v-if="!form.processing">ĐĂNG NHẬP</span>
          <span v-else>Đang xác thực...</span>
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
      form: this.$inertia.form({
        email: '',
        password: '',
        remember: false,
      })
    };
  },
  methods: {
    submit() {
      this.form.post('/login', {
        onFinish: () => this.form.reset('password'),
      });
    }
  }
};
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  width: 100vw;
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  box-sizing: border-box;
}

.login-card {
  width: 100%;
  max-width: 400px;
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  padding: 32px 24px;
  box-sizing: border-box;
}

.card-header { text-align: center; margin-bottom: 24px; }
.logo-circle { width: 50px; height: 50px; background: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 12px auto; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }
.card-header h2 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; }
.card-header p { margin: 6px 0 0 0; font-size: 12px; color: #64748b; }
.alert-error { background: #fef2f2; border: 1px solid #fca5a5; color: #dc2626; padding: 10px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; text-align: center; }
.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; }
.form-group input { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box; }
.form-group input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
.form-options { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.remember-me { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #64748b; cursor: pointer; }
.submit-btn { width: 100%; padding: 12px; background: #2563eb; color: #ffffff; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; }
.submit-btn:hover { background: #1d4ed8; }
.card-footer { margin-top: 20px; text-align: center; }
.back-home { color: #64748b; text-decoration: none; font-size: 13px; font-weight: 500; }
.back-home:hover { color: #2563eb; }

@media (max-width: 480px) {
  .login-card { padding: 24px 16px; }
}
</style>