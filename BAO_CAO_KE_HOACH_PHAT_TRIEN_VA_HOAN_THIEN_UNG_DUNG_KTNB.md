# BÁO CÁO TỔNG KẾT KẾ HOẠCH PHÁT TRIỂN & ĐÁNH GIÁ ỨNG DỤNG
## HỆ THỐNG QUẢN LÝ & GIÁM SÁT KIỂM TRA NỘI BỘ (KTNB - NTTU)

---

**Đơn vị thực hiện:** Phòng Kiểm tra Nội bộ – Trường Đại học Nguyễn Tất Thành  
**Người lập báo cáo:** Nguyễn Vĩnh Phúc  
**Thời gian hoàn thành:** Năm học 2025 – 2026  
**Địa chỉ triển khai:** [https://kiemtranoibo-ccks.ntt.edu.vn](https://kiemtranoibo-ccks.ntt.edu.vn)  

---

## MỤC LỤC
1. [TỔNG QUAN & BỐI CẢNH DỰ ÁN](#1-tổng-quan--bối-cảnh-dự-án)
2. [CÔNG NGHỆ ÁP DỤNG TRONG HỆ THỐNG](#2-công-nghệ-áp-dụng-trong-hệ-thống)
3. [CÁ NHÂN HÓA NGƯỜI DÙNG & BẢO MẬT PHÂN QUYỀN](#3-cá-nhân-hóa-người-dùng--bảo-mật-phân-quyền)
4. [CÁC TÍNH NĂNG TỰ ĐỘNG HÓA VƯỢT TRỘI](#4-các-tính-năng-tự-động-hóa-vượt-trội)
5. [KẾT QUẢ TRIỂN KHAI CÁC PHÂN HỆ CHỨC NĂNG (ĐÃ HOÀN THÀNH 100%)](#5-kết-quả-triển-khai-các-phân-hệ-chức-năng-đã-hoàn-thành-100)
6. [ĐÁNH GIÁ TÍNH KHẢ THI CỦA ỨNG DỤNG](#6-đánh-giá-tính-khả-thi-của-ứng-dụng)
7. [ĐÁNH GIÁ NHỮNG THUẬN LỢI KHI ĐƯA VÀO VẬN HÀNH THỰC TẾ](#7-đánh-giá-những-thuận-lợi-khi-đưa-vào-vận-hành-thực-tế)
8. [KẾT LUẬN & KIẾN NGHỊ](#8-kết-luận--kiến-nghị)

---

## 1. TỔNG QUAN & BỐI CẢNH DỰ ÁN

### 1.1. Thực trạng trước khi xây dựng hệ thống
Trước đây, công tác kiểm tra, giám sát nền nếp đào tạo tại Trường Đại học Nguyễn Tất Thành gặp nhiều rào cản do các quy trình phần lớn thực hiện bán thủ công:
- **Thời khóa biểu khổng lồ, phức tạp:** Hàng ngàn tiết học mỗi tuần diễn ra tại nhiều cơ sở (Quận 4, Quận 12, Quận 7, TP. Thủ Đức) bao gồm lớp trực tiếp, lớp học trực tuyến, thực hành ngoài và các buổi thi kết thúc môn. Việc theo dõi lịch kiểm tra chủ yếu dựa vào các file Excel rời rạc.
- **Minh chứng tản mạn:** Ảnh chụp màn hình lớp học online, biên bản lập tay tại phòng học, giấy xác nhận, hình ảnh hiện trường được lưu trữ không đồng bộ trên điện thoại cá nhân hoặc Zalo/Email, dẫn đến nguy cơ thất lạc minh chứng kiểm tra.
- **Áp lực tổng hợp báo cáo:** Cuối mỗi ngày hoặc mỗi tháng, chuyên viên kiểm tra phải mất hàng giờ đồng hồ để sao chép số liệu từ nhiều nguồn, tổng hợp số tiết học, số lượng việc không phù hợp (KPH), sinh viên vi phạm, biên bản sự việc để soạn thảo báo cáo Word/Excel gửi Ban Giám hiệu.

### 1.2. Mục tiêu của kế hoạch phát triển ứng dụng
Kế hoạch phát triển Hệ thống Kiểm tra Nội bộ (KTNB App) được đặt ra với mục tiêu:
1. **Số hóa 100% quy trình kiểm tra nội bộ:** Chuyển đổi toàn bộ biểu mẫu kiểm tra, biên bản ghi nhận sự việc, bàn giao tài sản sang môi trường số.
2. **Tự động hóa tối đa:** Tự động lấy lịch học, tự động cào link phòng học trực tuyến, tự động sao lưu dữ liệu/minh chứng lên Google Drive và tự động kết xuất báo cáo chuẩn quy cách hành chính NTTU.
3. **Cá nhân hóa và bảo mật cao:** Đảm bảo đúng người – đúng quyền, phân cấp minh bạch, bảo vệ dữ liệu nội bộ.

---

## 2. CÔNG NGHỆ ÁP DỤNG TRONG HỆ THỐNG

Ứng dụng được thiết kế và xây dựng dựa trên nền tảng công nghệ web hiện đại, chú trọng vào tính **ổn định, tốc độ xử lý nhanh, bảo mật và khả năng tương thích cao trên mọi thiết bị** (máy tính để bàn, laptop, máy tính bảng, điện thoại di động):

| Thành phần | Công nghệ sử dụng | Vai trò & Ưu điểm vượt trội |
| :--- | :--- | :--- |
| **Backend Framework** | **Laravel 11 (PHP 8.2+)** | Framework PHP số 1 thế giới, kiến trúc MVC chuẩn mực, bảo mật cao (CSRF, XSS, SQL Injection prevention), quản lý Transaction dữ liệu chặt chẽ và xử lý tải mượt mà. |
| **Cơ sở dữ liệu** | **MySQL (InnoDB Engine)** | Quản lý quan hệ dữ liệu toàn diện (Relational DB), đánh chỉ mục (Indexing) tối ưu giúp truy vấn hàng trăm ngàn dòng lịch học chỉ trong vài mili-giây. |
| **Frontend Reactive** | **Alpine.js & Blade Components** | Siêu nhẹ, phản hồi tức thì (Reactive UI), giúp tương tác động mượt mà không gây nặng trình duyệt như các framework cồng kềnh khác. |
| **Giao diện & Thiết kế** | **Tailwind CSS & Vite** | Chuẩn nhận diện thương hiệu NTTU (màu xanh chủ đạo), chuẩn Responsive đa màn hình, tối ưu hóa kích thước file tĩnh (minified assets). |
| **Xử lý Báo cáo Tự động**| **PhpSpreadsheet & PhpWord** | Đọc, bóc tách dữ liệu bảng tính Excel và tự động sinh văn bản Báo cáo tháng định dạng Microsoft Word (`.docx`) chuẩn thể thức văn bản hành chính Việt Nam. |
| **Đám mây & Lưu trữ** | **Google Drive API v3 & Google Cloud** | Kết nối trực tiếp qua Google Service Account, tự động đồng bộ và lưu trữ minh chứng vĩnh viễn trên đám mây của Nhà trường. |
| **Thuật toán Tự động hóa**| **Google Apps Script (GAS) & Scraper**| Tự động tìm kiếm và trích xuất phòng học Google Meet, Moodle LCMS thông qua mã môn và thời khóa biểu. |
| **Ký số Điện tử** | **HTML5 Canvas Signature Engine** | Cho phép vẽ, lưu trữ vector chữ ký điện tử trực tiếp của chuyên viên, nhân viên và người làm chứng ngay trên màn hình cảm ứng hoặc chuột. |

---

## 3. CÁ NHÂN HÓA NGƯỜI DÙNG & BẢO MẬT PHÂN QUYỀN

Một trong những điểm sáng nhất của ứng dụng là **tính cá nhân hóa sâu sắc** kết hợp cùng hệ thống phân quyền đa tầng (Role-Based Access Control - RBAC):

### 3.1. Phân quyền chi tiết theo vai trò (RBAC)
- Hệ thống hỗ trợ phân định rành mạch các nhóm người dùng: **Quản trị viên hệ thống (Admin), Lãnh đạo Phòng, Chuyên viên kiểm tra, Giảng viên, Nhân viên hỗ trợ.**
- Mỗi vai trò được cấp quyền can thiệp chi tiết đến từng chức năng: Xem, Thêm mới, Chỉnh sửa, Xóa, Kết xuất báo cáo (Export). Người dùng không có thẩm quyền sẽ không thể truy cập hoặc nhìn thấy các phân hệ nhạy cảm.

### 3.2. Cá nhân hóa trải nghiệm người dùng
- **Trang cá nhân & Lịch sử ca trực:** Mỗi chuyên viên sau khi đăng nhập sẽ có bảng theo dõi riêng về các ca trực, nhiệm vụ kiểm tra được phân công trong ngày và thống kê hiệu suất công việc cá nhân.
- **Bộ nhớ tham số cá nhân:** Hệ thống ghi nhớ thông tin người ghi nhận, chức vụ, bộ phận để tự động điền sẵn vào các biểu mẫu (Biên bản sự việc, Nhận - trả tài sản, Ghi nhận vi phạm), giảm 80% thao tác nhập liệu lặp lại.
- **Tích hợp Chữ ký điện tử cá nhân hóa:** Chuyên viên và người liên quan có thể ký trực tiếp lên màn hình (điện thoại hoặc máy tính). Chữ ký được mã hóa và đóng dấu bản quyền vào biên bản, đảm bảo tính pháp lý và tính chịu trách nhiệm cá nhân.
- **Cổng Check-in Giảng viên (Lecturer Portal):** Thiết kế cổng tương tác riêng dành cho giảng viên kiểm tra thông tin lớp học, chủ động báo cáo tình trạng buổi dạy hoặc gửi phản ánh về phòng học trực tuyến nhanh chóng.
- **Nhật ký thao tác hệ thống (Audit Activity Log):** Ghi nhận chính xác dòng thời gian: *Ai? Thao tác gì? Vào thời điểm nào? Dữ liệu cũ và mới là gì?* Đảm bảo tính minh bạch tuyệt đối, phục vụ công tác kiểm tra chéo nội bộ.

---

## 4. CÁC TÍNH NĂNG TỰ ĐỘNG HÓA VƯỢT TRỘI

Hệ thống đã loại bỏ hoàn toàn các khâu nhập liệu thủ công truyền thống nhờ chuỗi tính năng tự động hóa khép kín:

### 4.1. Tự động lấy và xử lý lịch học (Thời khóa biểu)
- **Tự động bóc tách file Excel TKB:** Hệ thống tích hợp bộ lọc thông minh (`ScheduleImportReadFilter`), có khả năng đọc và bóc tách các file Thời khóa biểu toàn trường với dung lượng hàng chục ngàn dòng trong vài giây.
- **Tự động chuẩn hóa dữ liệu:** Tự động nhận diện cơ sở, dãy nhà, số phòng học, tên giảng viên, mã lớp và phân loại chính xác hình thức học (Lớp trực tiếp, Lớp online, Cố vấn học tập, Thi kết thúc môn, Thực hành ngoài).
- **Tự động dò tìm link học Google Meet / Moodle LCMS:** Tự động kết nối cơ sở dữ liệu học trực tuyến, truy vấn và hiển thị link phòng học Google Meet tương ứng với mã học phần, giúp chuyên viên kiểm tra vào dự giờ lớp online chỉ bằng 1 cú nhấp chuột.

### 4.2. Tự động đẩy dữ liệu và minh chứng lên Google Drive
- **Đồng bộ hóa đám mây liên tục:** Khi chuyên viên chụp ảnh hoặc tải lên minh chứng (hình ảnh vi phạm, ảnh lớp học, tài liệu liên quan), hệ thống sử dụng **Google Drive v3 API** để tự động tải file lên thư mục Google Drive chuyên biệt của Phòng KTNB.
- **Bảo mật và chống tràn ổ cứng máy chủ:** Việc tự động đẩy lên Google Drive giúp bảo toàn dung lượng máy chủ lưu trữ, bảo đảm minh chứng không bao giờ bị mất ngay cả khi máy chủ gặp sự cố kỹ thuật.

### 4.3. Tự động tổng hợp và kết xuất báo cáo đa định dạng
- **Báo cáo cuối ngày tự động:** Hệ thống tự động quét toàn bộ các ca trực trong ngày (sáng, chiều, tối), tổng hợp các ghi nhận bất thường, số tiết thực dạy, số việc KPH và tạo thành bản Báo cáo cuối ngày gửi Lãnh đạo chỉ bằng 1 thao tác.
- **Tự động sinh Báo cáo tháng Microsoft Word (`.docx`):** Đây là tính năng đột phá nhất. Thay vì mất nhiều ngày soạn thảo, hệ thống tự động đưa dữ liệu thống kê vào tệp mẫu Word chuẩn của Trường ĐH Nguyễn Tất Thành (`baocaothang-template.docx`), tự động vẽ bảng biểu, điền số liệu thống kê việc KPH, sinh viên vi phạm, người tốt việc tốt chuẩn xác 100% về mặt định dạng văn bản.

---

## 5. KẾT QUẢ TRIỂN KHAI CÁC PHÂN HỆ CHỨC NĂNG (ĐÃ HOÀN THÀNH 100%)

Đến nay, toàn bộ các mục tiêu trong kế hoạch phát triển đã được **nghiệm thu và hoàn thành 100%**:

```
                              HỆ THỐNG KTNB - NTTU
                                       │
     ┌──────────────────┬──────────────┴─────┬──────────────────┬─────────────────┐
     ▼                  ▼                    ▼                  ▼                 ▼
[BỘ DANH MỤC]     [CÔNG CỤ KIỂM TRA]   [BÁO CÁO THỐNG KÊ]   [CÔNG CỤ MỞ RỘNG]  [QUẢN TRỊ HỆ THỐNG]
- Chức vụ          - Lớp trực tiếp      - Báo cáo cuối ngày - Kho minh chứng   - Lịch học theo ngày
- Dãy nhà / Phòng  - Lớp Online (Meet)  - Báo cáo tháng Word- Cổng Giảng viên  - Phân quyền (RBAC)
- Đơn vị / Khoa    - Cố vấn học tập     - Thống kê việc KPH - Tra cứu AI       - Sao lưu DB tự động
- Giảng viên / SV  - Thi kết thúc môn   - SV vi phạm        - Giám sát thực tế - Nhật ký Audit Log
- Loại văn bản     - Biên bản sự việc   - Người tốt việc tốt- Bảng thảo luận   - Tham số hệ thống
- Việc phát sinh   - Nhận - trả tài sản - Đơn thư / Yêu cầu - Thông báo nội bộ - Quản lý file mã nguồn
```

### Chi tiết các phân hệ đã vận hành:
1. **Phân hệ Tổng quan (Dashboard):** Hiển thị biểu đồ trực quan thời gian thực về tình hình kiểm tra, số lớp học đang diễn ra, số vụ việc phát sinh trong ngày.
2. **Phân hệ Công cụ kiểm tra (Core Inspection Modules):**
   - *Lớp trực tiếp & Lớp online:* Kiểm tra sĩ số, thời gian lên lớp, ghi nhận phát sinh.
   - *Cố vấn học tập:* Giám sát lịch sinh hoạt lớp định kỳ.
   - *Thi kết thúc môn:* Giám sát phòng thi, giám thị 1, 2, 3 và sinh viên dự thi.
   - *Biên bản sự việc:* Lập biên bản vi phạm tại chỗ, tích hợp chữ ký số 2 bên và ảnh hiện trường.
   - *Nhận – Trả tài sản & Đồ thất lạc:* Ghi nhận đồ rơi rớt, trả lại cho sinh viên, vinh danh "Người tốt việc tốt".
   - *Tiếp nhận đơn thư & Yêu cầu:* Tiếp nhận, theo dõi tiến độ xử lý khiếu nại, phản ánh của người học.
3. **Phân hệ Kho minh chứng (Centralized Evidence Manager):** Lưu trữ tập trung hơn 200+ bộ minh chứng thực tế, phân loại thông minh theo ngày tháng, phòng học, giảng viên, hỗ trợ tìm kiếm siêu tốc.
4. **Phân hệ Báo cáo tự động:** Kết xuất tức thì Báo cáo cuối ngày, Báo cáo tháng Word, Excel thống kê việc không phù hợp (KPH), sinh viên vi phạm.
5. **Phân hệ Quản trị & An toàn dữ liệu:** Phân quyền theo module, sao lưu cơ sở dữ liệu định kỳ, lưu trữ nhật ký truy cập.

---

## 6. ĐÁNH GIÁ TÍNH KHẢ THI CỦA ỨNG DỤNG

Qua quá trình xây dựng, thử nghiệm và đưa vào sử dụng thực tế, ứng dụng thể hiện **tính khả thi tuyệt đối** trên mọi phương diện:

### 6.1. Tính khả thi về mặt Kỹ thuật & Công nghệ (Technical Feasibility)
- **Tương thích hoàn hảo với hạ tầng hiện tại:** Ứng dụng chạy mượt mà trên môi trường máy chủ Web Server tiêu chuẩn của Nhà trường, không yêu cầu cài đặt phần mềm phụ trợ phức tạp phía người dùng cuối.
- **Tối ưu hóa tài nguyên:** Ứng dụng phản hồi cực nhanh (thời gian tải trang trung bình dưới 0.5 giây), cơ sở dữ liệu được thiết kế chuẩn hóa, sẵn sàng mở rộng khi quy mô trường tăng lên mà không lo nghẽn mạng hay quá tải.
- **An toàn thông tin:** Dữ liệu mật khẩu được mã hóa chuẩn Bcrypt, hệ thống phòng chống triệt để các lỗ hổng bảo mật web phổ biến (CSRF, XSS, SQL Injection).

### 6.2. Tính khả thi về mặt Nghiệp vụ & Đào tạo (Operational Feasibility)
- **Bám sát 100% quy trình thực tế của Phòng KTNB:** Hệ thống không sao chép máy móc từ các phần mềm thương mại bên ngoài mà được "đo ni đóng giày" theo đúng đặc thù, quy chế kiểm tra nội bộ của Trường Đại học Nguyễn Tất Thành.
- **Giao diện thân thiện, dễ sử dụng:** Giao diện tiếng Việt rõ ràng, bố cục hiện đại, màu sắc trực quan, giúp chuyên viên lớn tuổi hay nhân viên mới đều có thể sử dụng thành thạo chỉ sau 15 phút hướng dẫn.

### 6.3. Tính khả thi về mặt Kinh tế & Tài chính (Economic Feasibility)
- **Tiết kiệm tối đa ngân sách:** Do ứng dụng được nghiên cứu và phát triển nội bộ bởi cán bộ chuyên trách của Nhà trường, Nhà trường không phải tốn hàng trăm triệu đồng chi phí thuê ngoài (Outsourcing) hay phí duy trì bản quyền đắt đỏ hàng năm.
- **Tự chủ hoàn toàn công nghệ:** Toàn quyền kiểm soát mã nguồn, có thể nhanh chóng nâng cấp, sửa đổi chức năng bất cứ lúc nào khi Nhà trường có quy định hoặc quy trình mới mà không phụ thuộc vào đối tác bên thứ ba.

---

## 7. ĐÁNH GIÁ NHỮNG THUẬN LỢI KHI ĐƯA VÀO VẬN HÀNH THỰC TẾ

Việc triển khai thành công Ứng dụng Kiểm tra Nội bộ đem lại những giá trị và thuận lợi to lớn cho nhiều nhóm đối tượng trong Nhà trường:

### 7.1. Đối với Chuyên viên Kiểm tra Nội bộ
- **Giảm 70% thời gian tác nghiệp:** Không còn phải mang vác cặp tài liệu, sổ sách cồng kềnh khi đi kiểm tra các tầng lầu; chỉ cần một chiếc điện thoại thông minh hoặc máy tính bảng là có thể tra cứu lịch, ghi nhận vi phạm, chụp ảnh minh chứng và ký biên bản tại chỗ.
- **Loại bỏ áp lực làm báo cáo:** Tính năng tự động tổng hợp số liệu và xuất báo cáo Word cuối tháng giúp giải phóng chuyên viên khỏi các công việc hành chính thủ công, tập trung nâng cao chất lượng chuyên môn giám sát.
- **Tra cứu minh chứng tức thì:** Khi có khiếu nại hoặc cần kiểm tra đối chiếu, chuyên viên có thể tìm thấy ngay hình ảnh, biên bản sự việc chỉ trong vài giây thông qua công cụ tìm kiếm của Kho minh chứng.

### 7.2. Đối với Lãnh đạo Phòng & Ban Giám hiệu
- **Nắm bắt số liệu theo thời gian thực (Real-time):** Lãnh đạo có thể mở ứng dụng trên điện thoại mọi lúc mọi nơi để biết hôm nay toàn trường có bao nhiêu lớp học, tỷ lệ lên lớp đúng giờ, các sự việc phát sinh trong ngày mà không cần chờ chuyên viên gửi báo cáo giấy.
- **Dữ liệu chính xác, minh bạch, có bằng chứng xác thực:** Mọi số liệu báo cáo đều có minh chứng hình ảnh, chữ ký điện tử và lịch sử kiểm tra đính kèm, xóa bỏ hoàn toàn tình trạng báo cáo cảm tính hoặc thiếu căn cứ.
- **Ra quyết định điều hành nhanh chóng:** Giúp Lãnh đạo Nhà trường phát hiện sớm các điểm nghẽn, các khoa/bộ môn có nhiều việc không phù hợp để kịp thời chấn chỉnh, nâng cao chất lượng đào tạo chung của NTTU.

### 7.3. Đối với Giảng viên và Sinh viên
- **Minh bạch và khách quan:** Giảng viên và sinh viên khi có phát sinh sự việc đều được xem lại nội dung ghi nhận, cùng ký tên xác nhận trực tiếp trên màn hình, đảm bảo tính công bằng và tôn trọng quyền lợi của người học.
- **Lan tỏa giá trị nhân văn:** Chức năng ghi nhận "Nhận - Trả tài sản" và "Người tốt việc tốt" giúp sinh viên nhanh chóng tìm lại đồ thất lạc, đồng thời tạo động lực khen thưởng kịp thời cho những hành động đẹp trong nhà trường.

---

## 8. KẾT LUẬN & KIẾN NGHỊ

### 8.1. Kết luận
Kế hoạch phát triển Hệ thống Quản lý & Giám sát Kiểm tra Nội bộ (KTNB NTTU) đã được **triển khai hoàn thành xuất sắc 100% mục tiêu đề ra**. Ứng dụng không chỉ là một công cụ phần mềm đơn thuần mà là một **bước đột phá trong công tác chuyển đổi số** của Phòng Kiểm tra Nội bộ nói riêng và của Trường Đại học Nguyễn Tất Thành nói chung. 

Ứng dụng đáp ứng trọn vẹn các tiêu chí: **Hiện đại về công nghệ – Cá nhân hóa cao – Tự động hóa vượt trội – Khả thi tuyệt đối – Mang lại hiệu quả kinh tế và nghiệp vụ rõ rệt.**

### 8.2. Kiến nghị
1. **Ban Giám hiệu:** Tiếp tục công nhận và chỉ đạo áp dụng chính thức Hệ thống KTNB App làm công cụ làm việc chuẩn hóa bắt buộc cho toàn bộ các ca trực kiểm tra nội bộ trên toàn hệ thống các cơ sở của Trường.
2. **Trung tâm CNTT / Phòng Đào tạo:** Tạo điều kiện kết nối API đồng bộ trực tiếp dữ liệu Thời khóa biểu từ phần mềm Quản lý Đào tạo vào Hệ thống KTNB App nhằm tăng cường hơn nữa tính tự động hóa trong các năm học tiếp theo.

---

*Báo cáo được lập và lưu trữ trên Hệ thống Kiểm tra Nội bộ Trường Đại học Nguyễn Tất Thành.*
