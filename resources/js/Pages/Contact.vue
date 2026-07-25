<template>
  <div class="contact-page">
    <!-- 1. TÁI SỬ DỤNG THANH NAVIGATION BAR -->
    <NavigationBar />

    <!-- 2. KHUNG NỘI DUNG CHÍNH (MAIN CONTAINER CĂN GIỮA TIÊU CHUẨN) -->
    <main class="main-container">
      
      <!-- HEADER TIÊU ĐỀ TRANG -->
      <section class="page-banner">
        <span class="sub-title">CÔNG TY CP PHÁT TRIỂN HẠ TẦNG KCN BẮC GIANG</span>
        <h1 class="main-title">Khu Công Nghiệp Song Khê – Nội Hoàng</h1>
        <p class="location-tag">📍 Lô HC, KCN Song Khê – Nội Hoàng (Phía Bắc), Phường Tiền Phong, Tỉnh Bắc Ninh</p>
      </section>

      <!-- ================================================================= -->
      <!-- PHẦN I: GIỚI THIỆU KHÁI QUÁT KHU CÔNG NGHIỆP -->
      <!-- ================================================================= -->
      <section class="info-section">
        <h2 class="section-title">📋 Thông Tin Tổng Quan & Hạ Tầng</h2>

        <!-- 1. THÔNG TIN CHUNG -->
        <div class="info-card">
          <h3 class="card-title">1. Thông tin chung</h3>
          <div class="table-responsive">
            <table class="info-table">
              <tbody>
                <tr>
                  <td class="label-col">Tên khu công nghiệp:</td>
                  <td class="value-col"><strong>Khu công nghiệp Song Khê – Nội Hoàng</strong></td>
                </tr>
                <tr>
                  <td class="label-col">Địa chỉ:</td>
                  <td class="value-col">Phường Tiền Phong, tỉnh Bắc Ninh.</td>
                </tr>
                <tr>
                  <td class="label-col">Chủ đầu tư:</td>
                  <td class="value-col"><b style="color: #1e3a8a;">Công ty CP phát triển hạ tầng KCN Bắc Giang</b></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- 2. ĐẶC ĐIỂM KCN -->
        <div class="info-card">
          <h3 class="card-title">2. Đặc điểm khu công nghiệp</h3>
          <div class="table-responsive">
            <table class="info-table">
              <tbody>
                <tr>
                  <td class="label-col">Loại hình kinh tế:</td>
                  <td class="value-col">Đa ngành</td>
                </tr>
                <tr>
                  <td class="label-col">Tổng diện tích đất:</td>
                  <td class="value-col">Khoảng 105,6 ha</td>
                </tr>
                <tr>
                  <td class="label-col">Tỷ lệ lấp đầy:</td>
                  <td class="value-col"><span class="badge-full">Tỷ lệ lấp đầy: 100%</span></td>
                </tr>
                <tr>
                  <td class="label-col">Giao thông vận tải:</td>
                  <td class="value-col">
                    <p class="mb-2">Nằm trong vùng kinh tế trọng điểm phía Bắc: Hà Nội - Hải Phòng - Quảng Ninh - Bắc Ninh - Lạng Sơn. Nằm sát Quốc lộ 1A mới Hà Nội - Lạng Sơn. Cách TP. Bắc Giang 5 km, thủ đô Hà Nội 45 km, sân bay Nội Bài 45 km, cảng Hải Phòng 115 km, cửa khẩu Hữu Nghị Quan 115 km.</p>
                    <ul class="styled-list">
                      <li><b>🛣️ Đường bộ:</b> Ven QL 1A đi Lạng Sơn - Hà Nội; Gần QL 18 đi cảng Cái Lân (Quảng Ninh) & Sân bay Quốc tế Nội Bài.</li>
                      <li><b>🚆 Đường sắt:</b> Tuyến Hà Nội - Lạng Sơn; Tuyến cao tốc Yên Viên - Cái Lân.</li>
                      <li><b>🚢 Đường thủy:</b> Cách cảng biển Hải Phòng 155 km; Cách cảng biển Quảng Ninh 95 km.</li>
                    </ul>
                  </td>
                </tr>
                <tr>
                  <td class="label-col">Cơ sở hạ tầng:</td>
                  <td class="value-col">
                    <ul class="styled-list">
                      <li><b>Đường nội bộ:</b> Đường chính 12m (2 làn xe); Đường phụ 7,5m (1 làn xe).</li>
                      <li><b>Hạ tầng kỹ thuật:</b> Đã đầu tư hoàn chỉnh hệ thống giao thông, Trạm xử lý nước thải tập trung, điện nước, thông tin liên lạc, chiếu sáng, thoát nước mưa & nước thải độc lập.</li>
                    </ul>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- 3. CHI PHÍ ĐẦU TƯ -->
        <div class="info-card">
          <h3 class="card-title">3. Chi phí đầu tư</h3>
          <div class="grid-3">
            <div class="cost-box">
              <span class="cost-label">Giá thuê hạ tầng</span>
              <span class="cost-value">1.000.000đ – 2.000.000đ / m²</span>
              <small class="cost-sub">(Thời hạn đến ngày 15/04/2066)</small>
            </div>
            <div class="cost-box">
              <span class="cost-label">Tiền sử dụng hạ tầng</span>
              <span class="cost-value">10.000đ / m² / năm</span>
            </div>
            <div class="cost-box">
              <span class="cost-label">Tiền xử lý nước thải</span>
              <span class="cost-value">9.500đ / m³</span>
            </div>
          </div>
        </div>

        <!-- 4. KHUYẾN KHÍCH & HỖ TRỢ ĐẦU TƯ -->
        <div class="info-card">
          <h3 class="card-title">4. Khuyến khích & Hỗ trợ đầu tư</h3>
          <p><b>Lĩnh vực khuyến khích:</b> Công nghệ cao, điện tử, năng lượng, thực phẩm, may mặc...</p>
          <p class="mt-2"><b>Chính sách hỗ trợ của KCN:</b></p>
          <ul class="styled-list">
            <li>Miễn, giảm thuế đất theo quy định hiện hành của Nhà nước.</li>
            <li>Miễn, giảm thuế thu nhập doanh nghiệp (TNDN) theo ưu đãi Nhà nước.</li>
          </ul>
        </div>

        <!-- 5. THỦ TỤC ĐẦU TƯ -->
        <div class="info-card">
          <h3 class="card-title">5. Quy trình thủ tục đầu tư</h3>
          <ol class="step-list">
            <li>Ký thỏa thuận thuê lại đất với Chủ đầu tư.</li>
            <li>Nộp tiền sử dụng/thuê đất theo quy định.</li>
            <li>Đăng ký đầu tư tại Ban quản lý các KCN tỉnh Bắc Ninh; Đăng ký doanh nghiệp tại Sở Tài chính tỉnh Bắc Ninh.</li>
          </ol>
        </div>

        <!-- KHUNG THÔNG TIN LIÊN HỆ TRỰC TIẾP (CLICK GỌI LUÔN) -->
        <div class="direct-contact-bar">
          <div class="contact-info-item">
            <span class="icon">🏢</span>
            <div>
              <strong>Địa chỉ văn phòng:</strong>
              <p>Lô HC, KCN Song Khê – Nội Hoàng (phía Bắc), phường Tiền Phong, tỉnh Bắc Ninh</p>
            </div>
          </div>

          <div class="contact-info-item">
            <span class="icon">📞</span>
            <div>
              <strong>Điện thoại bàn / Fax:</strong>
              <p>
                <a href="tel:02043559266" class="phone-call-btn" title="Bấm vào đây để gọi ngay">
                  0204.3.559.266 <span>(Bấm để gọi luôn)</span>
                </a>
                <span class="sub-text"> | Fax: 0204.3.559.266</span>
              </p>
            </div>
          </div>

          <div class="contact-info-item">
            <span class="icon">✉️</span>
            <div>
              <strong>Email chính thức:</strong>
              <p><a href="mailto:congtypthtkcn@bacgiang.gov.vn">congtypthtkcn@bacgiang.gov.vn</a></p>
            </div>
          </div>
        </div>
      </section>

      <!-- ================================================================= -->
      <!-- PHẦN II: FORM LIÊN HỆ NỔI BẬT Ở CHÍNH GIỮA -->
      <!-- ================================================================= -->
      <section class="contact-form-wrapper" id="form-section">
        <div class="form-card">
          <div class="form-header">
            <h2>📩 Gửi Yêu Cầu Cho Chủ Đầu Tư</h2>
            <p>Vui lòng điền đầy đủ các thông tin có dấu (<span class="required">*</span>) để gửi trực tiếp tới ban quản lý KCN</p>
          </div>

          <!-- THÔNG BÁO THÀNH CÔNG NẾU CÓ -->
          <div v-if="$page.props.flash && $page.props.flash.success" class="alert-success">
            ✅ {{ $page.props.flash.success }}
          </div>

          <form @submit.prevent="submitContactForm" class="contact-form">
            <!-- 1. HỌ TÊN (BẮT BUỘC) -->
            <div class="form-group">
              <label>Họ và Tên <span class="required">*</span></label>
              <input 
                type="text" 
                v-model="form.fullname" 
                placeholder="Nhập họ và tên người liên hệ / doanh nghiệp" 
                :class="{ 'input-error': errors.fullname }"
              />
              <span v-if="errors.fullname" class="error-msg">{{ errors.fullname }}</span>
            </div>

            <!-- 2. SỐ ĐIỆN THOẠI (BẮT BUỘC) -->
            <div class="form-group">
              <label>Số Điện Thoại <span class="required">*</span></label>
              <input 
                type="tel" 
                v-model="form.phone" 
                placeholder="Nhập số điện thoại di động / bàn" 
                :class="{ 'input-error': errors.phone }"
              />
              <span v-if="errors.phone" class="error-msg">{{ errors.phone }}</span>
            </div>

            <div class="form-row">
              <!-- 3. EMAIL -->
              <div class="form-group half">
                <label>Địa Chỉ Email</label>
                <input 
                  type="email" 
                  v-model="form.email" 
                  placeholder="viethang@company.com" 
                  :class="{ 'input-error': errors.email }"
                />
                <span v-if="errors.email" class="error-msg">{{ errors.email }}</span>
              </div>

              <!-- 4. ĐỊA CHỈ -->
              <div class="form-group half">
                <label>Địa Chỉ Công Ty / Cá Nhân</label>
                <input 
                  type="text" 
                  v-model="form.address" 
                  placeholder="Tỉnh/Thành phố hoặc địa chỉ chi tiết" 
                />
              </div>
            </div>

            <!-- 5. NỘI DUNG LIÊN HỆ (BẮT BUỘC) -->
            <div class="form-group">
              <label>Nội Dung Liên Hệ <span class="required">*</span></label>
              <textarea 
                v-model="form.message" 
                rows="5" 
                placeholder="Nhập chi tiết nhu cầu thuê đất, nhà xưởng hoặc hỗ trợ đầu tư..."
                :class="{ 'input-error': errors.message }"
              ></textarea>
              <span v-if="errors.message" class="error-msg">{{ errors.message }}</span>
            </div>

            <!-- NÚT GỬI (ĐỦ THÔNG TIN BẮT BUỘC MỚI BẬT NÚT) -->
            <div class="form-actions">
              <button 
                type="submit" 
                class="btn-submit"
                :disabled="!isFormValid || form.processing"
                :class="{ 'btn-disabled': !isFormValid || form.processing }"
              >
                <span v-if="!form.processing">🚀 Gửi Yêu Cầu Liên Hệ</span>
                <span v-else>⏳ Đang gửi thông tin...</span>
              </button>
            </div>

            <p class="target-email-notice">
              🔒 Thông tin sẽ được chuyển trực tiếp tới Ban quản lý (Email: <code>khongsung97@gmail.com</code>)
            </p>
          </form>
        </div>
      </section>

      <!-- THÔNG BÁO THÀNH CÔNG RÕ RÀNG -->
      <div v-if="successMessage" class="alert-success">
        ✅ {{ successMessage }}
      </div>

    </main>
  </div>
</template>

<script>
import NavigationBar from '@/Components/NavigationBar.vue';

export default {
  name: 'ContactPage',
  components: {
    NavigationBar
  },
  data() {
    return {
      errors: {},
      successMessage: '',
      form: this.$inertia.form({
        fullname: '',
        phone: '',
        email: '',
        address: '',
        message: ''
      })
    };
  },
  computed: {
    // Kiểm tra xem đã điền ĐỦ 3 trường BẮT BUỘC chưa (Họ tên, SĐT, Nội dung)
    isFormValid() {
      const hasName = this.form.fullname && this.form.fullname.trim() !== '';
      const hasPhone = this.form.phone && this.form.phone.trim() !== '';
      const hasMessage = this.form.message && this.form.message.trim() !== '';
      
      return hasName && hasPhone && hasMessage;
    }
  },
  methods: {
    validateClient() {
      this.errors = {};
      if (!this.form.fullname.trim()) this.errors.fullname = 'Họ và tên là bắt buộc!';
      if (!this.form.phone.trim()) this.errors.phone = 'Số điện thoại là bắt buộc!';
      if (!this.form.message.trim()) this.errors.message = 'Nội dung liên hệ là bắt buộc!';
      
      if (this.form.email && !/\S+@\S+\.\S+/.test(this.form.email)) {
        this.errors.email = 'Email không hợp lệ!';
      }

      return Object.keys(this.errors).length === 0;
    },

    submitContactForm() {
      if (!this.validateClient()) return;

      // Gửi dữ liệu về backend Laravel
      this.form.post('/contact', {
        onSuccess: () => {
          // 1. Lấy thông báo từ flash session của Laravel
          const flashSuccess = page.props.flash?.success || this.$page.props.flash?.success;
          
          // 2. Gán thông báo hoặc hiển thị câu mặc định
          this.successMessage = flashSuccess || 'Gửi thông tin liên hệ thành công! Chúng tôi sẽ phản hồi sớm nhất.';
          
          // 3. Reset lại các ô input
          this.form.reset();
          this.errors = {};

          // 4. Tự động cuộn màn hình xuống khung thông báo
          this.$nextTick(() => {
            const formSec = document.getElementById('form-section');
            if (formSec) formSec.scrollIntoView({ behavior: 'smooth' });
          });
        },
        onError: (err) => {
          this.errors = err;
        }
      });
    }
  }
};
</script>

<style scoped>
/* KHUNG CHÍNH CĂN GIỮA TIÊU CHUẨN ĐỘ RỘNG WEB (MAX-WIDTH: 1140PX) */
.contact-page {
  background-color: #f8fafc;
  min-height: 100vh;
  padding-bottom: 60px;
}

.main-container {
  max-width: 1140px;
  margin: 0 auto;
  padding: 24px 16px;
  display: flex;
  flex-direction: column;
  gap: 32px;
}

/* BANNER TIÊU ĐỀ TRANG BAN ĐẦU */
.page-banner {
  background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
  color: #ffffff;
  padding: 32px 24px;
  border-radius: 16px;
  text-align: center;
  box-shadow: 0 10px 25px rgba(15, 23, 42, 0.15);
}

.sub-title {
  font-size: 13px;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: #93c5fd;
  font-weight: 700;
}

.main-title {
  font-size: 28px;
  font-weight: 800;
  margin: 8px 0 12px 0;
  color: #ffffff;
}

.location-tag {
  font-size: 14px;
  color: #cbd5e1;
  margin: 0;
}

/* PHẦN I: BẢNG GIỚI THIỆU KHÁI QUÁT KHU CÔNG NGHIỆP */
.info-section {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.section-title {
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
  border-left: 4px solid #2563eb;
  padding-left: 12px;
  margin: 0;
}

.info-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 20px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.card-title {
  font-size: 16px;
  font-weight: 700;
  color: #1e3a8a;
  margin-top: 0;
  margin-bottom: 14px;
  border-bottom: 1px dashed #cbd5e1;
  padding-bottom: 8px;
}

/* STYLED TABLES */
.table-responsive { overflow-x: auto; }
.info-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.info-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
.label-col { width: 220px; font-weight: 700; color: #475569; background: #f8fafc; }
.value-col { color: #1e293b; }

.badge-full {
  background: #dcfce7;
  color: #15803d;
  padding: 4px 10px;
  border-radius: 6px;
  font-weight: bold;
  font-size: 13px;
}

.styled-list { margin: 6px 0 0 0; padding-left: 18px; line-height: 1.6; }
.step-list { margin: 6px 0 0 0; padding-left: 20px; line-height: 1.8; font-weight: 600; color: #334155; }

/* KHUNG CHI PHÍ 3 CỘT */
.grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
.cost-box {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  padding: 16px;
  border-radius: 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}
.cost-label { font-size: 13px; color: #1e40af; font-weight: 600; }
.cost-value { font-size: 17px; font-weight: 800; color: #1e3a8a; margin-top: 4px; }
.cost-sub { font-size: 11px; color: #64748b; margin-top: 4px; }

/* KHUNG THÔNG TIN LIÊN HỆ TRỰC TIẾP */
.direct-contact-bar {
  background: #ffffff;
  border: 2px solid #2563eb;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  box-shadow: 0 4px 15px rgba(37, 99, 235, 0.08);
}

.contact-info-item { display: flex; gap: 14px; align-items: flex-start; }
.contact-info-item .icon { font-size: 22px; background: #eff6ff; padding: 8px; border-radius: 8px; }
.contact-info-item p { margin: 3px 0 0 0; font-size: 14px; color: #334155; }

.phone-call-btn {
  color: #dc2626 !important;
  font-weight: 800;
  font-size: 18px;
  text-decoration: none;
}
.phone-call-btn span { font-size: 12px; color: #2563eb; text-decoration: underline; font-weight: normal; }

/* ================================================================= */
/* PHẦN II: FORM LIÊN HỆ NỔI BẬT CHÍNH GIỮA KHUNG */
/* ================================================================= */
.contact-form-wrapper {
  display: flex;
  justify-content: center;
  margin-top: 10px;
}

.form-card {
  width: 100%;
  max-width: 720px; /* Căn giữa nổi bật tiêu chuẩn */
  background: #ffffff;
  border-radius: 16px;
  padding: 32px;
  border: 1px solid #cbd5e1;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
}

.form-header {
  text-align: center;
  margin-bottom: 24px;
}
.form-header h2 {
  font-size: 22px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 6px 0;
}
.form-header p {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}

.contact-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-row { display: flex; gap: 16px; }
.half { flex: 1; }

label { font-size: 13px; font-weight: 700; color: #334155; }
.required { color: #dc2626; }

input, textarea {
  width: 100%;
  padding: 11px 14px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 14px;
  box-sizing: border-box;
  outline: none;
  transition: all 0.2s ease;
  background-color: #f8fafc;
}

input:focus, textarea:focus {
  border-color: #2563eb;
  background-color: #ffffff;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

input.input-error, textarea.input-error {
  border-color: #dc2626;
  background-color: #fef2f2;
}

.error-msg { font-size: 11px; color: #dc2626; font-weight: 600; }

/* NÚT GỬI VÀ TRẠNG THÁI DISABLE */
.form-actions { margin-top: 10px; }

.btn-submit {
  width: 100%;
  padding: 14px;
  background: #2563eb;
  color: #ffffff;
  border: none;
  border-radius: 10px;
  font-size: 16px;
  font-weight: 800;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
  transition: all 0.2s ease;
}

.btn-submit:hover:not(:disabled) {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.btn-disabled, .btn-submit:disabled {
  background: #94a3b8 !important;
  cursor: not-allowed !important;
  box-shadow: none !important;
  opacity: 0.7;
}

.target-email-notice {
  text-align: center;
  font-size: 11px;
  color: #64748b;
  margin-top: 12px;
}
.target-email-notice code {
  background: #f1f5f9;
  padding: 2px 6px;
  border-radius: 4px;
  color: #0f172a;
  font-weight: bold;
}

.alert-success {
  background-color: #dcfce7 !important;
  color: #15803d !important;
  border: 1px solid #86efac !important;
  padding: 14px 18px !important;
  border-radius: 10px !important;
  font-size: 15px !important;
  font-weight: 700 !important;
  margin-bottom: 20px !important;
  text-align: center !important;
  box-shadow: 0 4px 12px rgba(22, 101, 52, 0.1) !important;
  animation: fadeIn 0.4s ease-in-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-8px); }
  to { opacity: 1; transform: translateY(0); }
}

/* RESPONSIVE MOBILE */
@media (max-width: 768px) {
  .form-row { flex-direction: column; gap: 16px; }
  .label-col { width: 100%; display: block; }
  .value-col { display: block; }
  .form-card { padding: 20px; }
}
</style>