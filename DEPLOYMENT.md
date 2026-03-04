# ডিপ্লয়মেন্ট চেকলিস্ট - হিসাব পত্র

## প্রি-ডিপ্লয়মেন্ট চেকলিস্ট

### ১. ডাটাবেস সেটআপ
- [ ] MySQL ডাটাবেস তৈরি করা হয়েছে
- [ ] `database.sql` ফাইল ইমপোর্ট করা হয়েছে
- [ ] ডাটাবেস ইউজার তৈরি করা হয়েছে
- [ ] পারমিশন সঠিকভাবে সেট করা হয়েছে

### ২. কনফিগারেশন ফাইল
- [ ] `config/database.php` আপডেট করা হয়েছে
- [ ] `config/app.php` চেক করা হয়েছে
- [ ] `config/google-config.php` আপডেট করা হয়েছে (Google OAuth এর জন্য)

### ৩. ফাইল আপলোড
- [ ] সব ফাইল সার্ভারে আপলোড করা হয়েছে
- [ ] ফাইল পারমিশন সঠিকভাবে সেট করা হয়েছে
- [ ] `.htaccess` ফাইল আপলোড করা হয়েছে

### ৪. সার্ভার রিকোয়ারমেন্ট
- [ ] PHP 7.4+ ইনস্টল করা আছে
- [ ] MySQL 5.7+ ইনস্টল করা আছে
- [ ] Apache mod_rewrite এনাবল করা আছে
- [ ] PHP Extensions: PDO, PDO_MySQL, cURL, JSON, GD

## ডিপ্লয়মেন্ট স্টেপস

### স্টেপ ১: ডাটাবেস কনফিগারেশন
```sql
-- ডাটাবেস তৈরি করুন
CREATE DATABASE if0_38847546_hisab;

-- ইউজার তৈরি করুন (যদি প্রয়োজন)
CREATE USER 'if0_38847546'@'localhost' IDENTIFIED BY 'SubtitleSync';
GRANT ALL PRIVILEGES ON if0_38847546_hisab.* TO 'if0_38847546'@'localhost';
FLUSH PRIVILEGES;

-- ডাটাবেস ইমপোর্ট করুন
USE if0_38847546_hisab;
SOURCE database.sql;
```

### স্টেপ ২: কনফিগারেশন আপডেট
```php
// config/database.php
define('DB_HOST', 'sql211.infinityfree.com');
define('DB_NAME', 'if0_38847546_hisab');
define('DB_USER', 'if0_38847546');
define('DB_PASS', 'SubtitleSync');

// config/app.php
define('APP_URL', 'https://hisab-potro.free.nf');

// config/google-config.php
define('GOOGLE_CLIENT_ID', '126175981408-f61peld0bnknh399mug92lol5mrqm33g.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', 'https://hisab-potro.free.nf/callback.php');
```

### স্টেপ ৩: ফাইল পারমিশন সেট
```bash
# লগ ডিরেক্টরি তৈরি
mkdir logs

# পারমিশন সেট
chmod 755 logs/
chmod 644 config/*.php
chmod 644 .htaccess
```

### স্টেপ ৪: ওয়েব সার্ভার কনফিগারেশন
```apache
# Apache এর জন্য .htaccess ফাইল ইতোমধ্যে অন্তর্ভুক্ত আছে
# mod_rewrite এনাবল করুন
a2enmod rewrite

# Apache রিস্টার্ট করুন
systemctl restart apache2
```

## পোস্ট-ডিপ্লয়মেন্ট টেস্টিং

### ১. বেসিক ফাংশনালিটি টেস্ট
- [ ] ওয়েবসাইট লোড হচ্ছে
- [ ] লগইন পেজ কাজ করছে
- [ ] সাইন আপ কাজ করছে
- [ ] Google OAuth কাজ করছে

### ২. ডাটাবেস কানেকশন টেস্ট
- [ ] ডাটাবেস কানেক্ট হচ্ছে
- [ ] নতুন ইউজার তৈরি হচ্ছে
- [ ] গ্রাহক তৈরি হচ্ছে
- [ ] বাকি এন্ট্রি হচ্ছে

### ৩. API এন্ডপয়েন্ট টেস্ট
```bash
# গ্রাহক API টেস্ট
curl -X GET "https://hisab-potro.free.nf/api/customers.php?action=list"

# বাকি API টেস্ট
curl -X GET "https://hisab-potro.free.nf/api/debts.php?action=list"

# সিঙ্ক API টেস্ট
curl -X POST "https://hisab-potro.free.nf/api/sync.php"
```

### ৪. রিয়েল-টাইম সিঙ্ক টেস্ট
- [ ] দুই ব্রাউজারে লগইন করুন
- [ ] একজন গ্রাহক তৈরি করুন
- [ ] অন্যজন রিফ্রেশ করে দেখুন গ্রাহক দেখাচ্ছে কিনা
- [ ] সিঙ্ক স্ট্যাটাস চেক করুন

## ট্রাবলশুটিং গাইড

### সাধারণ সমস্যা এবং সমাধান

#### ১. ডাটাবেস কানেকশন ত্রুটি
```
ত্রুটি: SQLSTATE[HY000] [2002] Connection refused
সমাধান:
- ডাটাবেস হোস্ট চেক করুন
- ইউজারনেম এবং পাসওয়ার্ড চেক করুন
- ডাটাবেস সার্ভার চলছে কিনা চেক করুন
```

#### ২. ৪০৩ Forbidden ত্রুটি
```
ত্রুটি: 403 Forbidden
সমাধান:
- ফাইল পারমিশন চেক করুন
- .htaccess ফাইল চেক করুন
- Apache কনফিগারেশন চেক করুন
```

#### ৩. ৫০০ Internal Server Error
```
ত্রুটি: 500 Internal Server Error
সমাধান:
- PHP error log চেক করুন
- .htaccess সিনট্যাক্স চেক করুন
- PHP version চেক করুন
```

#### ৪. Google OAuth ত্রুটি
```
ত্রুটি: redirect_uri_mismatch
সমাধান:
- Google Console এ রিডাইরেক্ট URI চেক করুন
- Client ID এবং Secret চেক করুন
- HTTPS ব্যবহার করছেন কিনা চেক করুন
```

#### ৫. সিঙ্ক কাজ করছে না
```
ত্রুটি: ডাটা সিঙ্ক হচ্ছে না
সমাধান:
- ইন্টারনেট কানেকশন চেক করুন
- ব্রাউজার কনসোল চেক করুন
- API endpoint টেস্ট করুন
- Session চেক করুন
```

## পারফরম্যান্স অপ্টিমাইজেশন

### ১. ডাটাবেস অপ্টিমাইজেশন
```sql
-- ইনডেক্স তৈরি (যদি মিসিং থাকে)
CREATE INDEX idx_customers_name ON customers(name);
CREATE INDEX idx_customers_phone ON customers(phone);
CREATE INDEX idx_debts_customer ON debts(customer_id);
CREATE INDEX idx_payments_customer ON payments(customer_id);

-- ডাটাবেস অপ্টিমাইজ
OPTIMIZE TABLE customers, debts, payments;
```

### ২. PHP অপ্টিমাইজেশন
```php
// php.ini এ নিচের সেটিংস যোগ করুন
memory_limit = 256M
max_execution_time = 30
upload_max_filesize = 2M
post_max_size = 8M
```

### ৩. ক্যাশিং অপ্টিমাইজেশন
```apache
# .htaccess এ ক্যাশিং সেটিংস
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
</IfModule>
```

## সিকিউরিটি চেকলিস্ট

### ১. ফাইল সিকিউরিটি
- [ ] সেনসিটিভ ফাইলের পারমিশন সঠিক (644)
- [ ] config ফোল্ডার প্রোটেক্টেড
- [ ] .htaccess ফাইল কাজ করছে

### ২. ডাটাবেস সিকিউরিটি
- [ ] ডাটাবেস ইউজারের সীমিত পারমিশন
- [ ] SQL injection prevention কাজ করছে
- [ ] Prepared statements ব্যবহার করা হচ্ছে

### ৩. অ্যাপ্লিকেশন সিকিউরিটি
- [ ] XSS prevention কাজ করছে
- [ ] CSRF protection কাজ করছে
- [ ] Session security কাজ করছে
- [ ] Password hashing সঠিকভাবে কাজ করছে

## মনিটরিং এবং লগিং

### ১. লগ ফাইল মনিটরিং
```bash
# PHP error log monitor
tail -f logs/php_errors.log

# Apache access log monitor
tail -f /var/log/apache2/access.log

# MySQL slow query log monitor
tail -f /var/log/mysql/mysql-slow.log
```

### ২. পারফরম্যান্স মনিটরিং
- [ ] পেজ লোড টাইম মনিটরিং
- [ ] ডাটাবেস কোয়েরি পারফরম্যান্স মনিটরিং
- [ ] মেমরি ইউসেজ মনিটরিং

### ৩. ইউজার অ্যাক্টিভিটি মনিটরিং
- [ ] লগইন/লগআউট লগিং
- [ ] অ্যাক্টিভিটি লগ মনিটরিং
- [ ] সিঙ্ক স্ট্যাটাস মনিটরিং

## ব্যাকআপ এবং রিকভারি

### ১. ডাটাবেস ব্যাকআপ
```bash
# ডেইলি ব্যাকআপ স্ক্রিপ্ট
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u if0_38847546 -p'SubtitleSync' if0_38847546_hisab > backup_$DATE.sql
```

### ২. ফাইল ব্যাকআপ
```bash
# ফাইল ব্যাকআপ স্ক্রিপ্ট
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
tar -czf backup_files_$DATE.tar.gz /path/to/hisab-potro/
```

### ৩. রিকভারি প্রক্রিয়া
```bash
# ডাটাবেস রিস্টোর
mysql -u if0_38847546 -p'SubtitleSync' if0_38847546_hisab < backup_20240304_120000.sql

# ফাইল রিস্টোর
tar -xzf backup_files_20240304_120000.tar.gz
```

## চূড়ান্ত চেকলিস্ট

### ডিপ্লয়মেন্টের আগে
- [ ] সব কনফিগারেশন চেক করা হয়েছে
- [ ] ব্যাকআপ নেওয়া হয়েছে
- [ ] টেস্টিং সম্পন্ন হয়েছে
- [ ] ডকুমেন্টেশন আপডেট করা হয়েছে

### ডিপ্লয়মেন্টের পরে
- [ ] ওয়েবসাইট সঠিকভাবে কাজ করছে
- [ ] সব ফিচার টেস্ট করা হয়েছে
- [ ] পারফরম্যান্স ঠিক আছে
- [ ] সিকিউরিটি চেক করা হয়েছে
- [ ] মনিটরিং সেটআপ করা হয়েছে

---

**ডিপ্লয়মেন্ট সফল!** 🎉

এখন আপনার হিসাব পত্র অ্যাপ্লিকেশনটি লাইভে প্রস্তুত।
