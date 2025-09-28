#!/bin/bash

# VEO Asset Management System - Cron Job Setup Script
# This script sets up the Laravel scheduler for production deployment

echo "🚀 VEO Asset Management System - Cron Job Setup"
echo "================================================"

# Get the current directory (project root)
PROJECT_ROOT=$(pwd)

echo "📁 Project Root: $PROJECT_ROOT"

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from the Laravel project root."
    exit 1
fi

# Create the cron job entry
CRON_ENTRY="* * * * * cd $PROJECT_ROOT && php artisan schedule:run >> /dev/null 2>&1"

echo "📝 Cron job entry to be added:"
echo "$CRON_ENTRY"
echo ""

# Check if cron job already exists
if crontab -l 2>/dev/null | grep -q "artisan schedule:run"; then
    echo "⚠️  Cron job for Laravel scheduler already exists!"
    echo "Current cron jobs:"
    crontab -l | grep "artisan schedule:run"
    echo ""
    read -p "Do you want to replace the existing cron job? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "❌ Setup cancelled."
        exit 0
    fi

    # Remove existing Laravel scheduler cron jobs
    crontab -l | grep -v "artisan schedule:run" | crontab -
    echo "🗑️  Removed existing Laravel scheduler cron jobs"
fi

# Add the new cron job
(crontab -l 2>/dev/null; echo "$CRON_ENTRY") | crontab -

if [ $? -eq 0 ]; then
    echo "✅ Cron job successfully added!"
    echo ""
    echo "📋 Current cron jobs:"
    crontab -l
    echo ""
    echo "🔄 The Laravel scheduler will now run every minute and execute:"
    echo "   • Sensor data collection every 15 minutes"
    echo "   • Comprehensive data collection every hour"
    echo "   • Daily health reports at 6:00 AM"
    echo ""
    echo "📊 To verify scheduled tasks are registered, run:"
    echo "   php artisan schedule:list"
    echo ""
    echo "📝 To view scheduler logs, check Laravel logs:"
    echo "   tail -f storage/logs/laravel.log"
else
    echo "❌ Failed to add cron job. Please check permissions."
    exit 1
fi

echo "🎉 Setup complete! The VEO Asset Management scheduler is now active."