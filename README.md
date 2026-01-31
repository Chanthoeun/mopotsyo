## About Starter Kit

Starter Kit is a Laravel framework and filamentphp combination for developing new web application project. it has features as below:

- Users Management
- Roles Management
- Filament Importer and Exporter
- User Ban and Unban
- Authentication Log
- Activity Log
- Debugbar
- Font Awesome
- Password Input

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Approval System Migration & Deployment Guide (Production)

### 2. Deployment Sequence
1.  **Pull latest code**:
    ```bash
    git pull origin main
    ```
2.  **Update dependencies**:
    ```bash
    composer install --no-dev --optimize-autoloader
    ```
3.  **Run ONE Command for Full Upgrade** (Unified):
    This command handles migrations (with data transfer), status reconciliation, and approver restoration automatically.
    ```bash
    php artisan app:upgrade-approvals
    ```

### 3. Verification
1.  **Bring App Up**:
    ```bash
    php artisan up
    ```
2.  **Check Status**: 
    Open any previously approved request to verify its **Approval History** is restored.

