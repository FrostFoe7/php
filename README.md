# Universal Question Bank Manager

A pure PHP application for managing question banks via CSV uploads, with a JSON API.

## Features
- **CSV Upload**: Parses Unicode, Bangla, and HTML content.
- **Full Editing**: Edit all question fields via a web interface.
- **JSON API**: Access files and questions via simple token-based API.
- **No Hashing**: Passwords and tokens are stored in plain text (as per requirements).
- **UUIDs**: Uses UUID v4 for all IDs.

## cPanel Setup Instructions

1.  **Create Database**:
    *   Log in to cPanel.
    *   Go to "MySQL® Databases".
    *   Create a new database (e.g., `username_questionbank`).
    *   Create a new user (e.g., `username_admin`) and set a password.
    *   Add the user to the database with **ALL PRIVILEGES**.

2.  **Import Schema**:
    *   Go to "phpMyAdmin" in cPanel.
    *   Select your new database.
    *   Click "Import".
    *   Upload or copy-paste the content of `database.sql`.
    *   **Important**: Insert an initial admin user manually into the `users` table using the "Insert" tab in phpMyAdmin.
        *   `id`: Generate a UUID (e.g., use an online generator or `uuid()` in SQL if available, or just `123e4567-e89b-12d3-a456-426614174000`).
        *   `email`: `admin@example.com`
        *   `password`: `yourpassword` (Plain text!)
        *   `name`: `Admin`

3.  **Upload Files**:
    *   Upload all files from the `php-api` folder to your `public_html` folder (or a subdirectory like `public_html/api`).

4.  **Configure Database Connection**:
    *   Edit `includes/config.php`.
    *   Update `DB_NAME`, `DB_USER`, and `DB_PASS` with your cPanel database details.

5.  **Verify**:
    *   Visit `yourdomain.com/login.php`.
    *   Login with the credentials you inserted into the database.

## API Usage

**Authentication**: Add `?token=YOUR_TOKEN` to all requests.
(You need to manually insert a token into the `api_tokens` table linked to your user ID).

*   **Get Files**: `GET /api/index.php?route=files&token=...`
*   **Get File Info**: `GET /api/index.php?route=file&id=FILE_UUID&token=...`
*   **Get Questions**: `GET /api/index.php?route=questions&file_id=FILE_UUID&token=...`
*   **Get Question**: `GET /api/index.php?route=question&id=QUESTION_UUID&token=...`
*   **Update Question**: `POST /api/index.php?route=update-question&token=...` (JSON body with `id` and fields to update)

## Folder Structure

```
public_html/
    index.php
    login.php
    logout.php
    file-upload.php
    file-view.php
    file-edit.php
    question-edit.php
    file-delete.php
    database.sql
    api/
        index.php
        routes/
            get-files.php
            get-file.php
            get-questions.php
            get-question.php
            update-question.php
    includes/
        bootstrap.php
        config.php
        db.php
        auth.php
        security.php
        uuid.php
        csv_parser.php
    templates/
        header.php
        footer.php
        nav.php
    assets/
        css/
            style.css
        js/
```
