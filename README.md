/_ FILE: public_html/README.md _/

# CSV to JSON Web System

This is a full PHP web system that allows uploading CSV files, converting them to JSON, storing them in a MySQL database, and providing a UI to edit the data.

## Project Features

- **CSV Upload**: Safely uploads and parses CSV files, preserving UTF-8 characters, HTML tags, and Bangla language.
- **JSON Storage**: Converts CSV data to JSON and stores it in a MySQL database.
- **Dashboard**: Lists all uploaded files with options to view, edit, or delete.
- **JSON Editor**: A card-style UI to edit, add, or delete questions (rows) from the parsed data.
- **Secure API**: A token-protected API endpoint to retrieve file data in JSON format.
- **Authentication**: A simple and secure login/logout system for admin access.
- **UI**: Built with Bootstrap 5 for a responsive and modern user experience.

## Setup Instructions (cPanel)

1.  **Database Setup**:
    - Create a new MySQL database in your cPanel.
    - Create a new MySQL user and assign it to the database with all privileges.
    - Import the `database.sql` file into your newly created database using phpMyAdmin.

2.  **Configuration**:
    - Open `includes/config.php`.
    - Update the database credentials (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`) with the details from the previous step.
    - Set a secure `ADMIN_USERNAME` and `ADMIN_PASSWORD`. The password should be hashed. You can use an online tool to generate a `PASSWORD_DEFAULT` hash.
    - Define a secret `API_KEY` for the API endpoint.

3.  **Upload Files**:
    - Upload all the files from the `public_html` directory to your domain's root directory (usually also named `public_html` or `www`) on your cPanel server.

4.  **Login**:
    - Navigate to `yourdomain.com/public/login.php` to access the login page.
    - Use the admin credentials you set in `config.php` to log in.

## File Structure

- `public_html/`: The web root.
  - `public/`: Contains all user-facing pages (`index.php`, `upload.php`, etc.).
  - `includes/`: Core files for configuration and session management.
  - `templates/`: Reusable HTML components like header, footer, and navigation.
  - `css/`: Custom stylesheets.
  - `api/`: API endpoints.
  - `database.sql`: The initial database schema.

## Security Notes

- This system uses prepared statements to prevent SQL injection.
- All admin pages are protected by a session check.
- File uploads are validated for extension (`.csv`) and size.
- **IMPORTANT**: It is recommended to set the admin password in `config.php` to a hashed value for better security.

# php
