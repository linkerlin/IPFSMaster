#!/bin/bash
# IPFS Master 启动脚本
# 使用方法: ./start.sh [端口]

PORT=${1:-7789}
HOST=${2:-localhost}

echo "🚀 启动 IPFS Master..."
echo "📡 地址: http://$HOST:$PORT"
echo "📌 按 Ctrl+C 停止服务器"
echo ""

php -S $HOST:$PORT -t public
