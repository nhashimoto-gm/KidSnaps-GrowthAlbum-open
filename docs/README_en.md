# KidSnaps Growth Album

![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange?logo=mysql)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3.2-purple?logo=bootstrap)
![License](https://img.shields.io/badge/License-MIT-green)
![GetID3](https://img.shields.io/badge/GetID3-Enabled-brightgreen)
![HEIC Support](https://img.shields.io/badge/HEIC-Supported-blue)

A photo and video album web application designed to document your child’s growth and memories.

## Table of Contents

- [Overview](#overview)
- [Quick Start](#quick-start)
- [Main Features](#main-features)
- [Technology Stack](#technology-stack)
- [Directory Structure](#directory-structure)
- [Setup Instructions](#setup-instructions)
- [Usage](#usage)
- [CLI Scripts](#cli-scripts)
- [Security Features](#security-features)
- [Database Schema](#database-schema)
- [Customization](#customization)
- [Troubleshooting](#troubleshooting)
- [Roadmap](#roadmap)
- [License](#license)
- [Support](#support)
- [Contributing](#contributing)

## Overview

**KidSnaps Growth Album** is a PHP-based web application designed to securely store and organize your family’s treasured memories. Upload and view photos or videos easily in a beautiful gallery layout.

## Quick Start

```bash
# 1. Clone the repository
git clone https://github.com/yourusername/KidSnaps-GrowthAlbum.git
cd KidSnaps-GrowthAlbum

# 2. Create a database configuration file
cp .env_db.example .env_db
nano .env_db  # Edit database credentials

# 3. Create database tables
mysql -u root -p personal_finance < sql/setup.sql

# 4. Set permissions for upload directories
chmod 755 uploads/ uploads/images/ uploads/videos/ uploads/thumbnails/

# 5. Start a local development server
php -S localhost:8000

# 6. Open in browser
# http://localhost:8000
```

You’re now ready to upload and view your media!

## Main Features

### Core Features
- **Media Uploads:** Upload photos (JPEG, PNG, GIF, HEIC) and videos (MP4, MOV, AVI)
- **Automatic HEIC Conversion:** Automatically converts Apple’s HEIC format to JPG
- **Gallery Display:** Beautiful grid-style gallery
- **Filtering:** Filter by type (photos/videos)
- **Search:** Search by title, description, or filename
- **Responsive Design:** Mobile-friendly with Bootstrap 5

### Media Processing
- **Image Rotation:** Rotate images by 90° increments
- **Automatic Thumbnails:** Auto-generate thumbnails for uploaded videos
- **EXIF Extraction:** Extracts photo metadata such as timestamp, camera info, GPS data
- **Video Metadata:** Extracts video information using GetID3

### Batch Processing Tools
- **Bulk Import:** Import multiple media files from directories
- **HEIC Conversion:** Batch convert existing HEIC images to JPG
- **Thumbnail Generator:** Create video thumbnails locally
- **EXIF Migration:** Apply EXIF metadata to existing media

### Security
- **File Validation:** Strict MIME-type validation
- **SQL Injection Prevention:** PDO prepared statements
- **XSS Protection:** HTML escaping
- **Directory Traversal Prevention:** File name sanitization

## Technology Stack

- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+ / MariaDB 10.3+
- **Frontend:**
  - Bootstrap 5.3.2
  - Bootstrap Icons
  - Vanilla JavaScript
- **Media Libraries:**
  - GetID3 (metadata extraction)
  - GD / ImageMagick (image processing)
  - FFmpeg (optional, thumbnail generation)
- **HEIC Conversion:**
  - ImageMagick
  - heic-to-jpg (CLI tool)
  - FFmpeg (fallback)
- **Architecture:** MVC-style structure

## Directory Structure

```text
KidSnaps-GrowthAlbum/
├── assets/
│   ├── css/style.css                     # Custom styles
│   └── js/script.js                      # JavaScript logic
├── config/database.php                   # DB connection
├── includes/                             # Shared components
│   ├── header.php / footer.php
│   ├── exif_helper.php / heic_converter.php
│   ├── video_metadata_helper.php / image_thumbnail_helper.php
│   └── getid3/                           # GetID3 library
├── sql/setup.sql                         # Database schema
├── uploads/                              # Media storage
│   ├── images/ / videos/ / thumbnails/
├── .env_db                               # Local DB config
├── .env_db.example                       # Sample config
├── .htaccess                             # Apache config
├── index.php                             # Main gallery
├── upload.php / delete.php / rotate.php  # Core actions
├── CLI scripts: bulk_import.php, migrate_exif.php, etc.
├── MIGRATION_GUIDE.md
├── README.md
└── LICENSE
```

## Setup Instructions

1. **Requirements**
   - PHP 7.4+, MySQL 5.7+ / MariaDB 10.3+
   - Apache or Nginx
   - PHP extensions: PDO, fileinfo, gd/imagemagick, exif, mbstring
   - Optional: ImageMagick, FFmpeg, heic-to-jpg

2. **Database Setup**
   ```bash
   mysql -u root -p
   USE personal_finance;
   SOURCE sql/setup.sql;
   ```

3. **Database Configuration**
   ```ini
   DB_HOST=localhost
   DB_NAME=personal_finance
   DB_USER=your_username
   DB_PASS=your_password
   ```

4. **Permissions**
   ```bash
   chmod 755 uploads/ uploads/images/ uploads/videos/
   ```

5. **PHP Configuration**
   ```ini
   upload_max_filesize = 50M
   post_max_size = 50M
   max_execution_time = 300
   memory_limit = 256M
   ```

6. **Access**
   Visit `http://localhost/KidSnaps-GrowthAlbum/` or your domain.

## Usage

### Uploading Media
1. Click “Upload Media”  
2. Choose a file (photo/video)  
3. Enter optional title and description  
4. Click “Upload”

### Viewing Media
- Click thumbnails in the gallery  
- Open in modal viewer  
- Videos display with controls automatically

### Filtering / Search
- Filter by type (“All”, “Photos only”, “Videos only”)  
- Search by title, description, or filename

### Deleting Media
- Click “Delete” → confirm

## CLI Scripts

Includes utilities for:
- Bulk importing media
- Local thumbnail generation
- Linking thumbnails
- Converting HEIC files
- Migrating EXIF data

(Usage examples retained from original README.)

## Security Features

- Strict MIME type and size checks (max 50MB)
- SQL injection prevention (PDO)
- XSS escaping
- Directory traversal protection
- PHP execution disabled in upload directories

### ⚠️ Basic Authentication Setup (Strongly Recommended)

This application currently does not have built-in user authentication. **If deploying to a public server, you MUST configure Basic Authentication.**

#### Apache Configuration

Add the following to `.htaccess` in the root directory:

```apache
# Basic Authentication
AuthType Basic
AuthName "KidSnaps Growth Album - Private Area"
AuthUserFile /path/to/.htpasswd
Require valid-user
```

Create `.htpasswd` file:

```bash
# Create password file with htpasswd command
htpasswd -c /path/to/.htpasswd username

# Add additional users (without -c option)
htpasswd /path/to/.htpasswd another_user
```

#### Nginx Configuration

Add the following to `nginx.conf` or server block:

```nginx
location / {
    auth_basic "KidSnaps Growth Album - Private Area";
    auth_basic_user_file /path/to/.htpasswd;

    try_files $uri $uri/ /index.php?$query_string;
}
```

Create `.htpasswd` file:

```bash
# Using openssl (recommended)
echo "username:$(openssl passwd -apr1)" > /path/to/.htpasswd

# Or using htpasswd command
htpasswd -c /path/to/.htpasswd username
```

#### Lolipop Rental Server Configuration

For Lolipop hosting, you can easily set this up via the admin panel:

1. Log in to Lolipop admin panel
2. Navigate to "Security" → "Access Restriction"
3. Select the target directory (KidSnaps-GrowthAlbum root)
4. Set username and password

Or manually create `.htaccess`:

```apache
AuthType Basic
AuthName "Private Album"
AuthUserFile /home/users/2/lolipop.jp-xxxx/.htpasswd
Require valid-user
```

**Important:** Use absolute paths for `.htpasswd`. Relative paths will not work.

## Database Schema

Tables include:
- **media_files** — stores uploaded media metadata
- **media_tags** — predefined tagging system
- **media_tag_relations** — tag associations

(Full schema preserved as in original.)

## Customization

- **Allowed File Types:** Edit `upload.php` `$allowedImageTypes` / `$allowedVideoTypes`
- **File Size Limit:** Modify `$maxFileSize`
- **Theme Colors:** Change CSS variables in `assets/css/style.css`

## Troubleshooting

- Check upload directory permissions  
- Verify PHP upload size settings  
- Review server error logs for issues

## Roadmap

### Implemented ✅

- [x] Photo & Video Uploads (JPEG, PNG, GIF, HEIC, MP4, MOV, AVI)
- [x] Gallery Display with Pagination
- [x] Search & Filtering
- [x] Multi-language Support (Japanese & English)
- [x] Dark Mode
- [x] Automatic HEIC Conversion
- [x] Image Rotation
- [x] EXIF Information Extraction (timestamp, location, camera info)
- [x] Video Metadata Extraction (GetID3)
- [x] Automatic Video Thumbnail Generation
- [x] Bulk Import Feature
- [x] Duplicate File Detection
- [x] Thumbnail Optimization & WebP Support
- [x] Lazy Loading
- [x] Browser Cache Optimization
- [x] Security Measures (SQL Injection, XSS Protection)
- [x] Photo Date Editing Feature

### Short-term Goals (v2.0) - 1-2 Months

- [ ] **User Authentication & Multi-user Support**
  - Login/Logout functionality
  - Per-user album management
  - Permission management (view-only, edit access, etc.)
  - **Currently addressed via Basic Authentication (recommended)**
- [ ] **Tagging System**
  - Multiple tags per media
  - Tag-based filtering
  - Tag cloud display
- [ ] **Enhanced Date Filtering**
  - Date range filtering
  - Year/Month view toggle
  - Calendar view

### Mid-term Goals (v3.0) - 3-6 Months

- [ ] **Slideshow Feature**
  - Auto-play
  - Transition effects
  - Fullscreen mode
- [ ] **Sharing Features**
  - External album sharing links
  - Password protection
  - Expiration dates
- [ ] **Favorites & Rating**
  - Star ratings for media
  - Favorite folders
  - Auto-selection of best shots
- [ ] **GPS Location Map Display**
  - Map view of photo locations
  - Location-based filtering

### Long-term Goals (v4.0+) - 6+ Months

- [ ] **AI-powered Auto-classification**
  - Face recognition & person tagging
  - Scene recognition
  - Similar photo grouping
- [ ] **Media Editing Tools**
  - Cropping, filter application
  - Brightness & contrast adjustment
  - Text & stamp overlays
- [ ] **Backup & Export**
  - Cloud storage integration (Dropbox, Google Drive)
  - ZIP album export
  - Automated backup scheduling
- [ ] **Comments & Memories**
  - Comments on media
  - Timeline-style memory display
  - Shared comments

### Technical Improvements (Ongoing)

- [ ] **Performance Optimization**
  - Database query optimization
  - CDN support
  - Advanced caching mechanisms
- [ ] **Enhanced Responsive Design**
  - Improved touch gesture support
  - Progressive Web App (PWA) support
- [ ] **Improved Test Coverage**
  - Unit tests
  - E2E tests
  - CI/CD pipeline
- [ ] **Security Enhancements**
  - CSRF protection
  - Session fixation prevention
  - Rate limiting

## License

Refer to the LICENSE file for details (MIT License).

## Support

For issues or questions, please use the GitHub Issues section.

## Contributing

Pull requests are welcome! For major changes, open an Issue first to discuss your proposal.

## Author

**KidSnaps Growth Album Development Team**

---

> ⚠️ **Note:** This application is intended for educational and personal use.  
> For production environments, implement additional security measures such as HTTPS, CSRF protection, and input validation.
