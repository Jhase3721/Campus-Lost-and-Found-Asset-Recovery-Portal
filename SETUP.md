# Local setup

## Database

1. Start MySQL in XAMPP.
2. Import `database/schema.sql` using phpMyAdmin or the MySQL command line.
3. Configure the database connection with environment variables if your local MySQL settings differ from the XAMPP defaults:
   - `CAMPUS_DB_HOST` (default `127.0.0.1`)
   - `CAMPUS_DB_NAME` (default `campus_lost_found`)
   - `CAMPUS_DB_USER` (default `root`)
   - `CAMPUS_DB_PASSWORD` (default empty for a standard local XAMPP setup)

Do not use the empty-password local default on a deployed server. Configure a dedicated MySQL account and a strong password there.

## Create the first administrator

Set the administrator email and a private password in the environment, then run `php tools/create_admin.php` from the project directory. The password must have at least 12 characters. For PowerShell:

```powershell
$env:CAMPUS_ADMIN_EMAIL = Read-Host "Administrator email"
$securePassword = Read-Host "Administrator password" -AsSecureString
$passwordPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
try {
    $env:CAMPUS_ADMIN_PASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($passwordPointer)
    php tools\create_admin.php
} finally {
    Remove-Item Env:CAMPUS_ADMIN_EMAIL, Env:CAMPUS_ADMIN_PASSWORD -ErrorAction SilentlyContinue
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($passwordPointer)
    $securePassword.Dispose()
}
```

The script creates an administrator-role account; it does not reset or promote existing accounts.

## Local testing administrator

For local testing only, the sign-in page can show a predefined test administrator. Enable it only on a local development server; the test account is rejected unless the request comes from the local machine and `CAMPUS_ENABLE_TEST_ADMIN` is set to `1`.

Create the account once from PowerShell in the project directory. These values must match the test credentials shown on the local sign-in page:

```powershell
$env:CAMPUS_ENABLE_TEST_ADMIN = "1"
php -S 127.0.0.1:8000
For PHP's built-in server, enable the local test login before starting it:

```powershell
$env:CAMPUS_ENABLE_TEST_ADMIN = "1"
php -S 127.0.0.1:8000
```

For Laragon/Apache, set `CAMPUS_ENABLE_TEST_ADMIN=1` in the local Apache or virtual-host environment and restart Apache. Do not enable this setting on a deployed server. The test account is restricted to local requests, but its credentials are intentionally fixed and must not be used for real data.

## Run the application

From the project directory, run `php -S 127.0.0.1:8000` and open `http://127.0.0.1:8000`. Users can register with their email. Admins use the same sign-in page and are redirected to the reports dashboard.

Set up HTTPS before deployment. Session cookies are marked secure automatically when the app is served over HTTPS.
