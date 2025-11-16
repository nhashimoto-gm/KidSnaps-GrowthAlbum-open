# CLAUDE.md - AI Assistant Guide for KidSnaps Growth Album

**Last Updated:** 2025-01-15
**Project:** KidSnaps Growth Album
**Version:** 1.0.0
**Purpose:** Comprehensive guide for AI assistants working with this codebase

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Technology Stack](#technology-stack)
3. [Directory Structure](#directory-structure)
4. [Database Schema](#database-schema)
5. [Development Workflows](#development-workflows)
6. [Coding Conventions](#coding-conventions)
7. [API Patterns](#api-patterns)
8. [Frontend Architecture](#frontend-architecture)
9. [Security Guidelines](#security-guidelines)
10. [Common Tasks](#common-tasks)
11. [Testing & Debugging](#testing--debugging)
12. [Deployment](#deployment)
13. [Recent Development Focus](#recent-development-focus)
14. [Important Notes](#important-notes)

---

## Project Overview

**KidSnaps Growth Album** is a PHP-based web application for managing family photo and video albums. It's designed to capture children's growth moments with rich metadata extraction (EXIF, GPS, timestamps) and modern web features.

### Key Features
- Photo/video uploads with EXIF extraction
- HEIC image support (client & server-side conversion)
- ZIP bulk import with Google Photos integration
- Duplicate detection (file hash-based)
- Multi-language support (Japanese/English)
- Dark mode theme
- Album management
- Video thumbnail generation
- Performance optimizations (lazy loading, caching, WebP)

### Target Deployment
- **Primary:** Shared hosting environments (LiteSpeed, Apache)
- **Database:** MySQL 5.7+ / MariaDB
- **PHP:** 7.4+ (8.0+ recommended for optimal performance)
- **No framework** - Vanilla PHP/JavaScript with Bootstrap 5

---

## Technology Stack

### Backend
| Technology | Version | Purpose |
|------------|---------|---------|
| PHP | 7.4+ | Server-side language |
| MySQL | 5.7+ | Database |
| PDO | - | Database access layer |
| Composer | - | PHP dependency management |
| fileeye/pel | 0.9.12 | EXIF read/write library |
| GetID3 | Bundled | Video metadata extraction |

### Frontend
| Technology | Version | Purpose |
|------------|---------|---------|
| Bootstrap | 5.3.2 | UI framework |
| Bootstrap Icons | 1.11.1 | Icon library |
| Vanilla JavaScript | ES6+ | Application logic (2945 lines) |
| exif-js | - | Client-side EXIF reading |
| piexifjs | - | Client-side EXIF manipulation |
| heic2any | - | Client-side HEIC conversion |
| SparkMD5 | - | File hashing |

### Media Processing
| Tool | Purpose |
|------|---------|
| GD / Imagick | Image manipulation |
| FFmpeg | Video thumbnails, HEIC conversion |
| libheif (optional) | HEIC conversion fallback |

### Build Tools
```json
{
  "terser": "JavaScript minification",
  "csso-cli": "CSS minification"
}
```

---

## Directory Structure

```
/
├── Root PHP Pages (User-facing)
│   ├── index.php              # Main gallery (pagination, search, filtering)
│   ├── albums.php             # Album listing
│   ├── album_detail.php       # Individual album view
│   ├── album_upload.php       # ZIP bulk import interface
│   ├── delete.php             # Media deletion handler
│   ├── rotate.php             # Image rotation handler
│   ├── install.php            # Installation wizard
│   └── toggle_admin_mode.php  # Admin mode toggle
│
├── api/                       # RESTful API endpoints
│   ├── check_duplicate.php    # POST: {hash, filename, filesize} → {isDuplicate, existing[]}
│   ├── refresh_exif.php       # Admin-only: Re-extract EXIF from files
│   ├── update_metadata.php    # POST: Update title/description
│   ├── update_photo_date.php  # POST: Edit photo date + EXIF write-back
│   ├── update_rotation.php    # POST: Store rotation angle (0/90/180/270)
│   └── write_exif_to_file.php # POST: Write EXIF to physical file
│
├── config/
│   ├── database.php           # PDO connection (reads .env_db)
│   └── admin.php              # Admin password verification
│
├── lib/                       # Server-side processing
│   ├── chunk_upload.php       # Receives file chunks (10MB default)
│   ├── finalize_upload.php    # Processes complete upload, DB insertion
│   ├── zip_import.php         # ZIP extraction & media import
│   ├── album_processor.php    # Album CRUD (OOP class)
│   ├── zip_preview.php        # ZIP content preview
│   ├── zip_import_progress.php # AJAX: Returns import status
│   └── debug_logs.php         # Protected debug log viewer
│
├── includes/                  # Helper libraries
│   ├── header.php/footer.php  # Common HTML components
│   ├── exif_helper.php        # EXIF extraction functions
│   ├── exif_writer.php        # EXIF writing (uses fileeye/pel)
│   ├── heic_converter.php     # HEIC→JPEG conversion (multi-method fallback)
│   ├── image_thumbnail_helper.php # GD/Imagick thumbnail generation
│   ├── video_metadata_helper.php  # GetID3 wrapper
│   ├── google_photos_metadata_helper.php # Google Takeout JSON parsing
│   └── getid3/                # GetID3 library (media metadata)
│
├── assets/
│   ├── css/
│   │   ├── style.css (921 lines)      # Main stylesheet
│   │   └── style.min.css              # Minified (production)
│   └── js/
│       ├── script.js (2945 lines)     # Main app logic
│       ├── script.min.js              # Minified (production)
│       ├── duplicate-checker.js       # SparkMD5-based hash calculation
│       ├── zip-upload.js              # ZIP upload UI & progress
│       └── refresh-exif.js            # EXIF refresh UI
│
├── scripts/                   # CLI utilities (run via `php scripts/...`)
│   ├── bulk/
│   │   ├── bulk_import.php           # Import directory of media
│   │   ├── analyze_duplicates.php    # Analyze duplicate files
│   │   └── remove_duplicates_v2.php  # Remove duplicates (--method hash|exif|filename)
│   ├── maintenance/
│   │   ├── regenerate_thumbnails.php # Video thumbnail regeneration
│   │   ├── update_file_hashes.php    # Calculate MD5 for existing files
│   │   ├── optimize_thumbnails.php   # Progressive JPEG + WebP conversion
│   │   ├── convert_existing_heic.php # Batch HEIC→JPEG conversion (server-side)
│   │   ├── convert_heic_python.py    # HEIC→JPEG/WebP (Windows/Python, offline)
│   │   ├── convert_heic_windows.ps1  # HEIC→JPEG/WebP (Windows/FFmpeg, offline)
│   │   ├── apply_converted_heic.php  # Apply offline-converted HEIC to DB
│   │   ├── fix_csv_paths.php         # Fix CSV path prefixes for HEIC conversion
│   │   └── check_heic_support.php    # HEIC support diagnostics
│   └── check/
│       ├── check_db.php              # Database connectivity test
│       ├── check_schema.php          # Schema validation
│       └── check_thumbnails.php      # Thumbnail integrity check
│
├── sql/                       # Database schemas & migrations
│   ├── setup.sql              # Main media_files table
│   ├── create_album_tables.sql # Album feature tables
│   ├── add_exif_data.sql      # EXIF columns migration
│   ├── add_file_hash.sql      # Duplicate detection column
│   ├── add_rotation.sql       # Rotation angle column
│   ├── add_thumbnail_column.sql # Thumbnail path column
│   ├── add_qt_metadata.sql    # QuickTime metadata columns
│   ├── add_google_photos_metadata.sql # Google Photos people data
│   ├── add_webp_thumbnail.sql # WebP thumbnail support
│   └── add_fulltext_index.sql # Search optimization
│
├── docs/                      # Documentation
│   ├── README.md / README_en.md
│   ├── PERFORMANCE_EVALUATION.md
│   ├── PERFORMANCE_IMPROVEMENT_GUIDE.md
│   ├── LOLIPOP_SETUP.md       # Shared hosting deployment
│   ├── DUPLICATE_CHECK_SETUP.md
│   ├── WEBP_IMPLEMENTATION.md
│   ├── HEIC_CONVERSION_WORKFLOW.md        # HEIC offline conversion guide
│   └── HEIC_CONVERSION_TROUBLESHOOTING.md # HEIC conversion troubleshooting
│
├── ffmpeg/                    # Local FFmpeg binary (optional)
│   └── ffmpeg                 # Linux/macOS binary
│
├── uploads/                   # User-generated content (.gitignored)
│   ├── images/                # Original images
│   ├── videos/                # Original videos
│   ├── thumbnails/            # Generated thumbnails
│   └── temp/                  # Temporary files (chunks, extractions)
│
└── Configuration files
    ├── .htaccess              # Apache: Security, caching, MIME types
    ├── .user.ini              # PHP settings (LiteSpeed-compatible)
    ├── .env_db                # Database credentials (gitignored)
    ├── .env_db.example        # Template for .env_db
    ├── composer.json          # PHP dependencies
    └── package.json           # Build scripts
```

---

## Database Schema

### Core Tables

#### `media_files` (Main table)
```sql
CREATE TABLE media_files (
    id INT PRIMARY KEY AUTO_INCREMENT,

    -- File Info
    filename VARCHAR(255),           -- Original filename
    stored_filename VARCHAR(255),    -- Hashed filename on disk
    file_path VARCHAR(500),          -- Full path to file
    file_type ENUM('image', 'video'),
    mime_type VARCHAR(100),
    file_size BIGINT,
    file_hash VARCHAR(32),           -- MD5 hash for duplicate detection

    -- Display
    thumbnail_path VARCHAR(500),
    rotation INT DEFAULT 0,          -- 0, 90, 180, 270
    title VARCHAR(255),
    description TEXT,

    -- EXIF Data
    exif_datetime DATETIME,          -- Photo taken date/time
    exif_latitude DECIMAL(10,8),
    exif_longitude DECIMAL(11,8),
    exif_location_name VARCHAR(255), -- Reverse geocoded location
    exif_camera_make VARCHAR(100),
    exif_camera_model VARCHAR(100),
    exif_orientation INT,            -- EXIF orientation tag

    -- Google Photos Integration
    google_photos_people JSON,       -- People tags from Google Takeout
    has_google_photos_metadata BOOLEAN DEFAULT FALSE,

    -- Timestamps
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_file_type (file_type),
    INDEX idx_upload_date (upload_date),
    INDEX idx_exif_datetime (exif_datetime),
    INDEX idx_file_hash (file_hash),
    FULLTEXT INDEX idx_fulltext_search (title, description, filename)
);
```

#### `albums` (Album management)
```sql
CREATE TABLE albums (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    cover_media_id INT,              -- FK to media_files
    media_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (cover_media_id) REFERENCES media_files(id) ON DELETE SET NULL
);
```

#### `album_media_relations` (Many-to-many)
```sql
CREATE TABLE album_media_relations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    album_id INT NOT NULL,
    media_id INT NOT NULL,
    display_order INT DEFAULT 0,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_album_media (album_id, media_id),
    FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE,
    FOREIGN KEY (media_id) REFERENCES media_files(id) ON DELETE CASCADE
);
```

#### `zip_import_history` (Import tracking)
```sql
CREATE TABLE zip_import_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    album_id INT,
    zip_filename VARCHAR(255),
    zip_size BIGINT,
    total_files INT,
    imported_files INT DEFAULT 0,
    failed_files INT DEFAULT 0,
    status ENUM('processing', 'completed', 'failed', 'cancelled'),
    error_message TEXT,
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,

    FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE SET NULL
);
```

### Future Tables (Defined but not actively used)
- `media_tags` - Tag definitions
- `media_tag_relations` - Media-to-tag relationships

---

## Development Workflows

### 1. Adding a New Feature

**Example: Adding a rating system**

```bash
# 1. Create database migration
cat > sql/add_rating_column.sql << 'EOF'
ALTER TABLE media_files ADD COLUMN rating INT DEFAULT 0;
ALTER TABLE media_files ADD INDEX idx_rating (rating);
EOF

# 2. Run migration
mysql -u user -p kidsnaps < sql/add_rating_column.sql

# 3. Create API endpoint
# Create api/update_rating.php with PDO prepared statements

# 4. Update frontend
# Add rating UI to assets/js/script.js
# Update translations object for i18n

# 5. Test
php scripts/check/check_schema.php

# 6. Minify assets (if JS/CSS changed)
npm run minify:all

# 7. Commit
git add .
git commit -m "Add rating system for media files"
```

### 2. Modifying File Upload Logic

**Critical Files:**
1. `assets/js/duplicate-checker.js` - Client-side hash calculation
2. `lib/chunk_upload.php` - Receives chunks, stores in session
3. `lib/finalize_upload.php` - Assembles chunks, extracts metadata, inserts DB
4. `includes/exif_helper.php` - EXIF extraction
5. `includes/video_metadata_helper.php` - Video metadata

**Workflow:**
```bash
# 1. Test current upload
curl -X POST http://localhost/lib/chunk_upload.php \
  -F "chunk=@test.jpg" \
  -F "chunk_index=0" \
  -F "total_chunks=1"

# 2. Make changes to upload logic

# 3. Test with various file types
# - HEIC images
# - Large videos (>100MB)
# - Google Photos ZIP with JSON metadata

# 4. Check duplicate detection still works
# Upload same file twice, verify warning appears

# 5. Verify database insertion
php scripts/check/check_latest_records.php
```

### 3. Database Schema Changes

**ALWAYS create migration files:**

```bash
# 1. Create migration SQL file
cat > sql/add_new_column.sql << 'EOF'
-- Migration: Add favorites feature
-- Date: 2025-01-15

ALTER TABLE media_files ADD COLUMN is_favorite BOOLEAN DEFAULT FALSE;
ALTER TABLE media_files ADD INDEX idx_is_favorite (is_favorite);
EOF

# 2. Test on development DB
mysql -u dev -p kidsnaps_dev < sql/add_new_column.sql

# 3. Update schema documentation
# Add column to CLAUDE.md and README.md

# 4. Update check script if needed
# Modify scripts/check/check_schema.php
```

### 4. Adding Translation Keys

**All UI text must be translated (EN + JP):**

```javascript
// In assets/js/script.js

const translations = {
  en: {
    // ... existing keys
    "new_feature": "New Feature",
    "new_feature_description": "This is a new feature"
  },
  ja: {
    // ... existing keys
    "new_feature": "新機能",
    "new_feature_description": "これは新機能です"
  }
};
```

**In HTML:**
```html
<button data-i18n="new_feature">New Feature</button>
<p data-i18n="new_feature_description">This is a new feature</p>
```

### 5. Performance Optimization

**After CSS/JS changes:**
```bash
# Minify assets
npm run minify:all

# Update version in header.php to bust cache
# Change: style.css?v=1.0.0 → style.css?v=1.0.1

# Test page load speed
curl -w "@curl-format.txt" -o /dev/null -s http://localhost/
```

**After thumbnail changes:**
```bash
# Regenerate all thumbnails
php scripts/maintenance/regenerate_thumbnails.php --all --force

# Optimize thumbnails
php scripts/maintenance/optimize_thumbnails.php --all --webp

# Verify file sizes reduced
du -sh uploads/thumbnails/
```

---

## Coding Conventions

### PHP

#### File Structure
```php
<?php
// 1. Error reporting (environment-based)
if (getenv('DEBUG_MODE') === '1') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// 2. Session start (if needed)
session_start();

// 3. Includes
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/exif_helper.php';

// 4. Constants
define('UPLOAD_DIR', __DIR__ . '/uploads/images/');

// 5. Main logic
try {
    $pdo = getDbConnection();
    // ... code
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
```

#### Database Access
```php
// ALWAYS use prepared statements
$stmt = $pdo->prepare("SELECT * FROM media_files WHERE id = :id");
$stmt->execute(['id' => $mediaId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

// NEVER concatenate user input
// ❌ BAD: "SELECT * FROM media_files WHERE id = " . $_POST['id']
// ✅ GOOD: Use prepared statements
```

#### Error Handling
```php
try {
    // Risky operation
    $result = processUpload($file);
} catch (Exception $e) {
    // Log server-side
    error_log("Upload failed: " . $e->getMessage());

    // Return user-friendly message
    http_response_code(500);
    echo json_encode([
        'error' => 'Upload failed. Please try again.',
        'debug' => getenv('DEBUG_MODE') === '1' ? $e->getMessage() : null
    ]);
    exit;
}
```

#### File Handling
```php
// Always validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/heic', 'video/mp4'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    throw new Exception("Invalid file type: $mimeType");
}

// Sanitize filenames
$safeFilename = basename($filename);  // Prevent path traversal
$hashedFilename = md5($safeFilename . time()) . '.' . $extension;
```

### JavaScript

#### Function Structure
```javascript
/**
 * Upload a file with chunking support
 * @param {File} file - The file to upload
 * @param {Function} progressCallback - Progress callback (percentage)
 * @returns {Promise<Object>} Upload result
 */
async function uploadFile(file, progressCallback) {
    try {
        // Validation
        if (!file) throw new Error('No file provided');

        // Calculate hash
        const hash = await calculateFileHash(file);

        // Check duplicates
        const isDuplicate = await checkDuplicate(hash, file.name, file.size);

        if (isDuplicate) {
            return {success: false, reason: 'duplicate'};
        }

        // Chunk and upload
        return await uploadInChunks(file, hash, progressCallback);

    } catch (error) {
        console.error('Upload failed:', error);
        showError(translate('upload_failed', currentLang));
        throw error;
    }
}
```

#### AJAX Pattern
```javascript
// Standard fetch pattern
async function apiCall(endpoint, data) {
    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const result = await response.json();
        return result;

    } catch (error) {
        console.error(`API call to ${endpoint} failed:`, error);
        throw error;
    }
}
```

#### Event Handling
```javascript
// Use event delegation for dynamic content
document.addEventListener('click', function(e) {
    if (e.target.matches('.delete-button')) {
        e.preventDefault();
        const mediaId = e.target.dataset.mediaId;
        deleteMedia(mediaId);
    }
});

// Bootstrap modal events
document.getElementById('uploadModal').addEventListener('show.bs.modal', function() {
    resetUploadForm();
});
```

### CSS

#### Custom Properties (Theming)
```css
:root {
    --bg-primary: #ffffff;
    --text-primary: #212529;
    --accent-color: #007bff;
}

:root[data-theme="dark"] {
    --bg-primary: #1a1a1a;
    --text-primary: #e9ecef;
    --accent-color: #4a9eff;
}

body {
    background-color: var(--bg-primary);
    color: var(--text-primary);
}
```

#### Responsive Design
```css
/* Mobile-first approach */
.gallery-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

@media (min-width: 768px) {
    .gallery-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 1200px) {
    .gallery-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
```

---

## API Patterns

### Request/Response Format

All APIs use **JSON for POST requests** and **JSON responses**.

#### Standard Request
```javascript
POST /api/update_metadata.php
Content-Type: application/json

{
    "media_id": 123,
    "title": "New Title",
    "description": "New description"
}
```

#### Standard Success Response
```json
{
    "success": true,
    "message": "Metadata updated successfully",
    "data": {
        "id": 123,
        "title": "New Title"
    }
}
```

#### Standard Error Response
```json
{
    "success": false,
    "error": "Media not found",
    "debug": "No media_files record with id=123" // Only in DEBUG_MODE
}
```

### Common API Endpoints

#### 1. Check Duplicate
```javascript
POST /api/check_duplicate.php
{
    "hash": "d8e8fca2dc0f896fd7cb4cb0031ba249",
    "filename": "photo.jpg",
    "filesize": 1234567
}

Response:
{
    "isDuplicate": true,
    "existing": [
        {
            "id": 45,
            "filename": "photo.jpg",
            "upload_date": "2025-01-10 15:30:00"
        }
    ],
    "count": 1
}
```

#### 2. Update Photo Date
```javascript
POST /api/update_photo_date.php
{
    "media_id": 123,
    "new_date": "2024-12-25 10:30:00"
}

Response:
{
    "success": true,
    "message": "Photo date updated successfully",
    "exif_written": true  // If EXIF write-back succeeded
}
```

#### 3. Refresh EXIF (Admin only)
```javascript
POST /api/refresh_exif.php
{
    "media_id": 123
}

Response:
{
    "success": true,
    "extracted_data": {
        "exif_datetime": "2024-12-25 10:30:00",
        "exif_latitude": 35.6762,
        "exif_longitude": 139.6503,
        "exif_camera_make": "Apple",
        "exif_camera_model": "iPhone 12 Pro"
    }
}
```

### Authentication Pattern

**Admin Mode:**
- Session-based: `$_SESSION['admin_mode'] === true`
- Toggle via `toggle_admin_mode.php` (password required)
- Check in API:
```php
session_start();
if (!isset($_SESSION['admin_mode']) || $_SESSION['admin_mode'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Admin mode required']);
    exit;
}
```

---

## Frontend Architecture

### JavaScript Modules (script.js)

The main `script.js` (2945 lines) is organized into functional sections:

#### 1. Translation & Localization
```javascript
const translations = { en: {...}, ja: {...} };
let currentLang = localStorage.getItem('language') || 'ja';

function translate(key, lang) {
    return translations[lang || currentLang][key] || key;
}

function updateLanguage(lang) {
    currentLang = lang;
    localStorage.setItem('language', lang);
    document.querySelectorAll('[data-i18n]').forEach(el => {
        el.textContent = translate(el.dataset.i18n);
    });
}
```

#### 2. Theme Management
```javascript
function toggleTheme() {
    const currentTheme = localStorage.getItem('theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
}
```

#### 3. Upload Flow
```javascript
// 1. File selection
fileInput.addEventListener('change', async (e) => {
    const files = Array.from(e.target.files);

    // 2. Calculate hashes
    for (const file of files) {
        const hash = await calculateFileHash(file);

        // 3. Check duplicates
        const isDupe = await checkDuplicate(hash, file.name, file.size);
        if (isDupe) {
            showDuplicateWarning(file.name);
        }
    }
});

// 4. Submit upload
uploadForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    for (const file of selectedFiles) {
        await uploadFileInChunks(file);
    }
});
```

#### 4. Modal Management
```javascript
// Bootstrap modal events
const uploadModal = new bootstrap.Modal(document.getElementById('uploadModal'));

document.getElementById('uploadBtn').addEventListener('click', () => {
    uploadModal.show();
});

uploadModal._element.addEventListener('hidden.bs.modal', () => {
    resetUploadForm();
});
```

### CSS Architecture

#### Theme Variables
```css
:root {
    /* Light theme (default) */
    --bg-primary: #ffffff;
    --bg-secondary: #f8f9fa;
    --text-primary: #212529;
    --text-secondary: #6c757d;
    --border-color: #dee2e6;
}

:root[data-theme="dark"] {
    --bg-primary: #1a1a1a;
    --bg-secondary: #2d2d2d;
    --text-primary: #e9ecef;
    --text-secondary: #adb5bd;
    --border-color: #495057;
}
```

#### Component Styling
- **Gallery Grid**: CSS Grid with responsive columns
- **Modal Overlays**: Bootstrap 5 modal system
- **Rotation Controls**: Absolute positioned overlay on hover
- **Lazy Loading**: `loading="lazy"` attribute (no JS needed)

---

## Security Guidelines

### Critical Security Rules

1. **Database Access**
   ```php
   // ✅ ALWAYS use prepared statements
   $stmt = $pdo->prepare("SELECT * FROM media_files WHERE id = ?");
   $stmt->execute([$id]);

   // ❌ NEVER concatenate user input
   $query = "SELECT * FROM media_files WHERE id = " . $_GET['id'];  // SQL INJECTION!
   ```

2. **Output Encoding**
   ```php
   // ✅ Escape all user-generated content
   echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

   // ❌ Never echo raw user input
   echo $_POST['title'];  // XSS VULNERABILITY!
   ```

3. **File Upload Validation**
   ```php
   // ✅ Validate MIME type (not just extension)
   $finfo = finfo_open(FILEINFO_MIME_TYPE);
   $mimeType = finfo_file($finfo, $filePath);

   if (!in_array($mimeType, $allowedTypes)) {
       throw new Exception("Invalid file type");
   }

   // ✅ Sanitize filename
   $safeFilename = basename($filename);  // Prevent ../../../etc/passwd

   // ✅ Hash filename before storage
   $storedName = md5($safeFilename . time()) . '.' . $ext;
   ```

4. **Path Traversal Prevention**
   ```php
   // ✅ Always use basename() for user-supplied paths
   $filename = basename($_POST['filename']);

   // ✅ Validate paths are within expected directory
   $fullPath = realpath($uploadDir . '/' . $filename);
   if (strpos($fullPath, $uploadDir) !== 0) {
       throw new Exception("Invalid path");
   }
   ```

5. **Environment-Based Debug Mode**
   ```php
   // ✅ NEVER enable debug mode in production
   if (getenv('DEBUG_MODE') === '1') {
       // Development only
       error_reporting(E_ALL);
       ini_set('display_errors', 1);
   } else {
       // Production
       error_reporting(0);
       ini_set('display_errors', 0);
   }
   ```

6. **Admin Authentication**
   ```php
   // Current: Basic password check
   session_start();

   if ($_POST['password'] === getenv('ADMIN_PASSWORD')) {
       $_SESSION['admin_mode'] = true;
   }

   // ⚠️ Note: No bcrypt yet. On roadmap for v2.0
   ```

### .htaccess Security

```apache
# Prevent PHP execution in uploads
<FilesMatch "\.(php|phtml|php3|php4|php5|phps)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>

# Disable directory listing
Options -Indexes

# Block access to sensitive files
<FilesMatch "\.(sql|log|bak|env|ini|md)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Security headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
```

### Recommended Security Additions

**For Production Deployment:**
1. Enable Basic Authentication (Apache/Nginx)
2. Use HTTPS (Let's Encrypt)
3. Set `DEBUG_MODE=0` or unset
4. Use strong `ADMIN_PASSWORD`
5. Restrict `lib/debug_logs.php` access
6. Regular security updates (Composer, dependencies)

---

## Common Tasks

### 1. Add a New Media Type

**Example: Adding WebP support**

```php
// 1. Update allowed types in includes/exif_helper.php
$allowedImageTypes = [
    'image/jpeg', 'image/png', 'image/gif', 'image/heic',
    'image/webp'  // Add this
];

// 2. Update duplicate-checker.js allowed types
const ALLOWED_TYPES = [
    'image/jpeg', 'image/png', 'image/gif', 'image/heic',
    'image/webp'
];

// 3. Update thumbnail generation in includes/image_thumbnail_helper.php
// WebP already supported by Imagick/GD

// 4. Test upload
// Upload a .webp file, verify thumbnail generation
```

### 2. Modify Pagination

**Change from 12 to 24 items per page:**

```php
// In index.php
$itemsPerPage = 24;  // Changed from 12

// Consider performance impact:
// - More database rows fetched
// - More thumbnails loaded (mitigated by lazy loading)
// - Larger page size

// No JavaScript changes needed (dynamically generated)
```

### 3. Add a New EXIF Field

**Example: Adding focal length**

```sql
-- 1. Migration
ALTER TABLE media_files ADD COLUMN exif_focal_length DECIMAL(6,2);
```

```php
// 2. Update includes/exif_helper.php
function extractExifData($filePath) {
    // ... existing code

    if (isset($exif['FocalLength'])) {
        $data['exif_focal_length'] = evaluateRational($exif['FocalLength']);
    }

    return $data;
}
```

```php
// 3. Update lib/finalize_upload.php
$stmt = $pdo->prepare("
    INSERT INTO media_files (..., exif_focal_length)
    VALUES (..., :exif_focal_length)
");
$stmt->execute([
    // ... existing bindings
    'exif_focal_length' => $exifData['exif_focal_length'] ?? null
]);
```

```javascript
// 4. Update frontend display (script.js)
const focalLength = mediaData.exif_focal_length;
if (focalLength) {
    modalBody.innerHTML += `<p>Focal Length: ${focalLength}mm</p>`;
}
```

### 4. Debugging Upload Issues

```bash
# 1. Check upload directory permissions
ls -la uploads/
# Should be 755 or 775, owned by web server user

# 2. Check PHP settings
php -i | grep -E '(upload_max_filesize|post_max_size|max_execution_time|memory_limit)'

# 3. Enable debug mode
echo "DEBUG_MODE=1" >> .env_db

# 4. Check debug logs
tail -f logs/upload_debug.log

# 5. Test database insert
php scripts/test/test_db_insert.php

# 6. Verify FFmpeg availability
php scripts/maintenance/check_heic_support.php

# 7. Check recent uploads
php scripts/check/check_latest_records.php
```

### 5. Regenerate All Thumbnails

```bash
# Videos only (missing thumbnails)
php scripts/maintenance/regenerate_thumbnails.php --missing

# All videos (force regeneration)
php scripts/maintenance/regenerate_thumbnails.php --all --force

# Optimize all thumbnails
php scripts/maintenance/optimize_thumbnails.php --all

# Generate WebP versions
php scripts/maintenance/optimize_thumbnails.php --all --webp

# Expected impact:
# - Progressive JPEG: 30-50% size reduction
# - WebP: Additional 25-35% reduction
# - Disk space: 40-60% savings overall
```

### 6. Handle Duplicates

```bash
# 1. Analyze duplicates (no changes)
php scripts/bulk/analyze_duplicates.php

# Output shows:
# - Duplicate count by method (hash, EXIF, filename)
# - Potential disk space savings

# 2. Preview removal (dry-run)
php scripts/bulk/remove_duplicates_v2.php --method hash --dry-run

# 3. Actually remove (recommended: hash method)
php scripts/bulk/remove_duplicates_v2.php --method hash

# Methods:
# - hash: Most accurate (MD5 file hash)
# - exif: EXIF datetime + size (photos only)
# - filename: Filename + size (least accurate)
```

### 7. Bulk Import from Directory

```bash
# Import all files from a directory
php scripts/bulk/bulk_import.php /path/to/photos

# Features:
# - Recursive directory traversal
# - EXIF extraction
# - Duplicate detection
# - Thumbnail generation
# - Progress reporting

# After import, check results:
php scripts/check/check_latest_records.php
```

---

## Testing & Debugging

### Debug Mode

**Enable debug mode (development only):**

```bash
# Add to .env_db
DEBUG_MODE=1
DEBUG_PASSWORD=your_secure_password
```

**Access debug logs:**
```
http://localhost/lib/debug_logs.php?pass=your_secure_password
```

**What debug mode enables:**
- PHP error display
- Detailed error messages in API responses
- Access to debug log viewer
- Upload process logging

**⚠️ NEVER enable in production!**

### Testing Upload Flow

```bash
# 1. Test single image upload
curl -X POST http://localhost/lib/finalize_upload.php \
  -F "file=@test.jpg" \
  -F "title=Test Image"

# 2. Test HEIC conversion
curl -X POST http://localhost/lib/finalize_upload.php \
  -F "file=@test.heic"

# 3. Test video thumbnail generation
curl -X POST http://localhost/lib/finalize_upload.php \
  -F "file=@test.mp4"

# 4. Test duplicate detection
# Upload same file twice, should see duplicate warning

# 5. Check database
mysql -u user -p kidsnaps -e "SELECT * FROM media_files ORDER BY id DESC LIMIT 5;"
```

### Database Verification

```bash
# Check connection
php scripts/check/check_db.php

# Verify schema
php scripts/check/check_schema.php

# Check latest records
php scripts/check/check_latest_records.php

# Check thumbnails
php scripts/check/check_thumbnails.php
```

### Frontend Debugging

```javascript
// Enable verbose logging in script.js
const DEBUG = true;

function debugLog(...args) {
    if (DEBUG) console.log('[DEBUG]', ...args);
}

// Use in code
debugLog('Upload started for file:', file.name);
```

---

## Deployment

### Requirements Checklist

- [ ] PHP 7.4+ (8.0+ recommended)
- [ ] MySQL 5.7+ or MariaDB 10.2+
- [ ] Apache or Nginx web server
- [ ] FFmpeg installed or local binary in `/ffmpeg/`
- [ ] PHP extensions: PDO, GD or Imagick, fileinfo, exif, mbstring
- [ ] Write permissions on `uploads/`, `logs/`
- [ ] Composer installed (for PHP dependencies)
- [ ] Node.js + npm (for build tools)

### Installation Steps

```bash
# 1. Clone repository
git clone https://github.com/nhashimoto-gm/KidSnaps-GrowthAlbum.git
cd KidSnaps-GrowthAlbum

# 2. Install dependencies
composer install
npm install

# 3. Configure database
cp .env_db.example .env_db
nano .env_db
# Set: DB_HOST, DB_NAME, DB_USER, DB_PASS, ADMIN_PASSWORD

# 4. Create database
mysql -u root -p -e "CREATE DATABASE kidsnaps CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 5. Run migrations
mysql -u user -p kidsnaps < sql/setup.sql
mysql -u user -p kidsnaps < sql/create_album_tables.sql
mysql -u user -p kidsnaps < sql/add_exif_data.sql
mysql -u user -p kidsnaps < sql/add_file_hash.sql
mysql -u user -p kidsnaps < sql/add_rotation.sql
mysql -u user -p kidsnaps < sql/add_google_photos_metadata.sql
mysql -u user -p kidsnaps < sql/add_fulltext_index.sql

# 6. Set permissions
chmod 755 uploads/ logs/
chmod 644 .env_db

# 7. Build assets
npm run minify:all

# 8. Deploy FFmpeg (optional but recommended)
mkdir -p ffmpeg
cp /path/to/ffmpeg ./ffmpeg/ffmpeg
chmod +x ./ffmpeg/ffmpeg

# 9. Configure Basic Authentication (REQUIRED for production)
# See README.md for Apache/Nginx setup

# 10. Test installation
php scripts/check/check_db.php
php scripts/check/check_schema.php
```

### Production Checklist

- [ ] Set `DEBUG_MODE=0` or remove from `.env_db`
- [ ] Configure Basic Authentication (.htaccess or nginx.conf)
- [ ] Enable HTTPS (Let's Encrypt)
- [ ] Set strong `ADMIN_PASSWORD`
- [ ] Restrict `lib/debug_logs.php` access
- [ ] Verify `.htaccess` is active (Apache)
- [ ] Configure backup strategy
- [ ] Set up monitoring (disk space, errors)
- [ ] Test upload limits (PHP settings)
- [ ] Verify FFmpeg availability
- [ ] Test HEIC conversion

### Shared Hosting (Lolipop)

See `docs/LOLIPOP_SETUP.md` for detailed instructions.

**Key differences:**
- Use `.user.ini` instead of `php.ini`
- Local FFmpeg binary required
- Basic Auth via control panel
- LiteSpeed instead of Apache

---

## Recent Development Focus

**Last 30 Commits (Nov 13-15, 2025):**

### Primary Theme: HEIC Support
- Client-side HEIC thumbnail conversion (heic2any)
- Server-side HEIC converter with fallback chain:
  1. FFmpeg (preferred)
  2. FFI + libheif
  3. ImageMagick
  4. Command-line tools
- HEIC support diagnostics script
- Local FFmpeg integration

### ZIP Import Enhancements
- Increased ZIP limit to 12GB
- Google Photos people filter
- Null reference fixes
- Preview before import
- Progress tracking improvements

### Performance
- Applied asset minification
- 504 Gateway Timeout fixes
- Progressive JPEG thumbnails
- WebP support

### Documentation
- Rewrote README in English
- Added fileeye/pel acknowledgment
- Updated performance guides

### Patterns Observed
1. **Fallback Chains**: Multiple methods for reliability (HEIC, FFmpeg)
2. **Dry-run Support**: Testing before destructive operations
3. **Progress Tracking**: AJAX polling for long operations
4. **Environment Awareness**: Debug mode, local vs system tools
5. **Comprehensive Logging**: Debug logs for troubleshooting

---

## Important Notes

### Critical Files to Understand

1. **lib/finalize_upload.php** - Core upload logic, EXIF extraction, DB insertion
2. **includes/heic_converter.php** - Multi-method HEIC conversion fallback
3. **includes/exif_helper.php** - EXIF extraction from images
4. **assets/js/script.js** - All frontend logic (2945 lines)
5. **config/database.php** - Database connection factory
6. **lib/album_processor.php** - OOP album management (newer pattern)

### When Modifying Code

1. **File Uploads**: Test with HEIC, large videos, ZIP imports
2. **Database Changes**: Always create migration SQL files
3. **Frontend Changes**: Update both EN and JP translations
4. **API Changes**: Maintain JSON request/response format
5. **Security**: Use prepared statements, escape output, validate files
6. **Performance**: Run `npm run minify:all` after JS/CSS changes
7. **Caching**: Update version numbers in header.php to bust cache

### Known Limitations

1. **No User Authentication**: Basic Auth recommended for production
2. **No CSRF Protection**: On roadmap for v2.0
3. **Session-based Admin**: No persistent user roles
4. **No Automated Tests**: Manual testing required
5. **Geocoding Rate Limit**: Nominatim API (1 req/sec)
6. **FFmpeg Dependency**: Required for video thumbnails, HEIC conversion

### Future Roadmap

**v2.0 (Short-term):**
- User authentication system
- Tagging functionality
- Enhanced date filtering

**v3.0 (Mid-term):**
- Slideshow feature
- Sharing links
- Favorites & ratings
- GPS map display

**v4.0+ (Long-term):**
- AI auto-classification
- Face recognition
- Media editing
- Cloud backup

### Performance Tips

1. **Database Indexes**: Already optimized for common queries
2. **Thumbnail Optimization**: Run `optimize_thumbnails.php --all --webp`
3. **Browser Caching**: Configured in .htaccess (1 year for media)
4. **Lazy Loading**: Already enabled on all images/videos
5. **Asset Minification**: Run `npm run minify:all` before deployment
6. **Full-text Search**: Enabled on title, description, filename
7. **WebP Support**: 25-35% smaller than JPEG

### Troubleshooting

**Upload fails:**
1. Check PHP settings (`upload_max_filesize`, `post_max_size`, `memory_limit`)
2. Verify directory permissions (uploads/ should be 755/775)
3. Enable debug mode and check logs
4. Test with smaller file first

**HEIC not converting:**
1. Run `php scripts/maintenance/check_heic_support.php`
2. Verify FFmpeg availability
3. Check ImageMagick installation
4. Enable debug mode for detailed errors

**Thumbnails missing:**
1. Run `php scripts/check/check_thumbnails.php`
2. Regenerate: `php scripts/maintenance/regenerate_thumbnails.php --all`
3. Verify FFmpeg for videos
4. Check GD/Imagick for images

**Slow page loads:**
1. Verify lazy loading is active
2. Check browser caching headers (.htaccess)
3. Run thumbnail optimization
4. Consider reducing items per page
5. Enable WebP support

---

## Quick Reference

### File Paths
```
Database Config:    .env_db
Main Gallery:       index.php
Album List:         albums.php
Upload Handler:     lib/finalize_upload.php
API Endpoints:      api/*.php
CLI Scripts:        scripts/{bulk,maintenance,check}/*.php
Uploads:            uploads/{images,videos,thumbnails}/
Logs:               logs/*.log
```

### Commands
```bash
# Development
npm run minify:all                              # Minify CSS/JS
composer install                                # Install PHP deps
php scripts/check/check_db.php                  # Test DB connection

# Maintenance
php scripts/maintenance/regenerate_thumbnails.php --all --force
php scripts/maintenance/optimize_thumbnails.php --all --webp
php scripts/maintenance/update_file_hashes.php
php scripts/bulk/remove_duplicates_v2.php --method hash --dry-run

# Diagnostics
php scripts/check/check_schema.php              # Verify schema
php scripts/check/check_latest_records.php      # Recent uploads
php scripts/maintenance/check_heic_support.php  # HEIC support
```

### Environment Variables (.env_db)
```ini
DB_HOST=localhost
DB_NAME=kidsnaps
DB_USER=your_user
DB_PASS=your_password
ADMIN_PASSWORD=your_admin_password
DEBUG_MODE=0                    # Set to 1 in development only
DEBUG_PASSWORD=your_debug_pass
```

### Key URLs
```
Main Gallery:    /index.php
Albums:          /albums.php
Album Upload:    /album_upload.php
Debug Logs:      /lib/debug_logs.php?pass=PASSWORD
```

---

## Conclusion

This codebase is a **production-ready, feature-rich photo album application** with:
- Strong EXIF and metadata support
- Modern web features (dark mode, i18n, lazy loading)
- Performance optimizations
- Security measures (with Basic Auth recommended)
- Comprehensive documentation
- Active development focus on media format support

**When in doubt:**
1. Check existing patterns in similar files
2. Use prepared statements for DB queries
3. Escape all output with `htmlspecialchars()`
4. Update translations for UI changes
5. Test with various file types (HEIC, large videos, ZIPs)
6. Run `npm run minify:all` before committing frontend changes
7. Create migration files for schema changes

**For assistance:**
- README.md - User-facing documentation
- docs/ - Detailed guides
- scripts/check/ - Diagnostic tools
- GitHub Issues - Bug reports

---

**Document Version:** 1.0
**Last Updated:** 2025-01-15
**Maintained by:** AI Assistant working with nhashimoto-gm
