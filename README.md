# Impreza Child Theme

Custom WordPress child theme for WooCommerce with Amelia booking integration.

## Overview

This theme extends the Impreza parent theme with custom cart enhancements, service booking features, and a modern build system for optimized production deployment.

## Key Features

- **Cart Enhancements**: Collapsible custom fields and add-ons on cart/checkout pages
- **Service Recommendations**: Display Amelia services on product pages and in mini cart
- **Booking Popup System**: User-friendly confirmation dialog for service bookings
- **Mini Cart Drawer**: Side-sliding cart with service recommendations
- **Modern Build System**: SCSS compilation, JavaScript minification, and automated deployment
- **Production Export**: One-command theme export ready for WordPress deployment

## Requirements

- WordPress 5.8+
- WooCommerce 5.0+
- Amelia Booking Plugin 1.0+
- Impreza Parent Theme
- Node.js 18+ (for development/building)

## Quick Start

### For Deployment

If you just need to deploy the theme:

```bash
# 1. Navigate to theme directory
cd Impreza-child/

# 2. Install dependencies
npm install

# 3. Build production theme
npm run build

# 4. Upload the generated folder
# Upload: /impreza-child-dist/ to your server
# Rename to: /wp-content/themes/impreza-child/

# 5. Activate in WordPress
# Dashboard → Appearance → Themes → Activate
```

### For Development

If you're actively developing:

```bash
# Navigate to theme directory
cd Impreza-child/

# Install dependencies
npm install

# Start watch mode (auto-compile on save)
npm run dev

# Edit files in /src/scss/ and /js/
# Changes compile automatically
# Hard refresh browser to see updates
```

## Build Commands

| Command | Purpose |
|---------|---------|
| `npm run build` | Production build with theme export (use before deployment) |
| `npm run dev` | Development mode with file watching |
| `npm run build:dev` | Development build with source maps |
| `npm run clean` | Remove exported theme folder |

## Project Structure

```
Impreza-child/
├── src/                          # Source files (edit these)
│   ├── scss/
│   │   ├── main.scss            # Main SCSS entry
│   │   ├── _variables.scss      # Design tokens
│   │   └── components/          # Component styles
│   └── js/
│       └── main.js              # Main JS entry
│
├── js/                           # Component JavaScript
│   ├── cart-enhancements.js
│   ├── booking-popup.js
│   ├── cart-side-drawer.js
│   └── ...
│
├── includes/                     # PHP classes
│   ├── class-cart-enhancements.php
│   ├── class-booking-popup-handler.php
│   ├── class-product-service-recommendations.php
│   ├── class-cart-service-recommendations.php
│   └── elementor-widgets/
│
├── dist/                         # Compiled assets (auto-generated)
│   ├── css/*.min.css
│   └── js/*.min.js
│
├── impreza-child-dist/           # Exported production theme
│   ├── assets/                  # Minified CSS/JS
│   ├── includes/                # PHP classes
│   ├── functions.php            # Paths auto-updated
│   └── style.css
│
├── functions.php                 # Theme initialization
├── style.css                     # WordPress theme file
└── screenshot.png               # Theme screenshot
```

## Theme Features

### Cart & Checkout Enhancements

Improves the cart and checkout experience with:

- **Collapsible Groups**: Custom fields and add-ons can be collapsed/expanded
- **Better Organization**: Product options grouped logically
- **Smooth Animations**: CSS transitions for better UX
- **Mobile Optimized**: Responsive design

Files are automatically loaded only on cart and checkout pages for optimal performance.

### Service Recommendations

#### Product Pages

Display recommended Amelia services on individual product pages:

- Link services to products via product meta
- Automatic display after add-to-cart button
- Alternative: Use shortcode `[product_service_recommendation]`
- Elementor widget available for custom placement

#### Mini Cart

Show service recommendations in the mini cart drawer:

- Based on products currently in cart
- Appears in side-sliding cart drawer
- Responsive card layout
- Direct booking links

### Booking Popup System

User-friendly confirmation before redirecting to Amelia booking:

- Appears when customer clicks "Book This Service"
- Two options: "Book Now" or "Continue Shopping"
- Saves incomplete bookings to WooCommerce session
- Auto-triggers on cart/checkout if booking incomplete
- Keyboard accessible (ESC to close)

### Mini Cart Drawer

Side-sliding cart drawer that opens site-wide:

- Click cart icon to open
- Shows products, totals, and service recommendations
- Smooth slide-in animation
- Click outside or close button to dismiss
- Mobile responsive

## Linking Services to Products

### Via Admin Interface (Recommended)

1. Go to: Dashboard → Service Locations → Service Page Links
2. For each Amelia service, select a linked page or product
3. Save changes

### Via Product Edit Screen

1. Edit any WooCommerce product
2. Find "Related Amelia Service" dropdown (General tab)
3. Select an Amelia service
4. Save product

Services linked via product meta take priority over global links.

## Making Style Changes

### Edit SCSS (Recommended)

```bash
# 1. Edit files in /src/scss/
# Example: /src/scss/components/_booking-popup.scss

# 2. Use shared variables from /src/scss/_variables.scss
.my-element {
  color: $primary-color;        # Purple brand color
  padding: $spacing-md;         # 16px spacing
  transition: $transition-base; # 0.3s ease
  
  @include respond-to(mobile) { # Mobile breakpoint
    padding: $spacing-sm;
  }
}

# 3. Build
npm run build

# 4. Hard refresh browser
# Mac: Cmd+Shift+R
# Windows: Ctrl+Shift+R
```

### SCSS Variables Available

- **Colors**: `$primary-color`, `$secondary-color`, `$text-color`, etc.
- **Spacing**: `$spacing-xs` through `$spacing-xxxl`
- **Typography**: `$font-family-base`, `$font-size-*`
- **Breakpoints**: `$breakpoint-mobile`, `$breakpoint-tablet`, `$breakpoint-desktop`
- **Mixins**: `respond-to()`, `button-reset()`, `overlay()`

### Direct CSS Editing (Not Recommended)

If you must edit CSS directly without the build system:

1. Edit files in `/css/` directory
2. Changes apply immediately (no build needed)
3. Note: These will be overwritten by next build

## JavaScript Customization

All JavaScript files in `/js/` are automatically processed:

- ES6+ syntax supported (transpiled to ES5)
- Automatic minification
- Source maps in development mode
- Console.log statements removed in production

## Deployment

### Quick Deploy (Recommended)

The build process creates a complete, production-ready theme:

```bash
# 1. Build
npm run build

# 2. Upload
# Upload: /impreza-child-dist/ folder to server
# To: /wp-content/themes/impreza-child/

# 3. Activate
# WordPress Dashboard → Appearance → Themes
```

**Benefits:**
- 90% smaller than development folder
- No source files or build tools
- Paths automatically updated
- Minified CSS/JS for fast loading

### Alternative: ZIP Upload

```bash
# 1. Build
npm run build

# 2. Create ZIP
cd impreza-child-dist/
zip -r ../impreza-child.zip .

# 3. Upload via WordPress
# Dashboard → Appearance → Themes → Add New → Upload Theme
```

### What Gets Exported

**Included in production theme:**
- `functions.php` (paths updated)
- `style.css`
- `screenshot.png`
- `/includes/` directory (all PHP classes)
- `/assets/` directory (minified CSS/JS)
- Documentation files

**Excluded from production:**
- `/src/` (SCSS sources)
- `/node_modules/` (dependencies)
- Build configuration files
- Development documentation

## Testing Checklist

After deployment, verify:

- [ ] All pages load without errors
- [ ] Product pages display correctly
- [ ] Service recommendations appear on linked products
- [ ] Cart and checkout pages work
- [ ] Booking popup appears and functions
- [ ] Mini cart drawer opens and closes
- [ ] Custom fields on cart/checkout are collapsible
- [ ] Mobile responsiveness
- [ ] Browser console shows no errors

## Performance

The build system significantly improves performance:

| Metric | Improvement |
|--------|-------------|
| CSS file sizes | 40-60% smaller |
| JS file sizes | 50-70% smaller |
| Production theme size | 90% smaller |
| Page load time | Faster |
| Google PageSpeed score | Higher |

### Conditional Loading

Assets load only where needed:

- `main.*` - All pages
- `cart-enhancements.*` - Cart & checkout only
- `product-service-recommendations.*` - Product pages only
- `booking-popup.*` - Product, cart & checkout
- `cart-side-drawer.*` - All pages (mini cart)

## Troubleshooting

### Build Issues

**Error: npm command not found**
```bash
# Install Node.js from: https://nodejs.org/
# Use LTS version
```

**Build fails**
```bash
# Clean and reinstall
rm -rf node_modules package-lock.json
npm install
npm run build
```

**CSS not updating**
```bash
# 1. Check you edited /src/scss/ files (not /dist/)
# 2. Hard refresh browser (Cmd+Shift+R / Ctrl+Shift+R)
# 3. Check terminal for compilation errors
# 4. Clear WordPress cache
```

### Service Recommendations

**Not showing on product page**
1. Verify Amelia service is linked to product
2. Check service status is "Visible" or "Hidden" (not deleted)
3. View page source - check if CSS/JS files load
4. Look for JavaScript errors in browser console

**Booking button not working**
1. Check Amelia booking page URL in `/js/product-service-recommendations.js`
2. Verify Amelia plugin is active
3. Check browser console for errors

### Booking Popup

**Popup not appearing**
1. Verify on product, cart, or checkout page
2. Check JavaScript console for errors
3. Confirm WooCommerce is active
4. Check if popup HTML is in page source (footer)

**Session not persisting**
1. Verify WooCommerce sessions are enabled
2. Check cookies are enabled in browser
3. Look for PHP errors in debug log

### Mini Cart

**Drawer won't open**
1. Check for JavaScript conflicts with other plugins
2. Verify cart icon has correct class/ID
3. Look for CSS z-index conflicts
4. Test with default theme to isolate issue

## Browser Compatibility

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Version Management

Before releasing updates:

1. Update version in `style.css`:
   ```css
   /*
   Theme Name: Impreza Child
   Version: 1.0.1
   */
   ```

2. Update version in `functions.php`:
   ```php
   define('IMPREZA_CHILD_VERSION', '1.0.1');
   ```

3. Build and test:
   ```bash
   npm run build
   ```

## Development Workflow

### Daily Development

```bash
# Start watch mode
npm run dev

# Edit files in /src/scss/ or /js/
# Save files - automatic compilation
# Hard refresh browser to see changes

# When done for the day
# Press Ctrl+C to stop watching
```

### Before Committing

```bash
# Build production files
npm run build

# Test thoroughly
# Commit changes
git add .
git commit -m "Your changes"
git push
```

### Before Deploying

```bash
# Final production build
npm run build

# Test exported theme locally
# Copy /impreza-child-dist/ to test site
# Verify all features work

# Deploy to production
# Upload /impreza-child-dist/ to server
```

## Security

- AJAX requests protected with WordPress nonces
- All user input sanitized
- Database queries use prepared statements
- Session data properly escaped
- No sensitive data exposed to frontend

## Support

For issues or questions:

1. Check browser console for errors (F12)
2. Review WordPress debug log
3. Verify all requirements are met
4. Test with default theme to isolate issue
5. Check plugin conflicts

## Best Practices

1. **Always build before deploying:**
   ```bash
   npm run build
   ```

2. **Use watch mode during development:**
   ```bash
   npm run dev
   ```

3. **Test on staging before production**

4. **Keep backups before major updates**

5. **Clear all caches after deployment:**
   - WordPress cache
   - Server cache
   - CDN cache
   - Browser cache

6. **Use SCSS variables for consistency:**
   - Edit `/src/scss/_variables.scss`
   - Use variables in components
   - Maintain design system

7. **Never edit `/dist/` files directly:**
   - They're auto-generated
   - Changes will be overwritten

## Deployment Script

The theme includes an automated deployment script for production:

```bash
npm run deploy:production
```

This single command will:

1. Build production assets with minification
2. Create production-ready theme in `/impreza-child-dist/`
3. Stash any uncommitted changes
4. Create a `production` branch with only the dist folder
5. Force push to `origin/production`
6. Reset your working branch
7. Restore stashed changes

**Deploy to server from production branch:**

```bash
# On your server
git clone -b production https://github.com/your-repo/Impreza-child.git impreza-child
# or
git pull origin production
```

**Manual deployment (alternative):**

```bash
npm run build
# Upload /impreza-child-dist/ to /wp-content/themes/impreza-child/
```

## Changelog

### Version 1.0.3 (December 4, 2025)

**Cart & Checkout Improvements**
- Removed placeholder images from Amelia booking items in cart and checkout
- Standardized product title styling across cart and checkout pages
- Fixed thumbnail display logic for service bookings

**SCSS Refactoring**
- Converted all hardcoded values to SCSS variables
- Improved maintainability with consistent design tokens
- Enhanced variable system in `_variables.scss`

**Code Quality**
- Added automated deployment script
- Updated .gitignore for proper development workflow
- Improved build system documentation

### Version 1.0.2 (December 3, 2025)

**Mini Cart Enhancements**
- Added close button (×) to mini cart drawer header
- Improved drawer accessibility and UX

**Bug Fixes**
- Fixed duplicate script loading for cart-enhancements.js
- Corrected file path from /js/ to /assets/js/ in cart enhancements
- Resolved cart total calculation to include service prices

### Version 1.0.1 (December 2, 2025)

**Service Recommendations**
- Hide recommendations when Amelia booking already exists in cart
- Added PHP and JavaScript filtering for better control
- Improved product page recommendation logic

**Cart Service Management**
- Fixed custom service removal when Amelia bookings are added
- Implemented dual-hook approach for reliability
- Corrected Amelia booking detection logic

**UI/UX Improvements**
- Removed services from cart fees/totals section
- Adjusted cart total calculation filter

### Version 1.0.0 (December 1, 2025)

**Initial Release**

- Cart and checkout enhancements with collapsible groups
- Product page service recommendations
- Mini cart service recommendations
- Booking confirmation popup system
- Side-sliding mini cart drawer
- Modern build system with webpack
- SCSS compilation with variables and mixins
- JavaScript minification and transpilation
- Automated production theme export
- Elementor widget for service recommendations
- Admin interface for service-page linking
- Conditional asset loading
- Mobile responsive design
- 90% smaller production deployments

## Credits

- **Parent Theme**: Impreza by TM Studio
- **Integrations**: WooCommerce, Amelia Booking
- **Build Tools**: Webpack, Babel, Sass

## License

This child theme inherits the license of the Impreza parent theme.