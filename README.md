# Dropplets 3.0

A minimalist markdown blogging platform. This is a modernized, security-hardened fork of [johnroper100/dropplets](https://github.com/johnroper100/dropplets) (which was itself a continuation of the original Dropplets).

No database, flat-file posts, a single password to manage everything, and a simple template system. The goals of the original are unchanged; the internals are rebuilt for PHP 8 and current security practice.

## Requirements

- PHP 8.1 or newer with the `gd`, `curl`, `mbstring`, and `json` extensions
- [Composer](https://getcomposer.org/)

## Get going

```
git clone https://github.com/bpmore/dropplets.git
cd dropplets
composer install
```

Point your web server's document root at the `public/` directory. Then:

1. Navigate to `https://your-domain/settings`
2. Fill in the form and set your password
3. Click create, and you land on the dashboard

For a quick local run without a web server:

```
composer install
php -S 127.0.0.1:8000 -t public public/index.php
# then open http://127.0.0.1:8000/settings
```

### Docker

```
docker compose up --build
# open http://localhost:8080/settings
```

## Manage your blog

Go to `https://your-domain/dashboard`. Writing, editing, publishing, hiding, deleting, settings, and logout all live there.

### Two-factor login (optional)

Settings → "Two-factor login" → Set up. Scan the QR code with any TOTP
authenticator app (1Password, Google Authenticator, Authy, Apple Passwords, …),
confirm one code, and store the recovery codes it shows you. From then on,
login asks for your password and then a 6-digit code.

Recovery: each recovery code works once in place of a TOTP code. If you lose
both the authenticator and the codes, delete `data/totp.json` on the server to
fall back to password-only login.

An RSS feed of published posts is available at `/feed`.

## Project layout

```
public/        <- web root (index.php, static/, uploads/, .htaccess)
src/           <- application code (PSR-4, namespace Dropplets\)
internal/      <- admin views (login, dashboard, write, settings)
templates/     <- front-end themes; liquid-new ships by default
data/          <- config.php and siteDatabase/ (NOT web-accessible)
vendor/        <- Composer dependencies
```

All assets are self-hosted (`public/static/`): no CDNs, no webfonts. Public pages ship a single ~7 KB stylesheet and no JavaScript, with automatic dark mode.

Keeping `data/` outside `public/` means your password hash and post store are never reachable over HTTP, regardless of web server.

## Themes

Fourteen themes ship in `templates/`; pick one in Settings. All are zero-JS,
self-hosted, and most adapt automatically to light/dark mode:

| Theme | Personality |
|---|---|
| `liquid-new` | Default. Clean card grid, system fonts, dark-mode aware |
| `puddle` | Quiet literary serif, single column, hairline rules |
| `typewriter` | Warm paper, typewriter headings, dashed dividers |
| `bink` | Date-gutter rows, orange brand band |
| `benlk` | Condensed uppercase headlines, dark walnut header, blue links |
| `terminal` | Phosphor-green console with a blinking cursor (always dark) |
| `gazette` | Broadsheet: double rules, datelines, ruled columns, drop caps |
| `noir` | Stark black, giant numbered headlines, one red accent (always dark) |
| `bloom` | Soft pastels, rounded glassy cards |
| `brutalist` | Hard borders, offset shadows, highlighter yellow |
| `zen` | Almost nothing: titles, dates, whitespace |
| `magazine` | Bold editorial hero story + kicker grid |
| `midnight` | Deep indigo, glass cards, violet-cyan glow (always dark) |
| `polaroid` | Snapshots taped to a board, handwritten captions |

`puddle`, `typewriter`, `bink`, and `benlk` are original reinterpretations of
community themes made for the original Dropplets (by jacksondc, judges119, and
benlk) — same spirit, all-new code.

## Building templates

A template is a folder under `templates/` with two required files:

- `home.php` lists posts
- `post.php` displays a single post

Static files (CSS, images, fonts) go in an `assets/` subfolder, served at
`/themes/<name>/<file>`. Call `Dropplets\dpl_render_head()` from your
`header.php` to get all SEO/social meta for free, and
`Dropplets\dpl_post_url($router, $post)` for canonical post links.
`Dropplets\dpl_excerpt($post)` gives a safe plain-text excerpt (it returns ''
for password-protected posts).

These variables are available:

- `$siteConfig` is the site configuration array
- `$allPosts` is the array of posts for the current page (each includes a resolved `imageUrl`)
- `$page` is the current page number
- `$limit` is the posts-per-page count
- `$post` is the single post array (on `post.php`, also with `imageUrl`)
- `$router` is the AltoRouter instance for generating URLs

Always escape user-controlled values in templates with `Dropplets\e()`, and render post bodies through Parsedown in safe mode (see the shipped `liquid-new/post.php` for the pattern). A `404.php` in the template is optional and used for not-found pages.

## Security

See `UPGRADE.md` for the full list of fixes relative to 2.2. In short: stored XSS, settings-form code injection, missing CSRF protection, GET-based destructive actions, image-fetch SSRF, and plaintext per-post passwords have all been addressed.

## License

GPL-3.0-or-later, same as upstream.
