# Migration: Add File UUID (YYMMDDHHMI Format)

## SQL Migration

Run this on your database:

```sql
-- Add new column for file UUID
ALTER TABLE csv_files ADD COLUMN file_uuid VARCHAR(10) UNIQUE NOT NULL DEFAULT '' AFTER id;

-- Generate UUIDs for existing files based on created_at timestamp
UPDATE csv_files 
SET file_uuid = DATE_FORMAT(created_at, '%y%m%d%H%i')
WHERE file_uuid = '';

-- If there are duplicates (files created in the same minute), append sequence number
UPDATE csv_files 
SET file_uuid = CONCAT(DATE_FORMAT(created_at, '%y%m%d%H%i'), LPAD(ROW_NUMBER() OVER (PARTITION BY DATE_FORMAT(created_at, '%y%m%d%H%i') ORDER BY id), 2, '0'))
WHERE LENGTH(file_uuid) = 10 AND file_uuid IN (
    SELECT file_uuid FROM (
        SELECT file_uuid, COUNT(*) as cnt FROM csv_files GROUP BY file_uuid HAVING cnt > 1
    ) duplicates
);

-- Add index for fast lookups
CREATE INDEX idx_file_uuid ON csv_files(file_uuid);
```

## Changes Made

### 1. `/public_html/includes/config.php`
- Added `generateFileUUID()` function to generate YYMMDDHHMI format

### 2. `/public_html/upload.php`
- Generate file_uuid when uploading new files

### 3. `/public_html/api/list.php` & `/public_html/api/get.php`
- Use `file_uuid` parameter instead of `id`
- Query by file_uuid instead of id

### 4. `/public_html/index.php`, `/public_html/view.php`, `/public_html/edit.php`, `/public_html/delete.php`
- Updated links to use file_uuid

## URL Changes

**Before:**
```
GET /api/list.php?key=frostfoe1337&file_id=2
```

**After:**
```
GET /api/list.php?key=frostfoe1337&file_uuid=2411231830
```

## Testing

After migration, test:
```bash
curl "http://csv.mnr.world/api/list.php?key=frostfoe1337"
curl "http://csv.mnr.world/api/list.php?key=frostfoe1337&file_uuid=2411231830"
```
