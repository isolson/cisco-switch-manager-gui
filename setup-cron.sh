#!/bin/bash
#
# Setup scheduled backups for SwitchConfig (Docker)
#
# This script creates a cron job that runs daily backups
# and syncs to GitHub if configured.
#

set -e

INSTALL_DIR="${INSTALL_DIR:-/opt/switchconfig}"
CRON_SCHEDULE="${CRON_SCHEDULE:-0 2 * * *}"  # Default: 2 AM daily

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}Setting up scheduled backups...${NC}"

# Create the backup script
BACKUP_SCRIPT="/usr/local/bin/switchconfig-backup"

sudo tee "$BACKUP_SCRIPT" > /dev/null << EOF
#!/bin/bash
# SwitchConfig scheduled backup
cd $INSTALL_DIR
docker compose exec -T switchconfig php backup-cron.php --sync --quiet
EOF

sudo chmod +x "$BACKUP_SCRIPT"

# Add to crontab
CRON_LINE="$CRON_SCHEDULE $BACKUP_SCRIPT >> /var/log/switchconfig-backup.log 2>&1"

# Check if already in crontab
if crontab -l 2>/dev/null | grep -q "switchconfig-backup"; then
    echo -e "${YELLOW}Cron job already exists. Updating...${NC}"
    (crontab -l 2>/dev/null | grep -v "switchconfig-backup"; echo "$CRON_LINE") | crontab -
else
    (crontab -l 2>/dev/null; echo "$CRON_LINE") | crontab -
fi

# Create log file
sudo touch /var/log/switchconfig-backup.log
sudo chmod 666 /var/log/switchconfig-backup.log

echo -e "${GREEN}✓ Scheduled backup configured!${NC}"
echo ""
echo "Backup schedule: $CRON_SCHEDULE (daily at 2 AM)"
echo "Log file: /var/log/switchconfig-backup.log"
echo ""
echo "To change the schedule, edit your crontab:"
echo "  crontab -e"
echo ""
echo "To run a backup manually:"
echo "  $BACKUP_SCRIPT"
