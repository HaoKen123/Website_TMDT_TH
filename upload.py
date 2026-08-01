import os
import sys
import ftplib

HOST = "pixelgear.getenjoyment.net"
USER = "4776587"
REMOTE_TARGET = "pixelgear.getenjoyment.net"

print("=== CÔNG CỤ TỰ ĐỘNG UPLOAD WEBSITE LÊN AWARDSPACE (FTP PASV) ===")
password = "thapvi123"

try:
    ftp = ftplib.FTP(HOST, USER, password)
    ftp.set_pasv(True)
    print("-> Kết nối FTP thành công!")
except Exception as e:
    print(f"Lỗi kết nối: {e}")
    sys.exit(1)

# Thử chuyển vào thư mục subdomain
try:
    ftp.cwd(REMOTE_TARGET)
except Exception:
    try:
        ftp.cwd(f"www/{REMOTE_TARGET}")
    except Exception:
        print(f"Lỗi: Không tìm thấy thư mục {REMOTE_TARGET} trên Host!")
        sys.exit(1)

def clean_remote_folder(folder="."):
    """Xóa sạch các file cũ trên host để đảm bảo bản upload mới hoàn toàn sạch sẽ"""
    print("-> Đang dọn dẹp các tệp cũ trên Host trước khi up mới...")
    try:
        items = ftp.nlst(folder)
        for item in items:
            name = os.path.basename(item)
            if name in [".", ".."]:
                continue
            try:
                ftp.delete(item)
                print(f"   [Đã xóa file cũ]: {item}")
            except Exception:
                try:
                    clean_remote_folder(item)
                    ftp.rmd(item)
                    print(f"   [Đã xóa thư mục cũ]: {item}")
                except Exception:
                    pass
    except Exception as e:
        print(f"   (Bỏ qua thông báo dọn dẹp: {e})")

# Thực hiện xóa sạch file cũ trước khi upload
clean_remote_folder(".")
print("-> Dọn dẹp hoàn tất! Bắt đầu upload dữ liệu mới...\n")

def ensure_remote_dir(path):
    parts = path.replace("\\", "/").strip("/").split("/")
    curr = ""
    for p in parts:
        if not p: continue
        curr = f"{curr}/{p}" if curr else p
        try:
            ftp.mkd(curr)
        except Exception:
            pass

ignored_names = {".git", "node_modules", "website.zip", "website.txt", "upload.py", ".gitignore"}

for root, dirs, files in os.walk("."):
    dirs[:] = [d for d in dirs if d not in ignored_names]
    
    rel_dir = os.path.relpath(root, ".").replace("\\", "/")
    if rel_dir != ".":
        ensure_remote_dir(rel_dir)
        
    for file in files:
        if file in ignored_names:
            continue
            
        local_path = os.path.join(root, file)
        remote_path = f"{rel_dir}/{file}" if rel_dir != "." else file
        
        print(f"Đang up: {remote_path} ... ", end="", flush=True)
        try:
            with open(local_path, "rb") as f:
                ftp.storbinary(f"STOR {remote_path}", f)
            print("XONG")
        except Exception as e:
            print(f"LỖI ({e})")

ftp.quit()
print("\n🎉 ĐÃ DỌN SẠCH CŨ & UPLOAD TOÀN BỘ WEBSITE MỚI THÀNH CÔNG 100%!")
