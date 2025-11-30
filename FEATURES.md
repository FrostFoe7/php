# Question Bank Management System - New Features

## ✅ All 4 Features Implemented

### 1. **File Name Editing** ✅
- Click "Rename" button on any file in the dashboard
- Edit the display name and assign a category
- Original CSV filename is preserved but display name is customizable

### 2. **Category Management System** ✅
- New page: `categories.php`
- Create categories with custom colors
- Add descriptions to categories
- Sort question banks by category
- View file count per category
- Delete categories (files remain unaffected)

**Key Features:**
- Navigate to: "Manage Categories" button in dashboard
- Create new categories with color coding
- Category-based sorting in dashboard

### 3. **CSV ID Fields Added** ✅
Database schema now supports:
- `external_id` - External identifier (e.g., from API)
- `batch_id` - Batch grouping identifier
- `set_id` - Set identifier

These fields are optional and can be populated from CSV if needed.

### 4. **Add/Merge Questions** ✅

**Individual Question Addition:**
- `add-question.php` - Add single questions to existing files
- Access via "Add New Question" button at bottom of file view
- Full form with all options (Answer, Type, Section, Explanation)

**CSV Merge:**
- Upload new CSV and merge into existing file
- Access via "Upload & Merge CSV" button at bottom of file view
- New questions are appended with auto-incrementing order indices
- Total question count is updated automatically

### 5. **Section Field Functional** ✅

**Now stores as VARCHAR with preset options:**
- `p` - Physics
- `c` - Chemistry  
- `m` - Math
- `b` - Biology
- `bm` - Bio + Math
- `bn` - Bio + Non-Bio
- `e` - English
- `i` - ICT
- `gk` - General Knowledge
- `iq` - IQ Test

**Usage:**
- Dropdown selector in edit form (file-edit.php)
- Dropdown selector in add question form (add-question.php)
- Store as string codes for custom filtering/grouping

---

## File Structure & New Files

### New Files Created:
- `/php-api/categories.php` - Category management UI
- `/php-api/add-question.php` - Add individual questions
- `/php-api/api/get-file.php` - API endpoint for fetching file data
- `/php-api/migrations/001_add_categories_and_fields.sql` - Database migration

### Updated Files:
- `/php-api/index.php` - Dashboard with sorting & category display
- `/php-api/file-view.php` - Added Add/Merge buttons
- `/php-api/file-edit.php` - Section field as dropdown
- `/php-api/file-upload.php` - Merge CSV support
- `/php-api/includes/csv_parser.php` - 0-indexed answer detection + section conversion

---

## How to Setup

### 1. Run Database Migration:
```bash
mysql -u [user] -p [database] < php-api/migrations/001_add_categories_and_fields.sql
```

### 2. Dashboard Features:

**Sort Options:**
- Recently Uploaded
- Name A-Z
- By Category
- Most Questions

**File Actions:**
- View - Display all questions
- Edit - Modify questions in bulk
- Rename - Change display name & assign category
- Delete - Remove file

---

## User Workflows

### Workflow 1: Upload Initial CSV
1. Click "Upload New CSV"
2. Select CSV file
3. Optionally check "Convert 0-indexed answers"
4. Click "Upload & Process"

### Workflow 2: Add Questions Later
1. Click "View" on a file
2. Scroll to bottom
3. Click "Add New Question" - fill form, submit
4. OR Click "Upload & Merge CSV" - upload new CSV to merge

### Workflow 3: Organize with Categories
1. Click "Manage Categories"
2. Create categories (e.g., "Chapter 1", "Physics", etc.)
3. Go to dashboard, click "Rename" on file
4. Select category from dropdown
5. Files now visible grouped by category when sorted by "Category"

### Workflow 4: Custom Subjects
Using section codes, you can now:
- Filter questions by section
- Create custom exams mixing sections
- Group questions by subject (P, C, M, etc.)

---

## Database Changes

### New Table: `categories`
```sql
CREATE TABLE categories (
  id char(36) PRIMARY KEY,
  name varchar(100) UNIQUE NOT NULL,
  description text,
  color varchar(7) DEFAULT '#007bff',
  created_at datetime DEFAULT CURRENT_TIMESTAMP
);
```

### Updated Table: `files`
```sql
ALTER TABLE files ADD display_name varchar(255);
ALTER TABLE files ADD category_id char(36) FOREIGN KEY;
ALTER TABLE files ADD external_id varchar(50);
ALTER TABLE files ADD batch_id varchar(50);
ALTER TABLE files ADD set_id varchar(50);
```

### Updated Table: `questions`
```sql
ALTER TABLE questions MODIFY section varchar(10) DEFAULT '0';
-- Now stores: 'p', 'c', 'm', 'b', 'bm', 'bn', 'e', 'i', 'gk', 'iq'
```

---

## Future Enhancement Ideas

✨ Using this foundation, you can now:
- Create **custom exams** mixing specific sections
- **Filter questions** by section for targeted practice
- **Group related files** with categories
- **Track question sources** with external_id, batch_id, set_id
- Add **bulk import** from section codes
- Create **section-based reports**
