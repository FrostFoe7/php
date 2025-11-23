# PHP CSV API Documentation

## Overview
Bullet-proof API for managing exam questions stored in MySQL as JSON. Supports CRUD operations, bulk operations, and question reordering with unique UID mapping.

## Base URL
```
https://csv.mnr.world/api
```

## Authentication
All requests require API key authentication:
- **Header Method**: `Authorization: Bearer frostfoe1337`
- **Query Parameter**: `?key=frostfoe1337`
- **Form Parameter**: `key=frostfoe1337`

## Error Response Format
```json
{
  "success": false,
  "message": "Error description here",
  "data": {}
}
```

## Success Response Format
```json
{
  "success": true,
  "message": "Success message",
  "data": { /* operation-specific data */ }
}
```

---

## Endpoints

### 1. GET /list.php
**List all or filtered questions**

#### Parameters
- `key` (string, required): API key
- `file_id` (integer, optional): Filter by file ID

#### Examples
```bash
# All questions
curl "https://csv.mnr.world/api/list.php?key=frostfoe1337"

# Questions from specific file
curl "https://csv.mnr.world/api/list.php?key=frostfoe1337&file_id=1"
```

#### Response
```json
{
  "success": true,
  "data": {
    "questions": [
      {
        "uid": 1,
        "file_id": 1,
        "question": "What is 2+2?",
        "description": "Basic math",
        "option1": "3",
        "option2": "4",
        "option3": "5",
        "option4": "6",
        "option5": "7",
        "correct": "B",
        "explanation": "2+2=4"
      }
    ],
    "total": 150
  }
}
```

---

### 2. GET /get.php
**Fetch specific file or single question**

#### Parameters
- `key` (string, required): API key
- `uid` (integer, optional): Question UID
- `file_id` (integer, optional): File ID

#### Examples
```bash
# Get single question
curl "https://csv.mnr.world/api/get.php?key=frostfoe1337&uid=42"

# Get file with all questions
curl "https://csv.mnr.world/api/get.php?key=frostfoe1337&file_id=1"
```

---

### 3. POST /update.php
**Update question field(s)**

#### Methods

**Method A: Single Field (Form-encoded)**
```bash
curl -X POST "https://csv.mnr.world/api/update.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "key=frostfoe1337&uid=1&field=question&value=New Question"
```

**Method B: Multiple Fields (JSON)**
```bash
curl -X POST "https://csv.mnr.world/api/update.php" \
  -H "Content-Type: application/json" \
  -d '{
    "key": "frostfoe1337",
    "uid": 1,
    "updates": {
      "question": "New Question",
      "description": "Updated",
      "correct": "A"
    }
  }'
```

**Method C: Update Options**
```bash
curl -X POST "https://csv.mnr.world/api/update.php" \
  -H "Content-Type: application/json" \
  -d '{
    "key": "frostfoe1337",
    "uid": 1,
    "option1": "New Option 1",
    "option2": "New Option 2"
  }'
```

#### Response
```json
{
  "success": true,
  "message": "Question field updated successfully.",
  "data": {
    "uid": 1,
    "field": "question",
    "value": "New Question"
  }
}
```

---

### 4. POST /delete.php
**Delete a question**

#### Form-encoded
```bash
curl -X POST "https://csv.mnr.world/api/delete.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "key=frostfoe1337&uid=1"
```

#### JSON
```bash
curl -X POST "https://csv.mnr.world/api/delete.php" \
  -H "Content-Type: application/json" \
  -d '{"key": "frostfoe1337", "uid": 1}'
```

#### Response
```json
{
  "success": true,
  "message": "Question deleted successfully.",
  "data": {
    "uid": 1,
    "deleted": true
  }
}
```

---

### 5. POST /create.php
**Create new question**

```bash
curl -X POST "https://csv.mnr.world/api/create.php" \
  -H "Content-Type: application/json" \
  -d '{
    "key": "frostfoe1337",
    "file_id": 1,
    "question": "What is the capital of France?",
    "description": "Geography question",
    "option1": "London",
    "option2": "Paris",
    "option3": "Berlin",
    "option4": "Madrid",
    "option5": "Rome",
    "correct": "B",
    "explanation": "Paris is the capital of France",
    "category": "Geography",
    "difficulty": "Easy"
  }'
```

#### Response
```json
{
  "success": true,
  "message": "Question created successfully.",
  "data": {
    "file_id": 1,
    "uid": 151,
    "question": { /* full question object */ }
  }
}
```

---

### 6. POST /reorder.php
**Reorder questions within a file**

```bash
curl -X POST "https://csv.mnr.world/api/reorder.php" \
  -H "Content-Type: application/json" \
  -d '{
    "key": "frostfoe1337",
    "file_id": 1,
    "order": [5, 3, 1, 2, 4]
  }'
```

#### Response
```json
{
  "success": true,
  "message": "Questions reordered successfully.",
  "data": {
    "file_id": 1,
    "reordered_count": 5
  }
}
```

---

### 7. POST /bulk.php
**Bulk operations**

#### Bulk Delete
```bash
curl -X POST "https://csv.mnr.world/api/bulk.php" \
  -H "Content-Type: application/json" \
  -d '{
    "key": "frostfoe1337",
    "operation": "delete",
    "uids": [1, 2, 3, 4, 5]
  }'
```

#### Bulk Update
```bash
curl -X POST "https://csv.mnr.world/api/bulk.php" \
  -H "Content-Type: application/json" \
  -d '{
    "key": "frostfoe1337",
    "operation": "update",
    "updates": [
      {"uid": 1, "field": "category", "value": "Math"},
      {"uid": 2, "field": "category", "value": "Math"}
    ]
  }'
```

#### Bulk Create
```bash
curl -X POST "https://csv.mnr.world/api/bulk.php" \
  -H "Content-Type: application/json" \
  -d '{
    "key": "frostfoe1337",
    "operation": "create",
    "file_id": 1,
    "questions": [
      {"question": "Q1?", "option1": "A", "option2": "B", "correct": "A"},
      {"question": "Q2?", "option1": "C", "option2": "D", "correct": "D"}
    ]
  }'
```

#### Response
```json
{
  "success": true,
  "message": "Bulk delete operation completed.",
  "data": {
    "success": 5,
    "failed": 0,
    "errors": []
  }
}
```

---

### 8. GET /files.php
**List all CSV files**

```bash
curl "https://csv.mnr.world/api/files.php?key=frostfoe1337"
```

#### Response
```json
{
  "success": true,
  "data": {
    "files": [
      {
        "id": 1,
        "filename": "questions_2024.csv",
        "description": "Year 2024 questions",
        "row_count": 150
      }
    ],
    "total": 5
  }
}
```

---

## HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 404 | Not Found |
| 405 | Method Not Allowed |
| 500 | Internal Server Error |

---

## Implementation Details

### UID System
- Global UIDs are assigned sequentially across all files
- UIDs are 1-indexed and automatically regenerated on each request
- When a question is deleted, all subsequent UIDs are recalculated
- **Note**: UIDs are dynamic and should not be stored permanently

### JSON Structure
Questions are stored as JSON arrays in the `csv_files.json_text` column:
```json
[
  {
    "question": "...",
    "option1": "...",
    "option2": "...",
    "option3": "...",
    "option4": "...",
    "option5": "...",
    "correct": "A|B|C|D|E",
    "description": "...",
    "explanation": "..."
  }
]
```

### Database
- **Table**: `csv_files`
- **Columns**: 
  - `id` (INT UNSIGNED, PK)
  - `filename` (VARCHAR 255)
  - `description` (TEXT)
  - `json_text` (LONGTEXT) - Stores JSON array of questions
  - `row_count` (INT UNSIGNED)
  - `size_kb` (DECIMAL)
  - `created_at` (TIMESTAMP)

---

## Error Handling

**Example Error Response**
```json
{
  "success": false,
  "message": "Record with specified uid not found.",
  "data": {}
}
```

Common errors:
- `Unauthorized: Invalid or missing API key.` (401)
- `Missing required field: uid` (400)
- `Record with specified uid not found.` (404)
- `Failed to update record in the database.` (500)

---

## Best Practices

1. **Always validate API responses** - Check `success` field before using data
2. **Handle UIDs carefully** - Don't rely on static UIDs; refresh after modifications
3. **Batch operations** - Use `/bulk.php` for multiple operations instead of individual requests
4. **Error handling** - Implement proper try-catch blocks in your client
5. **Rate limiting** - Consider implementing rate limiting for bulk operations

---

## JavaScript/TypeScript Client Examples

See Next.js integration files:
- `/src/app/api/update-question/route.ts`
- `/src/app/api/fetch-questions/route.ts`
- `/src/app/api/delete-question/route.ts` (to be created)
