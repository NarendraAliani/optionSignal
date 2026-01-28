# Option Signal Scanner

A PHP/MySQL based options signal scanner for NIFTY stocks.

## Features
- **Live Scanning**: Scans for options calls/puts where `Latest Close >= 2 * Previous Close`.
- **Indicators**: RSI(14) and EMA included in logic.
- **Secure Settings**: API keys stored with AES-256 encryption.
- **Database**: Normalized schema for stocks, contracts, and candles.
- **UI**: Bootstrap 5 dashboard with DataTables.

## Installation
1. Create a MySQL database `option_signal`.
2. Import `sql/schema.sql`.
3. Configure database credentials in `config/constants.php`.
4. (Optional) Run `tests/test_logic.php` to verify core algorithms.
5. Create an admin user in the `users` table manually (password must be hashed using `password_hash()`).
   ```sql
   INSERT INTO users (username, password_hash) VALUES ('admin', '$2y$10$...'); 
   ```
6. Visit `/public/index.php`.

## Usage
- Go to `/settings.php` to configure API keys.
- Use Dashboard to run scans.
