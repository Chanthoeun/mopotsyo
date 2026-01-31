## Features

Starter Kit is a powerful Laravel and Filament-based application featuring:

- **Users & Roles Management**: Granular control via Laravel Shield.
- **Advanced Approval System**:
    - **Approval History**: Full audit trail accessible via status badges.
    - **Force Approve**: Admin bypass for stuck or urgent requests.
    - **Automated Workflow Reconciliation**: Real-time status sync across steps.
- **Media & File Manager**: Integrated file handling.
- **Utility Tools**: Filament Importer/Exporter, Authentication Log, Activity Audit, and more.

## Approval System Migration & Deployment Guide

Follow these steps to upgrade the approval system on the production server.

### 1. Deployment Sequence

1.  **Pull latest code**:
    ```bash
    git pull 
    ```
2.  **Update dependencies**:
    ```bash
    composer update --ignore-platform-reqs --no-dev --optimize-autoloader
    ```
3.  **Run Full Upgrade**:
    This unified command handles migrations, data transfer, status reconciliation, and approver restoration.
    ```bash
    php artisan app:upgrade-approvals --force
    ```

### 2. Verification

1.  **Bring App Up**:
    ```bash
    php artisan up
    ```
2.  **Verify Results**: 
    Open any previously approved request to verify its **Approval History** is fully restored and statuses are reconciled.

