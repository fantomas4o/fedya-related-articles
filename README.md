# Fedya Related Articles

**Contributors:** fedya Serafiev  
**Tags:** related posts, related articles, SEO, internal linking, engagement  
**Requires at least:** 5.0  
**Tested up to:** 6.7  
**Requires PHP:** 7.4  
**Stable tag:** 3.4  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

## Description

**Fedya Related Articles** is a lightweight, fast, and beautiful WordPress plugin that displays related posts with thumbnails, modern card design, and full control — automatically or via shortcode.

Boost your **SEO**, reduce **bounce rate**, and keep visitors engaged by showing relevant content they'll love to read next.

### Key Features:

- **4-level smart matching algorithm** - finds related posts even when tags/categories are missing
- **Stunning responsive cards** with featured images
- **Auto-display** at the end of posts (toggle on/off)
- **Two shortcode options**: `[fedya_related]` and quick `[fedya]`
- **Advanced shortcode parameters**: custom title, columns, limit
- **Intelligent caching** - optimized performance with 1-hour cache
- **Optional excerpts** - show post descriptions under titles
- **Customizable**: title, number of posts, layout columns
- **Zero bloat**: no external CSS/JS – inline styles load instantly
- **Fully translatable** (ready for any language)
- **Mobile-first** design – looks great on all devices
- **Debug mode** for administrators - see why posts are/aren't showing

Perfect for blogs, news sites, magazines, and content-heavy websites.

> _"Може да ви е интересно и..." – now in perfect English: "You might also like..."_

No configuration needed out of the box — just activate and watch internal linking improve your site!

---

## Installation

1. Upload the plugin files to `/wp-content/plugins/fedya-related-articles/` directory, or install through WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to **Settings → Related Articles** to configure (optional)
4. Related posts will automatically appear at the end of each post!

---

## How It Works

### Intelligent 4-Level Matching Algorithm

The plugin uses a sophisticated multi-level approach to always find relevant content:

1. **Level 1** - Posts with matching tags AND categories (strongest relevance)
2. **Level 2** - Posts with matching tags only
3. **Level 3** - Posts with matching categories only
4. **Level 4** - Latest published posts (fallback - ensures related posts always appear)

This means **you'll always have related posts showing**, even on brand new articles without tags or categories!

---

## Usage

### Automatic Display
Related posts automatically appear at the end of every post. Toggle this in **Settings → Related Articles**.

### Manual Placement with Shortcodes

You have **two shortcode options**:

#### Quick shortcode (NEW in v3.3):
```
[fedya]
```

#### Standard shortcode:
```
[fedya_related]
```

Both shortcodes support the same parameters:

#### Basic Examples:
```
[fedya]
[fedya limit="5"]
[fedya title="Read Next"]
[fedya columns="3"]
```

#### Advanced Examples:
```
[fedya limit="6" columns="3"]
[fedya limit="4" title="More From This Topic" columns="2"]
[fedya_related limit="8" columns="4"]
```

### Shortcode Parameters:

| Parameter | Description | Default | Example |
|-----------|-------------|---------|---------|
| `limit` | Number of posts to show | 3 | `limit="5"` |
| `title` | Custom heading text | From settings | `title="Keep Reading"` |
| `columns` | Fixed column layout | auto | `columns="3"` |

**Column Options:**
- `columns="2"` - Always 2 columns (responsive on mobile)
- `columns="3"` - Always 3 columns (2 on tablet, 1 on mobile)
- `columns="4"` - Always 4 columns (2 on tablet, 1 on mobile)
- No parameter - Auto-fit based on available space

---

## Settings

Navigate to **Settings → Related Articles** in your WordPress admin:

### Available Options:

1. **Title** - Change the heading text (default: "You might also like:")
2. **Number of Posts** - How many related posts to show (1-10)
3. **Auto Display** - Toggle automatic display at end of posts
4. **Show Excerpt** - Display short post descriptions under titles (NEW in v3.4)

---

## What's New

### Version 3.4
- ✨ **4-level intelligent matching** - always finds related posts
- 🎯 **Fallback to latest posts** - never shows empty related section
- 🐛 **Better debug mode** - shows tag/category count for admins
- ⚡ **Performance boost** - improved caching and query optimization
- 🔧 **Excludes sticky posts** - cleaner results

### Version 3.3
- ✨ **New quick shortcode**: `[fedya]` - easier to type!
- 🎨 **Column control**: Set exact column layout with `columns` parameter
- 📝 **Custom titles per shortcode**: Override default heading
- 💾 **Smart caching**: Results cached for 1 hour - faster page loads
- 📱 **Better responsive**: Improved mobile/tablet layouts

---

## Frequently Asked Questions

### Why aren't related posts showing on some articles?

With version 3.4+, this should never happen! The plugin now uses a 4-level matching system that always finds content. If you're an admin, you'll see a debug message explaining the matching levels used.

### Can I use both shortcodes on the same site?

Absolutely! Both `[fedya]` and `[fedya_related]` work identically. Use whichever you prefer, or mix them - perfect for sites with existing `[fedya_related]` codes that want the shorter option for new posts.

### Does this slow down my site?

No! The plugin:
- Uses inline CSS (no extra HTTP requests)
- Implements 1-hour caching
- Uses optimized database queries
- Lazy-loads images automatically

### Can I customize the design?

Yes! Add custom CSS in **Appearance → Customize → Additional CSS**. All elements have `.fedya-related-*` class names for easy targeting.

### Does it work with custom post types?

Currently optimized for standard WordPress posts. Custom post type support coming in future versions.

### Is it translation-ready?

Yes! The plugin is fully translatable using the `fedya-related` text domain. Translation files go in `/languages/` directory.

---

## Technical Details

### Performance Features:
- **Object caching** - 1 hour cache per post
- **Optimized queries** - Only fetches post IDs, not full post objects
- **Lazy loading** - Images load on scroll
- **No external dependencies** - Zero HTTP requests for assets

### Developer Friendly:
- Clean, well-documented code
- WordPress coding standards compliant
- Secure: All outputs escaped, inputs sanitized
- Extensible: Easy to customize via theme functions

---

## Screenshots

1. **Beautiful card design** - Modern, responsive layout with featured images
2. **Settings page** - Simple, intuitive configuration
3. **Mobile view** - Perfect display on all screen sizes
4. **Shortcode examples** - Flexible placement options
5. **Debug mode** - See matching logic for admins

---

## Changelog

### 3.4 (2024)
- Added 4-level intelligent matching algorithm
- Added fallback to latest posts
- Improved debug information display
- Added ignore_sticky_posts parameter
- Performance optimizations

### 3.3 (2024)
- Added `[fedya]` quick shortcode
- Added shortcode parameters: title, columns
- Implemented 1-hour result caching
- Added optional post excerpts
- Improved responsive column layouts
- Better mobile/tablet breakpoints

### 3.2 (2024)
- Initial public release
- Auto-display functionality
- `[fedya_related]` shortcode
- Settings page
- Card design with thumbnails

---

## Credits

**Author:** fedya Serafiev  
**Website:** [https://urocibg.eu/](https://urocibg.eu/)

Built with ❤️ for the WordPress community.

---

## Support

For support, feature requests, or bug reports, please visit:
- Plugin URI: [https://urocibg.eu/](https://urocibg.eu/)
- Or use the WordPress.org support forums

---

## License

This plugin is licensed under GPLv2 or later.
You are free to use, modify, and distribute this plugin as needed.
