# KidSnaps Growth Album

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-blue.svg)

A web-based photo album application designed to capture and preserve your child's precious growth moments. Features include photo/video uploads, automatic EXIF extraction, location tracking, multi-language support (Japanese/English), dark mode, and more.

## ✨ Key Features

- 📸 **Photo & Video Upload** - Supports JPEG, PNG, GIF, HEIC, MP4, MOV, AVI
- 🗂️ **Album Management** - Organize media into albums, bulk import from ZIP files
- 🌍 **Automatic EXIF Extraction** - Captures shooting date/time, GPS location, camera info
- 🔍 **Duplicate Detection** - File hash-based duplicate checking to prevent storage waste
- 🌐 **Multi-language Support** - Japanese and English localization
- 🌙 **Dark Mode** - Eye-friendly dark theme
- 📱 **Responsive Design** - Optimized for mobile, tablet, and desktop
- 🎬 **Automatic Video Thumbnails** - ffmpeg-powered thumbnail generation
- 📄 **Pagination** - Gallery displays 12 items per page
- 🚀 **Performance Optimized** - Lazy loading, browser caching, progressive JPEG, WebP support

## 📁 Directory Structure

```
/
├── index.php                   # Main gallery page
├── albums.php                  # Album list page
├── album_detail.php            # Album detail view
├── album_upload.php            # ZIP bulk import page
├── upload.php                  # Upload handler
├── delete.php                  # Delete handler
├── rotate.php                  # Image rotation handler
├── install.php                 # Installation script
│
├── api/                        # API endpoints
│   ├── check_duplicate.php     # Duplicate check API
│   ├── refresh_exif.php        # EXIF refresh API
│   ├── update_metadata.php     # Metadata update API
│   ├── update_photo_date.php   # Photo date update API
│   ├── update_rotation.php     # Rotation update API
│   └── write_exif_to_file.php  # EXIF write-back API
│
├── assets/                     # Static resources
│   ├── css/                    # Stylesheets
│   │   └── style.css           # Main CSS
│   └── js/                     # JavaScript
│       ├── script.js           # Main script
│       ├── duplicate-checker.js # Client-side duplicate check
│       └── zip-upload.js       # ZIP upload handler
│
├── config/                     # Configuration
│   └── database.php            # Database connection config
│
├── docs/                       # Documentation
│   ├── README_en.md            # English README
│   ├── MIGRATION_GUIDE.md      # Migration guide
│   ├── LOLIPOP_SETUP.md        # Lolipop server setup guide
│   └── DUPLICATE_CHECK_SETUP.md # Duplicate check setup guide
│
├── includes/                   # Helper files
│   ├── header.php              # Header component
│   ├── footer.php              # Footer component
│   ├── exif_helper.php         # EXIF extraction
│   ├── heic_converter.php      # HEIC converter
│   ├── image_thumbnail_helper.php # Image thumbnail generator
│   ├── video_metadata_helper.php  # Video metadata extractor
│   └── getid3/                 # GetID3 library
│
├── lib/                        # Server-side processing
│   ├── chunk_upload.php        # Chunked upload handler
│   ├── finalize_upload.php     # Upload finalization
│   ├── debug_logs.php          # Debug log viewer
│   ├── zip_import.php          # ZIP extraction & import
│   ├── album_processor.php     # Album creation & management
│   └── zip_import_progress.php # Import progress API
│
├── logs/                       # Log files (.gitignore)
│   └── .gitkeep
│
├── migrations/                 # Database migrations
│   ├── add_file_hash_column.sql
│   └── ...
│
├── scripts/                    # CLI scripts
│   ├── bulk/                   # Bulk operations
│   │   ├── bulk_import.php     # Bulk import
│   │   ├── analyze_duplicates.php # Duplicate analysis
│   │   ├── remove_duplicates_v1_deprecated.php # Duplicate removal v1 (deprecated)
│   │   └── remove_duplicates_v2.php # Duplicate removal v2 (recommended)
│   ├── check/                  # Diagnostics
│   │   ├── check_db.php        # DB connection check
│   │   ├── check_schema.php    # Schema verification
│   │   ├── check_thumbnails.php # Thumbnail check
│   │   ├── check_latest_records.php # Latest records check
│   │   └── check_paths.php     # Path verification
│   │
│   ├── maintenance/            # Maintenance
│   │   ├── regenerate_thumbnails.php # Regenerate thumbnails
│   │   ├── update_file_hashes.php    # Update file hashes
│   │   ├── update_thumbnails.php     # Update thumbnails
│   │   ├── generate_thumbnails_local.php # Local thumbnail generation
│   │   ├── link_thumbnails.php       # Link thumbnails
│   │   ├── migrate_exif.php          # EXIF migration
│   │   └── convert_existing_heic.php # HEIC conversion
│   │
│   └── test/                   # Test scripts
│       ├── test_db_insert.php  # DB insert test
│       └── test_index.php      # Index test
│
├── sql/                        # SQL scripts
│   ├── setup.sql               # Database schema
│   ├── create_album_tables.sql # Album tables
│   └── ...
│
└── uploads/                    # Upload directory (.gitignore)
    ├── images/                 # Image files
    ├── videos/                 # Video files
    ├── thumbnails/             # Thumbnails
    └── temp/                   # Temporary files
```

## 🚀 Quick Start

### 1. Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- ffmpeg (required for video thumbnail generation)
- Web server (Apache / Nginx)

### 2. Installation

```bash
# Clone the repository
git clone https://github.com/nhashimoto-gm/KidSnaps-GrowthAlbum.git
cd KidSnaps-GrowthAlbum

# Create database configuration file
cp .env_db.example .env_db

# Edit .env_db with your database credentials
nano .env_db

# Access the installer in your browser
# http://your-domain.com/install.php
```

### 3. ffmpeg Setup (Recommended)

For video thumbnail generation, we recommend deploying ffmpeg locally:

```bash
# Download and extract ffmpeg
# https://ffmpeg.org/download.html

# Place in ./ffmpeg/ directory
mkdir -p ffmpeg
cp /path/to/ffmpeg ./ffmpeg/ffmpeg  # Linux/Mac
cp /path/to/ffmpeg.exe ./ffmpeg/ffmpeg.exe  # Windows
```

## 📖 Usage

### Basic Operations

1. **Upload Photos/Videos**
   - Click the "Upload" button on the top page
   - Select files (multiple selection supported)
   - Enter title and description (optional)
   - Click "Upload"

2. **Duplicate Check**
   - Automatic duplicate checking runs when selecting files
   - Warning displayed if duplicates are detected

3. **Search & Filter**
   - Use the search bar to find by title, description, or filename
   - Filter by "All", "Photos Only", or "Videos Only"

4. **Language Switch**
   - Use the "EN/JP" button in the header to toggle languages

5. **Dark Mode Toggle**
   - Use the moon/sun icon in the header to switch themes

### Album Management

1. **Create Album from ZIP**
   - Access `album_upload.php`
   - Select a ZIP file containing photos/videos
   - Enter album title and description (optional)
   - Click "Start Upload"
   - Automatic extraction, import, and album creation

2. **View Albums**
   - Access `albums.php` to see all albums
   - Click an album to view its contents
   - Navigate to individual media items

### CLI Scripts

#### Bulk Import

```bash
# Import all files from a directory
php scripts/bulk/bulk_import.php /path/to/photos
```

#### Duplicate Removal

**Recommended:** Use `remove_duplicates_v2.php` (supports multiple detection methods)

```bash
# Analyze duplicates
php scripts/bulk/analyze_duplicates.php

# Method 1: Remove by filename+size (dry-run)
php scripts/bulk/remove_duplicates_v2.php --method filename --dry-run

# Method 2: Remove by EXIF datetime+size (photos only, dry-run)
php scripts/bulk/remove_duplicates_v2.php --method exif --dry-run

# Method 3: Remove by file hash (most accurate, recommended, dry-run)
php scripts/bulk/remove_duplicates_v2.php --method hash --dry-run

# Actually remove (hash method)
php scripts/bulk/remove_duplicates_v2.php --method hash
```

**Note:** `remove_duplicates.php` (v1) is deprecated. Use `remove_duplicates_v2.php` instead.

#### Thumbnail Regeneration

```bash
# Only videos missing thumbnails
php scripts/maintenance/regenerate_thumbnails.php --missing

# All videos
php scripts/maintenance/regenerate_thumbnails.php --all

# Force regeneration
php scripts/maintenance/regenerate_thumbnails.php --all --force
```

#### Update File Hashes

```bash
# Calculate and update hashes for existing files
php scripts/maintenance/update_file_hashes.php
```

#### Thumbnail Optimization

```bash
# Optimize all thumbnails (progressive JPEG, size optimization)
php scripts/maintenance/optimize_thumbnails.php --all

# Also generate WebP versions (25-35% size reduction)
php scripts/maintenance/optimize_thumbnails.php --all --webp

# Dry-run (show what would be done without actually changing files)
php scripts/maintenance/optimize_thumbnails.php --dry-run
```

#### Database Verification

```bash
# Check database connection
php scripts/check/check_db.php

# Verify schema
php scripts/check/check_schema.php

# Check latest records
php scripts/check/check_latest_records.php
```

## 🔧 Configuration

### Database Settings

Configure in `.env_db`:

```
DB_HOST=localhost
DB_NAME=kidsnaps
DB_USER=your_username
DB_PASS=your_password
```

### Upload Limits

Configure in `php.ini` or `.user.ini`:

```ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
memory_limit = 256M
```

### Performance Optimization

The application includes built-in performance enhancements:

#### Automatically Applied Optimizations

1. **Lazy Loading**
   - Automatic `loading="lazy"` attribute on images/videos
   - Elements load only when scrolled into view
   - 60-80% improvement in initial load speed

2. **Browser Caching**
   - Images/Videos: 1-year cache with `immutable` flag
   - CSS/JavaScript: 1-month cache
   - 90%+ faster on repeat visits

3. **Progressive JPEG**
   - Thumbnails saved in progressively rendered format
   - Significantly improved perceived performance

#### Manual Optimizations

```bash
# Optimize existing thumbnails
php scripts/maintenance/optimize_thumbnails.php --all

# Generate WebP versions (25-35% smaller on supported browsers)
php scripts/maintenance/optimize_thumbnails.php --all --webp
```

#### Expected Performance Impact

| Optimization | First Load | Repeat Visits | Data Transfer |
|--------------|-----------|---------------|---------------|
| Lazy Loading | 60-80% reduction | - | 60-80% reduction |
| Thumbnail Optimization | 30-50% reduction | - | 40-60% reduction |
| Browser Caching | - | 90%+ reduction | 100% reduction |
| WebP Support | 25-35% reduction | 25-35% reduction | 25-35% reduction |

#### Additional Performance Improvements

For further optimization, refer to these documents:

- 📊 **[Performance Evaluation Report](PERFORMANCE_EVALUATION.md)** - Comprehensive analysis and recommendations
- 🚀 **[Quick Start Guide](docs/QUICK_START_PERFORMANCE.md)** - Basic improvements in 30 minutes
- 📖 **[Detailed Implementation Guide](docs/PERFORMANCE_IMPROVEMENT_GUIDE.md)** - Step-by-step instructions

**Recommended Additional Measures:**
- JavaScript/CSS minification (60-81% transfer reduction)
- Database composite indexes (50-70% query speed improvement)
- Full-text search indexes (70-90% search speed improvement)
- Complete WebP implementation (25-35% image size reduction)

These measures can improve **page load speed by 50-60%**.

## 🔒 Security

### Security Features
- `.htaccess` in upload directory prevents PHP execution
- Filenames hashed before storage
- SQL injection protection (PDO prepared statements)
- XSS protection (htmlspecialchars)
- Debug mode controlled by environment variables (auto-disabled in production)
- Debug log page access restriction (environment variable + password protection)

### ⚠️ Basic Authentication Setup (Strongly Recommended)

The application currently has no built-in user authentication. **If deploying to a public server, you must configure Basic Authentication.**

#### Apache Configuration

Add to `.htaccess` in the root directory:

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

Add to `nginx.conf` or server block:

```nginx
location / {
    auth_basic "KidSnaps Growth Album - Private Area";
    auth_basic_user_file /path/to/.htpasswd;

    try_files $uri $uri/ /index.php?$query_string;
}
```

Create `.htpasswd` file:

```bash
# Using openssl command (recommended)
echo "username:$(openssl passwd -apr1)" > /path/to/.htpasswd

# Or using htpasswd command
htpasswd -c /path/to/.htpasswd username
```

#### Lolipop Rental Server Setup

For Lolipop, you can configure via the admin panel:

1. Log in to Lolipop admin panel
2. Go to "Security" → "Access Restriction"
3. Specify target directory (KidSnaps-GrowthAlbum root)
4. Set username and password

Or manually create `.htaccess`:

```apache
AuthType Basic
AuthName "Private Album"
AuthUserFile /home/users/2/lolipop.jp-xxxx/.htpasswd
Require valid-user
```

**Important:** Use absolute paths for `.htpasswd`. Relative paths will not work.

### Environment Variable Configuration

To properly separate production and development environments:

#### Debug Mode Setting

```bash
# Development: Enable debug mode
export DEBUG_MODE=1

# Production: Don't set (disabled by default)
# Don't set DEBUG_MODE or set to 0
```

When debug mode is enabled:
- PHP errors are displayed on screen
- Debug log page (`lib/debug_logs.php`) becomes accessible

#### Debug Password Setting

```bash
# Add to .env_db file
DEBUG_PASSWORD=your_secure_password
```

Accessing debug log page:
```
http://your-domain.com/lib/debug_logs.php?pass=your_secure_password
```

**⚠️ Important:** Do not set `DEBUG_MODE` in production environments. Doing so creates security risks.

## 📝 License

MIT License - See [LICENSE](LICENSE) for details.

## 📋 Changelog

### Latest Refactoring (2025-01-10)

#### Security Improvements
- ✅ Debug mode now controlled by environment variable (`DEBUG_MODE`)
- ✅ Auto-disabled error display in production
- ✅ Debug log page (`lib/debug_logs.php`) now protected with environment variable + password

#### Code Quality Improvements
- ✅ Deprecated duplicate removal script v1 (v2 recommended)
- ✅ Unified error handling
- ✅ Code cleanup and documentation improvements

#### Development Environment Improvements
- ✅ Environment variable-based dev/prod separation
- ✅ More secure debug functionality

### Known Issues & Future Improvements

- [ ] CSRF protection implementation
- [ ] Session fixation countermeasures
- [ ] Integrated test suite
- [ ] Gradual migration to class-based design
- [ ] API rate limiting enhancement (Nominatim API)

## 🗺️ Roadmap

### Implemented Features ✅

- [x] Photo/video upload (JPEG, PNG, GIF, HEIC, MP4, MOV, AVI)
- [x] Album management & ZIP bulk import
- [x] Gallery display with pagination
- [x] Search & filtering
- [x] Multi-language support (Japanese/English)
- [x] Dark mode
- [x] HEIC automatic conversion
- [x] Image rotation
- [x] Automatic EXIF extraction (date/time, location, camera info)
- [x] Video metadata extraction (GetID3)
- [x] Automatic video thumbnail generation
- [x] Bulk import functionality
- [x] Duplicate file detection
- [x] Thumbnail optimization & WebP support
- [x] Lazy loading
- [x] Browser caching optimization
- [x] Security measures (SQL injection, XSS protection)
- [x] Photo date editing

### Short-term Goals (v2.0) - 1-2 Months

- [ ] **User Authentication & Multi-user Support**
  - Login/logout functionality
  - Per-user album management
  - Permission management (view-only, edit access, etc.)
  - **Currently: Basic Authentication recommended**
- [ ] **Tagging System**
  - Multiple tags per media item
  - Tag-based filtering
  - Tag cloud display
- [ ] **Enhanced Date Filtering**
  - Date range filtering
  - Year/month view switching
  - Calendar view

### Mid-term Goals (v3.0) - 3-6 Months

- [ ] **Slideshow Feature**
  - Auto-play functionality
  - Transition effects
  - Fullscreen mode
- [ ] **Sharing Features**
  - External album sharing links
  - Password protection
  - Expiration dates
- [ ] **Favorites & Ratings**
  - Star ratings for media
  - Favorites folder
  - Automatic best shot selection
- [ ] **GPS Location Map Display**
  - Map view of shooting locations
  - Location-based filtering

### Long-term Goals (v4.0+) - 6+ Months

- [ ] **AI-powered Auto-classification**
  - Face recognition for person tagging
  - Automatic scene recognition
  - Similar photo grouping
- [ ] **Media Editing**
  - Cropping, filters
  - Brightness/contrast adjustment
  - Text/stamp overlay
- [ ] **Backup & Export**
  - Cloud storage integration (Dropbox, Google Drive)
  - ZIP album export
  - Automatic backup scheduling
- [ ] **Comments & Memories**
  - Comments on media items
  - Timeline view of memories
  - Comment sharing

### Technical Improvements (Ongoing)

- [ ] **Performance Optimization**
  - Database query optimization
  - CDN support
  - Advanced caching mechanisms
- [ ] **Enhanced Responsive Design**
  - Improved touch gesture support
  - Progressive Web App (PWA) support
- [ ] **Improved Test Coverage**
  - Unit test additions
  - E2E test implementation
  - CI/CD pipeline setup
- [ ] **Security Enhancements**
  - CSRF protection implementation
  - Session fixation countermeasures
  - Rate limiting implementation

## 🤝 Contributing

Pull requests are welcome! For major changes, please open an issue first to discuss your proposed changes.

## 📧 Support

If you encounter issues, please report them on [GitHub Issues](https://github.com/nhashimoto-gm/KidSnaps-GrowthAlbum/issues).

## 📚 Related Documentation

- **[CLAUDE.md](./CLAUDE.md)** - Comprehensive AI Assistant Guide for development
- **[English README](./docs/README_en.md)** - Full English documentation
- **[Requirements Specification](./docs/REQUIREMENTS.md)** - Detailed requirements and specifications
- **[Performance Guide](./docs/PERFORMANCE_IMPROVEMENT_GUIDE.md)** - Optimization strategies
- **[Quick Start Performance](./docs/QUICK_START_PERFORMANCE.md)** - 30-minute performance boost guide
- **[Lolipop Setup](./docs/LOLIPOP_SETUP.md)** - Shared hosting deployment guide
- **[HEIC Conversion Workflow](./docs/HEIC_CONVERSION_WORKFLOW.md)** - HEIC image conversion guide
- **[HEIC Troubleshooting](./docs/HEIC_CONVERSION_TROUBLESHOOTING.md)** - HEIC conversion issues
- **[WebP Implementation](./docs/WEBP_IMPLEMENTATION.md)** - WebP format support guide
- **[Duplicate Check Setup](./docs/DUPLICATE_CHECK_SETUP.md)** - Duplicate detection configuration
- **[Album Feature Guide](./ALBUM_FEATURE_README.md)** - ZIP import and album management

## 🙏 Acknowledgments

- [Bootstrap 5](https://getbootstrap.com/) - UI Framework
- [EXIF.js](https://github.com/exif-js/exif-js) - EXIF extraction
- [fileeye/pel](https://github.com/pel/pel) - PHP EXIF Library (EXIF read/write)
- [heic2any](https://github.com/alexcorvi/heic2any) - HEIC conversion
- [SparkMD5](https://github.com/satazor/js-spark-md5) - File hash calculation
- [GetID3](https://www.getid3.org/) - Media metadata extraction
- [ffmpeg](https://ffmpeg.org/) - Video processing

---

Made with ❤️ for capturing precious moments
