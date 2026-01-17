# Cisco Switch Manager GUI

Web application for managing Cisco switches via SSH. Configure VLANs, port descriptions, and VoIP settings through an intuitive web interface.

## Quick Install (Raspberry Pi / Orange Pi / Linux)

One command installs everything (Docker, the app, and starts it automatically):

```bash
curl -fsSL https://raw.githubusercontent.com/isolson/cisco-switch-manager-gui/master/install-pi.sh | bash
```

After installation:
1. Edit `/opt/cisco-switch-manager-gui/config.php` to add your switches and VLANs
2. Access the web interface at `http://<your-pi-ip>:8088`
3. Log in with your switch SSH credentials

## Features

- **Switch Port Management** - Assign description, VLAN, and VoIP settings to switchports
- **Port Matrix View** - Visual overview of all ports arranged as on the physical device
- **Port List View** - Table overview of all ports with their settings
- **MAC Address Search** - Find which port a MAC address is connected to
- **Config Backup** - Backup switch configurations with optional GitHub sync
- **Scheduled Backups** - Automated daily backups via cron
- **Maps** - Create visual maps showing switch locations
- **Bulk Password Change** - Change passwords on all switches at once
- **Command Snippets** - Run pre-defined commands on switches
- **Mobile Optimized** - Responsive design works on phones and tablets
- **Dark Mode** - Easy on the eyes

## Installation Options

### Option 1: One-Line Installer (Recommended)

Works on Raspberry Pi, Orange Pi, or any Linux system with Docker support:

```bash
curl -fsSL https://raw.githubusercontent.com/isolson/cisco-switch-manager-gui/master/install-pi.sh | bash
```

This script will:
- Install Docker if not present
- Clone the repository to `/opt/cisco-switch-manager-gui`
- Create a starter `config.php`
- Build and start the Docker container
- Display the web interface URL

### Option 2: Docker Compose (Manual)

```bash
git clone https://github.com/isolson/cisco-switch-manager-gui.git
cd cisco-switch-manager-gui
cp config.php.example config.php
# Edit config.php to add your switches and VLANs
docker compose up -d --build
```

Access the web interface at `http://localhost:8088`

### Option 3: Traditional Apache Setup

1. Install packages (Debian/Ubuntu):
   ```bash
   apt install apache2 php php-ssh2
   ```
2. Copy all files to your webserver directory
3. Ensure `AllowOverride All` is set in Apache config for the directory
4. Copy `config.php.example` to `config.php` and customize
5. Access `index.php` in your browser

## Configuration

Edit `config.php` to configure:

```php
// Your switches
const SWITCHES = [
    ['addr' => '192.168.1.10', 'name' => 'Switch 1 - Server Room', 'group' => 'Building A'],
    ['addr' => '192.168.1.11', 'name' => 'Switch 2 - Office', 'group' => 'Building A'],
];

// VLANs available in the dropdown
const VISIBLE_VLAN = [
    ['id' => 'disabled', 'name' => '[----]  DISABLED'],
    ['id' => 1, 'name' => '[01]  Default'],
    ['id' => 10, 'name' => '[10]  Servers'],
    ['id' => 20, 'name' => '[20]  Workstations'],
];

// Enable config backup feature
const ENABLE_CONFIG_BACKUP = true;
```

See `config.php.example` for all available options.

## Config Backup & GitHub Sync

Back up your switch configurations and optionally sync to a private GitHub repository:

1. Enable backups in `config.php`: `const ENABLE_CONFIG_BACKUP = true;`
2. Access the Backup page from the menu
3. Click "GitHub Setup" to configure cloud sync (optional)
4. Back up individual switches or all at once

### Scheduled Backups

Set up automated daily backups:

```bash
cd /opt/cisco-switch-manager-gui
./setup-cron.sh
```

This creates a cron job that runs at 2 AM daily and syncs to GitHub if configured.

## Docker Commands

```bash
cd /opt/cisco-switch-manager-gui

# View logs
docker compose logs -f

# Restart
docker compose restart

# Stop
docker compose down

# Update to latest version
git pull && docker compose up -d --build
```

## Requirements

**Server:**
- Linux (Raspberry Pi OS, Ubuntu, Debian, etc.)
- Docker (auto-installed by the installer script), OR
- Apache2 + PHP 7/8 with `php-ssh2` extension

**Client:**
- Modern web browser (Chrome, Firefox, Safari, Edge)
- JavaScript enabled

**Switches:**
- Cisco IOS switches with SSH enabled
- See `docs/Example-SSH-Output.txt` for compatibility requirements

## Security Recommendations

- Use HTTPS in production (redirect HTTP to HTTPS)
- Restrict access to internal network only
- Do not expose to the internet
- Keep the system updated

## Custom Styling

Create `css/custom.css` to apply custom branding:

```css
#logo { background-image: url('mylogo.png'); }
```

## Screenshots

![Main Page](img/screenshot/main.png)
![Port List](img/screenshot/list.png)
![Port Matrix](img/screenshot/matrix.png)
![MAC Search](img/screenshot/search.png)

## Credits

Originally forked from [slub/switchconfig](https://github.com/slub/switchconfig).

**Third-Party Components:**
- SVG-Loader by SamHerbert (MIT License)
- Material Icons (Apache License 2.0)
- Switch & Key Icons from draw.io

## License

GPL v3 - see LICENSE.txt
