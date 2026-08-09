import ftplib
import sys
import time

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

HOST = "pixelgear.getenjoyment.net"
USER = "4776587"
PASSWORD = "thapvi123"

# Danh sách các file vừa sửa đổi
FILES_TO_UPLOAD = [
    "products.php",
    "login.php",
    "register.php",
    "signin.php",
    "signup.php",
    "google_config.php",
    "google_oauth.php",
    "google_callback.php",
    "google_login.php",
    "lang.php",
    "style.css",
    "index.php",
    "cart.php",
    "checkout.php",
    "profile.php",
    "product_detail.php",
    "admin/categories.php",
    "config_payment.php"
]

DOMAINS = ["pixelgear.getenjoyment.net", "honhathao.id.vn"]

print("⚡ === CONG CU UPLOAD SIEU TOC (CHI CHUA DEN 3 GIAY) ===")

def connect():
    for i in range(3):
        try:
            ftp = ftplib.FTP(HOST, USER, PASSWORD, timeout=15)
            ftp.set_pasv(True)
            return ftp
        except Exception:
            time.sleep(1)
    return None

ftp = connect()
if not ftp:
    print("❌ Lỗi kết nối FTP!")
    sys.exit(1)

print("-> Kết nối FTP thành công!")

for domain in DOMAINS:
    print(f"\n🚀 Đang up siêu tốc sang: {domain}")
    try:
        ftp.cwd("/")
        ftp.cwd(domain)
    except Exception:
        try:
            ftp.cwd("/")
            ftp.cwd(f"www.{domain}")
        except Exception as e:
            print(f"Bỏ qua {domain}: {e}")
            continue

    for filename in FILES_TO_UPLOAD:
        try:
            with open(filename, "rb") as f:
                ftp.storbinary(f"STOR {filename}", f)
            print(f"  ✓ Đã up: {filename}")
        except Exception as e:
            print(f"  ✗ Lỗi up {filename}: {e}")

try:
    ftp.quit()
except Exception:
    pass

print("\n🎉 HOÀN TẤT UPLOAD SIÊU TỐC TRONG BẢO MẮT!")
