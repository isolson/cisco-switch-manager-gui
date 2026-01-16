#!/bin/bash
#
# SwitchConfig Raspberry Pi Installer
#
# Usage: curl -fsSL https://raw.githubusercontent.com/isolson/switchconfig/master/install-pi.sh | bash
#
# This script installs SwitchConfig on a Raspberry Pi using Docker.
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

INSTALL_DIR="${INSTALL_DIR:-/opt/switchconfig}"
REPO_URL="${REPO_URL:-https://github.com/isolson/switchconfig.git}"

echo -e "${GREEN}"
echo "╔═══════════════════════════════════════════════════════════╗"
echo "║           SwitchConfig Raspberry Pi Installer             ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${YELLOW}Note: Running without root. You may be prompted for sudo password.${NC}"
    SUDO="sudo"
else
    SUDO=""
fi

# Function to check if a command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Install Docker if not present
install_docker() {
    if command_exists docker; then
        echo -e "${GREEN}✓ Docker is already installed${NC}"
        return
    fi

    echo -e "${YELLOW}Installing Docker...${NC}"
    curl -fsSL https://get.docker.com | $SUDO sh

    # Add current user to docker group
    if [ -n "$SUDO_USER" ]; then
        $SUDO usermod -aG docker "$SUDO_USER"
    elif [ -n "$USER" ] && [ "$USER" != "root" ]; then
        $SUDO usermod -aG docker "$USER"
    fi

    echo -e "${GREEN}✓ Docker installed${NC}"
}

# Check for docker compose
check_docker_compose() {
    if docker compose version >/dev/null 2>&1; then
        echo -e "${GREEN}✓ Docker Compose is available${NC}"
        COMPOSE_CMD="docker compose"
    elif command_exists docker-compose; then
        echo -e "${GREEN}✓ Docker Compose (standalone) is available${NC}"
        COMPOSE_CMD="docker-compose"
    else
        echo -e "${RED}✗ Docker Compose not found. Please install it.${NC}"
        exit 1
    fi
}

# Clone or update repository
setup_repository() {
    if [ -d "$INSTALL_DIR" ]; then
        echo -e "${YELLOW}Directory $INSTALL_DIR exists. Updating...${NC}"
        cd "$INSTALL_DIR"
        git pull origin master || true
    else
        echo -e "${YELLOW}Cloning SwitchConfig to $INSTALL_DIR...${NC}"
        $SUDO mkdir -p "$INSTALL_DIR"
        $SUDO chown "$USER:$USER" "$INSTALL_DIR" 2>/dev/null || true
        git clone "$REPO_URL" "$INSTALL_DIR"
        cd "$INSTALL_DIR"
    fi
    echo -e "${GREEN}✓ Repository ready${NC}"
}

# Create configuration file
setup_config() {
    cd "$INSTALL_DIR"

    if [ -f "config.php" ]; then
        echo -e "${YELLOW}config.php already exists. Keeping existing configuration.${NC}"
        return
    fi

    echo ""
    echo -e "${YELLOW}Creating configuration file...${NC}"
    echo ""

    # Create a basic config
    cat > config.php << 'CONFIGEOF'
<?php
/*** CONFIG FILE FOR SWITCHCONFIG ***/

// Enable config backup feature
const ENABLE_CONFIG_BACKUP = true;

// Enable password change feature
const ENABLE_PASSWORD_CHANGE = false;

// Execute "wr mem" after changes (can be slow)
const DO_WR_MEM = false;

// PoE settings
const DO_SET_POE = false;
const VOICE_VLAN = -1;

// Port settings
const PORT_DESCRIPTION_HINT = '';
const VISIBLE_PORTS = ['Gi', 'Fa'];
const HIDDEN_PORTS  = [];

// Backup settings
const BACKUP_DIR = '/var/lib/switchconfig/backups';
const BACKUP_RETENTION_COUNT = 30;
const BACKUP_SETTINGS_FILE = '/var/lib/switchconfig/backup_settings.json';

// Credential templates for automated backups
// Add your backup credentials here
const CREDENTIAL_TEMPLATES = [
    // Example:
    // ['id' => 'default', 'name' => 'Backup User', 'username' => 'backup', 'password' => 'yourpassword'],
];

// VLANs visible in the web interface
// Customize with your own VLANs
const VISIBLE_VLAN = [
    ['id' => 'disabled', 'name' => '[----]  DISABLED'],
    ['id' => 1, 'name' => '[01]  Default'],
    // Add more VLANs here
];

// Command snippets (optional)
const SNIPPETS = [];

// Your switches - add your switches here!
// Example:
// const SWITCHES = [
//     ['addr' => '192.168.1.10', 'name' => 'Switch 1 - Server Room', 'group' => 'Building A', 'credential' => 'default'],
//     ['addr' => '192.168.1.11', 'name' => 'Switch 2 - Office', 'group' => 'Building A'],
// ];
const SWITCHES = [
    // Add your switches here
];

// Maps (optional)
const MAPS = [];
CONFIGEOF

    echo -e "${GREEN}✓ Created config.php${NC}"
    echo -e "${YELLOW}  Edit $INSTALL_DIR/config.php to add your switches and VLANs${NC}"
}

# Create empty backup settings file
setup_backup_settings() {
    cd "$INSTALL_DIR"

    if [ ! -f "backup_settings.json" ]; then
        echo '{"github_configured": false}' > backup_settings.json
        echo -e "${GREEN}✓ Created backup_settings.json${NC}"
    fi
}

# Start the container
start_container() {
    cd "$INSTALL_DIR"

    echo ""
    echo -e "${YELLOW}Building and starting SwitchConfig...${NC}"
    $COMPOSE_CMD up -d --build

    echo -e "${GREEN}✓ SwitchConfig is running${NC}"
}

# Print completion message
print_complete() {
    LOCAL_IP=$(hostname -I | awk '{print $1}')

    echo ""
    echo -e "${GREEN}╔═══════════════════════════════════════════════════════════╗"
    echo "║              Installation Complete!                       ║"
    echo "╚═══════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo "SwitchConfig is now running!"
    echo ""
    echo -e "  ${GREEN}Web Interface:${NC} http://${LOCAL_IP}:8088"
    echo -e "  ${GREEN}Config File:${NC}   $INSTALL_DIR/config.php"
    echo ""
    echo "Next steps:"
    echo "  1. Edit config.php to add your switches and VLANs"
    echo "  2. Open the web interface and log in with your switch credentials"
    echo "  3. Go to Backup > GitHub Setup to configure cloud sync"
    echo ""
    echo "Useful commands:"
    echo "  cd $INSTALL_DIR && docker compose logs -f    # View logs"
    echo "  cd $INSTALL_DIR && docker compose restart    # Restart"
    echo "  cd $INSTALL_DIR && docker compose down       # Stop"
    echo "  cd $INSTALL_DIR && docker compose pull       # Update"
    echo ""
    echo "To enable scheduled daily backups:"
    echo "  cd $INSTALL_DIR && ./setup-cron.sh"
    echo ""
}

# Main installation flow
main() {
    install_docker
    check_docker_compose
    setup_repository
    setup_config
    setup_backup_settings
    start_container
    print_complete
}

main "$@"
