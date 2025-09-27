# สรุปการ Deploy และการตั้งค่าโปรเจคบน AWS (Ubuntu)

เอกสารนี้สรุปขั้นตอนการตั้งค่าเซิร์ฟเวอร์, การแก้ไขโค้ด, และปัญหาที่พบระหว่างการย้ายโปรเจค LINE Flex VIP จาก aaPanel ไปยัง AWS

## 1. ภาพรวมสถาปัตยกรรม

- **Cloud Provider**: AWS (EC2 - Ubuntu Server)
- **Web Server**: Apache2
- **Domain/DNS**: Cloudflare
- **SSL**: Let's Encrypt (จัดการโดย Certbot)
- **Backend**: PHP

## 2. ไฟล์ที่ไม่ได้อยู่ใน Git (การตั้งค่าบน Server)

ไฟล์เหล่านี้เป็นการตั้งค่าเฉพาะสำหรับเซิร์ฟเวอร์ AWS และ **จะไม่มีอยู่ใน Git Repository**

### 2.1 Apache Virtual Host (`/etc/apache2/sites-available/flex-le-ssl.conf`)

นี่คือไฟล์ Configuration สุดท้ายสำหรับ HTTPS (Port 443) ซึ่งเป็นไฟล์ที่ใช้งานจริงและสำคัญที่สุด

```apache
<IfModule mod_ssl.c>
<VirtualHost *:443>
    ServerName linecard.tectony.co.th
    # DocumentRoot ถูกตั้งค่าให้ชี้ไปที่โฟลเดอร์โปรเจคโดยตรง
    DocumentRoot /www/flex/magiccard/vip

    ErrorLog ${APACHE_LOG_DIR}/linecard-error.log
    CustomLog ${APACHE_LOG_DIR}/linecard-access.log combined

    # การตั้งค่าสำหรับ Directory เพื่อป้องกันปัญหาและกำหนดไฟล์เริ่มต้น
    <Directory /www/flex/magiccard/vip>
        # ปิดการแสดงรายชื่อไฟล์ (Directory Listing)
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        # กำหนดให้เรียก index.php เป็นไฟล์แรก
        DirectoryIndex index.php createflex.php
    </Directory>

    # --- SSL Configuration (สร้างโดย Certbot) ---
    SSLCertificateFile /etc/letsencrypt/live/linecard.tectony.co.th/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/linecard.tectony.co.th/privkey.pem
    Include /etc/letsencrypt/options-ssl-apache.conf
</VirtualHost>
</IfModule>
```

## 3. ไฟล์ที่ถูกแก้ไข/สร้าง และควรอัพเดทขึ้น Git

ไฟล์เหล่านี้คือส่วนหนึ่งของโปรเจคที่ถูกแก้ไข และควรถูก Commit และ Push ขึ้น Git Repository

### 3.1 `vip/index.php` (สร้างขึ้นใหม่)

- **หน้าที่**: สร้างขึ้นเพื่อเป็นไฟล์เริ่มต้น (Entry Point) ให้กับ `DocumentRoot` ทำให้ Server รู้ว่าต้องรันไฟล์ไหนก่อน
- **โค้ด**:
  ```php
  <?php
  require_once __DIR__ . '/createflex.php';
  ?>
  ```

### 3.2 `vip/createflex.php` (แก้ไข)

- **ปัญหาเดิม**: โค้ดพยายามดึงข้อมูลจาก `$_POST` ที่ไม่มีอยู่จริง ทำให้เกิด Error 500 บน PHP เวอร์ชันใหม่
- **การแก้ไข**: เพิ่ม Null Coalescing Operator (`??`) เพื่อกำหนดค่าเริ่มต้นที่ปลอดภัย ป้องกันไม่ให้ตัวแปรเป็น `null`
  ```php
  // จากเดิม
  // $alt_text = $_POST['alt_text'];
  // $json_text = json_decode($_POST['json_text'], true);

  // แก้ไขเป็น
  $alt_text = $_POST['alt_text'] ?? 'Magic Card';
  $json_text = json_decode($_POST['json_text'] ?? '[]', true);
  ```

### 3.3 `.gitignore` (สร้างขึ้นใหม่)
- **หน้าที่**: ป้องกันไม่ให้ไฟล์ที่ไม่จำเป็นถูกนำขึ้น Git
- **โค้ด**:
  ```
  *.log
  .DS_Store
  .vscode/
  tmp/
  ```

## 4. สรุปปัญหาที่พบและแนวทางการแก้ไข

1.  **ปัญหา**: **Directory Listing** (แสดงรายชื่อไฟล์)
    - **สาเหตุ**: `DocumentRoot` ชี้ไปที่โฟลเดอร์ที่ไม่มีไฟล์ `index.php` และ Apache ไม่ได้ถูกตั้งค่าให้ปิด `Options Indexes`
    - **การแก้ไข**: สร้างไฟล์ `index.php` และแก้ไขไฟล์ Apache Config ให้มี `Options -Indexes` และ `DirectoryIndex index.php`

2.  **ปัญหา**: **HTTP ERROR 500 (Internal Server Error)**
    - **สาเหตุ**: เกิด Fatal Error ในโค้ด PHP เนื่องจากพยายามใช้ฟังก์ชัน `count()` กับตัวแปรที่เป็น `null` ซึ่งเกิดจากการดึงค่าจาก `$_POST` ที่ไม่มีอยู่
    - **การแก้ไข**: ตรวจสอบ Apache Error Log (`/var/log/apache2/linecard-error.log`) เพื่อหาต้นตอ และแก้ไขโค้ด `createflex.php` ให้มีการตรวจสอบและกำหนดค่าเริ่มต้นที่ปลอดภัย

3.  **ปัญหา**: **การตั้งค่า SSL ไม่ถูกต้อง**
    - **สาเหตุ**: การแก้ไข `DocumentRoot` และ `Directory` ถูกทำในไฟล์ Config ของ HTTP (Port 80) แต่ไม่ได้ทำในไฟล์ของ HTTPS (Port 443) ที่ Certbot สร้างขึ้น (`flex-le-ssl.conf`)
    - **การแก้ไข**: นำการตั้งค่าที่ถูกต้องทั้งหมดไปใส่ในไฟล์ `flex-le-ssl.conf`

4.  **ปัญหา**: **Apache Restart ไม่ได้**
    - **สาเหตุ**: มีการพิมพ์ชื่อ Path ของ SSL Certificate ผิดในไฟล์ `flex-le-ssl.conf`
    - **การแก้ไข**: ใช้คำสั่ง `sudo apache2ctl configtest` เพื่อหาจุดที่ผิด และแก้ไข Path ให้ถูกต้องตามที่ Certbot สร้างไว้

---
**สถานะปัจจุบัน**: ระบบทำงานได้อย่างถูกต้องบน AWS และมีการตั้งค่าที่เหมาะสมแล้ว
