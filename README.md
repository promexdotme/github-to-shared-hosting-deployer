# GitHub to Shared Hosting Deployer 🚀

**Deploy from GitHub to Shared Hosting (cPanel/Plesk) without Git, SSH, or FTP.**

This is a lightweight, single-file PHP script designed for shared hosting environments where you don't have shell access. It allows you to download a GitHub repository (or a specific folder within it) and extract it directly to your server using a simple web interface.

## 🎯 The Problem
You have a project on GitHub (e.g., a React app built into a `dist` folder, or a PHP website). You want to push it to your shared hosting server.
* **Old Way:** Download Zip -> Unzip -> Open FileZilla -> Upload 3,000 files -> Wait.
* **New Way:** Upload this script once -> Paste GitHub URL -> Click Deploy.

## ✨ Features

* **Zero Dependencies:** Runs on standard PHP (uses `ZipArchive` and `cURL`, standard on 99% of hosts).
* **Smart Folder Extraction:** Can extract *only* a specific subfolder (e.g., `/dist`, `/build`, `/public`) and flatten it to the root directory.
* **Private Repo Support:** Works with GitHub Personal Access Tokens.
* **Auto-Cleanup:** Deletes the temporary ZIP file immediately after extraction.

## 📦 How to Use

1.  Upload `deploy.php` to the folder on your hosting server where you want your site to live (e.g., `public_html/`).
2.  Navigate to `www.your-domain.com/deploy.php` in your browser.
3.  Enter your GitHub URL:
    * **Whole Repo:** `https://github.com/username/my-project`
    * **Specific Folder:** `https://github.com/username/my-project/tree/main/dist`
4.  (Optional) Enter a GitHub Personal Access Token if the repo is private.
5.  Click **Fetch & Deploy**.

### The "Dist" Magic
If your URL points to a subfolder (like `/dist`), the script will extract the **contents** of that folder directly to the directory where the script is running. It removes the parent folder structure automatically.

## ⚠️ Security Warning

**THIS SCRIPT IS POTENTIALLY DANGEROUS.**

It allows anyone with the URL to overwrite files in your directory.
1.  **Delete `deploy.php` immediately** after you finish deploying.
2.  Alternatively, password protect the file using cPanel's "Directory Privacy" or rename it to a random string (e.g., `deploy_x97z.php`) so it cannot be guessed.

## 📄 License

MIT. Feel free to modify and use it for your projects.
