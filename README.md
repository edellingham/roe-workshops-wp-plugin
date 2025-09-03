# ROE Workshops WordPress Plugin

WordPress plugin for Grundy/Kendall Regional Office of Education workshop registration system. Connects to FileMaker Pro database via ODBC and provides frontend workshop display and registration functionality.

**GitHub Repository**: https://github.com/edellingham/roe-workshops-wp-plugin

## Features

- **ODBC Integration**: Direct connection to FileMaker Pro database
- **Workshop Sync**: Automatic and manual sync of workshop data to WordPress cache
- **Frontend Display**: Responsive workshop listings with search and filter
- **Registration System**: Complete registration workflow with email confirmations  
- **Admin Interface**: WordPress admin dashboard for monitoring and configuration
- **Error Logging**: Comprehensive error tracking and reporting

## Requirements

- WordPress 5.0+
- PHP 7.0+
- **For Remote Servers**: API bridge deployed on FileMaker server
- **For Local Servers**: PHP ODBC extension + FileMaker ODBC driver

## Connection Methods

### API Bridge (Recommended for Remote Servers)
The plugin can connect to FileMaker via HTTP API instead of direct ODBC. This allows:
- WordPress site on different server than FileMaker
- Better security (no direct database exposure)
- Easier firewall management
- More reliable connections over internet

**Requirements**: API bridge from this repository must be deployed on the FileMaker server at:
`/Registration/api-bridge/` (from the legacy site)

### Direct ODBC (Local Server Only)
Traditional ODBC connection for same-server deployments:
- WordPress and FileMaker on same Windows server
- Direct database access
- Faster for local connections
- Requires ODBC driver configuration

## Installation

### Option 1: Direct Download from GitHub
1. **Download Plugin**
   ```bash
   # Clone from GitHub
   git clone https://github.com/edellingham/roe-workshops-wp-plugin.git roe-workshops
   
   # Or download ZIP and extract
   wget https://github.com/edellingham/roe-workshops-wp-plugin/archive/main.zip
   unzip main.zip
   mv roe-workshops-wp-plugin-main roe-workshops
   ```

2. **Upload to WordPress**
   ```bash
   # Upload to WordPress plugins directory
   rsync -av roe-workshops/ your-server:/path/to/wordpress/wp-content/plugins/roe-workshops/
   ```

### Option 2: Direct Server Installation
1. **SSH to your WordPress server**
2. **Navigate to plugins directory**
   ```bash
   cd /path/to/wordpress/wp-content/plugins/
   ```
3. **Clone repository**
   ```bash
   git clone https://github.com/edellingham/roe-workshops-wp-plugin.git roe-workshops
   ```

2. **Configure ODBC**
   - Ensure FileMaker ODBC driver is installed
   - Verify DSN 'CEDARWOOD' is configured
   - Test connection from command line:
   ```bash
   php -r "var_dump(odbc_connect('CEDARWOOD', 'webuser', 'PDAcedar'));"
   ```

3. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Activate "ROE Workshops"
   - Database tables will be created automatically

4. **Configure Settings**
   - Go to ROE Workshops → Sync Settings
   - Choose connection method (API Bridge or Direct ODBC)
   - **For API Bridge**: Enter API URL and keys
   - **For Direct ODBC**: Configure DSN and credentials
   - Test connection
   - Run initial sync

## API Bridge Setup (For Remote Servers)

If your WordPress site is on a different server than FileMaker, you need to deploy the API bridge:

1. **Deploy API Bridge on FileMaker Server**
   ```bash
   # Copy from legacy site to active location
   cp -r /old-site/Registration/api-bridge /active-site/Registration/
   
   # Or deploy standalone version
   cp -r ROE_API_STANDALONE/* /inetpub/wwwroot/api/
   ```

2. **Configure API Bridge**
   - Run the installer: `https://your-filemaker-server.com/Registration/api-bridge/install.php`
   - Save the generated API keys
   - Add your WordPress server IP to the whitelist

3. **Configure WordPress Plugin**
   - Connection Method: "API Bridge"
   - API URL: `https://your-filemaker-server.com/Registration/api-bridge/bridge.php`
   - API Key: [from installer]
   - Test connection

## Usage

### Frontend Display

**Workshop Listings**
```php
// Basic workshop list
[roe-workshops]

// Filtered workshop list
[roe-workshops category="Technology" limit="10"]

// Workshop list with search disabled
[roe-workshops show_search="false"]
```

**Workshop Detail**
```php
// Auto-detect from URL parameter (?workshop=WS123)
[roe-workshop-detail]

// Specific workshop
[roe-workshop-detail number="WS123"]
```

### Admin Functions

**Manual Sync**
- ROE Workshops → Dashboard → "Sync Workshops Now"

**Monitor Status**
- View sync statistics on dashboard
- Check error logs for issues
- Test ODBC connection

**Configuration**
- ROE Workshops → Sync Settings
- Configure connection details
- Set sync frequency (hourly/daily)

## File Structure

```
roe-workshops/
├── roe-workshops.php              # Main plugin file
├── includes/
│   ├── class-odbc-connector.php      # ODBC connection handler
│   ├── class-workshop-sync.php       # Data synchronization
│   ├── class-admin-interface.php     # Admin interface
│   └── class-frontend-display.php    # Public display
├── admin/
│   ├── admin-dashboard.php           # Dashboard template
│   ├── sync-settings.php            # Settings page
│   └── error-logs.php               # Error log viewer
├── templates/
│   ├── workshop-list-template.php    # Workshop listing
│   ├── workshop-detail-template.php  # Single workshop
│   └── registration-form-template.php # Registration form
├── assets/
│   ├── css/
│   │   ├── public.css               # Frontend styles
│   │   └── admin.css                # Admin styles
│   └── js/
│       ├── public.js                # Frontend JavaScript
│       └── admin.js                 # Admin JavaScript
└── README.md                        # This file
```

## Database Schema

The plugin creates three WordPress tables:

### wp_roe_workshops
Cached workshop data from FileMaker:
- `workshop_number` - Primary identifier
- `title`, `description_full` - Workshop content
- `start_date`, `start_time`, `end_time` - Scheduling
- `workshop_type` - Category
- `max_registration_count`, `current_registration_count` - Capacity
- `cost_student`, `cost_employee`, `web_rate` - Pricing
- `status`, `approved` - Workshop state

### wp_roe_sessions  
Individual session data for multi-session workshops:
- `workshop_number` - Links to workshop
- `session_date`, `begin_time`, `end_time` - Session timing
- `location_building_room`, `location_full` - Session location

### wp_roe_error_log
Plugin error tracking:
- `level` - ERROR, WARNING, INFO, DEBUG
- `message` - Error description
- `context` - Additional details (JSON)
- `timestamp` - When error occurred

## Configuration

### WordPress wp-config.php
```php
// Optional: Override default ODBC settings
define('ROE_ODBC_DSN', 'CEDARWOOD');
define('ROE_ODBC_USER', 'webuser');
define('ROE_ODBC_PASS', 'your_password');
define('ROE_DEBUG_MODE', false);
```

### Plugin Options
Available via ROE Workshops → Sync Settings:
- ODBC connection details
- Sync frequency (hourly, twice daily, daily)
- Company information for emails
- Debug mode toggle

## Customization

### Styling
Override styles by adding CSS to your theme:
```css
/* Override workshop card styling */
.roe-workshop-card {
    border-color: your-brand-color;
}
```

### Templates
Copy templates to your active theme to customize:
```
/wp-content/themes/your-theme/roe-workshops/
├── workshop-list-template.php
├── workshop-detail-template.php
└── registration-form-template.php
```

## Troubleshooting

### Common Issues

**API Connection Fails**
- Verify API bridge URL is accessible
- Check API key is correct
- Confirm WordPress server IP is whitelisted
- Review API bridge logs on FileMaker server

**ODBC Connection Fails** (Direct connection only)
- Check DSN exists: Windows → ODBC Data Source Administrator
- Verify FileMaker Server is running
- Test connection from command line
- Check firewall settings

**Workshops Not Syncing**
- Check ROE Workshops → Error Logs
- Verify FileMaker database permissions
- Test manual sync from dashboard
- Check WordPress cron is working

**Registration Emails Not Sending**
- Verify WordPress mail configuration
- Check spam folders
- Test with WordPress mail testing plugin
- Review error logs for SMTP issues

### Debug Mode
Enable debug mode for detailed logging:
1. ROE Workshops → Sync Settings
2. Check "Enable debug logging"
3. Review logs in ROE Workshops → Error Logs

### Command Line Testing
```bash
# Test ODBC connection
php -r "var_dump(odbc_connect('CEDARWOOD', 'webuser', 'password'));"

# Test WordPress plugin activation
wp plugin activate roe-workshops

# Manual sync trigger
wp cron event run roe_workshop_sync

# Check scheduled events
wp cron event list
```

## Security

- All user inputs are sanitized using WordPress functions
- CSRF protection via nonces on all forms  
- Prepared statements for database queries
- Error messages don't expose sensitive information
- Admin functions require appropriate WordPress capabilities

## Performance

- Workshop data cached in WordPress database
- Automatic cleanup of old workshops and sessions
- Efficient database queries with proper indexes
- Frontend caching compatible

## Support

For issues with this plugin:
1. Check ROE Workshops → Error Logs in WordPress admin
2. Enable debug mode for detailed logging
3. Verify ODBC connection is working
4. Test FileMaker database access

## Development

### Local Development Setup
1. Install WordPress locally
2. Install ODBC drivers for your platform
3. Set up FileMaker Pro or mock database
4. Copy plugin to wp-content/plugins/
5. Configure connection settings

### Adding Features
- Extend `ROE_ODBC_Connector` for new queries
- Add new templates in `/templates/` directory
- Use WordPress hooks and filters for customization
- Follow WordPress coding standards

---

**Version**: 1.0.0  
**Author**: Claude Code  
**Last Updated**: January 2025