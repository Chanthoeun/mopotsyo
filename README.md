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
    Copy the example environment file and configure it:
    ```bash
    cp .env.example .env
    ```
    *Note: The default configuration is set up for Docker.*

3.  **Start Docker Containers**
    ```bash
    docker-compose up -d
    ```

4.  **Install Dependencies**
    ```bash
    docker-compose execute app composer install
    docker-compose execute app npm install
    docker-compose execute app npm run build
    ```

5.  **Generate App Key**
    ```bash
    docker-compose execute app php artisan key:generate
    ```

6.  **Run Migrations**
    ```bash
    docker-compose execute app php artisan migrate --seed
    ```

7.  **Access the Application**
    -   **App**: [http://localhost:8005](http://localhost:8005)
    -   **Mailpit**: [http://localhost:8026](http://localhost:8026)

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

