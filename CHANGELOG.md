# Changelog

All notable changes to `laravel-backup-db` will be documented in this file.

## 1.0.0-alpha - 2024-01-01

**⚠️ ALPHA RELEASE - NOT PRODUCTION READY**

### Added
- Initial release of Laravel Backup Database Manager
- Web interface for viewing database backups
- One-click database restoration functionality
- Authorization system using Laravel Gates
- Configurable UI themes (dark/light)
- Comprehensive logging of all operations
- Bootstrap 5 responsive design
- File size and date information display
- CSRF protection for all forms
- Integration with Spatie Laravel Backup package
- Customizable route prefixes and middleware
- Detailed documentation and setup guides

### Security
- Gate-based authorization for all routes
- CSRF token validation on all forms
- Path validation to prevent directory traversal
- Secure temporary file handling during restoration

### Notice
- This is an alpha release and has not been thoroughly tested in production environments
- Please use with caution and report any issues encountered
- Extensive testing is recommended before any production use