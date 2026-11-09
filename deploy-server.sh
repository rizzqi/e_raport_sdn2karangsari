#!/bin/bash
# ==============================================================================
# E-RAPORT SDN 2 KARANGSARI - SERVER DEPLOY TOOLKIT
# Server Auto-Pull dari GitHub via Tailscale SSH
# ==============================================================================

# Warna Terminal
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
RED='\033[0;31m'
GRAY='\033[0;90m'
NC='\033[0m' # No Color

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="$SCRIPT_DIR/.server_ip"
SERVER_IP="100.99.65.48"

# Baca konfigurasi IP server jika ada
if [ -f "$CONFIG_FILE" ]; then
    SAVED_IP=$(cat "$CONFIG_FILE" | tr -d '[:space:]')
    if [ -n "$SAVED_IP" ]; then
        SERVER_IP="$SAVED_IP"
    fi
else
    echo "$SERVER_IP" > "$CONFIG_FILE"
fi

# Deteksi branch git aktif
get_active_branch() {
    local detected
    detected=$(git branch --show-current 2>/dev/null)
    if [ -n "$detected" ]; then
        echo "$detected"
    else
        echo "main"
    fi
}

show_header() {
    local target_ip=$1
    local branch=$2
    clear
    echo -e "${CYAN}======================================================================${NC}"
    echo -e "${CYAN}       E-RAPORT SDN 2 KARANGSARI - SERVER DEPLOY TOOLKIT              ${NC}"
    echo -e "${CYAN}                 Server Auto-Pull dari GitHub                         ${NC}"
    echo -e "${CYAN}======================================================================${NC}"
    echo -e "${GRAY}   Target Server: root@$target_ip  |  Branch: $branch${NC}"
    echo ""
}

while true; do
    CURRENT_BRANCH=$(get_active_branch)
    show_header "$SERVER_IP" "$CURRENT_BRANCH"

    echo "PILIHAN DEPLOY:"
    echo "  [1]  Server Pull dari GitHub (Otomatis Tarik Update & Deploy ke Server)"
    echo "  [2]  Cek Status Server & Health Check"
    echo "  [0]  Keluar"
    echo ""

    read -r -p "Pilih menu [1/2/0, Enter untuk '1']: " PILIHAN
    PILIHAN=${PILIHAN:-1}

    case "$PILIHAN" in
        0)
            echo ""
            echo -e "${YELLOW}[INFO] Deployment Toolkit ditutup. Sampai jumpa!${NC}"
            exit 0
            ;;
        1)
            echo ""
            echo -e "${CYAN}=== [1] SERVER PULL DARI GITHUB ===${NC}"
            echo "Target Server : root@$SERVER_IP"
            echo "Branch default: $CURRENT_BRANCH"
            read -r -p "Ketik branch target [Enter untuk '$CURRENT_BRANCH']: " TARGET_BRANCH
            TARGET_BRANCH=${TARGET_BRANCH:-$CURRENT_BRANCH}

            echo ""
            echo -e "${YELLOW}Menghubungi Server via SSH ($SERVER_IP)...${NC}"
            echo -e "${GRAY}Memerintahkan server untuk menarik commit terbaru dari GitHub (branch: $TARGET_BRANCH)...${NC}"
            echo ""

            ssh -o StrictHostKeyChecking=accept-new -o ConnectTimeout=8 "root@$SERVER_IP" "cd /var/www/e_raport_sdn2karangsari && bash deploy.sh $TARGET_BRANCH"

            if [ $? -eq 0 ]; then
                echo ""
                echo -e "${GREEN}  [SUKSES] Server berhasil menarik update dari GitHub dan aplikasi telah diperbarui!${NC}"
            else
                echo ""
                echo -e "${RED}  [GAGAL] Server mengalami kendala saat menarik update dari GitHub.${NC}"
                echo -e "${YELLOW}  Pastikan koneksi Tailscale di perangkat Anda dan server aktif.${NC}"
            fi
            echo ""
            read -r -p "Tekan Enter untuk kembali ke menu..."
            ;;
        2)
            echo ""
            echo -e "${CYAN}=== [2] CEK STATUS SERVER & HEALTH CHECK ===${NC}"
            echo -e "${YELLOW}Menghubungi target root@$SERVER_IP...${NC}"
            echo ""

            ssh -o StrictHostKeyChecking=accept-new -o ConnectTimeout=5 "root@$SERVER_IP" 'bash -s' << 'EOF'
echo "--------------------------------------------------------"
echo "  INFORMASI STATUS SERVER E-RAPORT SDN 2 KARANGSARI"
echo "--------------------------------------------------------"
printf "%-20s : %s\n" "Host Name" "$(hostname)"
printf "%-20s : %s\n" "OS System" "$(uname -srm)"
printf "%-20s : %s\n" "Server Uptime" "$(uptime -p 2>/dev/null || uptime)"
echo "--------------------------------------------------------"
echo "STATUS LAYANAN UTAMA:"

check_service() {
    local name=$1
    local svc=$2
    if systemctl is-active --quiet "$svc" 2>/dev/null; then
        printf "  [OK] %-15s : AKTIF (Running)\n" "$name"
    else
        printf "  [--] %-15s : MATI / TIDAK AKTIF\n" "$name"
    fi
}

check_service "Web Server Nginx" "nginx"
(systemctl is-active --quiet "php8.4-fpm" 2>/dev/null && check_service "PHP 8.4-FPM" "php8.4-fpm") || check_service "PHP 8.3-FPM" "php8.3-fpm"
(systemctl is-active --quiet "mariadb" 2>/dev/null && check_service "Database MariaDB" "mariadb") || check_service "Database MySQL" "mysql"
check_service "Tailscale VPN" "tailscaled"

echo "--------------------------------------------------------"
echo "PENGGUNAAN SUMBER DAYA:"
printf "  Disk (/)   : %s\n" "$(df -h / | awk 'NR==2 {print $3 " / " $2 " (Terpakai " $5 ")"}')"
printf "  RAM/Memori : %s\n" "$(free -m | awk 'NR==2 {printf "%s MB / %s MB (%.1f%%)", $3, $2, $3*100/$2}')"

echo "--------------------------------------------------------"
echo "STATUS APLIKASI WEB:"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/login 2>/dev/null || echo "000")
if [ "$HTTP_CODE" = "200" ]; then
    printf "  Aplikasi Web (Local Port) : [OK] Respon HTTP 200 (Login Page Ready)\n"
elif [ "$HTTP_CODE" = "302" ]; then
    printf "  Aplikasi Web (Local Port) : [OK] Respon HTTP 302 (Redirect)\n"
else
    printf "  Aplikasi Web (Local Port) : [PERINGATAN] Respon HTTP %s\n" "$HTTP_CODE"
fi
echo "--------------------------------------------------------"
EOF

            if [ $? -ne 0 ]; then
                echo ""
                echo -e "${RED}  [GAGAL] Tidak dapat terhubung ke server $SERVER_IP.${NC}"
                echo -e "${YELLOW}  Pastikan koneksi Tailscale di perangkat Anda dan server aktif.${NC}"
            fi
            echo ""
            read -r -p "Tekan Enter untuk kembali ke menu..."
            ;;
        *)
            echo ""
            echo -e "${YELLOW}[PERINGATAN] Pilihan '$PILIHAN' tidak valid. Silakan pilih 1, 2, atau 0.${NC}"
            sleep 1
            ;;
    esac
done

