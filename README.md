## Installation

### Prerequisites

-   [Docker](https://www.docker.com/)
-   [Docker Compose](https://docs.docker.com/compose/)

### Getting Started

1.  **Clone the repository**
    ```bash
    git clone git@github.com:Chanthoeun/mopotsyo.git
    cd mopotsyo
    ```

2.  **Environment Setup**
    Copy the Docker-ready environment file and configure it:
    ```bash
    cp .env.docker .env
    ```
    *Note: The default configuration in `.env.docker` is pre-configured for Docker networking.*

3.  **Start Docker Containers**
    ```bash
    docker compose up -d
    ```

4.  **Install Dependencies**
    ```bash
    # Fix permissions if needed (see Troubleshooting)
    docker compose exec app composer install
    docker compose exec app npm install
    docker compose exec app npm run build
    ```

5.  **Generate App Key**
    ```bash
    docker compose exec app php artisan key:generate
    ```

6.  **Run Migrations**
    ```bash
    docker compose exec app php artisan migrate --seed
    ```

7.  **Access the Application**
    -   **App**: [http://localhost:8005](http://localhost:8005)
    -   **Mailpit**: [http://localhost:8026](http://localhost:8026)
    -   **PHPMyAdmin**: [http://localhost:8081](http://localhost:8081)

### Troubleshooting

If you encounter permission errors during installation (e.g., Composer cache, logs, or npm), run:

```bash
# Fix Composer and Vendor permissions
mkdir -p vendor
chmod -R 777 .composer vendor

# Fix Storage, Cache, and Public permissions
chmod -R 777 storage bootstrap/cache public

# Fix NPM permissions
mkdir -p .npm node_modules
chmod -R 777 .npm node_modules

# Fix Git Safe Directory (if error: fatal: detected dubious ownership)
docker compose exec app git config --global --add safe.directory /var/www
```


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

