# การลบคอลัมน์ Adoption จาก impact_pathway.php

## ส่วนที่ต้องแก้ไข:

### 1. ลบคอลัมน์จากตาราง header (บรรทัดประมาณ 460-470):

เปลี่ยนจาก:

```html
<th class="header-user">ผู้ใช้ประโยชน์<br /><small>User</small></th>
<th class="header-adoption">การนำไปใช้<br /><small>Adoption</small></th>
<th class="header-outcome">ผลลัพธ์<br /><small>Outcome</small></th>
```

เป็น:

```html
<th class="header-user">ผู้ใช้ประโยชน์<br /><small>User</small></th>
<th class="header-outcome">ผลลัพธ์<br /><small>Outcome</small></th>
```

### 2. ลบเซลล์ในตาราง tbody (บรรทัดประมาณ 475-495):

เปลี่ยนจาก:

```html
<tr>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
  <!-- เซลล์ Adoption -->
  <td></td>
  <td></td>
</tr>
```

เป็น:

```html
<tr>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
  <!-- ลบเซลล์ Adoption ออก -->
  <td></td>
</tr>
```

### 3. ลบฟอร์มฟิลด์ adoption_description (บรรทัดประมาณ 580-590):

ลบทั้งส่วนนี้:

```html
<div class="form-group">
  <label class="form-label">
    <span class="step-number">5</span>
    การนำไปใช้ (Adoption)
  </label>
  <input
    type="text"
    class="form-input"
    name="adoption_description"
    value="<?php echo htmlspecialchars($_POST['adoption_description'] ?? ''); ?>"
  />
  <div class="form-help">อธิบายวิธีการและกระบวนการนำผลผลิตไปใช้</div>
</div>
```

### 4. อัปเดตหมายเลขขั้นตอนในฟอร์ม:

เปลี่ยนหมายเลขขั้นตอนจาก:

- ผลลัพธ์: step-number 6 → 5
- ผลกระทบ: step-number 7 → 6

### 5. ลบการประมวลผล adoption_description ใน PHP:

ลบบรรทัด:

```php
$adoption_description = trim($_POST['adoption_description']);
```

และลบจากคำสั่ง SQL INSERT:

```php
adoption_description,
```

และ

```php
$adoption_description,
```

### 6. ลบสไตล์ CSS สำหรับ header-adoption:

ลบ:

```css
.header-adoption {
  background-color: #f3e5f5;
}
```
