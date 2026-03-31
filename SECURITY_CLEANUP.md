# ============================================================
#  SECURITY CLEANUP INSTRUCTIONS
#  ============================================================
# 
# 🚨 CRITICAL: Remove sensitive files from Git history
# 
# If you have already committed sensitive files (uploads/, config.php),
# you MUST remove them from Git history, not just add to .gitignore!
#

# 📋 STEP 1: Check what sensitive files are tracked
git ls-files | grep -E "(uploads/|config\.php|\.env|\.sql)"

# 📋 STEP 2: Remove sensitive files from Git tracking
# (This removes from Git but keeps local files)

# Remove uploads folder (MOST CRITICAL - contains user IDs, COE, billing proofs)
git rm -r --cached uploads/

# Remove config file (contains database credentials)
git rm --cached config.php

# Remove any SQL files
git rm --cached *.sql

# Remove any .env files
git rm --cached .env*

# 📋 STEP 3: Add .gitignore and commit
git add .gitignore
git commit -m "🔒 Add .gitignore and remove sensitive files"

# 📋 STEP 4: Force push to rewrite history
# ⚠️ WARNING: This rewrites Git history!
git push --force-with-lease origin main

# 📋 STEP 5: Verify cleanup
git ls-files | grep -E "(uploads/|config\.php|\.env|\.sql)"
# Should return nothing

# ============================================================
#  🚨 AFTER CLEANUP - SECURITY CHECKLIST
#  ============================================================

# ✅ 1. Verify uploads/ folder is NOT in Git
# ✅ 2. Verify config.php is NOT in Git  
# ✅ 3. Verify no .env files are in Git
# ✅ 4. Verify no .sql files are in Git
# ✅ 5. Test that application still works locally
# ✅ 6. Deploy fresh config.php to production (manual setup)

# ============================================================
#  🛡️ FUTURE PROTECTION
#  ============================================================

# The .gitignore file now protects against:
# - uploads/ (user documents - IDs, COE, billing proofs)
# - config.php (database credentials)
# - .env files (environment variables)
# - *.sql files (database dumps)
# - Log files, temp files, IDE files

# ALWAYS check before committing:
# git status
# git diff --cached

# NEVER commit:
# - User uploaded documents
# - Database credentials
# - Production configuration files
# - Any sensitive personal data
