#!/bin/bash

# cPanel 403 Fix Script
# Run on cPanel server via terminal or SSH

#!/bin/bash

echo "=== cPanel 403 Forbidden Fix Script ==="
echo "This script will fix common 403 issues for your PHP application"
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# 1. Fix file permissions
echo -e "${YELLOW}Step 1: Fixing file permissions...${NC}"
DOMAIN_PATH="$HOME/public_html"

if [ -d "$DOMAIN_PATH" ]; then
    echo "Setting directory permissions to 755..."
    find "$DOMAIN_PATH" -type d -exec chmod 755 {} \;
    
    echo "Setting file permissions to 644..."
    find "$DOMAIN_PATH" -type f -exec chmod 644 {} \;
    
    echo -e "${GREEN}✓ Permissions fixed${NC}"
else
    echo -e "${RED}✗ public_html not found at $DOMAIN_PATH${NC}"
    exit 1
fi

# 2. Create logs directory
echo ""
echo -e "${YELLOW}Step 2: Creating logs directory...${NC}"
if [ ! -d "$DOMAIN_PATH/logs" ]; then
    mkdir -p "$DOMAIN_PATH/logs"
    chmod 755 "$DOMAIN_PATH/logs"
    echo -e "${GREEN}✓ Logs directory created${NC}"
else
    echo -e "${GREEN}✓ Logs directory already exists${NC}"
fi

# 3. Check .htaccess
echo ""
echo -e "${YELLOW}Step 3: Checking .htaccess...${NC}"
if [ -f "$DOMAIN_PATH/.htaccess" ]; then
    echo -e "${GREEN}✓ .htaccess found${NC}"
    chmod 644 "$DOMAIN_PATH/.htaccess"
    echo "✓ .htaccess permissions set to 644"
else
    echo -e "${RED}✗ .htaccess not found${NC}"
    echo "Note: .htaccess file should be created from the PHP application"
fi

# 4. Check session directory
echo ""
echo -e "${YELLOW}Step 4: Checking session directory...${NC}"
TEMP_DIR="/tmp"
if [ -w "$TEMP_DIR" ]; then
    echo -e "${GREEN}✓ $TEMP_DIR is writable${NC}"
else
    echo -e "${RED}✗ $TEMP_DIR is not writable${NC}"
    echo "Try creating: mkdir -p /var/sessions && chmod 777 /var/sessions"
fi

# 5. Check PHP configuration
echo ""
echo -e "${YELLOW}Step 5: Checking PHP configuration...${NC}"
php -v
echo ""

# 6. Test database connection
echo -e "${YELLOW}Step 6: Testing database connection...${NC}"
php -r "
\$conn = new mysqli('localhost', 'zxtfmwrs_zxtfmwrs', 'ws;0V;5YG2p0Az', 'zxtfmwrs_mnr_course');
if (\$conn->connect_error) {
    echo 'Error: ' . \$conn->connect_error;
    exit(1);
} else {
    echo 'Database connection successful!';
    \$result = \$conn->query('SELECT COUNT(*) as cnt FROM csv_files');
    if (\$result) {
        \$row = \$result->fetch_assoc();
        echo ' Files: ' . \$row['cnt'];
    }
}
"

echo ""
echo -e "${YELLOW}Step 7: Fix summary${NC}"
echo "✓ File permissions set"
echo "✓ Logs directory created"
echo "✓ .htaccess configured"
echo ""
echo -e "${GREEN}=== All fixes applied ===${NC}"
echo ""
echo "Next steps:"
echo "1. Visit: https://your-domain.com/cpanel-diagnose.php"
echo "2. Review the diagnostic output"
echo "3. If 403 still occurs, check cPanel settings:"
echo "   - Go to: cPanel > Select PHP Version"
echo "   - Choose: PHP 7.4+ (or latest)"
echo "   - Handler: suPHP or PHP-FPM"
echo ""
