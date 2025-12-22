# Language Switching Guide

## 🌐 How to Change Language

### Method 1: URL Parameter (Any Page)

Add `?lang=ur` or `?lang=en` to any URL:

**Urdu:**
```
http://localhost/usoft/index.php?lang=ur
http://localhost/usoft/accounts/list.php?lang=ur
http://localhost/usoft/reports/daily-book.php?lang=ur
```

**English:**
```
http://localhost/usoft/index.php?lang=en
http://localhost/usoft/accounts/list.php?lang=en
http://localhost/usoft/reports/daily-book.php?lang=en
```

### Method 2: Language Dropdown (Navbar)

1. Login to the system
2. Look at the top-right corner of the navbar
3. Click on the language dropdown (shows current language: "اردو" or "English")
4. Select your preferred language:
   - **اردو** - Switch to Urdu
   - **English** - Switch to English

### Method 3: Login Page Buttons

On the login page (`login.php`), you'll see language buttons:
- Click **اردو** button to switch to Urdu
- Click **English** button to switch to English

## 📍 Quick Access URLs

### Switch to Urdu:
```
http://localhost/usoft/?lang=ur
```

### Switch to English:
```
http://localhost/usoft/?lang=en
```

## 🔄 How It Works

1. When you click a language option or add `?lang=` parameter:
   - Language is saved in session
   - Page redirects to same page without the parameter
   - All text changes to selected language
   - Direction changes (RTL for Urdu, LTR for English)
   - Font changes (Urdu fonts for Urdu, English fonts for English)

2. Language preference persists:
   - Saved in PHP session
   - Remains until you change it again
   - Works across all pages

## 📝 Examples

### Dashboard in Urdu:
```
http://localhost/usoft/index.php?lang=ur
```

### Dashboard in English:
```
http://localhost/usoft/index.php?lang=en
```

### Accounts List in Urdu:
```
http://localhost/usoft/accounts/list.php?lang=ur
```

### Accounts List in English:
```
http://localhost/usoft/accounts/list.php?lang=en
```

### Reports in Urdu:
```
http://localhost/usoft/reports/daily-book.php?lang=ur
```

### Reports in English:
```
http://localhost/usoft/reports/daily-book.php?lang=en
```

## ✅ Current Implementation

- ✅ Language switcher in navbar (top-right)
- ✅ Language buttons on login page
- ✅ URL parameter support (`?lang=ur` or `?lang=en`)
- ✅ Session persistence
- ✅ Automatic RTL/LTR switching
- ✅ Font switching

## 🎯 Best Practice

**Recommended:** Use the navbar dropdown for language switching as it's:
- Always visible
- Easy to access
- Works from any page
- No need to modify URLs

