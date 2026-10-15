# Lost & Found Management System

A premium, secure, PHP + MySQL Lost & Found Management System built for XAMPP. Designed for a System Administration & Maintenance capstone project.

## 🚀 Quick Setup (XAMPP)

1. Install **XAMPP** and start **Apache** + **MySQL**.
2. Copy this project folder to:
3. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
4. Click **Import** → choose `database/lost_and_found_db.sql` → **Go**.
5. Place your logo at:
   (Square image, any size — it will be preserved.)
6. Visit: `http://localhost/lost_and_found/`

## 👤 Create First Administrator

1. Visit: `http://localhost/lost_and_found/setup/create_admin.php`
2. Default credentials:
   - Username: `admin`
   - Password: `Admin123!`
3. **⚠️ Change the password immediately after first login.**
4. **DELETE `setup/create_admin.php`** after creating the admin account.

## 👥 Create Staff Accounts

1. Log in as Administrator.
2. Go to **Administration → Manage Users**.
3. Fill out the "Create New Staff Account" form.

## 🔐 Security Features

- Password hashing via `password_hash()` / `password_verify()`
- Prepared statements (no SQL injection)
- CSRF tokens on every state-changing form
- Role-based authorization (admin / staff)
- Server-side validation on every input
- Safe image upload (MIME validation, random filenames, stored outside web execution)
- Session regeneration on login
- Backups stored outside web-accessible directory

## 💾 Backup & Recovery

- **Automatic**: Admin → Backup & Recovery → "Create Backup Now"
- **Manual**: phpMyAdmin → Export → `lost_and_found_db` → SQL
- **Restore**: Upload the `.sql` file through phpMyAdmin → Import

## 🧪 Testing

### As Admin
- Create staff accounts
- Report lost items, add found items
- Confirm matches between lost/found records
- Update statuses (Open → Matched → Returned → Closed)
- View Activity Log, System Monitor, Settings

### As Staff
- Report lost items, add found items
- View and edit their own records
- Cannot access admin-only pages (returns 403)

## 📁 Project Structure
lost_and_found/
├── index.php # Landing page
├── login.php # Sign in
├── logout.php # Sign out
├── dashboard.php # Admin home
├── config/database.php # DB connection
├── includes/ # Reusable components
├── admin/ # Admin-only pages
├── lost_items/ # Lost items module
├── found_items/ # Found items module
├── profile/ # User profile
├── assets/ # CSS, JS, images
├── picture/logo.jpg # Your logo (place here)
├── uploads/ # Uploaded images
├── storage/backups/ # DB backups (protected)
├── database/ # SQL import file
└── setup/ # One-time admin setup

## 🎨 Design

- **Palette**: White, cool gray, deep navy, restrained electric-blue accent
- **Typography**: Poppins (body), Outfit (display headings)
- **Liquid glass**: Used only on the top navigation bar
- **Logo**: Square, never cropped to circle, `object-fit: contain`

## 🛠 Troubleshooting

| Issue | Solution |
|-------|----------|
| Apache won't start | Another app uses port 80. Change Apache port in `httpd.conf` or stop Skype/IIS. Update `BASE_URL` in `config/database.php` if using a custom port. |
| MySQL won't start | Port 3306 conflict, or data folder corrupted. Check XAMPP Control Panel logs. |
| "Database connection failed" | Verify MySQL is running. Check `DB_USER`/`DB_PASS` in `config/database.php`. |
| Login fails | Ensure admin was created via `setup/create_admin.php`. Check `users` table in phpMyAdmin. |
| Images won't upload | Ensure `uploads/` folder is writable (right-click → Properties → Security). |
| Backup fails | Ensure `storage/backups/` is writable. `mysqldump` must be in system PATH (included in XAMPP). |

## 🗓 Maintenance Schedule

- **Daily**: Review activity log, verify backups
- **Weekly**: Check disk space, review error logs
- **Monthly**: Review user accounts, update XAMPP, test backup restoration
- **As needed**: Create staff accounts, adjust settings, restore from backup

## 📝 Administration Log Template

| Date | Activity | Action Taken | Result |
|------|----------|--------------|--------|
| YYYY-MM-DD | _e.g., User account review_ | _e.g., Deactivated 2 inactive staff_ | _Success_ |

## 📄 License

Built for educational / capstone purposes.