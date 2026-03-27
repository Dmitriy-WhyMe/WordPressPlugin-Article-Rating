# Article Pulse Rating

Lightweight WordPress plugin that adds a fast 5-star rating block to posts, stores votes in `post_meta`, and prevents repeat votes with cookies.

## Why This Plugin

Most rating plugins are either too heavy or too generic for editorial content.  
Article Pulse Rating gives you a clean, focused article feedback widget you can drop anywhere with one shortcode.

## 🚀 Features

- ⭐ 5-star article rating UI via shortcode: `[article_rating]`
- 🧩 Optional explicit post targeting: `[article_rating post_id="123"]`
- ⚡ AJAX vote submission (no page reload)
- 🔒 Duplicate-vote protection per post using a cookie
- 📊 Live average score + vote count updates
- ⚙️ Admin setting for custom rating question text
- 📱 Responsive block layout for desktop and mobile
- 🛠 Quick access `Settings` link from the Plugins page

## 📦 Installation

1. Clone or download this repository.
2. Build the plugin ZIP:

```bat
build-plugin.bat
```

3. Locate the built package:

```text
dist/article-pulse-rating.zip
```

4. In WordPress Admin, go to `Plugins -> Add New -> Upload Plugin`.
5. Upload `article-pulse-rating.zip`, install, and activate.

## 🛠 Usage

Add the shortcode inside a post/page:

```text
[article_rating]
```

Rate a specific post ID from any template/content area:

```text
[article_rating post_id="123"]
```

The plugin stores and updates:

- `_kb_rating_total`
- `_kb_rating_count`
- `_kb_rating_average`

## ⚙️ Configuration

Go to:

`Settings -> Article Pulse Rating`

Available option:

- `Question text`: heading shown above the star rating block

## 📸 Screenshots

Add screenshots to `/assets` (or your docs folder) and reference them here:

- Screenshot 1: rating block on desktop
- Screenshot 2: rating block on mobile
- Screenshot 3: plugin settings page in WordPress Admin

## 📌 Use Cases

- Editorial teams collecting quick quality feedback on blog posts
- Knowledge base sites measuring article usefulness
- Content marketers prioritizing high-value articles by reader score
- Product docs teams identifying pages that need updates

## 🤝 Contributing

Contributions are welcome and appreciated.

1. Fork the repository
2. Create a feature branch (`feat/my-improvement`)
3. Commit your changes with clear messages
4. Open a Pull Request with context and testing notes

Please keep changes focused and include screenshots for UI updates.

## 📄 License

MIT License.
