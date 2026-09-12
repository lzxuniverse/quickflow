# Hướng dẫn chi tiết: Thiết lập Public Tunnel (Cloudflare & Herd Share) cho QuickFlow MCP Server

Tài liệu này hướng dẫn chi tiết từng bước (Step-by-Step) cách tạo đường hầm an toàn (Secure Tunnel) để đưa **QuickFlow MCP Server** từ máy tính cục bộ (Localhost / Laravel Herd) lên Internet, phục vụ cho việc tích hợp vào **Claude Web (Connectors)**, **ChatGPT Actions**, hoặc các **Web AI Agents**.

---

## 1. Mô hình hoạt động tổng quan

```
┌─────────────────────────────────────────────────────────┐
│              INTERNET / CLOUD PLATFORMS                 │
│  (Claude Web Connectors, ChatGPT Web, Postman Online)    │
└────────────────────────────┬────────────────────────────┘
                             │  HTTPS Request (JSON-RPC 2.0)
                             ▼
┌─────────────────────────────────────────────────────────┐
│                    SECURE TUNNEL                        │
│   [Cloudflare trycloudflare.com]  HOẶC  [Herd Expose]   │
└────────────────────────────┬────────────────────────────┘
                             │  Forward traffic (Reverse Proxy)
                             ▼
┌─────────────────────────────────────────────────────────┐
│                 LOCAL MACHINE (WINDOWS)                 │
│  Laravel Herd Web Server: https://quickflow.test        │
│  MCP Endpoint:           /mcp  (hoặc /api/mcp)          │
│  Database:               MySQL (198 Properties, 5 Ten.) │
└─────────────────────────────────────────────────────────┘
```

---

## 2. PHƯƠNG PHÁP 1: Sử dụng Cloudflare Tunnel (Khuyên Dùng ⭐)

Cloudflare Tunnel là giải pháp được khuyến nghị hàng đầu vì:
- **100% miễn phí**, không cần đăng ký tài khoản.
- Không giới hạn phiên kết nối.
- Không bị phụ thuộc vào công cụ `wmic` của Windows.
- Tốc độ mạng cao thông qua mạng lưới toàn cầu của Cloudflare.

### Bước 1: Chuẩn bị file thực thi
File thực thi `cloudflared.exe` đã được tải sẵn ngay tại thư mục gốc dự án:
```
e:\quickflow-engine\dev\quickflow\cloudflared.exe
```
*(File này đã được cấu hình trong `.gitignore` để không bị đưa vào source code Git).*

### Bước 2: Chạy lệnh khởi tạo Tunnel
1. Mở PowerShell hoặc Windows Terminal.
2. Di chuyển vào thư mục dự án:
   ```powershell
   cd e:\quickflow-engine\dev\quickflow
   ```
3. Chạy một trong các lệnh ngắn gọn sau:
   - **Cách 1 (Ngắn nhất):**
     ```powershell
     composer tunnel
     ```
   - **Cách 2 (Laravel Artisan):**
     ```powershell
     php artisan mcp:tunnel
     ```
   - **Cách 3 (File shortcut):**
     ```powershell
     .\tunnel
     ```
   *(Cả 3 cách trên đều tự động gọi `cloudflared.exe` với đầy đủ các cờ cần thiết và tự động in nổi bật link MCP URL dạng `https://.../mcp` để bạn copy ngay).*

   - **Cách 4 (Lệnh gốc nếu muốn gõ thủ công):**
     ```powershell
     .\cloudflared.exe tunnel --url https://quickflow.test --http-host-header quickflow.test --no-tls-verify
     ```

> [!IMPORTANT]
> **Giải thích các tham số bắt buộc:**
> - `--url https://quickflow.test`: Cổng đích nội bộ mà tunnel sẽ chuyển tiếp dữ liệu đến.
> - `--http-host-header quickflow.test`: **Cực kỳ quan trọng!** Báo cho Laravel Herd biết bạn đang gọi site `quickflow.test`. Nếu thiếu cờ này, Herd sẽ trả về trang lỗi `404 Site not found`.
> - `--no-tls-verify`: Bỏ qua việc kiểm tra chứng chỉ SSL tự ký (self-signed) nội bộ của Herd.

### Bước 3: Lấy Public Endpoint cho MCP
Khi lệnh chạy, Cloudflare sẽ hiển thị thông báo có dạng:
```
+--------------------------------------------------------------------------------------------+
|  Your quick Tunnel has been created! Visit it at (it may take some time to be reachable):  |
|  https://xxxx-xxxx-xxxx.trycloudflare.com                                                  |
+--------------------------------------------------------------------------------------------+
```

Đường dẫn MCP Server hoàn chỉnh của bạn là:
```
https://xxxx-xxxx-xxxx.trycloudflare.com/mcp
```
*(Lưu ý: Mất khoảng 10-15 giây ban đầu để tên miền trycloudflare.com cập nhật DNS trên toàn cầu).*

### Bước 4: Tắt và Bật lại Tunnel
- **Để dừng**: Nhấn `Ctrl + C` trong cửa sổ PowerShell đang chạy tunnel.
- **Để bật lại**: Chạy lại dòng lệnh ở Bước 2. Cloudflare sẽ tự sinh một URL ngẫu nhiên mới.

---

## 3. PHƯƠNG PHÁP 2: Sử dụng Herd Share (Expose)

`herd share` sử dụng công cụ Expose của BeyondCode được tích hợp sẵn bên trong Laravel Herd.

### Bước 1: Sửa lỗi thiếu `wmic` trên Windows 11 (Chỉ làm 1 lần duy nhất)
Trên các phiên bản Windows 11 gần đây, Microsoft đã gỡ bỏ lệnh `wmic`. Để chạy được `herd share`, bạn cần kích hoạt lại tính năng này:

1. Nhấn phím `Windows`, gõ **PowerShell**, nhấp chuột phải chọn **Run as Administrator**.
2. Chạy lệnh:
   ```powershell
   DISM /Online /Add-Capability /CapabilityName:WMIC~~~~
   ```
3. Chờ tiến trình hiển thị `100.0% The operation completed successfully`.

### Bước 2: Chạy lệnh `herd share`
1. Mở PowerShell thường tại thư mục dự án:
   ```powershell
   cd e:\quickflow-engine\dev\quickflow
   ```
2. Chạy lệnh:
   ```powershell
   herd share
   ```
   *(Hoặc nếu đang đứng ở thư mục khác: `herd share quickflow.test`)*

### Bước 3: Lấy Public Endpoint cho MCP
Herd sẽ hiển thị bảng điều khiển Expose với địa chỉ công khai dạng:
```
Public HTTPS: https://quickflow-xxxx.sharedwithexpose.com
```

Đường dẫn MCP Server của bạn là:
```
https://quickflow-xxxx.sharedwithexpose.com/mcp
```

---

## 4. Cách thêm vào Claude Web (`claude.ai`)

1. Truy cập vào trang quản lý Connectors của Claude:
   👉 **https://claude.ai/customize/connectors**
2. Nhấn nút **Add Connector** (hoặc **Add Custom Tool / Integration**).
3. Điền các thông tin:
   - **Name**: `QuickFlow Properties`
   - **URL / Endpoint**: Dán đường dẫn public của bạn vào (ví dụ: `https://xxxx-xxxx.trycloudflare.com/mcp`).
4. Nhấn **Save / Connect**. Claude sẽ tự động gửi request `initialize` và `tools/list` để nạp 6 công cụ properties vào phiên làm việc.

---

## 5. Mẫu câu lệnh chat thử nghiệm trên Claude Web

Sau khi kết nối thành công, bạn mở một cuộc trò chuyện mới trên Claude và gõ các câu lệnh kiểm tra:

- **Kiểm tra kết nối và số liệu thống kê:**
  > *"Hãy cho tôi biết tổng quan về số lượng khách sạn, số phòng và điểm đánh giá trung bình trong hệ thống QuickFlow."*
  *(Claude sẽ tự động gọi tool `properties_stats`)*

- **Tìm kiếm khách sạn theo khu vực:**
  > *"Tìm cho tôi danh sách các khách sạn ở Việt Nam hoặc Nhật Bản đang hoạt động."*
  *(Claude sẽ tự động gọi tool `properties_list` với filter `search`)*

- **Xem chi tiết một cơ sở:**
  > *"Lấy chi tiết thông tin các loại phòng và giờ check-in/check-out của khách sạn có mã [UUID]."*
  *(Claude sẽ tự động gọi tool `properties_get`)*

---

## 6. Bảng so sánh & Xử lý sự cố (Troubleshooting)

| Đặc điểm | Cloudflare Tunnel (`cloudflared`) | Herd Share (`Expose`) |
| :--- | :--- | :--- |
| **Yêu cầu tài khoản** | Không cần | Tài khoản Expose miễn phí (giới hạn thời gian) hoặc Pro |
| **Lỗi Windows 11 WMIC** | Không bị ảnh hưởng | Cần chạy lệnh cài đặt WMIC ở Mục 3 |
| **Tốc độ & Ổn định** | Rất cao (Cloudflare Edge) | Tốt |
| **Lệnh chạy** | `.\cloudflared.exe tunnel --url https://quickflow.test --http-host-header quickflow.test --no-tls-verify` | `herd share` |

### Các lỗi thường gặp

1. **Lỗi `404 Site not found` khi gọi qua Cloudflare Tunnel**:
   - *Nguyên nhân:* Thiếu tham số `--http-host-header quickflow.test`.
   - *Cách sửa:* Luôn kèm cờ `--http-host-header quickflow.test` khi chạy `cloudflared`.

2. **Lỗi `419 Page Expired (CSRF Token Mismatch)`**:
   - Đã được tự động xử lý sẵn trong file [bootstrap/app.php](file:///e:/quickflow-engine/dev/quickflow/bootstrap/app.php) (`validateCsrfTokens(except: ['mcp'])`).

3. **Lỗi `Host not found` khi vừa bật Cloudflare Tunnel**:
   - *Nguyên nhân:* Tên miền `trycloudflare.com` cần khoảng 10-15 giây để phổ biến DNS.
   - *Cách sửa:* Đợi 10-15 giây rồi gửi lại request.
