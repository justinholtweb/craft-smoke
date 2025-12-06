# Setting Up Smoke on GitHub

## Initialize Git Repository

```bash
cd ~/Sites/smoke
git init
git add .
git commit -m "Initial commit: Smoke plugin v0.1.0

- On-page frontend editing for Craft CMS
- Powered by DataStar framework
- Support for Plain Text, Rich Text, Lightswitch, Dropdown, Table fields
- Asset, Entry, and Matrix field display (editing coming soon)
- Mobile responsive with keyboard shortcuts
- Permission-aware editing"
```

## Create GitHub Repository

1. Go to https://github.com/new
2. Create a new repository named `smoke`
3. Don't initialize with README, .gitignore, or license (we already have those)
4. Click "Create repository"

## Push to GitHub

```bash
# Add the remote (replace with your actual GitHub URL)
git remote add origin https://github.com/justinholtweb/smoke.git

# Push to GitHub
git branch -M main
git push -u origin main
```

## Using Smoke from GitHub

Once published, users can install directly from GitHub:

### Option 1: Via Composer (Recommended for Production)

Add to your project's `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/justinholtweb/smoke"
    }
  ],
  "require": {
    "justinholtweb/smoke": "dev-main"
  }
}
```

Then run:
```bash
composer require justinholtweb/smoke:dev-main
```

### Option 2: Local Development (Current Setup)

Your CKC project is already configured to use the local version:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../smoke"
    }
  ],
  "require": {
    "justinholtweb/smoke": "@dev"
  }
}
```

This allows you to develop Smoke locally while using it in your CKC project.

## Switching Between Local and GitHub

### To use local version:
```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../smoke"
    }
  ],
  "require": {
    "justinholtweb/smoke": "@dev"
  }
}
```

### To use GitHub version:
```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/justinholtweb/smoke"
    }
  ],
  "require": {
    "justinholtweb/smoke": "dev-main"
  }
}
```

Then run `composer update justinholtweb/smoke`

## Repository Structure

```
smoke/
├── .gitignore              # Ignores vendor/, .DS_Store, etc.
├── LICENSE                 # MIT License
├── README.md               # Main documentation
├── QUICKSTART.md           # Installation guide
├── INTEGRATION_EXAMPLE.md  # Usage examples
├── GITHUB_SETUP.md         # This file
├── composer.json           # Package definition
└── src/                    # Source code
    ├── Plugin.php
    ├── controllers/
    ├── services/
    ├── variables/
    ├── assetbundles/
    └── templates/
```

## Tagging Releases

When ready to release a version:

```bash
# Tag the release
git tag -a v0.1.0 -m "Release v0.1.0 - Initial POC"
git push origin v0.1.0

# Users can then require a specific version
composer require justinholtweb/smoke:^0.1.0
```

## Publishing to Packagist (Optional)

To make it easily installable without adding repository config:

1. Go to https://packagist.org
2. Click "Submit"
3. Enter: `https://github.com/justinholtweb/smoke`
4. Packagist will auto-update on new tags

Once on Packagist, users can install with just:
```bash
composer require justinholtweb/smoke
```

## Development Workflow

1. **Make changes** in `~/Sites/smoke`
2. **Test** in your CKC project (already linked via local path)
3. **Commit** changes to git
4. **Push** to GitHub
5. **Tag** when ready for release

## Notes

- The plugin is currently set up for local development via path repository
- Once pushed to GitHub, you can switch to VCS repository
- Keep the local version for development, use GitHub for production
