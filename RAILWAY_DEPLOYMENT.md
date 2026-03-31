# 🚀 Railway Deployment Guide

## 📋 Quick Setup

### 1. Choose Your Config File

**Option A: Use Railway Config (Recommended for Cloud)**
```bash
# Replace the existing config.php with Railway version
cp config.railway.php config.php
```

**Option B: Keep Local Config**
- Use `config.php` for local development
- Use `config.railway.php` for Railway deployment
- Railway will automatically use the correct one

### 2. Set Environment Variables in Railway

Go to your Railway project → Settings → Variables and add:

#### **Database Variables** (Railway MySQL Service)
```
MYSQLHOST = containers-us-west-XXX.railway.app
MYSQLUSER = root
MYSQLPASSWORD = [your-password]
MYSQLDATABASE = railway
MYSQLPORT = 3306
```

#### **Application Variables**
```
APP_URL = https://your-app-name.railway.app
DEBUG = false
```

#### **Optional Email Variables**
```
SMTP_HOST = smtp.gmail.com
SMTP_PORT = 587
SMTP_USERNAME = your-email@gmail.com
SMTP_PASSWORD = your-app-password
SMTP_FROM = noreply@yourdomain.com
```

### 3. Deploy to Railway

1. **Connect GitHub Repository**
   - Go to Railway.app
   - Click "New Project" → "Deploy from GitHub repo"
   - Select your lending-system repository

2. **Add MySQL Service**
   - Click "New Service" → "MySQL"
   - Railway will provide connection details

3. **Configure Environment Variables**
   - Copy MySQL connection details from Railway MySQL service
   - Add them to your app's environment variables

4. **Deploy!**
   - Railway will automatically build and deploy
   - Your app will be available at `https://your-app-name.railway.app`

## 🔧 Environment Variables Explained

### Required Variables
- `MYSQLHOST` - Database host (Railway provides this)
- `MYSQLUSER` - Database username (usually `root`)
- `MYSQLPASSWORD` - Database password (Railway provides this)
- `MYSQLDATABASE` - Database name (Railway provides this)
- `MYSQLPORT` - Database port (usually `3306`)
- `APP_URL` - Your application URL

### Optional Variables
- `DEBUG` - Set to `true` for debugging, `false` for production
- `SMTP_*` - Email configuration for notifications

## 🛡️ Security Notes

✅ **Safe for Production**
- Uses environment variables (no hardcoded credentials)
- Error messages are sanitized in production
- Debug mode is disabled by default

✅ **Local Development**
- Falls back to localhost values if env vars not set
- Works with XAMPP/MAMP/WAMP out of the box

## 🔄 Deployment Workflow

### For New Deployments:
```bash
# 1. Update Railway config
cp config.railway.php config.php

# 2. Commit and push
git add config.php
git commit -m "Update config for Railway deployment"
git push

# 3. Railway will auto-deploy
```

### For Local Development:
```bash
# Keep your local config with XAMPP settings
# Use config.php for local, config.railway.php for production
```

## 🌐 Alternative Platforms

This config also works with:
- **Heroku** (use `CLEARDB_DATABASE_URL` parsing)
- **Render** (similar env var structure)
- **DigitalOcean App Platform** 
- **AWS Elastic Beanstalk**

## 📱 Post-Deployment Checklist

- [ ] Test user registration
- [ ] Test loan application
- [ ] Test admin login
- [ ] Test file uploads
- [ ] Verify database connectivity
- [ ] Check email settings (if configured)
- [ ] Test responsive design on mobile

## 🚨 Troubleshooting

### "Database connection failed"
- Check environment variables spelling
- Verify MySQL service is running
- Check if database was created

### "500 Internal Server Error"
- Check Railway logs
- Verify PHP syntax in config.php
- Ensure all required extensions are installed

### "Files not uploading"
- Check uploads directory permissions
- Verify UPLOAD_MAX_SIZE setting
- Check file type restrictions

---

**🎉 Your handsome lending system is now ready for the cloud!**
