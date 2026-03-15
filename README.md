# Filament Starter Kit

A robust Laravel 12 / Filament 5 starter kit featuring advanced security, media management, and audit capabilities.

## 🚀 Features

### 🛡️ Advanced Security Suite
- **Multi-Factor Authentication (MFA)**: Built-in TOTP support with a polished, user-friendly challenge interface.
- **Password Governance**:
    - **Password Expiry**: Configurable expiry days to force regular credential updates.
    - **Forced Reset**: Administrative ability to require users to reset their password upon next login.
- **Throttling & Protection**:
    - Configurable login attempt limits and lockout durations.
    - MFA resend throttling to prevent SMS/Email spam.
    - Password reset request limits.
- **Security Hub**: Integrated security settings directly in the admin panel.

### 📊 Audit Hub
A centralized location for all system logs and auditing tools:
- **Authentication Logs**: Track successful logins, failures, IP addresses, and user agents.
- **Activity Logs**: Comprehensive tracking of model changes and system events.

### 🖼️ Media Management
- **Filament Media Manager**: Integrated file browser for uploading, organizing, and reusing media across the platform.
- **Optimized UI**: Custom theme integration ensures perfectly scaled icons and responsive interactions.

### ⚙️ System Settings
- Unified configuration for security thresholds and platform behavior.
- Cleanly organized under the "Settings" navigation group.

## 🛠️ Technology Stack
- **PHP 8.4**
- **Laravel 12**
- **Filament 5**
- **Livewire 4**
- **Tailwind CSS 4**
- **SQLite**

## 🏁 Getting Started

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd filamentfive
   ```

2. **Install dependencies**:
   ```bash
   composer install
   npm install
   ```

3. **Environment Setup**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database Migration & Seeding**:
   ```bash
   touch database/database.sqlite
   php artisan migrate --seed
   ```

5. **Build Assets**:
   ```bash
   npm run build
   ```

6. **Serve the Application**:
   Use Laravel Herd or serve manually:
   ```bash
   php artisan serve
   ```

## 📜 License
The MIT License (MIT). Project based on Laravel and Filament.
